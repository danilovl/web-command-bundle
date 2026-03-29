<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Controller\Api;

use Danilovl\WebCommandBundle\Dto\RunCommandDto;
use Danilovl\WebCommandBundle\Entity\{
    Command,
    Job
};
use Danilovl\WebCommandBundle\Message\RunCommandMessage;
use Danilovl\WebCommandBundle\Service\{
    CommandRunner,
    CommandService,
    ConfigurationProvider
};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\{
    Response,
    JsonResponse
};
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Throwable;

final readonly class CommandController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        private CommandRunner $commandRunner,
        private CommandService $commandService,
        private ConfigurationProvider $configurationProvider
    ) {}

    #[Route(
        path: '',
        name: 'danilovl_web_commands',
        methods: ['GET'])
    ]
    public function list(): JsonResponse
    {
        $filteredCommands = $this->commandService->getPublicCommands();

        return new JsonResponse($filteredCommands);
    }

    #[Route(
        path: '/{id}/run',
        name: 'danilovl_web_command_run',
        requirements: ['id' => Requirement::DIGITS],
        methods: ['POST']
    )]
    public function run(
        Command $command,
        #[MapRequestPayload]
        RunCommandDto $runCommandDto
    ): JsonResponse {
        if (!$command->isActive()) {
            $errorData = ['error' => 'Command not found or disabled'];
            $notFoundStatus = Response::HTTP_NOT_FOUND;

            return new JsonResponse(
                data: $errorData,
                status: $notFoundStatus
            );
        }

        if (!$this->commandService->isGranted($command)) {
            return new JsonResponse(
                data: ['error' => 'Access denied'],
                status: Response::HTTP_FORBIDDEN
            );
        }

        $input = $runCommandDto->input;

        if (!$command->isAllowCustomParameters() && !empty($input)) {
            return new JsonResponse(
                data: ['error' => 'Custom parameters are not allowed for this command'],
                status: Response::HTTP_BAD_REQUEST
            );
        }

        $input = $input !== [] ? $input : $command->getParameters();

        $timeout = $runCommandDto->timeout ?? $this->configurationProvider->defaultTimeout ?? 300;

        $isAsyncCommand = $command->isAsync();

        if ($isAsyncCommand && !$this->configurationProvider->enableAsync) {
            return new JsonResponse(
                data: ['error' => 'Async execution is disabled in the configuration'],
                status: Response::HTTP_BAD_REQUEST
            );
        }

        if ($isAsyncCommand && $this->configurationProvider->enableAsync) {
            $job = new Job($command, $input);
            $this->entityManager->persist($job);
            $this->entityManager->flush();

            $jobId = $job->getId();

            $message = new RunCommandMessage($jobId);
            $this->messageBus->dispatch($message);

            $responseData = [
                'status' => 'queued',
                'jobId' => $jobId
            ];
            $acceptedStatus = Response::HTTP_ACCEPTED;

            return new JsonResponse($responseData, $acceptedStatus);
        }

        try {
            $result = $this->commandRunner->run(
                command: $command,
                input: $input,
                timeout: $timeout
            );
        } catch (Throwable $e) {
            return new JsonResponse(
                data: ['error' => $e->getMessage()],
                status: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new JsonResponse($result);
    }
}
