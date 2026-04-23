<?php

namespace App\Service;

use App\Entity\WeeklyMeeting;
use App\Entity\User;
use App\Repository\WeeklyMeetingRepository;
use Doctrine\ORM\EntityManagerInterface;

class WeeklyMeetingCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WeeklyMeetingRepository $repository,
        private readonly WeeklyMeetingNotificationService $notificationService,
    ) {
    }

    public function getListing(): array
    {
        return $this->repository->findBy([], ['meetingDate' => 'DESC']);
    }

    public function save(WeeklyMeeting $meeting, ?User $actingUser = null): int
    {
        $isNew = $meeting->getId() === null;
        if ($meeting->getOrganizer() === null && $actingUser instanceof User) {
            $meeting->setOrganizer($actingUser);
        }

        $meeting->syncParticipantsSummary();
        $meeting->touch();
        $this->entityManager->persist($meeting);
        $this->entityManager->flush();

        return $this->notificationService->notifyAttendees($meeting, $isNew);
    }

    public function delete(WeeklyMeeting $meeting): void
    {
        $this->entityManager->remove($meeting);
        $this->entityManager->flush();
    }
}
