<?php

declare(strict_types=1);

// phpcs:ignoreFile
namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250603120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ownership, visibility and relations for workplace plugins and members';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            "Migration can only be executed safely on 'mysql'.",
        );

        $this->addSql("ALTER TABLE workplace ADD owner_id BINARY(16) NOT NULL, ADD is_private TINYINT(1) DEFAULT '0' NOT NULL, ADD enabled TINYINT(1) DEFAULT '1' NOT NULL");
        $this->addSql('CREATE INDEX idx_workplace_owner ON workplace (owner_id)');
        $this->addSql('ALTER TABLE workplace ADD CONSTRAINT fk_workplace_owner FOREIGN KEY (owner_id) REFERENCES user (id)');

        $this->addSql(<<<'SQL'
            CREATE TABLE workplace_plugins (
                workplace_id BINARY(16) NOT NULL,
                plugin_id BINARY(16) NOT NULL,
                INDEX IDX_workplace_plugins_workplace (workplace_id),
                INDEX IDX_workplace_plugins_plugin (plugin_id),
                PRIMARY KEY(workplace_id, plugin_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql('ALTER TABLE workplace_plugins ADD CONSTRAINT FK_workplace_plugins_workplace FOREIGN KEY (workplace_id) REFERENCES workplace (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workplace_plugins ADD CONSTRAINT FK_workplace_plugins_plugin FOREIGN KEY (plugin_id) REFERENCES plugin (id) ON DELETE CASCADE');

        $this->addSql(<<<'SQL'
            CREATE TABLE workplace_members (
                workplace_id BINARY(16) NOT NULL,
                user_id BINARY(16) NOT NULL,
                INDEX IDX_workplace_members_workplace (workplace_id),
                INDEX IDX_workplace_members_user (user_id),
                PRIMARY KEY(workplace_id, user_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql('ALTER TABLE workplace_members ADD CONSTRAINT FK_workplace_members_workplace FOREIGN KEY (workplace_id) REFERENCES workplace (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workplace_members ADD CONSTRAINT FK_workplace_members_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            "Migration can only be executed safely on 'mysql'.",
        );

        $this->addSql('ALTER TABLE workplace_members DROP FOREIGN KEY FK_workplace_members_workplace');
        $this->addSql('ALTER TABLE workplace_members DROP FOREIGN KEY FK_workplace_members_user');
        $this->addSql('DROP TABLE workplace_members');

        $this->addSql('ALTER TABLE workplace_plugins DROP FOREIGN KEY FK_workplace_plugins_workplace');
        $this->addSql('ALTER TABLE workplace_plugins DROP FOREIGN KEY FK_workplace_plugins_plugin');
        $this->addSql('DROP TABLE workplace_plugins');

        $this->addSql('ALTER TABLE workplace DROP FOREIGN KEY fk_workplace_owner');
        $this->addSql('DROP INDEX idx_workplace_owner ON workplace');
        $this->addSql('ALTER TABLE workplace DROP owner_id, DROP is_private, DROP enabled');
    }
}
