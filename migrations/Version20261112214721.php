<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261112214721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remplacement des sessions du portail par les associations permanentes Numelib-Solaccess';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE solaccess_portal_account_link (id INT AUTO_INCREMENT NOT NULL, client_id VARCHAR(64) NOT NULL, external_user_id VARCHAR(64) NOT NULL, enabled TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, solaccess_user_id INT NOT NULL, UNIQUE INDEX uniq_spal_client_external (client_id, external_user_id), UNIQUE INDEX uniq_spal_client_solaccess (client_id, solaccess_user_id), INDEX IDX_A1B65CE9F05112B7 (solaccess_user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE solaccess_portal_account_link ADD CONSTRAINT FK_A1B65CE9F05112B7 FOREIGN KEY (solaccess_user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE solaccess_portal_session DROP FOREIGN KEY `FK_26BCF025F05112B7`');
        $this->addSql('DROP TABLE solaccess_portal_session');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE solaccess_portal_session (handle_hash VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, numelib_user_id VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, browser_binding_hash VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, expires_at DATETIME NOT NULL, revoked_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, solaccess_user_id INT NOT NULL, INDEX IDX_26BCF025F05112B7 (solaccess_user_id), INDEX idx_solaccess_portal_numelib (numelib_user_id), PRIMARY KEY (handle_hash)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE solaccess_portal_session ADD CONSTRAINT `FK_26BCF025F05112B7` FOREIGN KEY (solaccess_user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE solaccess_portal_account_link DROP FOREIGN KEY FK_A1B65CE9F05112B7');
        $this->addSql('DROP TABLE solaccess_portal_account_link');
    }
}
