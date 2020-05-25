<?php

namespace App\Repository;

use App\Entity\CommentPublication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CommentPublication|null find($id, $lockMode = null, $lockVersion = null)
 * @method CommentPublication|null findOneBy(array $criteria, array $orderBy = null)
 * @method CommentPublication[]    findAll()
 * @method CommentPublication[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentPublicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommentPublication::class);
    }

    // /**
    //  * @return CommentPublicationController[] Returns an array of CommentPublicationController objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CommentPublicationController
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
