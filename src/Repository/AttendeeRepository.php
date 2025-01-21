<?php

namespace App\Repository;

use App\Entity\Attendee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Attendee>
 */
class AttendeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Attendee::class);
    }

    public function countTotalAttendeesByEventAndEmail(int $eventId, string $email): int
    {
        return $this->createQueryBuilder('a')
            ->select('count(a.id)')
            ->andWhere('a.event = :eventId')
            ->andWhere('a.email = :email')
            ->setParameters(new ArrayCollection([
                new Parameter('eventId', $eventId),
                new Parameter('email', $email)
            ]))
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
