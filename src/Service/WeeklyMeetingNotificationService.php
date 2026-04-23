<?php

namespace App\Service;

use App\Entity\WeeklyMeeting;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class WeeklyMeetingNotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function notifyAttendees(WeeklyMeeting $meeting, bool $isNew): int
    {
        $sent = 0;
        $recipients = array_filter(
            $meeting->getAttendees()->toArray(),
            static fn (User $user): bool => $user->isActive() && (bool) $user->getEmail()
        );

        if ($recipients === []) {
            return 0;
        }

        $subjectPrefix = $isNew ? 'Nouvelle reunion' : 'Mise a jour reunion';
        $subject = sprintf('%s - %s', $subjectPrefix, $meeting->getTitle());
        $organizer = $meeting->getOrganizer()?->getFullName() ?: 'Organisation interne';
        $date = $meeting->getMeetingDate()?->format('d/m/Y a H:i') ?? 'Date a confirmer';
        $from = 'no-reply@relais-medical.local';
        $agenda = $meeting->getAgenda() ?: 'Ordre du jour a consulter dans l application.';

        foreach ($recipients as $recipient) {
            $email = (new Email())
                ->from($from)
                ->to((string) $recipient->getEmail())
                ->subject($subject)
                ->html(sprintf(
                    '<p>Bonjour %s,</p><p>Vous etes concerne par une reunion commerciale.</p><ul><li><strong>Sujet :</strong> %s</li><li><strong>Date :</strong> %s</li><li><strong>Organisateur :</strong> %s</li></ul><p><strong>Ordre du jour</strong><br>%s</p><p>Merci de consulter l application pour le detail et les actions a suivre.</p>',
                    htmlspecialchars((string) $recipient->getFullName(), ENT_QUOTES),
                    htmlspecialchars($meeting->getTitle(), ENT_QUOTES),
                    htmlspecialchars($date, ENT_QUOTES),
                    htmlspecialchars($organizer, ENT_QUOTES),
                    nl2br(htmlspecialchars($agenda, ENT_QUOTES))
                ));

            try {
                $this->mailer->send($email);
                ++$sent;
            } catch (TransportExceptionInterface $exception) {
                $this->logger->warning('Meeting notification failed.', [
                    'meeting_id' => $meeting->getId(),
                    'recipient' => $recipient->getEmail(),
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $sent;
    }
}
