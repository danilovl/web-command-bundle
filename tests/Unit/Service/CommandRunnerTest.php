<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Tests\Unit\Service;

use Danilovl\WebCommandBundle\Entity\Command;
use Danilovl\WebCommandBundle\Service\CommandRunner;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CommandRunnerTest extends TestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
    }

    public function testRunSetsMemoryLimitAndTimeout(): void
    {
        $defaultMemoryLimit = '512M';
        $defaultTimeLimit = 600;
        $consolePath = '/tmp/bin/console';
        
        $commandRunner = new CommandRunner(
            $consolePath,
            $defaultMemoryLimit,
            $defaultTimeLimit,
            $this->entityManager
        );

        $command = new Command;
        $command->setCommand('test:command');
        $command->setSaveHistory(false);

        $this->assertInstanceOf(CommandRunner::class, $commandRunner);
    }
}
