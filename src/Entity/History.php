<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Entity;

use Danilovl\WebCommandBundle\Repository\HistoryRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

#[ORM\Table(name: 'danilovl_web_command_history')]
#[ORM\Entity(repositoryClass: HistoryRepository::class)]
class History implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Command::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Command $command;

    #[ORM\Column(name: 'async')]
    private bool $async;

    #[ORM\Column(name: 'duration')]
    private float $duration;

    #[ORM\Column(name: 'exit_code')]
    private int $exitCode;

    #[ORM\Column(name: 'error_message', type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(name: 'output', type: Types::TEXT, nullable: true)]
    private ?string $output = null;

    #[ORM\Column(name: 'created_at')]
    private DateTimeImmutable $createdAt;

    public function __construct(Command $command, bool $async, float $duration, int $exitCode)
    {
        $this->command = $command;
        $this->async = $async;
        $this->duration = $duration;
        $this->exitCode = $exitCode;
        $this->createdAt = new DateTimeImmutable;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCommand(): Command
    {
        return $this->command;
    }

    public function setCommand(Command $command): self
    {
        $this->command = $command;

        return $this;
    }

    public function isAsync(): bool
    {
        return $this->async;
    }

    public function setAsync(bool $async): self
    {
        $this->async = $async;

        return $this;
    }

    public function getDuration(): float
    {
        return $this->duration;
    }

    public function setDuration(float $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function setExitCode(int $exitCode): self
    {
        $this->exitCode = $exitCode;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getOutput(): ?string
    {
        return $this->output;
    }

    public function setOutput(?string $output): self
    {
        $this->output = $output;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'command' => $this->command,
            'async' => $this->async,
            'duration' => $this->duration,
            'exitCode' => $this->exitCode,
            'errorMessage' => $this->errorMessage,
            'output' => $this->output,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s')
        ];
    }
}
