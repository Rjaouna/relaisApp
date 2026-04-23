<?php

namespace App\Repository;

use App\Entity\MenuVisibility;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MenuVisibility>
 */
class MenuVisibilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MenuVisibility::class);
    }

    /**
     * @return array<string, MenuVisibility>
     */
    public function findIndexedByCode(): array
    {
        $indexed = [];

        foreach ($this->findAll() as $setting) {
            $indexed[$setting->getCode()] = $setting;
        }

        return $indexed;
    }
}
