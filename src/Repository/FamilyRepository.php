<?php

namespace App\Repository;

use App\Entity\Family;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Family|null find($id, $lockMode = null, $lockVersion = null)
 * @method Family|null findOneBy(array $criteria, array $orderBy = null)
 * @method Family[]    findAll()
 * @method Family[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FamilyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Family::class);
    }

    /**
     * @return Family[] Returns an array of Family objects
     */
    public function findByUser(int $id)
    {
        $query = $this->createQueryBuilder('f')
            ->innerJoin('f.members', 'm')
            ->andWhere('m.id = :id')
            ->setParameter('id', $id, Types::INTEGER)
            ->orderBy('f.name', 'ASC')
            ->getQuery();
        return $query->getResult();
    }

    /**
     * @return Family[] Returns an array of Family objects
     */
    public function findByCodeAndUser(string $code, int $id)
    {
        $query = $this->createQueryBuilder('f')
            ->select('f.name')
            ->innerJoin('f.members', 'm')
            ->andWhere('f.code = :code')
            ->andWhere('m.id = :id')
            ->setParameter('code', $code, Types::STRING)
            ->setParameter('id', $id, Types::INTEGER)
            ->orderBy('f.name', 'ASC')
            ->getQuery();
        return $query->getResult();
    }

    // /**
    //  * @return Family[] Returns an array of Family objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    public function findOneByCode($value): ?Family
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.code = :val')
            ->setParameter('val', $value, Types::STRING)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
