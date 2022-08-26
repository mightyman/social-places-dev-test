<?php

namespace App\Traits\Classes;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\Event\LifecycleEventArgs;

trait HasEntityManager
{
    private ?EntityManagerInterface $_entityManager;

    public function getEntityManager(): ?EntityManagerInterface {
        return $this->_entityManager;
    }


    public function setEntityManager(?EntityManagerInterface $entityManager): void {
        $this->_entityManager = $entityManager;
    }

    /**
     * @ORM\PostLoad
     * @ORM\PostPersist
     * @param LifecycleEventArgs $args
     */
    public function fetchEntityManager(LifecycleEventArgs $args): void {
        $this->setEntityManager($args->getEntityManager());
    }

}
