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
                id         CHAR(36)     NOT NULL COMMENT '(DC2Type:uuid)',
                name       VARCHAR(255) NOT NULL,
                email      VARCHAR(255) NOT NULL,
                password   VARCHAR(255) NOT NULL,
                created_at DATETIME     NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME     NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                deleted_at DATETIME     DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY (id),
                UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        // -----------------------------------------------------------------
        // work_entries
        // -----------------------------------------------------------------
        $this->addSql(<<<'SQL'
            CREATE TABLE work_entries (
                id         CHAR(36)     NOT NULL COMMENT '(DC2Type:uuid)',
                user_id    CHAR(36)     NOT NULL COMMENT '(DC2Type:uuid)',
                start_date DATETIME     NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                end_date   DATETIME     DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                created_at DATETIME     NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME     NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                deleted_at DATETIME     DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY (id),
                INDEX IDX_5D3B2488A76ED395 (user_id),
                CONSTRAINT FK_5D3B2488A76ED395
                    FOREIGN KEY (user_id)
                    REFERENCES users (id)
                    ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE work_entries DROP FOREIGN KEY FK_5D3B2488A76ED395');
        $this->addSql('DROP TABLE work_entries');
        $this->addSql('DROP TABLE users');
    }
}
