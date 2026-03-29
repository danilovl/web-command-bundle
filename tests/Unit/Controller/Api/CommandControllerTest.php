<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Tests\Unit\Controller\Api;

use Danilovl\WebCommandBundle\Controller\Api\CommandController;
use Danilovl\WebCommandBundle\Dto\RunCommandDto;
use Danilovl\WebCommandBundle\Entity\Command;
use Danilovl\WebCommandBundle\Service\{
    CommandRunner,
    CommandService
};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
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
            enableAsync: true,
            defaultTimeout: 300
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
            enableAsync: true,
            defaultTimeout: 300
        );

        $runCommandDto = new RunCommandDto(['--extra']);
        $response = $controller->run($command, $runCommandDto);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('Custom parameters are not allowed', (string) $response->getContent());
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
            enableAsync: true,
            defaultTimeout: 300
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
            enableAsync: true,
            defaultTimeout: 300
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
            enableAsync: false,
            defaultTimeout: 300
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
            enableAsync: true,
            defaultTimeout: 300
        );

        $response = $controller->list();
        /** @var array<array{name: string}> $data */
        $data = json_decode((string) $response->getContent(), true);

        $this->assertCount(2, $data);
        $this->assertEquals('c1', $data[0]['name']);
        $this->assertEquals('c2', $data[1]['name']);
    }
}
