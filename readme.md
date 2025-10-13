# Bro World Backend

Environnement Symfony 7 pour construire et exploiter l'API REST du projet Bro World. Le dépôt fournit une stack Docker complète (Nginx, PHP-FPM, MySQL, RabbitMQ, Elasticsearch, Redis, Mailpit, Kibana) ainsi que les outils nécessaires pour le développement, les tests automatisés et l'observabilité.

[![CI Symfony Rest API](https://github.com/bro-world/bro-world-backend/actions/workflows/ci.yml/badge.svg)](https://github.com/bro-world/bro-world-backend/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

[Code source](https://github.com/bro-world/bro-world-backend)

## Prérequis

* Docker Engine version 23.0 ou ultérieure
* Docker Compose version 2.0 ou ultérieure
* Un éditeur ou IDE (PhpStorm recommandé)
* MySQL Workbench pour l'administration si besoin

> OS recommandé : distribution Linux basée sur Ubuntu.

## Stack technique

1. Nginx 1.27
2. PHP 8.4 FPM
3. MySQL 8
4. Symfony 7
5. RabbitMQ 4
6. Elasticsearch 7 + Kibana 7
7. Redis 7
8. Mailpit (debug e-mails en environnement de développement)

## Fonctionnalités

* **API REST documentée** : exposition des ressources métier via des contrôleurs versionnés, documentation Swagger accessible sur [`http://localhost/api/doc`](http://localhost/api/doc). Voir [docs/swagger.md](docs/swagger.md) et [docs/postman.md](docs/postman.md) pour les collections Postman.
* **Gestion des utilisateurs et des rôles** : commandes console pour créer des utilisateurs, groupes et rôles (`App\\User`, `App\\Role`). Référez-vous à [docs/commands.md](docs/commands.md) pour la liste complète des commandes.
* **Authentification par clés API et JWT** : module `App\ApiKey` pour les intégrations inter-applicatives (header `Authorization: ApiKey <token>`), génération de clés RSA avec `make generate-jwt-keys`, configuration détaillée dans [docs/api-key.md](docs/api-key.md).
* **Messagerie asynchrone** : Symfony Messenger couplé à RabbitMQ avec des files priorisées (`messages_high`, `messages_low`) et une file d'intégration externe (`external`). Interface de supervision RabbitMQ disponible sur [`http://localhost:15672`](http://localhost:15672) (login `guest`/`guest`). Détails et options de reprise dans [docs/messenger.md](docs/messenger.md).
* **Recherche et analytique** : intégration Elasticsearch (index template `make elastic-create-or-update-template`, utilisateur par défaut `elastic/changeme`). Kibana accessible via [`http://localhost:5601`](http://localhost:5601).
* **Logs et monitoring** : modules `App\Log` et `App\General` pour tracer requêtes, connexions, santé de la base de données et nettoyage automatisé via cron (voir commandes `make migrate-cron-jobs` et `./bin/console logs:cleanup`).
* **Gestion des médias et outils utilitaires** : modules `App\Media` et `App\Tool` (génération de DateDimension, sérialiseurs Messenger, etc.).
* **Outils de développement** : configuration prête à l'emploi pour Xdebug, PhpStorm, PhpInsights, PhpMetrics, documentation dans [docs/development.md](docs/development.md) et [docs/testing.md](docs/testing.md).
* **Opérations & delivery** : fiches pratiques couvrant idempotence, CI/CD GitLab, déploiements Kubernetes, Azure Blob via Flysystem, Mercure JWT, Temporal, etc. Voir [docs/operational-fiches.md](docs/operational-fiches.md).

## Mise en place de Docker Engine et Docker Compose

Suivre la procédure officielle décrite dans la documentation [Docker Engine](https://docs.docker.com/engine/install/).

*Linux* : exécuter `sudo usermod -aG docker $USER` après l'installation.

*macOS* : pour Docker Desktop ≥ 4.22, vérifier que [virtiofs](https://www.docker.com/blog/speed-boost-achievement-unlocked-on-docker-desktop-4-6-for-mac/) est activé (valeur par défaut).

## Installation et configuration locale (DEV)

1. Cloner ce dépôt :
   ```bash
   git clone https://github.com/bro-world/bro-world-backend.git
   cd bro-world-backend
   ```
2. Configurer les secrets applicatifs :
   * générer un `APP_SECRET` unique dans `.env`, `.env.staging` et `.env.prod` ;
   * adapter les identifiants MySQL (`DATABASE_URL`), RabbitMQ (`RABBITMQ_USER`/`RABBITMQ_PASS`), Elasticsearch (`ELASTICSEARCH_USERNAME`/`ELASTICSEARCH_PASSWORD`), Redis et les DSN mailers (`MAILER_DSN`, `MAILJET_API_KEY`, `MAILJET_SECRET_KEY`) à vos environnements ;
   * protéger les clés JWT (`config/jwt/*.pem`) en remplaçant la passphrase `JWT_PASSPHRASE`.
   Les fichiers `.env.local*` ou `env.*.local` permettent de surcharger les valeurs sensibles hors contrôle de version.
3. Nettoyer les volumes persistants si nécessaire (`rm -rf var/mysql-data`).
4. Configurer Xdebug au besoin via `/docker/dev/xdebug-main.ini` (Linux/Windows) ou `/docker/dev/xdebug-osx.ini` (macOS). Cf. [docs/xdebug.md](docs/xdebug.md).
5. Construire, démarrer et préparer l'environnement :
   ```bash
   make build
   make start
   make composer-install
   make generate-jwt-keys
   ```
6. Initialiser la base de données, les rôles/groupes, les cron jobs, les files Messenger et les templates Elasticsearch :
   ```bash
   make migrate
   make create-roles-groups
   make migrate-cron-jobs
   make messenger-setup-transports
   make elastic-create-or-update-template
   ```
7. Vérifier les points d'entrée principaux :
   * Swagger : [`http://localhost/api/doc`](http://localhost/api/doc)
   * RabbitMQ : [`http://localhost:15672`](http://localhost:15672)
   * Kibana : [`http://localhost:5601`](http://localhost:5601)
   * Mailpit : [`http://localhost:8025`](http://localhost:8025)

## Environnements STAGING et PROD locaux

Les fichiers `compose-staging.yaml` et `compose-prod.yaml` fournissent des variantes dédiées. Adapter les identifiants et secrets (`.env.staging`, `.env.prod`) avant exécution.

### STAGING
```bash
make build-staging
make start-staging
make generate-jwt-keys
make migrate-no-test
make create-roles-groups
make migrate-cron-jobs
make messenger-setup-transports
make elastic-create-or-update-template
```

### PROD
```bash
make build-prod
make start-prod
make generate-jwt-keys
make migrate-no-test
make create-roles-groups
make migrate-cron-jobs
make messenger-setup-transports
make elastic-create-or-update-template
```

## Commandes de développement & QA

Les principales commandes `make` à connaître (voir [docs/commands.md](docs/commands.md) pour la liste exhaustive) :

| Objectif | Commande |
| --- | --- |
| Lancer la stack dev | `make start` / `make stop` |
| Recréer les conteneurs | `make down && make build && make start` |
| Shell dans le conteneur Symfony | `make ssh` |
| Installer les dépendances | `make composer-install` |
| Mises à jour de sécurité Composer | `make composer-audit` |
| Tests automatisés | `make phpunit` (couverture dans `reports/coverage/index.html`) |
| Qualité de code | `make ecs`, `make ecs-fix`, `make phpcs`, `make phpstan`, `make phpinsights`, `make phpmd`, `make phpcpd`, `make phpcpd-html-report` |
| Analyses complémentaires | `make phpmetrics`, `make composer-unused`, `make composer-require-checker`, `make composer-normalize`, `make composer-validate` |

Pour l'exécution ciblée des tests ou des commandes Symfony, entrer dans le conteneur (`make ssh`) puis utiliser `./vendor/bin/phpunit` ou `./bin/console ...` selon les besoins. Des guides détaillés sont disponibles dans [docs/testing.md](docs/testing.md).

## Bonnes pratiques & ressources

* [docs/development.md](docs/development.md) : conventions de code, organisation DDD, outils qualité.
* [docs/commands.md](docs/commands.md) : catalogue complet des commandes Makefile et Symfony.
* [docs/testing.md](docs/testing.md) : typologies de tests et génération de rapports.
* [docs/postman.md](docs/postman.md) / [docs/swagger.md](docs/swagger.md) : documentation et exploration de l'API.
* [docs/messenger.md](docs/messenger.md) : configuration RabbitMQ, files, stratégies de retry.
* [docs/api-key.md](docs/api-key.md) : gestion des intégrations externes via clés API.
* [docs/phpstorm.md](docs/phpstorm.md) et `docs/phpstorm/` : configuration IDE.
* [docs/xdebug.md](docs/xdebug.md) : debugging pas-à-pas.

## Astuces supplémentaires

* Utiliser `make help` pour lister toutes les cibles disponibles.
* Penser à exécuter `make report-clean` avant une relance complète des tests pour purger les anciens rapports.
* En cas de modification du `Dockerfile` ou des manifests `compose*.yaml`, reconstruire les images avec `make build`.
* Les fichiers Postman sont fournis dans le dossier [`docs/postman`](docs/postman) et les guides pour Swagger/Postman se trouvent dans `docs/`.

## Licence

Projet distribué sous licence [MIT](LICENSE).
