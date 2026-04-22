<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422001000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add zones entity, attach commercials to zones, add visit result and update tours to programmee status.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE zone (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, city VARCHAR(120) NOT NULL, code VARCHAR(60) DEFAULT NULL, is_active TINYINT(1) NOT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE commercial ADD zone_id INT DEFAULT NULL');
        $this->addSql("INSERT INTO zone (name, city, code, is_active, notes, created_at, updated_at) SELECT DISTINCT commercial.zone, commercial.city, NULL, 1, NULL, NOW(), NOW() FROM commercial WHERE commercial.zone IS NOT NULL AND commercial.zone <> ''");
        $this->addSql('UPDATE commercial c INNER JOIN zone z ON z.name = c.zone AND z.city = c.city SET c.zone_id = z.id');
        $this->addSql('CREATE INDEX IDX_7653F3AEFEE797D8 ON commercial (zone_id)');
        $this->addSql('ALTER TABLE commercial ADD CONSTRAINT FK_7653F3AEFEE797D8 FOREIGN KEY (zone_id) REFERENCES zone (id)');
        $this->addSql('ALTER TABLE commercial DROP zone');
        $this->addSql('ALTER TABLE visit ADD result VARCHAR(50) DEFAULT NULL');
        $this->addSql("UPDATE tour SET status = 'programmee' WHERE status = 'planifiee'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commercial ADD zone VARCHAR(120) NOT NULL');
        $this->addSql('UPDATE commercial c LEFT JOIN zone z ON c.zone_id = z.id SET c.zone = COALESCE(z.name, c.city)');
        $this->addSql('ALTER TABLE commercial DROP FOREIGN KEY FK_7653F3AEFEE797D8');
        $this->addSql('DROP INDEX IDX_7653F3AEFEE797D8 ON commercial');
        $this->addSql('ALTER TABLE commercial DROP zone_id');
        $this->addSql('ALTER TABLE visit DROP result');
        $this->addSql("UPDATE tour SET status = 'planifiee' WHERE status = 'programmee'");
        $this->addSql('DROP TABLE zone');
    }
}
