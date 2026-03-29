<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Entity;

use Danilovl\WebCommandBundle\Repository\CommandRepository;
use DateTime;
use DateTimeImmutable;
use JsonSerializable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'danilovl_web_command')]
#[ORM\Entity(repositoryClass: CommandRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Command implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id')]
    private int $id;

    #[ORM\Column(name: 'name', length: 255, unique: true)]
    private string $name;

    #[ORM\Column(name: 'command', length: 255)]
    private string $command;

    /**
     * @var string[]
     */
    #[ORM\Column(name: 'parameters', type: Types::JSON)]
    private array $parameters = [];

    #[ORM\Column(name: 'allow_custom_parameters')]
    private bool $allowCustomParameters = true;

    #[ORM\Column(name: 'voter_class', length: 255, nullable: true)]
    private ?string $voterClass = null;

    #[ORM\Column(name: 'active')]
    private bool $active = true;

    #[ORM\Column(name: 'async')]
    private bool $async = false;

    #[ORM\Column(name: 'save_output')]
    private bool $saveOutput = false;

    #[ORM\Column(name: 'save_history')]
    private bool $saveHistory = true;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'created_at')]
    protected DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', nullable: true)]
    protected ?DateTime $updatedAt = null;

    #[ORM\PrePersist]
    public function timestampAblePrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable;
    }

    #[ORM\PreUpdate]
    public function timestampAblePreUpdate(): void
    {
        $this->updatedAt = new DateTime;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function setCommand(string $command): self
    {
        $this->command = $command;

        return $this;
    }

    public function isAllowCustomParameters(): bool
    {
        return $this->allowCustomParameters;
    }

    public function setAllowCustomParameters(bool $allowCustomParameters): self
    {
        $this->allowCustomParameters = $allowCustomParameters;

        return $this;
    }

    public function getVoterClass(): ?string
    {
        return $this->voterClass;
    }

    public function setVoterClass(?string $voterClass): self
    {
        $this->voterClass = $voterClass;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

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

    public function isSaveOutput(): bool
    {
        return $this->saveOutput;
    }

    public function setSaveOutput(bool $saveOutput): self
    {
        $this->saveOutput = $saveOutput;

        return $this;
    }

    public function isSaveHistory(): bool
    {
        return $this->saveHistory;
    }

    public function setSaveHistory(bool $saveHistory): self
    {
        $this->saveHistory = $saveHistory;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param string[] $parameters
     */
    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'command' => $this->command,
            'allowCustomParameters' => $this->allowCustomParameters,
            'voterClass' => $this->voterClass,
            'active' => $this->active,
            'async' => $this->async,
            'saveOutput' => $this->saveOutput,
            'saveHistory' => $this->saveHistory,
            'description' => $this->description,
            'parameters' => $this->parameters,
            'createdAt' => $this->createdAt->format(DateTimeImmutable::ATOM),
            'updatedAt' => $this->updatedAt?->format(DateTime::ATOM),
        ];
    }
}
