<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423195500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la date de demande de fermeture des tournees';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tour ADD closure_requested_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tour DROP closure_requested_at');
    }
}
