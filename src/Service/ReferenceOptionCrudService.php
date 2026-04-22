<?php

namespace App\Service;

use App\Entity\ReferenceOption;
use App\Repository\ReferenceOptionRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReferenceOptionCrudService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ReferenceOptionRepository $referenceOptionRepository,
    ) {
    }

    public function getListing(): array
    {
        return $this->referenceOptionRepository->findBy([], ['category' => 'ASC', 'sortOrder' => 'ASC', 'label' => 'ASC']);
    }

    public function getChoices(string $category, array $fallbackChoices = []): array
    {
        $options = $this->referenceOptionRepository->findActiveByCategory($category);
        if ($options === []) {
            return $fallbackChoices;
        }

        $choices = [];
        foreach ($options as $option) {
            $choices[$option->getLabel() ?? 'Option'] = $option->getValue() ?? '';
        }

        return $choices;
    }

    public function save(ReferenceOption $referenceOption): void
    {
        $referenceOption->setLabel($this->normalizeLabel($referenceOption->getLabel()));

        if (trim((string) $referenceOption->getValue()) === '') {
            $referenceOption->setValue($this->slugify($referenceOption->getLabel()));
        } else {
            $referenceOption->setValue($this->slugify($referenceOption->getValue()));
        }

        $referenceOption->touch();
        $this->entityManager->persist($referenceOption);
        $this->entityManager->flush();
    }

    public function delete(ReferenceOption $referenceOption): void
    {
        $this->entityManager->remove($referenceOption);
        $this->entityManager->flush();
    }

    private function normalizeLabel(?string $label): string
    {
        $value = trim((string) $label);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    private function slugify(?string $value): string
    {
        $normalized = trim((string) $value);
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized) ?: $normalized;
        $normalized = strtolower($normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? $normalized;

        return trim($normalized, '_');
    }
}
