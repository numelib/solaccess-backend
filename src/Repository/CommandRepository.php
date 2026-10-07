<?php

namespace App\Repository;

use App\Config\CommandStatus;
use App\Entity\AccessDevice;
use App\Entity\Command;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Command>
 */
class CommandRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Command::class);
    }

    public function findLastInProgressByDevice(
        AccessDevice $device,
    ): Command|null {
        /** @var Command|null */
        $command = $this->createQueryBuilder("c")
            ->where("c.accessDevice = :device")
            ->andWhere("c.processedAt IS NULL")
            ->orderBy("c.issuedAt", "DESC")
            ->setParameter("device", $device)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($command === null) {
            return $command;
        }

        return $command->getStatus() === CommandStatus::IN_PROGRESS
            ? $command
            : null;
    }

    public function countIssuedAfter(\DateTimeInterface $limitDate)
    {
        return $this->createQueryBuilder("command")
            ->select("COUNT(command.id)")
            ->where("command.issuedAt >= :limitDate")
            ->setParameter("limitDate", $limitDate)
            ->getQuery()
            ->getSingleScalarResult();
    }
    //    /**
    //     * @return Command[] Returns an array of Command objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Command
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
