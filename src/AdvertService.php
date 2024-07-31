<?php

namespace App;

use App\Entity\Advert;
use Doctrine\ORM\EntityManagerInterface;

class AdvertService
{
    public function save(Advert $advert, EntityManagerInterface $entityManager): Advert
    {
        $entityManager->persist($advert);
        $entityManager->flush();

        return $advert;
    }


}