<?php declare(strict_types=1);

namespace App\Repository;

use App\ApiResource\DownloadRequest;
use App\Entity\Download;
use App\Enum\DownloadState;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Download>
 */
class DownloadRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Download::class);
    }

//    /**
//     * @return Download[] Returns an array of Download objects
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

//    public function findOneBySomeField($value): ?Download
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function findSameDownload(DownloadRequest $downloadRequest): ?Download {
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

    public function getQueuePosition(DateTimeImmutable $createdAt): int {
        $qb = $this->createQueryBuilder('d');
        return $qb
            ->andWhere('d.createdAt < :created_at')
            ->setParameter('created_at', $createdAt)
            ->andWhere($qb->expr()->in('d.state', [
                DownloadState::Waiting->value,
                DownloadState::Running->value,
            ]))
            ->select('COUNT(d.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function getWaitingDownloadsQueuePositions(): array {
        $qb = $this->createQueryBuilder('d2');
        $waitingState = DownloadState::Waiting->value;
        $subquery = $qb
            ->andWhere('d2.createdAt < d1.createdAt')
            ->andWhere($qb->expr()->in('d2.state', [
                DownloadState::Waiting->value,
                DownloadState::Running->value,
            ]))
            ->select('COUNT(d2.id)')
            ->getDQL()
        ;
        $query = $this->createQueryBuilder('d1')
            ->select('d1 download')
            ->addSelect("({$subquery}) queuePosition")
            ->andWhere("d1.state = '{$waitingState}'")
            ->getQuery()
        ;
        return $query->getArrayResult();
    }
}
