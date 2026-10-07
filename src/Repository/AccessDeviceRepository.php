<?php

namespace App\Repository;

use App\Entity\AccessDevice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccessDevice>
 */
class AccessDeviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessDevice::class);
    }

    public function findOneEnabledByTokenPublicId(
        string $tokenPublicId,
    ): ?AccessDevice {
        return $this->createQueryBuilder("d")
            ->andWhere("d.tokenPublicId = :tokenPublicId")
            ->andWhere("d.enabled = true")
            ->setParameter("tokenPublicId", $tokenPublicId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Returns a list of access device that have no logs that occured after $limitDate.
     *
     * @return AccessDevice[]
     */
    public function findByMissingSince(\Datetime $limitDate): array
    {
        return $this->createQueryBuilder("access_device")
            ->select("access_device")
            ->leftJoin("access_device.lock", "access_device_lock")
            ->leftJoin("access_device_lock.logs", "access_device_logs")
            ->groupBy("access_device.id")
            ->having("MAX(access_device_logs.occurredAt) < :limit_date")
            ->setParameter("limit_date", $limitDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AccessDevice[]
     */
    public function findLockStates(array $ids, int $limit = 20): array
    {
        return $this->createQueryBuilder("access_device")
            ->select("partial access_device.{id, lockState}")
            ->setMaxResults($limit)
            ->where("access_device.id IN (:ids)")
            ->setParameter("ids", $ids)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return AccessDevice[] Returns an array of AccessDevice objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?AccessDevice
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
