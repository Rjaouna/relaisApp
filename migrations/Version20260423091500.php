<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423091500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add menu visibility settings table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE menu_visibility (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(80) NOT NULL, enabled TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_3868236277153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE menu_visibility');
    }
}
