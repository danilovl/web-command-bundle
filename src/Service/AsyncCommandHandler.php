<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Service;

use Danilovl\WebCommandBundle\Entity\Job;
use Danilovl\WebCommandBundle\Message\RunCommandMessage;
use Danilovl\WebCommandBundle\Repository\JobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler]
final readonly class AsyncCommandHandler
{
    public function __construct(
        private CommandRunner $commandRunner,
        private EntityManagerInterface $entityManager,
        private JobRepository $jobRepository
    ) {}

    public function __invoke(RunCommandMessage $message): void
    {
        $jobId = $message->jobId;
        /** @var Job|null $job */
        $job = $this->jobRepository->find($jobId);
        $isQueued = $job?->isQueued() ?? false;

        if (!$job || !$isQueued) {
            return;
        }

        try {
            $job->setStatus('running');
            $this->entityManager->flush();

            $command = $job->getCommand();
            $input = $job->getInput();

            $this->commandRunner->run(
                command: $command,
                input: $input,
                isAsync: true
            );

            $job->setStatus('completed');
        } catch (Throwable) {
            $job->setStatus('failed');
        }

        $this->entityManager->flush();
    }
}
