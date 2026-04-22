<?php

namespace App\Service;

use App\Repository\SupplierRepository;

class SupplierService
{
    public function __construct(
        private readonly SupplierRepository $supplierRepository,
    ) {
    }

    public function getListing(): array
    {
        return $this->supplierRepository->findBy([], ['name' => 'ASC']);
    }
}
