<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260422180522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE customer_satisfaction (id INT AUTO_INCREMENT NOT NULL, satisfaction_level VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, expectation_summary LONGTEXT DEFAULT NULL, market_listening LONGTEXT DEFAULT NULL, delivery_requested_at DATE DEFAULT NULL, next_action LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, client_id INT NOT NULL, INDEX IDX_8456CF0A19EB6921 (client_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE field_feedback (id INT AUTO_INCREMENT NOT NULL, category VARCHAR(50) NOT NULL, priority VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, summary LONGTEXT NOT NULL, market_signals LONGTEXT DEFAULT NULL, decision_action LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, client_id INT DEFAULT NULL, commercial_id INT DEFAULT NULL, visit_id INT DEFAULT NULL, INDEX IDX_411C39519EB6921 (client_id), INDEX IDX_411C3957854071C (commercial_id), INDEX IDX_411C39575FA0FF2 (visit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_launch_project (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) NOT NULL, target_city VARCHAR(120) DEFAULT NULL, target_entities LONGTEXT DEFAULT NULL, market_study LONGTEXT DEFAULT NULL, feasibility_notes LONGTEXT DEFAULT NULL, import_conditions LONGTEXT DEFAULT NULL, registration_required TINYINT NOT NULL, status VARCHAR(50) NOT NULL, follow_up_notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, product_id INT DEFAULT NULL, INDEX IDX_AA6623DB4584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE supplier_consultation (id INT AUTO_INCREMENT NOT NULL, need_title VARCHAR(180) NOT NULL, need_details LONGTEXT NOT NULL, expected_delay VARCHAR(120) DEFAULT NULL, status VARCHAR(50) NOT NULL, sample_status VARCHAR(50) NOT NULL, quoted_amount NUMERIC(12, 2) DEFAULT NULL, negotiated_amount NUMERIC(12, 2) DEFAULT NULL, compliance_notes LONGTEXT DEFAULT NULL, negotiation_notes LONGTEXT DEFAULT NULL, selected_supplier TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, supplier_id INT NOT NULL, INDEX IDX_FC2F12FF2ADD6D8C (supplier_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE weekly_meeting (id INT AUTO_INCREMENT NOT NULL, meeting_date DATETIME NOT NULL, title VARCHAR(180) NOT NULL, team_scope VARCHAR(120) DEFAULT NULL, status VARCHAR(50) NOT NULL, agenda LONGTEXT DEFAULT NULL, decisions LONGTEXT DEFAULT NULL, action_items LONGTEXT DEFAULT NULL, participants_summary LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE customer_satisfaction ADD CONSTRAINT FK_8456CF0A19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE field_feedback ADD CONSTRAINT FK_411C39519EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE field_feedback ADD CONSTRAINT FK_411C3957854071C FOREIGN KEY (commercial_id) REFERENCES commercial (id)');
        $this->addSql('ALTER TABLE field_feedback ADD CONSTRAINT FK_411C39575FA0FF2 FOREIGN KEY (visit_id) REFERENCES visit (id)');
        $this->addSql('ALTER TABLE product_launch_project ADD CONSTRAINT FK_AA6623DB4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE supplier_consultation ADD CONSTRAINT FK_FC2F12FF2ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C74404559F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C7440455C266AE59 FOREIGN KEY (assigned_commercial_id) REFERENCES commercial (id)');
        $this->addSql('ALTER TABLE commercial ADD CONSTRAINT FK_7653F3AE9F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id)');
        $this->addSql('ALTER TABLE commercial ADD CONSTRAINT FK_7653F3AEA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE delivery ADD CONSTRAINT FK_3781EC1019EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE objective ADD CONSTRAINT FK_B996F1017854071C FOREIGN KEY (commercial_id) REFERENCES commercial (id)');
        $this->addSql('ALTER TABLE offer ADD CONSTRAINT FK_29D6873E19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE offer_item ADD CONSTRAINT FK_E1E30B0953C674EE FOREIGN KEY (offer_id) REFERENCES offer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE offer_item ADD CONSTRAINT FK_E1E30B094584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE offer_item RENAME INDEX idx_5e889e6453c674ee TO IDX_E1E30B0953C674EE');
        $this->addSql('ALTER TABLE offer_item RENAME INDEX idx_5e889e644584665a TO IDX_E1E30B094584665A');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD2ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id)');
        $this->addSql('ALTER TABLE supply_order ADD CONSTRAINT FK_91F9D33C2ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id)');
        $this->addSql('ALTER TABLE tour ADD CONSTRAINT FK_6AD1F9697854071C FOREIGN KEY (commercial_id) REFERENCES commercial (id)');
        $this->addSql('ALTER TABLE visit ADD CONSTRAINT FK_437EE93919EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer_satisfaction DROP FOREIGN KEY FK_8456CF0A19EB6921');
        $this->addSql('ALTER TABLE field_feedback DROP FOREIGN KEY FK_411C39519EB6921');
        $this->addSql('ALTER TABLE field_feedback DROP FOREIGN KEY FK_411C3957854071C');
        $this->addSql('ALTER TABLE field_feedback DROP FOREIGN KEY FK_411C39575FA0FF2');
        $this->addSql('ALTER TABLE product_launch_project DROP FOREIGN KEY FK_AA6623DB4584665A');
        $this->addSql('ALTER TABLE supplier_consultation DROP FOREIGN KEY FK_FC2F12FF2ADD6D8C');
        $this->addSql('DROP TABLE customer_satisfaction');
        $this->addSql('DROP TABLE field_feedback');
        $this->addSql('DROP TABLE product_launch_project');
        $this->addSql('DROP TABLE supplier_consultation');
        $this->addSql('DROP TABLE weekly_meeting');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C74404559F2C3FAB');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C7440455C266AE59');
        $this->addSql('ALTER TABLE commercial DROP FOREIGN KEY FK_7653F3AE9F2C3FAB');
        $this->addSql('ALTER TABLE commercial DROP FOREIGN KEY FK_7653F3AEA76ED395');
        $this->addSql('ALTER TABLE delivery DROP FOREIGN KEY FK_3781EC1019EB6921');
        $this->addSql('ALTER TABLE objective DROP FOREIGN KEY FK_B996F1017854071C');
        $this->addSql('ALTER TABLE offer DROP FOREIGN KEY FK_29D6873E19EB6921');
        $this->addSql('ALTER TABLE offer_item DROP FOREIGN KEY FK_E1E30B0953C674EE');
        $this->addSql('ALTER TABLE offer_item DROP FOREIGN KEY FK_E1E30B094584665A');
        $this->addSql('ALTER TABLE offer_item RENAME INDEX idx_e1e30b094584665a TO IDX_5E889E644584665A');
        $this->addSql('ALTER TABLE offer_item RENAME INDEX idx_e1e30b0953c674ee TO IDX_5E889E6453C674EE');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD2ADD6D8C');
        $this->addSql('ALTER TABLE supply_order DROP FOREIGN KEY FK_91F9D33C2ADD6D8C');
        $this->addSql('ALTER TABLE tour DROP FOREIGN KEY FK_6AD1F9697854071C');
        $this->addSql('ALTER TABLE visit DROP FOREIGN KEY FK_437EE93919EB6921');
    }
}
