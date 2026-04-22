<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422033000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert client zone to relation, require address, add coordinates';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client ADD zone_id INT DEFAULT NULL, ADD latitude NUMERIC(10, 7) DEFAULT NULL, ADD longitude NUMERIC(10, 7) DEFAULT NULL');
        $this->addSql('UPDATE client c LEFT JOIN zone z ON z.name = c.zone SET c.zone_id = z.id WHERE c.zone IS NOT NULL');
        $this->addSql('UPDATE client SET address = \'Adresse non renseignee\' WHERE address IS NULL OR address = \'\'');
        $this->addSql('ALTER TABLE client DROP zone, CHANGE address address VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C74404557E3C61F9 FOREIGN KEY (zone_id) REFERENCES zone (id)');
        $this->addSql('CREATE INDEX IDX_C74404557E3C61F9 ON client (zone_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C74404557E3C61F9');
        $this->addSql('DROP INDEX IDX_C74404557E3C61F9 ON client');
        $this->addSql('ALTER TABLE client ADD zone VARCHAR(120) DEFAULT NULL, DROP zone_id, DROP latitude, DROP longitude');
        $this->addSql('ALTER TABLE client CHANGE address address VARCHAR(255) DEFAULT NULL');
    }
}
