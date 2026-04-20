<?php

namespace App\Repository;

use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Item>
 */
class ItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

       public function findPublishedAndClosedItems($id = null): array
       {
            $result = $this->createQueryBuilder('i');

            $result->Where('i.status = :val1')
            ->orWhere('i.status = :val2')
            ->setParameter('val1', Item::PUBLISHED, ParameterType::STRING)
            ->setParameter('val2', Item::CLOSED, ParameterType::STRING)
            ;


            if($id) {
                $result->join('i.categories', 'c')
                ->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $id, ParameterType::INTEGER);
            }

            return $result->getQuery()
            ->getResult()
            ;
       }

        public function findWithOffers($id): ?Item
        {
            return $this->createQueryBuilder('i')
            ->leftJoin('i.offers', 'o')
            ->addSelect('o')
            ->leftJoin('o.user', 'u')
            ->addSelect('u')
            ->andWhere('i.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
            ;
        }

       public function findAllWithOffers($id = null): array
       {
            $result = $this->createQueryBuilder('i')
            ->leftJoin('i.offers', 'o')
            ->addSelect('o')
            ->leftJoin('o.user', 'u')
            ->addSelect('u')
            ;

            if($id) {
                $result->join('i.categories', 'c')
                ->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $id, ParameterType::INTEGER);
            }

            return $result->getQuery()
            ->getResult()
            ;
       }
}
