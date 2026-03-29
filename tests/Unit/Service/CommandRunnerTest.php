<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Tests\Unit\Service;

use Danilovl\WebCommandBundle\Entity\{
    Command,
    History
};
use Danilovl\WebCommandBundle\Event\CommandStartEvent;
use Danilovl\WebCommandBundle\Service\{
    CommandRunner,
    ConfigurationProvider
};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Throwable;
use RuntimeException;

#[AllowMockObjectsWithoutExpectations]
class CommandRunnerTest extends TestCase
{
    private MockObject&EntityManagerInterface $entityManager;

    private MockObject&EventDispatcherInterface $eventDispatcher;

    private MockObject&Security $security;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->security = $this->createMock(Security::class);
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
            $this->entityManager,
            $this->eventDispatcher,
            $this->security
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

        $commandRunner = new CommandRunner($configurationProvider, $this->entityManager, $this->eventDispatcher, $this->security);

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

        $commandRunner = new CommandRunner($configurationProvider, $this->entityManager, $this->eventDispatcher, $this->security);

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

    public function testRunCancelledByEventWithReason(): void
    {
        $configurationProvider = new ConfigurationProvider(
            apiPrefix: '/api',
            consolePath: 'bin/console',
            enableAsync: true,
            defaultTimeout: 300,
            defaultTimeLimit: 300,
            defaultMemoryLimit: null,
            enabledAdminDashboard: true,
            enabledDashboardLiveStatus: true
        );

        $commandRunner = new CommandRunner($configurationProvider, $this->entityManager, $this->eventDispatcher, $this->security);

        $command = new Command;
        $command->setCommand('test:command');

        $reason = 'Testing reason';

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static function (CommandStartEvent $event) use ($reason) {
                $event->setShouldContinue(false);
                $event->setReason($reason);

                return true;
            }))
            ->willReturnCallback(static function (CommandStartEvent $event): CommandStartEvent {
                return $event;
            });

        $result = $commandRunner->run($command);

        $this->assertEquals($reason, $result['output']);
        $this->assertEquals(-1, $result['exitCode']);
        $this->assertEquals('0.0000', $result['duration']);
    }

    public function testRunCancelledByEventSavesHistory(): void
    {
        $configurationProvider = new ConfigurationProvider(
            apiPrefix: '/api',
            consolePath: 'bin/console',
            enableAsync: true,
            defaultTimeout: 300,
            defaultTimeLimit: 300,
            defaultMemoryLimit: null,
            enabledAdminDashboard: true,
            enabledDashboardLiveStatus: true
        );

        $commandRunner = new CommandRunner($configurationProvider, $this->entityManager, $this->eventDispatcher, $this->security);

        $command = new Command;
        $command->setCommand('test:command');
        $command->setSaveHistory(true);

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(CommandStartEvent::class))
            ->willReturnCallback(static function (CommandStartEvent $event): CommandStartEvent {
                $event->setShouldContinue(false);

                return $event;
            });

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(History::class));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $commandRunner->run($command);
    }
}
