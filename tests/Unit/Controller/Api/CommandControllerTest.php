<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Tests\Unit\Controller\Api;

use Danilovl\WebCommandBundle\Controller\Api\CommandController;
use Danilovl\WebCommandBundle\Dto\RunCommandDto;
use Danilovl\WebCommandBundle\Entity\Command;
use Danilovl\WebCommandBundle\Service\{
    CommandRunner,
    CommandService,
    ConfigurationProvider
};
use Doctrine\ORM\EntityManagerInterface;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\{
    AllowMockObjectsWithoutExpectations,
    DataProvider
};
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\MessageBusInterface;

#[AllowMockObjectsWithoutExpectations]
class CommandControllerTest extends TestCase
{
    private MockObject&EntityManagerInterface $entityManager;

    private MockObject&MessageBusInterface $messageBus;

    private MockObject&CommandRunner $commandRunner;

    private MockObject&CommandService $commandService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->commandRunner = $this->createMock(CommandRunner::class);
        $this->commandService = $this->createMock(CommandService::class);
    }

    private function getConfigurationProvider(
        bool $enableAsync = true,
        int $defaultTimeout = 300
    ): ConfigurationProvider {
        return new ConfigurationProvider(
            apiPrefix: '/api',
            consolePath: 'bin/console',
            enableAsync: $enableAsync,
            defaultTimeout: $defaultTimeout,
            defaultTimeLimit: 300,
            defaultMemoryLimit: '512M',
            enabledAdminDashboard: true,
            enabledDashboardLiveStatus: true
        );
    }

    public function testRunAccessDenied(): void
    {
        $command = new Command;
        $command->setId(1);
        $command->setName('test');
        $command->setVoterClass('TEST_VOTER');

        $this->commandService
            ->method('isGranted')
            ->with($command)
            ->willReturn(false);

        $controller = new CommandController(
            entityManager: $this->entityManager,
            messageBus: $this->messageBus,
            commandRunner: $this->commandRunner,
            commandService: $this->commandService,
            configurationProvider: $this->getConfigurationProvider()
        );

        $runCommandDto = new RunCommandDto([]);
        $response = $controller->run($command, $runCommandDto);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringContainsString('Access denied', (string) $response->getContent());
    }

    public function testRunCustomParametersNotAllowed(): void
    {
        $command = new Command;
        $command->setId(1);
        $command->setName('test');
        $command->setCommand('test:cmd');
        $command->setAllowCustomParameters(false);

        $this->commandService->method('isGranted')->willReturn(true);

        $controller = new CommandController(
            entityManager: $this->entityManager,
            messageBus: $this->messageBus,
            commandRunner: $this->commandRunner,
            commandService: $this->commandService,
            configurationProvider: $this->getConfigurationProvider()
        );

        $runCommandDto = new RunCommandDto(['--extra']);
        $response = $controller->run($command, $runCommandDto);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('Custom parameters are not allowed', (string) $response->getContent());
    }

    public function testRunAllowCustomParametersFalseFails(): void
    {
        $command = new Command;
        $command->setId(1);
        $command->setName('test');
        $command->setCommand('actual:command');
        $command->setParameters(['--default']);
        $command->setAllowCustomParameters(false);
        $command->setAsync(false);

        $this->commandService
            ->method('isGranted')
            ->willReturn(true);

        $controller = new CommandController(
            entityManager: $this->entityManager,
            messageBus: $this->messageBus,
            commandRunner: $this->commandRunner,
            commandService: $this->commandService,
            configurationProvider: $this->getConfigurationProvider()
        );

        $runCommandDto = new RunCommandDto(['--default']);
        $response = $controller->run($command, $runCommandDto);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testRunAllowCustomParametersFalseSuccess(): void
    {
        $command = new Command;
        $command->setId(1);
        $command->setName('test');
        $command->setCommand('actual:command');
        $command->setParameters(['--default']);
        $command->setAllowCustomParameters(false);
        $command->setAsync(false);

        $this->commandService
            ->method('isGranted')
            ->willReturn(true);

        $this->commandRunner
            ->method('run')
            ->with($command, ['--default'], 300)
            ->willReturn(['output' => 'ok', 'exitCode' => 0, 'duration' => '1.0000']);

        $controller = new CommandController(
            entityManager: $this->entityManager,
            messageBus: $this->messageBus,
            commandRunner: $this->commandRunner,
            commandService: $this->commandService,
            configurationProvider: $this->getConfigurationProvider()
        );

        $runCommandDto = new RunCommandDto([]);
        $response = $controller->run($command, $runCommandDto);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testRunSuccessSynchronous(): void
    {
        $command = new Command;
        $command->setId(1);
        $command->setName('test');
        $command->setCommand('actual:command');
        $command->setParameters(['--default']);
        $command->setAsync(false);

        $this->commandService
            ->method('isGranted')
            ->willReturn(true);

        $this->commandRunner
            ->method('run')
            ->with($command, ['--default'], 300)
            ->willReturn(['output' => 'ok', 'exitCode' => 0, 'duration' => '1.0000']);

        $controller = new CommandController(
            entityManager: $this->entityManager,
            messageBus: $this->messageBus,
            commandRunner: $this->commandRunner,
            commandService: $this->commandService,
            configurationProvider: $this->getConfigurationProvider()
        );

        $runCommandDto = new RunCommandDto([]);
        $response = $controller->run($command, $runCommandDto);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('ok', (string) $response->getContent());
    }

    public function testRunSuccessSynchronousWithCustomTimeout(): void
    {
        $command = new Command;
        $command->setId(1);
        $command->setName('test');
        $command->setCommand('actual:command');
        $command->setParameters([]);
        $command->setAsync(false);

        $this->commandService
            ->method('isGranted')
            ->willReturn(true);

        $this->commandRunner
            ->method('run')
            ->with($command, [], 120)
            ->willReturn(['output' => 'ok', 'exitCode' => 0, 'duration' => '1.0000']);

        $controller = new CommandController(
            entityManager: $this->entityManager,
            messageBus: $this->messageBus,
            commandRunner: $this->commandRunner,
            commandService: $this->commandService,
            configurationProvider: $this->getConfigurationProvider()
        );

        $runCommandDto = new RunCommandDto([], 120);
        $response = $controller->run($command, $runCommandDto);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testRunAsyncCommandWithAsyncDisabled(): void
    {
        $command = new Command;
        $command->setId(1);
        $command->setName('test');
        $command->setAsync(true);

        $this->commandService->method('isGranted')->willReturn(true);

        $controller = new CommandController(
            entityManager: $this->entityManager,
            messageBus: $this->messageBus,
            commandRunner: $this->commandRunner,
            commandService: $this->commandService,
            configurationProvider: $this->getConfigurationProvider(enableAsync: false)
        );

        $runCommandDto = new RunCommandDto([]);
        $response = $controller->run($command, $runCommandDto);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('Async execution is disabled', (string) $response->getContent());
    }

    public function testListFilteringByVoter(): void
    {
        $command1 = new Command;
        $command1->setId(1);
        $command1->setName('c1');
        $command1->setCommand('cmd1');
        $command1->timestampAblePrePersist();

        $command2 = new Command;
        $command2->setId(2);
        $command2->setName('c2');
        $command2->setCommand('cmd2');
        $command2->timestampAblePrePersist();

        $this->commandService
            ->method('getPublicCommands')
            ->willReturn([$command1, $command2]);

        $controller = new CommandController(
            entityManager: $this->entityManager,
            messageBus: $this->messageBus,
            commandRunner: $this->commandRunner,
            commandService: $this->commandService,
            configurationProvider: $this->getConfigurationProvider()
        );

        $response = $controller->list();
        /** @var array<array{name: string}> $data */
        $data = json_decode((string) $response->getContent(), true);

        $this->assertCount(2, $data);
        $this->assertEquals('c1', $data[0]['name']);
        $this->assertEquals('c2', $data[1]['name']);
    }

    #[DataProvider('timeoutProvider')]
    public function testRunTimeoutIsLimited(?int $requestedTimeout, int $expectedTimeout): void
    {
        $command = new Command;
        $command->setId(1);
        $command->setName('test');
        $command->setCommand('actual:command');
        $command->setAsync(false);

        $this->commandService
            ->method('isGranted')
            ->willReturn(true);

        $this->commandRunner
            ->expects($this->once())
            ->method('run')
            ->with($command, [], $expectedTimeout)
            ->willReturn(['output' => 'ok', 'exitCode' => 0, 'duration' => '1.0000']);

        $controller = new CommandController(
            entityManager: $this->entityManager,
            messageBus: $this->messageBus,
            commandRunner: $this->commandRunner,
            commandService: $this->commandService,
            configurationProvider: $this->getConfigurationProvider(defaultTimeout: 300)
        );

        $response = $controller->run($command, new RunCommandDto([], $requestedTimeout));

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @return iterable<string, array{int|null, int}>
     */
    public static function timeoutProvider(): iterable
    {
        yield 'not requested' => [null, 300];
        yield 'within limit' => [120, 120];
        yield 'above limit' => [100_000, 300];
        yield 'zero' => [0, 1];
        yield 'negative' => [-10, 1];
    }

    public function testRunTimeoutUsesFallbackWithoutConfiguration(): void
    {
        $command = new Command;
        $command->setId(1);
        $command->setName('test');
        $command->setCommand('actual:command');
        $command->setAsync(false);

        $this->commandService
            ->method('isGranted')
            ->willReturn(true);

        $this->commandRunner
            ->expects($this->once())
            ->method('run')
            ->with($command, [], 300)
            ->willReturn(['output' => 'ok', 'exitCode' => 0, 'duration' => '1.0000']);

        $controller = new CommandController(
            entityManager: $this->entityManager,
            messageBus: $this->messageBus,
            commandRunner: $this->commandRunner,
            commandService: $this->commandService,
            configurationProvider: new ConfigurationProvider(
                apiPrefix: '/api',
                consolePath: 'bin/console',
                enableAsync: false,
                defaultTimeout: null,
                defaultTimeLimit: null,
                defaultMemoryLimit: null,
                enabledAdminDashboard: false,
                enabledDashboardLiveStatus: true
            )
        );

        $response = $controller->run($command, new RunCommandDto([], 1_000));

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testRunCustomParametersAreForbiddenByDefault(): void
    {
        $command = new Command;
        $command->setId(1);
        $command->setName('test');
        $command->setCommand('actual:command');
        $command->setAsync(false);

        $this->commandService
            ->method('isGranted')
            ->willReturn(true);

        $this->commandRunner
            ->expects($this->never())
            ->method('run');

        $controller = new CommandController(
            entityManager: $this->entityManager,
            messageBus: $this->messageBus,
            commandRunner: $this->commandRunner,
            commandService: $this->commandService,
            configurationProvider: $this->getConfigurationProvider()
        );

        $response = $controller->run($command, new RunCommandDto(['--env=prod']));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testRunAcceptsOnlyJsonPayload(): void
    {
        $parameter = new ReflectionMethod(CommandController::class, 'run')->getParameters()[1];
        $attributes = $parameter->getAttributes(MapRequestPayload::class);

        $this->assertCount(1, $attributes);
        $this->assertSame('json', $attributes[0]->newInstance()->acceptFormat);
    }
}
