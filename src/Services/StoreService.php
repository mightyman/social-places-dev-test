<?php

namespace App\Services;

use App\Entity\Brand;
use Doctrine\ORM\EntityManagerInterface;

class StoreService
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {
    }

    public function discoverBrandByName(string $brandName): Brand {
        $brand = $this->entityManager->getRepository(Brand::class)->findOneBy(['name' => $brandName]);

        if ($brand === null) {
            $brand = new Brand();
            $brand->setName($brandName);
            $this->entityManager->persist($brand);
            $this->entityManager->flush();
        }

        return $brand;
    }
}
