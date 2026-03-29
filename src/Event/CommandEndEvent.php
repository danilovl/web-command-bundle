<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Event;

use Danilovl\WebCommandBundle\Entity\Command;
use Symfony\Contracts\EventDispatcher\Event;

class CommandEndEvent extends Event
{
    /**
     * @param string[] $input
     */
    public function __construct(
        private readonly Command $command,
        private readonly array $input,
        private readonly bool $isAsync,
        private readonly int $exitCode,
        private readonly float $duration,
        private readonly string $output
    ) {}

    public function getCommand(): Command
    {
        return $this->command;
    }

    /**
     * @return string[]
     */
    public function getInput(): array
    {
        return $this->input;
    }

    public function isAsync(): bool
    {
        return $this->isAsync;
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function getDuration(): float
    {
        return $this->duration;
    }

    public function getOutput(): string
    {
        return $this->output;
    }
}
