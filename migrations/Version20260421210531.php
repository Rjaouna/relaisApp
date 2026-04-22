<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260421210531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) NOT NULL, city VARCHAR(120) NOT NULL, type VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, email VARCHAR(180) DEFAULT NULL, phone VARCHAR(30) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, potential_score INT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, annual_revenue NUMERIC(12, 2) NOT NULL, last_visit_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commercial (id INT AUTO_INCREMENT NOT NULL, full_name VARCHAR(180) NOT NULL, city VARCHAR(120) NOT NULL, zone VARCHAR(120) NOT NULL, sales_target INT NOT NULL, visits_target INT NOT NULL, new_clients_target INT NOT NULL, current_clients_load INT NOT NULL, current_visits_load INT NOT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_7653F3AEA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE delivery (id INT AUTO_INCREMENT NOT NULL, reference VARCHAR(100) NOT NULL, scheduled_at DATETIME NOT NULL, status VARCHAR(50) NOT NULL, delay_days INT NOT NULL, city VARCHAR(120) NOT NULL, client_id INT NOT NULL, INDEX IDX_3781EC1019EB6921 (client_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE market (id INT AUTO_INCREMENT NOT NULL, city VARCHAR(120) NOT NULL, clients_count INT NOT NULL, revenue NUMERIC(12, 2) NOT NULL, competition_score INT NOT NULL, coverage_score INT NOT NULL, global_score INT NOT NULL, zone_status VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE objective (id INT AUTO_INCREMENT NOT NULL, period_label VARCHAR(80) NOT NULL, sales_target INT NOT NULL, visits_target INT NOT NULL, new_clients_target INT NOT NULL, sales_actual INT NOT NULL, visits_actual INT NOT NULL, new_clients_actual INT NOT NULL, commercial_id INT NOT NULL, INDEX IDX_B996F1017854071C (commercial_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE offer (id INT AUTO_INCREMENT NOT NULL, reference VARCHAR(50) NOT NULL, amount NUMERIC(12, 2) NOT NULL, status VARCHAR(50) NOT NULL, issued_at DATETIME NOT NULL, conditions_summary LONGTEXT DEFAULT NULL, history_notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, client_id INT NOT NULL, UNIQUE INDEX UNIQ_29D6873EAEA34913 (reference), INDEX IDX_29D6873E19EB6921 (client_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) NOT NULL, category VARCHAR(120) NOT NULL, purchase_price NUMERIC(12, 2) NOT NULL, sale_price NUMERIC(12, 2) NOT NULL, stock_quantity INT NOT NULL, market_status VARCHAR(50) NOT NULL, supplier_id INT DEFAULT NULL, INDEX IDX_D34A04AD2ADD6D8C (supplier_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE supplier (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) NOT NULL, country VARCHAR(120) NOT NULL, status VARCHAR(50) NOT NULL, reactivity_score INT NOT NULL, price_score INT NOT NULL, contact_email VARCHAR(180) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE supply_order (id INT AUTO_INCREMENT NOT NULL, reference VARCHAR(100) NOT NULL, ordered_at DATETIME NOT NULL, lead_time_days INT NOT NULL, status VARCHAR(50) NOT NULL, amount NUMERIC(12, 2) NOT NULL, supplier_id INT NOT NULL, INDEX IDX_91F9D33C2ADD6D8C (supplier_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tour (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) NOT NULL, city VARCHAR(120) NOT NULL, scheduled_for DATETIME NOT NULL, status VARCHAR(50) NOT NULL, planned_visits INT NOT NULL, completed_visits INT NOT NULL, route_summary VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, commercial_id INT NOT NULL, INDEX IDX_6AD1F9697854071C (commercial_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, full_name VARCHAR(180) NOT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE visit (id INT AUTO_INCREMENT NOT NULL, scheduled_at DATETIME NOT NULL, type VARCHAR(50) NOT NULL, priority VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, objective LONGTEXT DEFAULT NULL, report LONGTEXT DEFAULT NULL, next_action LONGTEXT DEFAULT NULL, interest_level INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, client_id INT NOT NULL, INDEX IDX_437EE93919EB6921 (client_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE commercial ADD CONSTRAINT FK_7653F3AEA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT FK_3781EC1019EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE objective ADD CONSTRAINT FK_B996F1017854071C FOREIGN KEY (commercial_id) REFERENCES commercial (id)');
        $this->addSql('ALTER TABLE offer ADD CONSTRAINT FK_29D6873E19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD2ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id)');
        $this->addSql('ALTER TABLE supply_order ADD CONSTRAINT FK_91F9D33C2ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id)');
        $this->addSql('ALTER TABLE tour ADD CONSTRAINT FK_6AD1F9697854071C FOREIGN KEY (commercial_id) REFERENCES commercial (id)');
        $this->addSql('ALTER TABLE visit ADD CONSTRAINT FK_437EE93919EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commercial DROP FOREIGN KEY FK_7653F3AEA76ED395');
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY FK_3781EC1019EB6921');
        $this->addSql('ALTER TABLE objective DROP FOREIGN KEY FK_B996F1017854071C');
        $this->addSql('ALTER TABLE offer DROP FOREIGN KEY FK_29D6873E19EB6921');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD2ADD6D8C');
        $this->addSql('ALTER TABLE supply_order DROP FOREIGN KEY FK_91F9D33C2ADD6D8C');
        $this->addSql('ALTER TABLE tour DROP FOREIGN KEY FK_6AD1F9697854071C');
        $this->addSql('ALTER TABLE visit DROP FOREIGN KEY FK_437EE93919EB6921');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE commercial');
        $this->addSql('DROP TABLE delivery');
        $this->addSql('DROP TABLE market');
        $this->addSql('DROP TABLE objective');
        $this->addSql('DROP TABLE offer');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE supplier');
        $this->addSql('DROP TABLE supply_order');
        $this->addSql('DROP TABLE tour');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE visit');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
