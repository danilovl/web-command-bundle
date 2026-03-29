<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Entity;

use Danilovl\WebCommandBundle\Repository\JobRepository;
use DateTimeImmutable;
use JsonSerializable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'danilovl_web_command_job')]
#[ORM\Entity(repositoryClass: JobRepository::class)]
class Job implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Command::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Command $command;

    /**
     * @var string[]
     */
    #[ORM\Column(name: 'input', type: Types::JSON)]
    private array $input;

    #[ORM\Column(name: 'status', length: 20)]
    private string $status = 'queued';

    #[ORM\Column(name: 'created_at')]
    private DateTimeImmutable $createdAt;

    /**
     * @param string[] $input
     */
    public function __construct(Command $command, array $input = [])
    {
        $this->command = $command;
        $this->input = $input;
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

    public function getCommandName(): string
    {
        return $this->command->getName();
    }

    /**
     * @return string[]
     */
    public function getInput(): array
    {
        return $this->input;
    }

    /**
     * @param string[] $input
     */
    public function setInput(array $input): self
    {
        $this->input = $input;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

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

    public function isQueued(): bool
    {
        return $this->status === 'queued';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $createdAt = $this->createdAt->format(DateTimeImmutable::ATOM);

        return [
            'id' => $this->getId(),
            'commandId' => $this->command->getId(),
            'commandName' => $this->getCommandName(),
            'status' => $this->status,
            'input' => $this->input,
            'createdAt' => $createdAt,
        ];
    }
}
