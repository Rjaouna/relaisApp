<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421235500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add decision support columns to client for assignment, segmentation and solvency.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client ADD zone VARCHAR(120) DEFAULT NULL, ADD segment VARCHAR(120) DEFAULT NULL, ADD solvency_score INT DEFAULT NULL, ADD assigned_commercial_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_EEABF67C984B0E2F ON client (assigned_commercial_id)');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_EEABF67C984B0E2F FOREIGN KEY (assigned_commercial_id) REFERENCES commercial (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_EEABF67C984B0E2F');
        $this->addSql('DROP INDEX IDX_EEABF67C984B0E2F ON client');
        $this->addSql('ALTER TABLE client DROP assigned_commercial_id, DROP zone, DROP segment, DROP solvency_score');
    }
}
