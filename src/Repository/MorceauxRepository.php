<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Morceaux;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Morceaux>
 */
class MorceauxRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Morceaux::class);
    }

    public function save(Morceaux $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Morceaux $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Catalogue des morceaux, éventuellement filtré par une recherche libre
     * (titre, artiste ou genre).
     */
    public function searchQuery(?string $term = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.genreid', 'g')
            ->addSelect('g')
            ->orderBy('m.id', 'DESC');

        $term = trim((string) $term);
        if ('' !== $term) {
            $qb->andWhere('m.nomMorceaux LIKE :term OR m.artiste LIKE :term OR g.nomGenre LIKE :term')
                ->setParameter('term', '%'.$term.'%');
        }

        return $qb;
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Morceaux>
     */
    public function findLatest(int $limit = 5): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
