<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261112181456 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table des sessions Solaccess pour le portail Numelib';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE solaccess_portal_session (handle_hash VARCHAR(64) NOT NULL, numelib_user_id VARCHAR(64) NOT NULL, browser_binding_hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, revoked_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, solaccess_user_id INT NOT NULL, INDEX idx_solaccess_portal_numelib (numelib_user_id), INDEX IDX_26BCF025F05112B7 (solaccess_user_id), PRIMARY KEY (handle_hash)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE solaccess_portal_session ADD CONSTRAINT FK_26BCF025F05112B7 FOREIGN KEY (solaccess_user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE solaccess_portal_session DROP FOREIGN KEY FK_26BCF025F05112B7');
        $this->addSql('DROP TABLE solaccess_portal_session');
    }
}
