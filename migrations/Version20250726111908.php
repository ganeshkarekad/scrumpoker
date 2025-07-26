<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250726111908 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Add the new userLoginId column
        $this->addSql('ALTER TABLE users ADD user_login_id VARCHAR(36) NULL');
        
        // Generate UUIDs for existing users
        $this->addSql("UPDATE users SET user_login_id = UUID() WHERE user_login_id IS NULL");
        
        // Now make it NOT NULL and add constraints
        $this->addSql('ALTER TABLE users MODIFY user_login_id VARCHAR(36) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9BC3F045D ON users (user_login_id)');
        $this->addSql('CREATE INDEX idx_user_login_id ON users (user_login_id)');
        
        // Remove unique constraint from username (if it exists)
        $this->addSql('ALTER TABLE users DROP INDEX IF EXISTS UNIQ_1483A5E9F85E0677');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_1483A5E9BC3F045D ON users');
        $this->addSql('DROP INDEX idx_user_login_id ON users');
        $this->addSql('ALTER TABLE users DROP user_login_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9F85E0677 ON users (username)');
    }
}
