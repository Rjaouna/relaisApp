<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260423192717 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE appointment (id INT AUTO_INCREMENT NOT NULL, scheduled_at DATETIME NOT NULL, status VARCHAR(50) NOT NULL, subject VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, visit_id INT NOT NULL, client_id INT NOT NULL, commercial_id INT NOT NULL, UNIQUE INDEX UNIQ_FE38F84475FA0FF2 (visit_id), INDEX IDX_FE38F84419EB6921 (client_id), INDEX IDX_FE38F8447854071C (commercial_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F84475FA0FF2 FOREIGN KEY (visit_id) REFERENCES visit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F84419EB6921 FOREIGN KEY (client_id) REFERENCES client (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F8447854071C FOREIGN KEY (commercial_id) REFERENCES commercial (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE visit ADD appointment_scheduled_at DATETIME DEFAULT NULL, CHANGE admin_reviewed_at admin_reviewed_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F84475FA0FF2');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F84419EB6921');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F8447854071C');
        $this->addSql('DROP TABLE appointment');
        $this->addSql('ALTER TABLE visit DROP appointment_scheduled_at, CHANGE admin_reviewed_at admin_reviewed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
