<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates the `users` and `work_entries` tables with UUID primary keys,
 * soft-delete support and a foreign-key relation between them.
 */
final class Version20260622174800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users and work_entries tables';
    }

    public function up(Schema $schema): void
    {
        // -----------------------------------------------------------------
        // users
        // -----------------------------------------------------------------
        $this->addSql(<<<'SQL'
            CREATE TABLE users (
                id         BINARY(16)   NOT NULL,
                name       VARCHAR(255) NOT NULL,
                email      VARCHAR(255) NOT NULL,
                password   VARCHAR(255) NOT NULL,
                created_at DATETIME     NOT NULL,
                updated_at DATETIME     NOT NULL,
                deleted_at DATETIME     DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        // -----------------------------------------------------------------
        // work_entries
        // -----------------------------------------------------------------
        $this->addSql(<<<'SQL'
            CREATE TABLE work_entries (
                id         BINARY(16)   NOT NULL,
                user_id    BINARY(16)   NOT NULL,
                start_date DATETIME     NOT NULL,
                end_date   DATETIME     DEFAULT NULL,
                created_at DATETIME     NOT NULL,
                updated_at DATETIME     NOT NULL,
                deleted_at DATETIME     DEFAULT NULL,
                PRIMARY KEY (id),
                INDEX IDX_F8330BE7A76ED395 (user_id),
                INDEX IDX_WE_USER_START_DATE (user_id, start_date),
                CONSTRAINT FK_F8330BE7A76ED395
                    FOREIGN KEY (user_id)
                    REFERENCES users (id)
                    ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE work_entries DROP FOREIGN KEY FK_F8330BE7A76ED395');
        $this->addSql('DROP TABLE work_entries');
        $this->addSql('DROP TABLE users');
    }
}
