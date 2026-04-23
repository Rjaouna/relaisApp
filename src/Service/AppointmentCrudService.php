<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\Commercial;
use App\Entity\User;
use App\Entity\Visit;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class AppointmentCrudService
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
    ) {
    }

    /**
     * @return Appointment[]
     */
    public function getListing(): array
    {
        return $this->appointmentRepository->findForListing();
    }

    /**
     * @return Appointment[]
     */
    public function getListingForUser(?User $user): array
    {
        $commercial = $user?->getCommercial();

        if (!$commercial instanceof Commercial) {
            return [];
        }

        return $this->appointmentRepository->findForCommercial($commercial);
    }

    public function canAccessAppointment(?User $user, Appointment $appointment): bool
    {
        $commercial = $user?->getCommercial();

        if (!$commercial instanceof Commercial) {
            return false;
        }

        return $appointment->getCommercial()?->getId() === $commercial->getId();
    }

    public function validateNoConflictForVisit(Visit $visit): void
    {
        $commercial = $visit->getClient()?->getAssignedCommercial();
        $scheduledAt = $visit->getAppointmentScheduledAt();

        if (!$commercial instanceof Commercial || !$scheduledAt instanceof \DateTimeImmutable) {
            return;
        }

        if ($this->appointmentRepository->hasConflictForCommercial($commercial, $scheduledAt, $visit)) {
            throw new \LogicException('Impossible de programmer ce rendez-vous : ce commercial a deja un autre RDV a moins d une heure de cet horaire.');
        }
    }

    public function syncFromVisit(Visit $visit): void
    {
        $appointment = $visit->getId() !== null
            ? $this->appointmentRepository->findOneByVisitId($visit->getId())
            : null;

        $shouldCreateOrUpdate = $visit->getResult() === Visit::RESULT_APPOINTMENT_BOOKED
            && $visit->getAppointmentScheduledAt() instanceof \DateTimeImmutable
            && $visit->getClient()?->getAssignedCommercial() instanceof Commercial;

        if (!$shouldCreateOrUpdate) {
            if ($appointment instanceof Appointment) {
                $this->entityManager->remove($appointment);
            }

            return;
        }

        $this->validateNoConflictForVisit($visit);

        $appointment ??= new Appointment();

        $appointment
            ->setVisit($visit)
            ->setClient($visit->getClient())
            ->setCommercial($visit->getClient()?->getAssignedCommercial())
            ->setScheduledAt($visit->getAppointmentScheduledAt())
            ->setStatus(Appointment::STATUS_PLANNED)
            ->setSubject(sprintf('RDV %s', $visit->getClient()?->getName() ?? 'client'));

        $appointment->touch();
        $this->entityManager->persist($appointment);
    }

    public function cancel(Appointment $appointment, ?string $note = null): void
    {
        $appointment
            ->setStatus(Appointment::STATUS_CANCELLED)
            ->setNote($note ?: null);

        $appointment->touch();
        $this->entityManager->persist($appointment);
        $this->entityManager->flush();
    }

    public function reschedule(Appointment $appointment, \DateTimeImmutable $scheduledAt, ?string $note = null): void
    {
        $commercial = $appointment->getCommercial();
        if (!$commercial instanceof Commercial) {
            throw new \LogicException('Aucun commercial n est lie a ce rendez-vous.');
        }

        if ($this->appointmentRepository->hasConflictForCommercial($commercial, $scheduledAt, $appointment->getVisit())) {
            throw new \LogicException('Impossible de reprogrammer ce rendez-vous : un autre RDV existe deja a moins d une heure.');
        }

        $appointment
            ->setScheduledAt($scheduledAt)
            ->setStatus(Appointment::STATUS_PLANNED)
            ->setNote($note ?: null);

        if ($appointment->getVisit() instanceof Visit) {
            $appointment->getVisit()->setAppointmentScheduledAt($scheduledAt);
            $appointment->getVisit()->touch();
            $this->entityManager->persist($appointment->getVisit());
        }

        $appointment->touch();
        $this->entityManager->persist($appointment);
        $this->entityManager->flush();
    }

    public function notifyCommercial(Appointment $appointment): void
    {
        $emailAddress = $appointment->getCommercial()?->getUser()?->getEmail();
        if (!$emailAddress) {
            throw new \LogicException('Aucun email commercial n est disponible pour ce rendez-vous.');
        }

        $email = (new Email())
            ->to($emailAddress)
            ->subject(sprintf('Rappel RDV - %s', $appointment->getClient()?->getName() ?? 'Client'))
            ->text(sprintf(
                "Bonjour,\n\nUn rendez-vous est programme.\n\nClient : %s\nDate : %s\nObjet : %s\n\nMerci de preparer le suivi commercial.",
                $appointment->getClient()?->getName() ?? 'Client',
                $appointment->getScheduledAt()?->format('d/m/Y H:i') ?? 'Non definie',
                $appointment->getSubject() ?? 'Rendez-vous commercial'
            ));

        $this->mailer->send($email);

        $appointment->setNotifiedAt(new \DateTimeImmutable());
        $appointment->touch();
        $this->entityManager->persist($appointment);
        $this->entityManager->flush();
    }
}
