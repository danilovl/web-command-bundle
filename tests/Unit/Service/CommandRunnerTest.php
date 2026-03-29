<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Tests\Unit\Service;

use Danilovl\WebCommandBundle\Entity\{
    Command,
    History
};
use Danilovl\WebCommandBundle\Service\{
    CommandRunner,
    ConfigurationProvider
};
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Throwable;
use RuntimeException;

#[AllowMockObjectsWithoutExpectations]
class CommandRunnerTest extends TestCase
{
    private MockObject&EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
    }

    public function testRunSavesHistoryOnExitCodeError(): void
    {
        $defaultMemoryLimit = '512M';
        $defaultTimeLimit = 600;
        $consolePath = '/non/existent/console';

        $configurationProvider = new ConfigurationProvider(
            apiPrefix: '/api',
            consolePath: $consolePath,
            enableAsync: true,
            defaultTimeout: 300,
            defaultTimeLimit: $defaultTimeLimit,
            defaultMemoryLimit: $defaultMemoryLimit,
            enabledAdminDashboard: true,
            enabledDashboardLiveStatus: true
        );

        $commandRunner = new CommandRunner(
            $configurationProvider,
            $this->entityManager
        );

        $command = new Command;
        $command->setCommand('test:command');
        $command->setSaveHistory(true);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(static function (History $history) use ($command) {
                return $history->getCommand() === $command && $history->getExitCode() !== 0;
            }));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $commandRunner->run($command);
    }

    public function testRunSavesHistoryOnException(): void
    {
        $configurationProvider = new ConfigurationProvider(
            apiPrefix: '/api',
            consolePath: 'php',
            enableAsync: true,
            defaultTimeout: 300,
            defaultTimeLimit: 300,
            defaultMemoryLimit: null,
            enabledAdminDashboard: true,
            enabledDashboardLiveStatus: true
        );

        $commandRunner = new CommandRunner($configurationProvider, $this->entityManager);

        $command = new Command;
        $command->setCommand('test:command');
        $command->setSaveHistory(true);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(static function (History $history) use ($command) {
                return $history->getCommand() === $command && $history->getExitCode() === -1;
            }));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->expectException(Throwable::class);

        $commandRunner->run($command, callback: static function (): void {
            throw new RuntimeException('Test exception');
        });
    }

    public function testRunDoesNotSaveHistoryWhenDisabled(): void
    {
        $configurationProvider = new ConfigurationProvider(
            apiPrefix: '/api',
            consolePath: '/non/existent/console',
            enableAsync: true,
            defaultTimeout: 300,
            defaultTimeLimit: 300,
            defaultMemoryLimit: null,
            enabledAdminDashboard: true,
            enabledDashboardLiveStatus: true
        );

        $commandRunner = new CommandRunner($configurationProvider, $this->entityManager);

        $command = new Command;
        $command->setCommand('test:command');
        $command->setSaveHistory(false);

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        $commandRunner->run($command);
    }
}
