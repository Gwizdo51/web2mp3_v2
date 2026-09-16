<?php

namespace App\Repository;

use App\ApiResource\DownloadRequest;
use App\Entity\DownloadView;
use App\Enum\DownloadState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DownloadView>
 */
class DownloadViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DownloadView::class);
    }

//    /**
//     * @return DownloadView[] Returns an array of DownloadView objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('d.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?DownloadView
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function findSameDownload(DownloadRequest $downloadRequest): ?DownloadView {
        $qb = $this->createQueryBuilder('d');
        return $qb
            ->andWhere('d.link = :link')
            ->setParameter('link', $downloadRequest->link)
            ->andWhere('d.format = :format')
            ->setParameter('format', $downloadRequest->format)
            ->andWhere('d.quality = :quality')
            ->setParameter('quality', $downloadRequest->quality)
            ->andWhere($qb->expr()->in('d.state', [
                DownloadState::Waiting->value,
                DownloadState::Running->value,
                DownloadState::Succeeded->value,
            ]))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
