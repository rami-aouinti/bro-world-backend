<?php

declare(strict_types=1);

// phpcs:ignoreFile
namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250602123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create workplace table to store workplace entities';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            "Migration can only be executed safely on 'mysql'.",
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE workplace (
                id BINARY(16) NOT NULL,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT NULL,
                updated_at DATETIME DEFAULT NULL,
                UNIQUE INDEX uq_workplace_slug (slug),
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

        $this->addSql('DROP TABLE workplace');
    }
}
