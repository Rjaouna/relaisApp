<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260421204839 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) NOT NULL, city VARCHAR(120) NOT NULL, type VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, email VARCHAR(180) DEFAULT NULL, phone VARCHAR(30) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, potential_score INT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, annual_revenue NUMERIC(12, 2) NOT NULL, last_visit_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE offer (id INT AUTO_INCREMENT NOT NULL, reference VARCHAR(50) NOT NULL, amount NUMERIC(12, 2) NOT NULL, status VARCHAR(50) NOT NULL, issued_at DATETIME NOT NULL, conditions_summary LONGTEXT DEFAULT NULL, history_notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, client_id INT NOT NULL, UNIQUE INDEX UNIQ_29D6873EAEA34913 (reference), INDEX IDX_29D6873E19EB6921 (client_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE visit (id INT AUTO_INCREMENT NOT NULL, scheduled_at DATETIME NOT NULL, type VARCHAR(50) NOT NULL, priority VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, objective LONGTEXT DEFAULT NULL, report LONGTEXT DEFAULT NULL, next_action LONGTEXT DEFAULT NULL, interest_level INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, client_id INT NOT NULL, INDEX IDX_437EE93919EB6921 (client_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE offer ADD CONSTRAINT FK_29D6873E19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE visit ADD CONSTRAINT FK_437EE93919EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE offer DROP FOREIGN KEY FK_29D6873E19EB6921');
        $this->addSql('ALTER TABLE visit DROP FOREIGN KEY FK_437EE93919EB6921');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE offer');
        $this->addSql('DROP TABLE visit');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
