<?php

namespace App\Repository;

use App\Entity\ImagePost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ImagePost|null find($id, $lockMode = null, $lockVersion = null)
 * @method ImagePost|null findOneBy(array $criteria, array $orderBy = null)
 * @method ImagePost[]    findAll()
 * @method ImagePost[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImagePostRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ImagePost::class);
    }

    // /**
    //  * @return ImagePost[] Returns an array of ImagePost objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ImagePost
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
