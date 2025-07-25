<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250725164905 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE room_admins (id INT AUTO_INCREMENT NOT NULL, room_id INT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_room_admin_room (room_id), INDEX idx_room_admin_user (user_id), INDEX idx_room_admin_created_at (created_at), UNIQUE INDEX unique_room_user_admin (room_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rooms (id INT AUTO_INCREMENT NOT NULL, created_by_id INT NOT NULL, room_key CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_7CA11A96881616B4 (room_key), INDEX IDX_7CA11A96B03A8386 (created_by_id), INDEX idx_room_key (room_key), INDEX idx_room_created_at (created_at), INDEX idx_room_updated_at (updated_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE room_users (room_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_5E3F044254177093 (room_id), INDEX IDX_5E3F0442A76ED395 (user_id), PRIMARY KEY(room_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_votes (id INT AUTO_INCREMENT NOT NULL, room_id INT NOT NULL, user_id INT NOT NULL, vote_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, INDEX idx_user_vote_room (room_id), INDEX idx_user_vote_user (user_id), INDEX idx_user_vote_vote (vote_id), INDEX idx_user_vote_created_at (created_at), INDEX idx_user_vote_updated_at (updated_at), UNIQUE INDEX unique_room_user_vote (room_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_1483A5E9F85E0677 (username), INDEX idx_user_username (username), INDEX idx_user_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE votes (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(5) NOT NULL, INDEX idx_vote_label (label), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE room_admins ADD CONSTRAINT FK_E3AED3CE54177093 FOREIGN KEY (room_id) REFERENCES rooms (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE room_admins ADD CONSTRAINT FK_E3AED3CEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rooms ADD CONSTRAINT FK_7CA11A96B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE room_users ADD CONSTRAINT FK_5E3F044254177093 FOREIGN KEY (room_id) REFERENCES rooms (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE room_users ADD CONSTRAINT FK_5E3F0442A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_votes ADD CONSTRAINT FK_B349819754177093 FOREIGN KEY (room_id) REFERENCES rooms (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_votes ADD CONSTRAINT FK_B3498197A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_votes ADD CONSTRAINT FK_B349819772DCDAFC FOREIGN KEY (vote_id) REFERENCES votes (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE room_admins DROP FOREIGN KEY FK_E3AED3CE54177093');
        $this->addSql('ALTER TABLE room_admins DROP FOREIGN KEY FK_E3AED3CEA76ED395');
        $this->addSql('ALTER TABLE rooms DROP FOREIGN KEY FK_7CA11A96B03A8386');
        $this->addSql('ALTER TABLE room_users DROP FOREIGN KEY FK_5E3F044254177093');
        $this->addSql('ALTER TABLE room_users DROP FOREIGN KEY FK_5E3F0442A76ED395');
        $this->addSql('ALTER TABLE user_votes DROP FOREIGN KEY FK_B349819754177093');
        $this->addSql('ALTER TABLE user_votes DROP FOREIGN KEY FK_B3498197A76ED395');
        $this->addSql('ALTER TABLE user_votes DROP FOREIGN KEY FK_B349819772DCDAFC');
        $this->addSql('DROP TABLE room_admins');
        $this->addSql('DROP TABLE rooms');
        $this->addSql('DROP TABLE room_users');
        $this->addSql('DROP TABLE user_votes');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE votes');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
