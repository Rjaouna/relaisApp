<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422194500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add organizer and attendees to weekly meetings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE weekly_meeting ADD organizer_id INT DEFAULT NULL');
        $this->addSql('CREATE TABLE weekly_meeting_attendee (weekly_meeting_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_709BEB6ED0E4B7D (weekly_meeting_id), INDEX IDX_709BEB6A76ED395 (user_id), PRIMARY KEY(weekly_meeting_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE weekly_meeting ADD CONSTRAINT FK_D0E4B7DA76ED395 FOREIGN KEY (organizer_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE weekly_meeting_attendee ADD CONSTRAINT FK_709BEB6ED0E4B7D FOREIGN KEY (weekly_meeting_id) REFERENCES weekly_meeting (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE weekly_meeting_attendee ADD CONSTRAINT FK_709BEB6A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE weekly_meeting_attendee DROP FOREIGN KEY FK_709BEB6ED0E4B7D');
        $this->addSql('ALTER TABLE weekly_meeting_attendee DROP FOREIGN KEY FK_709BEB6A76ED395');
        $this->addSql('ALTER TABLE weekly_meeting DROP FOREIGN KEY FK_D0E4B7DA76ED395');
        $this->addSql('DROP TABLE weekly_meeting_attendee');
        $this->addSql('ALTER TABLE weekly_meeting DROP organizer_id');
    }
}
