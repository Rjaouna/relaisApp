<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422023000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create reference_option table for admin-managed choices';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE reference_option (id INT AUTO_INCREMENT NOT NULL, category_name VARCHAR(80) NOT NULL, label VARCHAR(120) NOT NULL, option_value VARCHAR(120) NOT NULL, is_active TINYINT(1) NOT NULL, sort_order INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX uniq_reference_option_category_value (category_name, option_value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE reference_option');
    }
}
