<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260424113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le multi-zones commercial, le rattachement explicite visite-tour, la zone sur les tournees et les champs de suivi RDV.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS commercial_zone_assignment');
        $this->addSql('CREATE TABLE commercial_zone_assignment (commercial_id INT NOT NULL, zone_id INT NOT NULL, INDEX IDX_5D9ABF251E0A3C4 (commercial_id), INDEX IDX_5D9ABF2F46DD8D47 (zone_id), PRIMARY KEY(commercial_id, zone_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = MyISAM');

        $this->addSql('ALTER TABLE tour ADD zone_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_BAA0A2C6F46DD8D47 ON tour (zone_id)');

        $this->addSql('ALTER TABLE visit ADD tour_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_2B91CA51D60322AC ON visit (tour_id)');

        $this->addSql('ALTER TABLE appointment ADD note LONGTEXT DEFAULT NULL, ADD notified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        $this->addSql('INSERT IGNORE INTO commercial_zone_assignment (commercial_id, zone_id) SELECT id, zone_id FROM commercial WHERE zone_id IS NOT NULL');
        $this->addSql('UPDATE tour t INNER JOIN commercial c ON c.id = t.commercial_id SET t.zone_id = c.zone_id WHERE t.zone_id IS NULL AND c.zone_id IS NOT NULL');
        $this->addSql("UPDATE visit v INNER JOIN client c ON c.id = v.client_id INNER JOIN tour t ON t.commercial_id = c.assigned_commercial_id AND t.city = c.city AND DATE(t.scheduled_for) = DATE(v.scheduled_at) SET v.tour_id = t.id WHERE v.tour_id IS NULL AND v.archived_at IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_2B91CA51D60322AC ON visit');
        $this->addSql('ALTER TABLE visit DROP tour_id');

        $this->addSql('DROP INDEX IDX_BAA0A2C6F46DD8D47 ON tour');
        $this->addSql('ALTER TABLE tour DROP zone_id');

        $this->addSql('ALTER TABLE appointment DROP note, DROP notified_at');

        $this->addSql('DROP TABLE commercial_zone_assignment');
    }
}
