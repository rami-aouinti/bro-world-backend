<?php

declare(strict_types=1);

// phpcs:ignoreFile
namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250601120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create idempotency_key table to persist API idempotent responses';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            "Migration can only be executed safely on 'mysql'.",
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE idempotency_key (
                id BINARY(16) NOT NULL,
                idempotency_key VARCHAR(255) NOT NULL,
                request_hash VARCHAR(128) NOT NULL,
                response_status SMALLINT NOT NULL,
                response_headers LONGTEXT NOT NULL COMMENT '(DC2Type:json)',
                response_body LONGTEXT NOT NULL,
                tenant VARCHAR(64) DEFAULT NULL,
                expires_at DATETIME DEFAULT NULL,
                created_at DATETIME DEFAULT NULL,
                updated_at DATETIME DEFAULT NULL,
                INDEX idx_idempotency_key_expires_at (expires_at),
                UNIQUE INDEX uq_idempotency_key_key (idempotency_key),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            "Migration can only be executed safely on 'mysql'.",
        );

        $this->addSql('DROP TABLE idempotency_key');
    }
}
