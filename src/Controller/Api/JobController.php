<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Controller\Api;

use Danilovl\WebCommandBundle\Entity\Job;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class JobController
{
    #[Route(
        path: '/jobs/{id}/status',
        name: 'danilovl_web_command_job_status',
        requirements: ['id' => '\d+'],
        methods: ['GET'])
    ]
    public function status(Job $job): JsonResponse
    {
        return new JsonResponse([
            'status' => $job->getStatus()
        ]);
    }
}
