<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Repository;

use Danilovl\WebCommandBundle\Entity\Job;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Job>
 */
class JobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Job::class);
    }

    /**
     * @return Job[]
     */
    public function getActiveJobs(): array
    {
        /** @var Job[] $result */
        $result = $this->createQueryBuilder('j')
            ->where('j.status IN (:statuses)')
            ->setParameter('statuses', ['queued', 'running'])
            ->orderBy('j.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
