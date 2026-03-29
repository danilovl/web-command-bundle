<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Event;

use Danilovl\WebCommandBundle\Entity\Command;
use Symfony\Contracts\EventDispatcher\Event;

class CommandStartEvent extends Event
{
    private bool $shouldContinue = true;

    private ?string $reason = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $metaInfo = null;

    /**
     * @param string[] $input
     */
    public function __construct(
        private readonly Command $command,
        private readonly array $input,
        private readonly bool $isAsync
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

    /**
     * @return array<string, mixed>|null
     */
    public function getMetaInfo(): ?array
    {
        return $this->metaInfo;
    }

    /**
     * @param array<string, mixed>|null $metaInfo
     */
    public function setMetaInfo(?array $metaInfo): void
    {
        $this->metaInfo = $metaInfo;
    }

    public function shouldContinue(): bool
    {
        return $this->shouldContinue;
    }

    public function setShouldContinue(bool $shouldContinue): void
    {
        $this->shouldContinue = $shouldContinue;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): void
    {
        $this->reason = $reason;
    }
}
