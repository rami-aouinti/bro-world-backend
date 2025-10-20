# Bonnes pratiques d'exploitation et de delivery

## Table des matières
- [Fiche 1 — Idempotence API](#fiche-1--idempotence-api)
- [Fiche 2 — Éviter les cascades de pannes](#fiche-2--éviter-les-cascades-de-pannes)
- [Fiche 3 — Versioning API sans casser les clients](#fiche-3--versioning-api-sans-casser-les-clients)
- [Fiche 4 — Passer au asynchrone](#fiche-4--passer-au-asynchrone)
- [Fiche 5 — Pipeline CI/CD type](#fiche-5--pipeline-cicd-type)
- [Fiche 6 — Déploiements sans interruption](#fiche-6--déploiements-sans-interruption)
- [Fiche 7 — Migrations DB expand/contract](#fiche-7--migrations-db-expandcontract)
- [Fiche 8 — Dockerfile multi-stage Symfony](#fiche-8--dockerfile-multi-stage-symfony)
- [Fiche 9 — Logs JSON + Correlation ID](#fiche-9--logs-json--correlation-id)
- [Fiche 10 — OpenTelemetry dans Symfony](#fiche-10--opentelemetry-dans-symfony)
- [Fiche 11 — Diagnostiquer une requête lente](#fiche-11--diagnostiquer-une-requête-lente)
- [Fiche 12 — Secrets & chiffrement](#fiche-12--secrets--chiffrement)
- [Fiche 13 — Azure Blob via Flysystem](#fiche-13--azure-blob-via-flysystem)
- [Fiche 14 — Sécuriser Mercure (JWT)](#fiche-14--sécuriser-mercure-jwt)
- [Fiche 15 — Temporal workflow (suppression différée)](#fiche-15--temporal-workflow-suppression-différée)
- [Fiche 16 — Sauvegarde & monitoring MongoDB](#fiche-16--sauvegarde--monitoring-mongodb)

---

### Fiche 1 — Idempotence API
**FR**  
Nous pouvons garantir l'idempotence des écritures critiques exposées par l'API Symfony en combinant :
- un en-tête `Idempotency-Key` validé par un attribut de contrôleur ;
- un stockage Doctrine (`Media\Repository\MediaIdempotencyRepository`) qui conserve le résultat initial associé à la clé ;
- des contraintes uniques en base pour prévenir les doublons au niveau SQL ;
- la propagation de l'identifiant d'événement sur les messages Messenger lorsque l'appel se poursuit de manière asynchrone.

Exemple : point d'entrée `POST /api/media/upload` qui retourne immédiatement la réponse enregistrée lorsque la même clé est fournie.

```php
<?php
// src/Media/Controller/MediaUploadController.php (extrait)
#[Route('/api/media/upload', name: 'api_media_upload', methods: ['POST'])]
#[RequireIdempotencyKey]
public function __invoke(Request $request, MediaUploader $uploader, MediaResultCache $cache): JsonResponse
{
    $key = $request->attributes->get(IdempotencyKey::ATTRIBUTE);
    if ($cached = $cache->find($key)) {
        return $this->json($cached, Response::HTTP_OK);
    }

    $result = $uploader->handle(MediaUpload::fromRequest($request));
    $cache->store($key, $result);

    return $this->json($result, Response::HTTP_CREATED);
}
```

**DE**  
Wir sichern Idempotenz über einen `Idempotency-Key`, der mitsamt dem Erstresultat gespeichert wird. Kritische Operationen verwenden idempotente HTTP-Verben (`PUT`/Upsert) sowie eindeutige Datenbank-Constraints. Für asynchrone Aufrufe propagieren wir die Event-ID auf Messenger-Nachrichten, damit Downstream-Services deduplizieren können.

---

### Fiche 2 — Éviter les cascades de pannes
**FR**  
Les intégrations sortantes (par exemple vers l'API de génération d'avatars) passent par un client HTTP résilient. Les timeouts sont bornés, les retries utilisent un `exponential backoff` avec jitter, un circuit breaker protège la dépendance et les appels sont isolés dans un pool dédié (bulkhead). Les messages Messenger réessaient en asynchrone avec une DLQ.

```php
$client = new ResilientHttpClient(
    baseUrl: $config->avatarEndpoint(),
    timeout: 1500,
    retries: 3,
    backoffJitter: true,
    circuitBreakerThreshold: 5,
);
$response = $client->get('/risk/score', ['X-Correlation-ID' => $correlationId]);
```

**DE**  
Wir setzen Timeouts, Retries mit Backoff + Jitter, Circuit-Breaker sowie Bulkheads ein. Die Idempotenz beim Aufrufer verhindert Retry-Stürme. Falls die Abhängigkeit länger ausfällt, wird auf asynchrone Verarbeitung (Queue + DLQ) gewechselt.

---

### Fiche 3 — Versioning API sans casser les clients
**FR**  
L'API publique est explicitement versionnée (`/api/v1`). Les évolutions non rétro-compatibles suivent un cycle d'annonce/dépréciation puis un déploiement progressif via l'API Gateway. Les spécifications OpenAPI du projet exposent les versions.

```yaml
servers:
  - url: https://api.example.com/api/v1
paths:
  /media:
    get:
      operationId: listMedia
      responses:
        '200': { description: OK }
```

Sur Kubernetes, le routage canary peut être défini ainsi :

```yaml
# charts/api/templates/gateway.yaml (pseudo)
- match: [uri: { prefix: "/api/v1" }]
  route:
    - destination: { host: api-v1, weight: 90 }
    - destination: { host: api-v2, weight: 10 }
```

**DE**  
Explizite Versionierung (`/api/v1`), Rückwärtskompatibilität und angekündigte Deprecations. Canary-Routing auf der API-Gateway-Schicht erlaubt ein schrittweises Ausrollen neuer Versionen.

---

### Fiche 4 — Passer au asynchrone
**FR**  
Lorsque la latence est variable (génération de médias, e-mails), nous utilisons Symfony Messenger avec RabbitMQ (ou Redis Streams) et accusons réception immédiatement (HTTP `202`). Les messages sont corrélés via `X-Correlation-ID` et acheminés vers une DLQ en cas d'échec répété.

```yaml
# config/packages/messenger.yaml (extrait)
framework:
  messenger:
    transports:
      async: '%env(MESSENGER_TRANSPORT_DSN)%'
      failed: '%env(MESSENGER_FAILED_TRANSPORT_DSN)%'
    routing:
      App\Message\GenerateMediaPreview: async
```

**DE**  
Bei schwankender Latenz oder fragilen Abhängigkeiten nutzen wir Events/Queues mit DLQ und Korrelation. Der Client erhält `202 Accepted`, die Verarbeitung läuft später weiter.

---

### Fiche 5 — Pipeline CI/CD type
**FR**  
La pipeline GitLab CI enchaîne build, tests, audits de sécurité, packaging Docker puis déploiement helm. Les migrations `expand` sont jouées sur l'environnement de staging avant les tests E2E.

```yaml
stages: [build, test, security, package, deploy]

build:
  stage: build
  script:
    - composer install --no-dev --prefer-dist --no-progress
    - npm ci
    - npm run build

test:
  stage: test
  script:
    - phpunit --coverage-clover=coverage.xml

sast_sca:
  stage: security
  script:
    - composer audit || true
    - npm audit --audit-level=high || true

image:
  stage: package
  script:
    - docker build -t $CI_REGISTRY_IMAGE:$CI_COMMIT_SHA .

deploy_staging:
  stage: deploy
  script:
    - helm upgrade --install api charts/api -f charts/api/values-staging.yaml
```

**DE**  
Build → Tests → Security-Scans → Docker-Image → Staging (Expand-Migrationen) → E2E → Canary mit automatischem Rollback bei Bedarf.

---

### Fiche 6 — Déploiements sans interruption
**FR**  
Les déploiements Kubernetes utilisent la stratégie `RollingUpdate` avec des probes de `readiness` et `liveness`. Les métriques SLO issues d'OpenTelemetry pilotent les gates. En cas de souci, `kubectl rollout undo` est exécuté automatiquement par ArgoCD.

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: media-api
spec:
  replicas: 3
  strategy:
    type: RollingUpdate
    rollingUpdate:
      maxUnavailable: 1
      maxSurge: 1
  template:
    spec:
      containers:
        - name: app
          image: registry.example.com/media-api:1.5.0
          readinessProbe:
            httpGet: { path: /healthz, port: 8080 }
          livenessProbe:
            httpGet: { path: /livez, port: 8080 }
```

**DE**  
Rolling- oder Canary-Deployments mit SLO-Gates. Bei Regressionen erfolgt automatisch ein Rollback.

---

### Fiche 7 — Migrations DB expand/contract
**FR**  
Les migrations Doctrine suivent un pattern en trois temps : `expand`, `backfill`, `contract`. Le code applicatif reste compatible tout au long du cycle.

```php
public function up(Schema $schema): void
{
    $this->addSql('ALTER TABLE media ADD COLUMN alt_text VARCHAR(255) DEFAULT NULL');
}

public function postUp(Schema $schema): void
{
    $this->addSql("UPDATE media SET alt_text = filename WHERE alt_text IS NULL");
}

public function contract(): void
{
    $this->addSql('ALTER TABLE media ALTER COLUMN alt_text SET NOT NULL');
}
```

**DE**  
Nicht-destruktiv hinzufügen (Expand), Backfill außerhalb der Spitzenzeiten, kompatiblen Code deployen, danach Contract (NOT NULL, altes Feld droppen).

---

### Fiche 8 — Dockerfile multi-stage Symfony
**FR**  
Un Dockerfile multi-stage permet d'obtenir une image finale légère, non-root et traçable.

```dockerfile
FROM php:8.3-cli AS build
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-progress
COPY . .
RUN php bin/console cache:clear --env=prod

FROM gcr.io/distroless/php82
WORKDIR /app
COPY --from=build /app /app
USER 1000:1000
HEALTHCHECK --interval=30s CMD php -r "echo file_get_contents('http://127.0.0.1:8080/healthz');"
LABEL org.opencontainers.image.revision=$CI_COMMIT_SHA
```

**DE**  
Schlanke Multi-Stage-Images, Non-Root-User, Healthcheck und Labels für Nachvollziehbarkeit.

---

### Fiche 9 — Logs JSON + Correlation ID
**FR**  
Les logs Symfony sont structurés (JSON) et enrichis d'un `X-Correlation-ID` propagé par middleware. Les sorties vont vers `stdout` pour être collectées par Loki/ELK.

```yaml
# config/packages/monolog.yaml (extrait)
monolog:
  handlers:
    main:
      type: stream
      path: 'php://stdout'
      formatter: monolog.formatter.json
```

Un `RequestIdListener` ajoute un identifiant si l'en-tête n'est pas fourni et le transmet aux appels sortants.

**DE**  
Strukturierte JSON-Logs mit Correlation-ID, Aggregation via Loki/ELK, kontrollierte Aufbewahrung.

---

### Fiche 10 — OpenTelemetry dans Symfony
**FR**  
Le bootstrap OpenTelemetry configure l'export OTLP (OTEL collector) et ajoute des spans métier.

```php
$provider = (new TracerProviderFactory())->create([
    'exporter' => ['type' => 'otlp', 'endpoint' => $_ENV['OTLP_ENDPOINT']],
    'sampler' => ['type' => 'parentbased_always_on'],
]);
$tracer = $provider->getTracer('media-api');

$span = $tracer->spanBuilder('media.generation')->startSpan();
try {
    $service->generate($command);
} finally {
    $span->end();
}
```

**DE**  
Auto-Instrumentierung für HTTP/DB/Queues, fachliche Spans, OTLP-Export und W3C-`traceparent`-Propagation.

---

### Fiche 11 — Diagnostiquer une requête lente
**FR**  
Activez le `slow_query_log` PostgreSQL puis analysez le plan.

```sql
EXPLAIN ANALYZE
SELECT *
FROM media
WHERE owner_id = :owner
ORDER BY created_at DESC
LIMIT 20;
```

Un index composite conseillé :

```sql
CREATE INDEX idx_media_owner_created_at
ON media(owner_id, created_at DESC);
```

**DE**  
Slow-Query-Log + `EXPLAIN ANALYZE`, passende zusammengesetzte Indizes, N+1 vermeiden.

---

### Fiche 12 — Secrets & chiffrement
**FR**  
Aucun secret dans Git. Utilisez SOPS + KMS pour chiffrer `values.yaml` et Sealed Secrets pour Kubernetes.

```bash
sops -e charts/api/secrets.yaml > charts/api/secrets.enc.yaml
```

```yaml
apiVersion: bitnami.com/v1alpha1
kind: SealedSecret
metadata:
  name: api-secrets
spec:
  encryptedData:
    DATABASE_URL: AgB8...
```

**DE**  
Keine Klartext-Secrets im Repo; Tresor (Vault/SOPS+KMS), Rotation, strenges RBAC und Audit.

---

### Fiche 13 — Azure Blob via Flysystem
**FR**  
Le stockage distant des médias utilise Flysystem et l'adaptateur Azure Blob avec SAS token court.

```yaml
# config/packages/flysystem.yaml (extrait)
flysystem:
  storages:
    media.storage:
      adapter: 'azure'
      options:
        account: '%env(AZURE_ACCOUNT)%'
        container: '%env(AZURE_CONTAINER)%'
        sasToken: '%env(AZURE_SAS_TOKEN)%'
```

**DE**  
Temporäre SAS-Tokens, Verschlüsselung at rest, CDN + Cache-Control/ETag, Lifecycle-Regeln.

---

### Fiche 14 — Sécuriser Mercure (JWT)
**FR**  
Les JWT Mercure sont distincts pour la publication et l'abonnement, avec un scope de topics.

```json
{
  "publish": ["media/{id}"],
  "subscribe": ["media/*"],
  "exp": 1735689600
}
```

Les clés sont stockées dans Azure Key Vault et injectées au runtime via CSI driver.

**DE**  
Getrennte JWTs für Publish/Subscribe, Topic-Scopes, TLS, Rate-Limits und Zugriffs-Logs.

---

### Fiche 15 — Temporal workflow (suppression différée)
**FR**  
Temporal orchestre la suppression différée des médias : 30 jours après un soft delete, l'activité supprime le blob Azure (idempotent).

```php
#[WorkflowInterface]
class PurgeWorkflow
{
    #[WorkflowMethod]
    public function run(string $mediaId): void
    {
        Workflow::sleep(Duration::days(30));
        Activities::deleteBlob($mediaId);
    }
}
```

**DE**  
Zuverlässige Orchestrierung mit Retries und Idempotenz: 30 Tage nach dem logischen Delete wird das Medium physisch gelöscht.

---

### Fiche 16 — Sauvegarde & monitoring MongoDB
**FR**
L'ODM MongoDB complète désormais MySQL pour conserver les journaux HTTP/login et les conversations Messenger. L'instance Docker locale est déclarée sous le service `mongodb` avec un volume persistant `./var/mongodb-data`, ce qui reflète la topologie des environnements distants.【F:compose.yaml†L69-L84】 Pour garantir la continuité d'activité :

* **Sauvegarde** : planifier un `mongodump` quotidien via le scheduler applicatif afin d'archiver les collections `log_request`, `log_login` et `log_login_failure` nommées dans les documents ODM.【F:src/Log/Infrastructure/Document/LogRequestDocument.php†L10-L83】 Un exemple de commande à inscrire dans le scheduler :

  ```bash
  docker compose exec -T mongodb mongodump --db "$MONGODB_DB" --archive=/backups/mongo-$(date +%F).gz --gzip
  ```

  Le bundle CommandScheduler exécute ces tâches en s'appuyant sur le cron `scheduler:execute` déjà lancé par `docker/general/cron`, il suffit donc d'ajouter un job dédié via une commande type `scheduler:create` sur la plateforme cible.【F:docker/general/cron†L1-L2】【F:src/Log/Transport/Command/Scheduler/CleanupLogsScheduledCommand.php†L24-L90】
* **Monitoring** : exposer des métriques en supervisant `db.serverStatus()` ou `mongostat` depuis le conteneur (`docker compose exec mongodb mongostat --rowcount 1`) et en reliant les alertes à la consommation disque du volume `mongodb-data`.
* **Maintenance des données** : le nettoyage SQL déclenché par `logs:cleanup` reste en place; pour conserver une rétention alignée côté Mongo, utiliser un script `mongosh` périodique (`db.log_request.deleteMany({createdAt: {$lt: ISODate(...)}})`). Les réinitialisations manuelles des comptes déclenchent déjà la suppression dans Mongo via la ressource `LogLoginFailureResource`.【F:src/Log/Application/Resource/LogLoginFailureResource.php†L17-L107】

**DE**
Die MongoDB-Instanz ergänzt MySQL, um HTTP-/Login-Logs sowie Messenger-Konversationen auszulagern. Der Docker-Service `mongodb` bindet ein persistentes Volume `./var/mongodb-data`, wodurch lokale und entfernte Umgebungen deckungsgleich bleiben.【F:compose.yaml†L69-L84】 Für einen stabilen Betrieb gilt:

* **Backups**: Tägliche `mongodump`-Runs über den Command Scheduler einplanen, um die in den Dokumentklassen referenzierten Collections (`log_request`, `log_login`, `log_login_failure`) zu sichern.【F:src/Log/Infrastructure/Document/LogRequestDocument.php†L10-L83】 Beispielkommando:

  ```bash
  docker compose exec -T mongodb mongodump --db "$MONGODB_DB" --archive=/backups/mongo-$(date +%F).gz --gzip
  ```

  Der Cron im Container ruft `scheduler:execute` auf; neue Dumps lassen sich daher als geplante Jobs hinterlegen, analog zu `scheduler:cleanup-logs` im Code.【F:docker/general/cron†L1-L2】【F:src/Log/Transport/Command/Scheduler/CleanupLogsScheduledCommand.php†L24-L90】
* **Monitoring**: Kennzahlen per `mongostat` oder `db.serverStatus()` erfassen (`docker compose exec mongodb mongostat --rowcount 1`) und die Volume-Auslastung von `mongodb-data` beobachten.
* **Datenhygiene**: `logs:cleanup` entfernt weiterhin alte SQL-Zeilen; ergänzend sollte ein `mongosh`-Script regelmäßig veraltete Dokumente löschen. Benutzer-Resets beseitigen ihre Fehlversuche bereits synchron über die Ressource `LogLoginFailureResource`.【F:src/Log/Application/Resource/LogLoginFailureResource.php†L17-L107】

