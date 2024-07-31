<?php

namespace App\Repository;

use App\Entity\Advert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Advert>
 */
class AdvertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Advert::class);
    }

    public function search(float|bool|int|string|null $title, float|bool|int|string|null $priceMin, float|bool|int|string|null $priceMax)
    {
        $queryBuilder = $this->createQueryBuilder('q');

        if ($title) {
            $queryBuilder
                ->andWhere('q.title LIKE :title')
                ->setParameter('title', '%' . $title . '%');
        }

        if ($priceMin) {
            $queryBuilder
                ->andWhere('q.price >= :priceMin')
                ->setParameter('priceMin', $priceMin);
        }

        if ($priceMax) {
            $queryBuilder
                ->andWhere('q.price <= :priceMax')
                ->setParameter('priceMax', $priceMax);
        }

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }
}
