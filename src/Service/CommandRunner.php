<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Service;

use Danilovl\WebCommandBundle\Entity\{
    Command,
    History
};
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\{
    Process,
    PhpExecutableFinder
};
use Throwable;

readonly class CommandRunner
{
    public function __construct(
        #[Autowire('%web_command.console_path%')]
        private string $consolePath,
        #[Autowire('%web_command.default_memory_limit%')]
        private ?string $defaultMemoryLimit,
        #[Autowire('%web_command.default_time_limit%')]
        private ?int $defaultTimeLimit,
        private EntityManagerInterface $entityManager
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
        $timeout ??= $this->defaultTimeLimit ?? 300;

        $phpBinary = (new PhpExecutableFinder)->find(false);
        if ($phpBinary === false) {
            throw new RuntimeException('Unable to find PHP binary.');
        }

        $commandArguments = [
            $phpBinary
        ];

        if ($this->defaultMemoryLimit !== null) {
            $commandArguments[] = '-d';
            $commandArguments[] = 'memory_limit=' . $this->defaultMemoryLimit;
        }

        $commandArguments[] = $this->consolePath;
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

            if ($command->isSaveHistory()) {
                $history = new History($command, $isAsync, $duration, $exitCode);
                if ($command->isSaveOutput()) {
                    $history->setOutput($fullOutput);
                }

                if ($exitCode !== 0) {
                    $errorMessage = $errorOutput ?: $output;
                    $history->setErrorMessage($errorMessage);
                }

                $this->entityManager->persist($history);
                $this->entityManager->flush();
            }

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

            if ($command->isSaveHistory()) {
                $history = new History($command, $isAsync, $duration, $exitCode);
                $history->setErrorMessage($errorOutput);
                $this->entityManager->persist($history);
                $this->entityManager->flush();
            }

            throw $e;
        }
    }
}
