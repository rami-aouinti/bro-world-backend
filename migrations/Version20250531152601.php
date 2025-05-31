<?php

declare(strict_types=1);

// phpcs:ignoreFile
namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250531152601 extends AbstractMigration
{
    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function getDescription(): string
    {
        return 'TODO: Describe reason for this migration';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql(<<<'SQL'
            ALTER TABLE log_login CHANGE type type ENUM('failure', 'success') NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE story DROP FOREIGN KEY FK_EB560438A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE story ADD CONSTRAINT FK_EB560438A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user CHANGE language language ENUM('en', 'ru', 'ua', 'fi') NOT NULL COMMENT 'User language for translations', CHANGE locale locale ENUM('en', 'ru', 'ua', 'fi') NOT NULL COMMENT 'User locale for number, time, date, etc. formatting.'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_follow DROP FOREIGN KEY FK_D665F4DD956F010
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_follow DROP FOREIGN KEY FK_D665F4DAC24F853
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_follow ADD CONSTRAINT FK_D665F4DD956F010 FOREIGN KEY (followed_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_follow ADD CONSTRAINT FK_D665F4DAC24F853 FOREIGN KEY (follower_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_profile DROP FOREIGN KEY FK_D95AB405A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_profile ADD CONSTRAINT FK_D95AB405A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * {@inheritdoc}
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql(<<<'SQL'
            ALTER TABLE log_login CHANGE type type VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE story DROP FOREIGN KEY FK_EB560438A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE story ADD CONSTRAINT FK_EB560438A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user CHANGE language language VARCHAR(255) NOT NULL COMMENT 'User language for translations', CHANGE locale locale VARCHAR(255) NOT NULL COMMENT 'User locale for number, time, date, etc. formatting.'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_follow DROP FOREIGN KEY FK_D665F4DAC24F853
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_follow DROP FOREIGN KEY FK_D665F4DD956F010
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_follow ADD CONSTRAINT FK_D665F4DAC24F853 FOREIGN KEY (follower_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_follow ADD CONSTRAINT FK_D665F4DD956F010 FOREIGN KEY (followed_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_profile DROP FOREIGN KEY FK_D95AB405A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_profile ADD CONSTRAINT FK_D95AB405A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE
        SQL);
    }
}
