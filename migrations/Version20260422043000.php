<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422043000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add offer_item table for offer product lines';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE offer_item (id INT AUTO_INCREMENT NOT NULL, offer_id INT NOT NULL, product_id INT NOT NULL, quantity INT NOT NULL, unit_price NUMERIC(12, 2) NOT NULL, line_total NUMERIC(12, 2) NOT NULL, INDEX IDX_5E889E6453C674EE (offer_id), INDEX IDX_5E889E644584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE offer_item');
    }
}
