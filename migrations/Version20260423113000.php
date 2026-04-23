<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add admin review workflow columns to visit';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE visit ADD admin_review_status VARCHAR(50) DEFAULT NULL, ADD admin_review_comment LONGTEXT DEFAULT NULL, ADD admin_reviewed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE visit DROP admin_review_status, DROP admin_review_comment, DROP admin_reviewed_at');
    }
}
