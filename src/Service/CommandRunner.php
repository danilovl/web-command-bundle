<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Service;

use Danilovl\WebCommandBundle\Entity\{
    Command,
    History
};
use Danilovl\WebCommandBundle\Event\{
    CommandStartEvent,
    CommandEndEvent
};
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Process\{
    Process,
    PhpExecutableFinder
};
use Throwable;

readonly class CommandRunner
{
    public function __construct(
        private ConfigurationProvider $configurationProvider,
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $eventDispatcher,
        private Security $security
    ) {}

    /**
     * @param string[] $input
     * @param callable|null $callback
     * @return array{output: string, exitCode: int|null, duration: string}
     */
    public function run(
        Command $command,
        array $input = [],
        ?int $timeout = null,
        ?callable $callback = null,
        bool $isAsync = false
    ): array {
        $metaInfo = $this->getMetaInfo();

        $event = new CommandStartEvent(
            command: $command,
            input: $input,
            isAsync: $isAsync
        );
        $event->setMetaInfo($metaInfo);
        $this->eventDispatcher->dispatch($event);

        if (!$event->shouldContinue()) {
            $reason = $event->getReason() ?? 'Command execution cancelled by event.';
            $duration = 0.000_0;
            $exitCode = -1;

            $this->saveHistory(
                command: $command,
                isAsync: $isAsync,
                duration: $duration,
                exitCode: $exitCode,
                output: $reason,
                metaInfo: $event->getMetaInfo()
            );

            return [
                'output' => $reason,
                'exitCode' => $exitCode,
                'duration' => '0.0000',
            ];
        }

        $timeout ??= $this->configurationProvider->defaultTimeLimit ?? 300;

        $phpBinary = (new PhpExecutableFinder)->find(false);
        if ($phpBinary === false) {
            throw new RuntimeException('Unable to find PHP binary.');
        }

        $commandArguments = [
            $phpBinary
        ];

        if ($this->configurationProvider->defaultMemoryLimit !== null) {
            $commandArguments[] = '-d';
            $commandArguments[] = 'memory_limit=' . $this->configurationProvider->defaultMemoryLimit;
        }

        $commandArguments[] = $this->configurationProvider->consolePath;
        $commandArguments[] = $command->getCommand();

        foreach ($input as $item) {
            $commandArguments[] = $item;
        }

        $process = new Process($commandArguments);

        $processTimeout = $timeout > 0 ? (float) $timeout : null;
        $process->setTimeout($processTimeout);

        $startTime = hrtime(true);

        try {
            $process->run($callback);
            $endTime = hrtime(true);
            $durationCalculation = ($endTime - $startTime) / 1e9;
            $duration = $durationCalculation;

            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();
            $fullOutput = $output . $errorOutput;
            $exitCode = (int) $process->getExitCode();

            $this->saveHistory(
                command: $command,
                isAsync: $isAsync,
                duration: $duration,
                exitCode: $exitCode,
                output: $fullOutput,
                metaInfo: $event->getMetaInfo()
            );

            $endEvent = new CommandEndEvent(
                command: $command,
                input: $input,
                isAsync: $isAsync,
                exitCode: $exitCode,
                duration: $duration,
                output: $fullOutput
            );
            $this->eventDispatcher->dispatch($endEvent);

            return [
                'output' => $fullOutput,
                'exitCode' => $exitCode,
                'duration' => number_format($duration, 4, '.', ''),
            ];
        } catch (Throwable $e) {
            $endTime = hrtime(true);
            $durationCalculation = ($endTime - $startTime) / 1e9;
            $duration = $durationCalculation;
            $exitCode = -1;
            $errorOutput = $e->getMessage();

            $this->saveHistory(
                command: $command,
                isAsync: $isAsync,
                duration: $duration,
                exitCode: $exitCode,
                output: $errorOutput,
                metaInfo: $event->getMetaInfo()
            );

            $endEvent = new CommandEndEvent(
                command: $command,
                input: $input,
                isAsync: $isAsync,
                exitCode: $exitCode,
                duration: $duration,
                output: $errorOutput
            );
            $this->eventDispatcher->dispatch($endEvent);

            throw $e;
        }
    }

    /**
     * @param array<string, mixed>|null $metaInfo
     */
    private function saveHistory(
        Command $command,
        bool $isAsync,
        float $duration,
        int $exitCode,
        string $output,
        ?array $metaInfo
    ): void {
        if (!$command->isSaveHistory()) {
            return;
        }

        $history = new History(
            command: $command,
            async: $isAsync,
            duration: $duration,
            exitCode: $exitCode
        );
        $history->setMetaInfo($metaInfo);

        if ($command->isSaveOutput()) {
            $history->setOutput($output);
        }

        if ($exitCode !== 0) {
            $history->setErrorMessage($output);
        }

        $this->entityManager->persist($history);
        $this->entityManager->flush();
    }

    /**
     * @return array<string, mixed>
     */
    private function getMetaInfo(): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof UserInterface) {
            return ['userIdentifier' => 'anonymous'];
        }

        $metaInfo = [
            'userIdentifier' => $user->getUserIdentifier()
        ];

        if (method_exists($user, 'getId')) {
            $metaInfo['userId'] = $user->getId();
        }

        return $metaInfo;
    }
}
