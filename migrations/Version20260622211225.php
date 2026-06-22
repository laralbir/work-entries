<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260622211225 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add roles column to users; seed initial admin user (admin@workentries.com).';
    }

    public function up(Schema $schema): void
    {
        // Add roles column; existing rows temporarily get an empty JSON array
        $this->addSql("ALTER TABLE users ADD roles JSON NOT NULL DEFAULT ('[]')");

        // Backfill existing users with the default worker role
        $this->addSql("UPDATE users SET roles = JSON_ARRAY('ROLE_WORKER') WHERE roles = JSON_ARRAY()");

        // Remove the server-side default (the application always supplies the value)
        $this->addSql('ALTER TABLE users ALTER COLUMN roles DROP DEFAULT');

        // Insert initial admin user
        // UUID v7: 019ef3a0-0000-7000-8000-000000000001 (time-ordered, fixed for reproducibility)
        // Password: nimda  –  bcrypt hash generated with cost 13
        $this->addSql(<<<'SQL'
            INSERT INTO users (id, name, email, password, roles, created_at, updated_at, deleted_at)
            VALUES (
                UUID_TO_BIN('019ef3a0-0000-7000-8000-000000000001'),
                'Administrator',
                'admin@workentries.com',
                '$2y$13$lQ87IS6PeEm8Pbepqfcc1eJBYNIu69ewpXOk1xvgzbdFqO5HJoWwi',
                JSON_ARRAY('ROLE_ADMIN'),
                NOW(),
                NOW(),
                NULL
            )
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM users WHERE email = 'admin@workentries.com'");
        $this->addSql('ALTER TABLE users DROP COLUMN roles');
    }
}
