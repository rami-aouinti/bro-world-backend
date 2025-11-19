<?php

declare(strict_types=1);

// phpcs:ignoreFile
namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250604120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reminder flags to calendar events';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            "Migration can only be executed safely on 'mysql'.",
        );

        $this->addSql(<<<'SQL'
            ALTER TABLE calendar_event
                ADD four_hour_reminder_sent TINYINT(1) DEFAULT '0' NOT NULL,
                ADD fifteen_minute_reminder_sent TINYINT(1) DEFAULT '0' NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            "Migration can only be executed safely on 'mysql'.",
        );

        $this->addSql(<<<'SQL'
            ALTER TABLE calendar_event
                DROP four_hour_reminder_sent,
                DROP fifteen_minute_reminder_sent
        SQL);
    }
}
