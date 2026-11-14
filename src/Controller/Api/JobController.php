<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Controller\Api;

use Danilovl\WebCommandBundle\Entity\Job;
use Danilovl\WebCommandBundle\Repository\JobRepository;
use Danilovl\WebCommandBundle\Service\CommandService;
use Symfony\Component\HttpFoundation\{
    Response,
    JsonResponse
};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final readonly class JobController
{
    public function __construct(
        private JobRepository $jobRepository,
        private CommandService $commandService
    ) {}

    #[Route(
        path: '/jobs/active',
        name: 'danilovl_web_command_jobs_active',
        methods: ['GET']
    )]
    public function active(): JsonResponse
    {
        $activeJobs = array_values(array_filter(
            $this->jobRepository->getActiveJobs(),
            fn (Job $job): bool => $this->commandService->isGranted($job->getCommand())
        ));

        return new JsonResponse($activeJobs);
    }

    #[Route(
        path: '/jobs/{id}/status',
        name: 'danilovl_web_command_job_status',
        requirements: ['id' => Requirement::DIGITS],
        methods: ['GET']
    )]
    public function status(Job $job): JsonResponse
    {
        if (!$this->commandService->isGranted($job->getCommand())) {
            return new JsonResponse(
                data: ['error' => 'Access denied'],
                status: Response::HTTP_FORBIDDEN
            );
        }

        return new JsonResponse([
            'status' => $job->getStatus()
        ]);
    }
}
