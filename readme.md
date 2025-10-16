# Bro World Backend

Symfony 7 environment for building and operating the Bro World REST API. The repository ships with a full Docker stack (Nginx, PHP-FPM, MySQL, RabbitMQ, Elasticsearch, Redis, Mailpit, Kibana) and provides tooling for development, quality assurance, and observability.

[![CI Symfony Rest API](https://github.com/bro-world/bro-world-backend/actions/workflows/ci.yml/badge.svg)](https://github.com/bro-world/bro-world-backend/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

## Table of contents
- [Project overview](#project-overview)
- [Prerequisites](#prerequisites)
- [Technical stack](#technical-stack)
- [Key capabilities](#key-capabilities)
- [Local setup (development)](#local-setup-development)
- [Running staging and production-like stacks](#running-staging-and-production-like-stacks)
- [Development and QA commands](#development-and-qa-commands)
- [Troubleshooting tips](#troubleshooting-tips)
- [Further documentation](#further-documentation)
- [License](#license)

## Project overview

The Bro World backend exposes business resources through a versioned REST API. The codebase follows Symfony best practices with a modular domain-driven structure (`App\User`, `App\Media`, `App\Tool`, etc.). The Docker environment mirrors production as closely as possible so that features and fixes can be tested locally with minimal drift.

Core goals:
- Provide a developer-friendly setup with one command to bootstrap the full stack.
- Offer robust authentication via API keys and JWTs for both user-facing and application-to-application flows.
- Support asynchronous workloads with RabbitMQ-backed message queues.
- Supply monitoring, logging, and analytics tooling out of the box.

## Prerequisites

Install the following tools before cloning the repository:

- Docker Engine **23.0** or newer.
- Docker Compose **2.0** or newer.
- An IDE or editor with PHP support (PhpStorm recommended).
- Optional: MySQL Workbench for database administration.

> **Recommended OS**: an Ubuntu-based Linux distribution. The stack also runs on macOS and Windows (WSL2) provided Docker Desktop is available.

## Technical stack

| Service            | Version | Purpose                                             |
|--------------------|---------|-----------------------------------------------------|
| Nginx              | 1.27    | Reverse proxy and static asset delivery             |
| PHP-FPM            | 8.4     | PHP runtime with Symfony, Composer and extensions   |
| MySQL              | 8       | Primary relational database                         |
| Symfony            | 7       | API implementation framework                        |
| RabbitMQ           | 4       | Message broker for asynchronous tasks               |
| Elasticsearch + Kibana | 7   | Full-text search and analytics dashboard            |
| Redis              | 7       | Cache, rate limiting, and message transport storage |
| Mailpit            | latest  | SMTP sink for capturing emails in development       |

## Key capabilities

- **Documented REST API** – Swagger UI is available at [`http://localhost/api/doc`](http://localhost/api/doc). Additional references live in [`docs/swagger.md`](docs/swagger.md) and [`docs/postman.md`](docs/postman.md) for ready-to-import Postman collections.
- **User and role management** – Console commands create users, groups, and roles (`App\User`, `App\Role`). See [`docs/commands.md`](docs/commands.md) for the complete catalog.
- **API key & JWT authentication** – The `App\ApiKey` module enables partner integrations via `Authorization: ApiKey <token>`. Generate RSA keys with `make generate-jwt-keys` and review [`docs/api-key.md`](docs/api-key.md) for configuration details.
- **Asynchronous messaging** – Symfony Messenger connects to RabbitMQ queues (`messages_high`, `messages_low`, `external`). Monitor queues through [`http://localhost:15672`](http://localhost:15672) with credentials `guest` / `guest` and read more in [`docs/messenger.md`](docs/messenger.md).
- **Search & analytics** – Elasticsearch templates are managed with `make elastic-create-or-update-template`. Kibana is exposed at [`http://localhost:5601`](http://localhost:5601).
- **Logging & monitoring** – Modules `App\Log` and `App\General` track requests, database health, and background jobs. Scheduled cleanup runs via cron (`make migrate-cron-jobs`, `./bin/console logs:cleanup`).
- **Media and tooling utilities** – `App\Media` handles file management while `App\Tool` contains helpers (DateDimension generation, Messenger serializers, etc.).
- **Developer tooling** – Xdebug, PhpInsights, PhpMetrics, Rector, PHPStan, and more are configured out of the box. Consult [`docs/development.md`](docs/development.md) and [`docs/testing.md`](docs/testing.md).
- **Operations guidance** – Operational playbooks cover idempotency, CI/CD, Kubernetes deployments, Azure Blob via Flysystem, Temporal workflows, and more inside [`docs/operational-fiches.md`](docs/operational-fiches.md).

## Local setup (development)

1. **Clone the repository**
   ```bash
   git clone https://github.com/bro-world/bro-world-backend.git
   cd bro-world-backend
   ```
2. **Configure secrets**
   - Generate a unique `APP_SECRET` in `.env`, `.env.staging`, and `.env.prod`.
   - Update credentials for MySQL (`DATABASE_URL`), RabbitMQ (`RABBITMQ_USER` / `RABBITMQ_PASS`), Elasticsearch (`ELASTICSEARCH_USERNAME` / `ELASTICSEARCH_PASSWORD`), Redis, and mailers (`MAILER_DSN`, `MAILJET_API_KEY`, `MAILJET_SECRET_KEY`).
   - Replace the default JWT passphrase `JWT_PASSPHRASE` and secure the key files under `config/jwt/*.pem`.
   - Use `.env.local*` files to override sensitive values without committing them.
3. **Optional cleanup**
   Remove existing persistent data if required: `rm -rf var/mysql-data`.
4. **Enable Xdebug (optional)**
   Adjust `/docker/dev/xdebug-main.ini` (Linux/Windows) or `/docker/dev/xdebug-osx.ini` (macOS). A step-by-step guide is in [`docs/xdebug.md`](docs/xdebug.md).
5. **Build and start the stack**
   ```bash
   make build
   make start
   make composer-install
   make generate-jwt-keys
   ```
6. **Initialize data and supporting services**
   ```bash
   make migrate
   make create-roles-groups
   make migrate-cron-jobs
   make messenger-setup-transports
   make elastic-create-or-update-template
   ```
7. **Verify main entry points**
   - API documentation: [`http://localhost/api/doc`](http://localhost/api/doc)
   - RabbitMQ management: [`http://localhost:15672`](http://localhost:15672)
   - Kibana dashboard: [`http://localhost:5601`](http://localhost:5601)
   - Mailpit inbox: [`http://localhost:8025`](http://localhost:8025)

## Running staging and production-like stacks

The repository provides `compose-staging.yaml` and `compose-prod.yaml` for environment-specific setups. Prior to running the commands, adjust secrets in `.env.staging` and `.env.prod`.

### Staging
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

### Production
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

## Development and QA commands

The `Makefile` centralizes the most common routines (see [`docs/commands.md`](docs/commands.md) for the exhaustive list).

| Purpose | Command |
| --- | --- |
| Launch dev stack | `make start` / `make stop` |
| Recreate containers | `make down && make build && make start` |
| Open a shell inside the Symfony container | `make ssh` |
| Install Composer dependencies | `make composer-install` |
| Check Composer security advisories | `make composer-audit` |
| Run automated tests | `make phpunit` (coverage report in `reports/coverage/index.html`) |
| Enforce code style | `make ecs`, `make ecs-fix`, `make phpcs` |
| Static analysis | `make phpstan`, `make phpinsights`, `make phpmd`, `make phpcpd`, `make phpcpd-html-report` |
| Additional tooling | `make phpmetrics`, `make composer-unused`, `make composer-require-checker`, `make composer-normalize`, `make composer-validate` |

Inside the container (`make ssh`) you can execute `./vendor/bin/phpunit` or `./bin/console` for fine-grained control. Refer to [`docs/testing.md`](docs/testing.md) for test strategy guidelines.

## Troubleshooting tips

- Run `make help` to list every available make target.
- Execute `make report-clean` before re-running tests to drop stale coverage reports.
- After changing the `Dockerfile` or any `compose*.yaml`, rebuild images with `make build` to apply updates.
- Postman collections are stored in [`docs/postman`](docs/postman) and can be imported directly in the Postman app.
- Persistent issues with Docker volumes? Remove `var/mysql-data` and restart the stack.

## Further documentation

- [`docs/development.md`](docs/development.md) – coding conventions, DDD structure, and quality tooling.
- [`docs/commands.md`](docs/commands.md) – reference of Make and Symfony commands.
- [`docs/testing.md`](docs/testing.md) – test types and reporting guidance.
- [`docs/postman.md`](docs/postman.md) / [`docs/swagger.md`](docs/swagger.md) – API exploration resources.
- [`docs/messenger.md`](docs/messenger.md) – RabbitMQ queues, retries, and supervisor options.
- [`docs/api-key.md`](docs/api-key.md) – external integrations using API keys.
- [`docs/devops-integration.md`](docs/devops-integration.md) – DevOps architecture overview.
- [`docs/phpstorm.md`](docs/phpstorm.md) and `docs/phpstorm/` – IDE setup snippets.
- [`docs/xdebug.md`](docs/xdebug.md) – debugging from your IDE.

## License

Distributed under the [MIT License](LICENSE).
