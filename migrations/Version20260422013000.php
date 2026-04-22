<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422013000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create city entity and link zones to cities.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE city (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_2D5B023455B5302 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB');
        $this->addSql('INSERT INTO city (name, is_active, created_at, updated_at) SELECT DISTINCT zone.city, 1, NOW(), NOW() FROM zone WHERE zone.city IS NOT NULL AND zone.city <> \'\'');
        $this->addSql('ALTER TABLE zone ADD city_id INT DEFAULT NULL');
        $this->addSql('UPDATE zone z INNER JOIN city c ON c.name = z.city SET z.city_id = c.id');
        $this->addSql('ALTER TABLE zone CHANGE city_id city_id INT NOT NULL');
        $this->addSql('CREATE INDEX IDX_9F39F8B18BAC62AF ON zone (city_id)');
        $this->addSql('ALTER TABLE zone ADD CONSTRAINT FK_9F39F8B18BAC62AF FOREIGN KEY (city_id) REFERENCES city (id)');
        $this->addSql('ALTER TABLE zone DROP city');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone ADD city VARCHAR(120) NOT NULL');
        $this->addSql('UPDATE zone z INNER JOIN city c ON z.city_id = c.id SET z.city = c.name');
        $this->addSql('ALTER TABLE zone DROP FOREIGN KEY FK_9F39F8B18BAC62AF');
        $this->addSql('DROP INDEX IDX_9F39F8B18BAC62AF ON zone');
        $this->addSql('ALTER TABLE zone DROP city_id');
        $this->addSql('DROP TABLE city');
    }
}
