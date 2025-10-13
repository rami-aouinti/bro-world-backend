# DevOps integration walkthrough

Cette fiche synthétise tous les points "DevOps" déjà opérationnels dans le projet afin de pouvoir les présenter en entretien : conteneurisation, automatisation développeur, pipeline CI/CD, observabilité et fiabilité applicative.

## 1. Plateforme locale orchestrée par Docker Compose

* `compose.yaml` instancie une stack complète (Nginx, PHP-FPM Symfony, MySQL, RabbitMQ, Elasticsearch + Kibana, Redis, Mailpit, Mercure) avec les bons volumes et ports pour reproduire l'environnement de prod en local.【F:compose.yaml†L1-L113】【F:compose.yaml†L114-L193】
* Le service `symfony` est factorisé via un template réutilisé par `supervisord` pour exécuter cron et les consumers Messenger dans un conteneur séparé, ce qui simplifie l'exploitation des workers.【F:compose.yaml†L17-L47】

## 2. Image applicative PHP durcie

* Le `Dockerfile` unique produit l'image PHP-FPM 8.4 et adapte l'installation suivant l'argument `BUILD_ARGUMENT_ENV` (dev/test/staging/prod). On y trouve l'installation des dépendances système (amqp, redis, intl, supervisor, cron) et des extensions PHP critiques pour la prod.【F:Dockerfile†L1-L67】
* L'image applique un durcissement basique : recréation de l'utilisateur `www-data`, permissions limitées, copie des configs PHP/Xdebug adaptées à l'environnement puis exécution de `composer install` en mode prod ou dev selon la cible.【F:Dockerfile†L69-L122】【F:Dockerfile†L124-L153】

## 3. Automatisation développeur via Makefile

* Le `Makefile` charge les variables d'environnement et expose des commandes `make build|start|down` pour chaque cible (dev, test CI, staging, prod), en injectant les bons ports et secrets dans `docker compose`. Cela garantit la parité des environnements (ex. `make build-test` utilise `compose-test-ci.yaml`).【F:Makefile†L1-L104】
* Il centralise les tâches récurrentes : génération des clés JWT (`make generate-jwt-keys`), shell dans les conteneurs (`make ssh`, `make ssh-nginx`), et lancement des outils qualité (phpunit, ecs, phpstan…) appelés aussi par la CI.【F:Makefile†L132-L203】【F:Makefile†L205-L274】

## 4. Gestion de la configuration et des secrets

* Les services Symfony injectent tous les paramètres sensibles via des variables d'environnement bindées dans `services.yaml` (ex. délais idempotence/purge, credentials Azure, réglages Messenger). Aucun secret n'est codé en dur.【F:config/services.yaml†L1-L92】
* Les environnements `.env`, `.env.test` contiennent seulement des valeurs par défaut pour le développement et peuvent être surchargés par `.env.local` ignoré par Git (bonne pratique pour les secrets locaux).【F:Makefile†L1-L20】

## 5. Observabilité et traçabilité

* Un subscriber HTTP gère l'en-tête `X-Correlation-ID` en entrée/sortie et stocke l'identifiant dans un provider injectable pour les services et les logs.【F:src/General/Transport/EventSubscriber/CorrelationIdSubscriber.php†L7-L55】
* Le processor Monolog récupère ce même identifiant et l’ajoute automatiquement dans `extra` et `context`; `monolog.yaml` déclare ce processor globalement et formate les logs en JSON en prod/staging/test pour ingestion centralisée.【F:src/Log/Application/Monolog/CorrelationIdProcessor.php†L7-L34】【F:config/packages/monolog.yaml†L1-L55】

## 6. Fiabilité des API synchrones

* `IdempotencyService` persiste la réponse HTTP associée à un `Idempotency-Key`, rejoue la réponse en cas de répétition et empêche la réutilisation d’une clé avec un payload différent. Cela évite les doubles débits en cas de retry client.【F:src/General/Application/Service/Http/IdempotencyService.php†L7-L110】
* La TTL de ces réponses est configurable par environnement (bind dans `services.yaml`), et un repository dédié gère le nettoyage (voir migration `Version20250601120000`).【F:config/services.yaml†L21-L36】

## 7. Traitements asynchrones et nettoyage différé

* Le bus Messenger est configuré avec trois transports RabbitMQ (haute priorité, basse priorité, external) et un transport `failed` Doctrine pour les messages en erreur, en mode retry exponentiel.【F:config/packages/messenger.yaml†L1-L78】
* `MediaDeletionScheduler` planifie la purge physique d’un média en envoyant un message retardé (DelayStamp) tout en journalisant l’opération avec le `correlation_id`. Le handler `DelayedMediaDeletionHandler` vérifie que le fichier n’existe plus en base avant de supprimer le blob via l’adaptateur Azure et logge chaque étape.【F:src/Media/Application/Service/MediaDeletionScheduler.php†L7-L58】【F:src/Media/Application/MessageHandler/DelayedMediaDeletionHandler.php†L7-L74】
* L’adaptateur `AzureBlobStorage` supprime les blobs via HTTP signé, détecte les statuts non 2xx et remonte une `ServiceUnavailableHttpException` tout en loggant les détails pour observabilité.【F:src/Media/Infrastructure/Storage/AzureBlobStorage.php†L7-L74】

## 8. Pipeline CI/CD

* `bitbucket-pipelines.yml` lance une image Docker-in-Docker, installe les dépendances via `.bitbucket/dependencies.sh`, puis exécute la stack de test `compose-test-ci.yaml`. Il enchaîne provisioning (migrations, transports Messenger, templates Elasticsearch), tests unitaires et l’intégralité des quality gates (audit Composer/NPM, ECS, PHPCS, PHPStan, PhpInsights, PHP Mess Detector, PHPCPD).【F:bitbucket-pipelines.yml†L1-L32】
* Le pipeline démontre la philosophie "shift-left" : on provisionne tout l’environnement applicatif avant les tests, ce qui rapproche la CI d’un déploiement réel (utile à mentionner en entretien DevOps).【F:bitbucket-pipelines.yml†L10-L31】

## 9. Capitalisation pour un pitch en entretien

* Mettre en avant la parité des environnements (Dockerfile unique + Compose multi-roles + Makefile) et la gouvernance des secrets par variables d’environnement Symfony.
* Souligner les garde-fous qualité (CI Bitbucket, outils PSR/QA, tests, provisioning infra automatisé).
* Décrire l’observabilité prête à l’emploi (Correlation ID propagé, logs JSON, readiness/liveness dans Compose) et les patterns de résilience (idempotence, retries Messenger, storage Azure encapsulé).
* Illustrer l’approche DevOps par la suppression différée des médias : workflow async orchestré, instrumentation des logs et configuration via env, montrant la collaboration étroite entre développement et opérations.

Avec cette lecture, vous disposez d’un fil conducteur concret pour expliquer comment le projet intègre les pratiques DevOps de bout en bout : de la construction d’image à la surveillance post-déploiement.
