<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423201500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute l archivage des visites et des tournees';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tour ADD archived_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE visit ADD archived_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tour DROP archived_at');
        $this->addSql('ALTER TABLE visit DROP archived_at');
    }
}
