<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Tests\Unit\Controller\Api;

use Danilovl\WebCommandBundle\Controller\Api\HistoryController;
use Danilovl\WebCommandBundle\Entity\{
    Command,
    History
};
use Danilovl\WebCommandBundle\Repository\HistoryRepository;
use Danilovl\WebCommandBundle\Service\CommandService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\{
    Request,
    Response
};

class HistoryControllerTest extends TestCase
{
    private function createCommand(): Command
    {
        $command = new Command;
        $command->setId(1);
        $command->setName('test');
        $command->setCommand('test:command');
        $command->timestampAblePrePersist();

        return $command;
    }

    private function createHistory(Command $command): History
    {
        $history = new History($command, false, 0.1, 1);
        $history->setId(1);
        $history->setOutput('secret output');

        return $history;
    }

    private function createCommandService(bool $isGranted): CommandService
    {
        $commandService = $this->createStub(CommandService::class);
        $commandService->method('isGranted')->willReturn($isGranted);

        return $commandService;
    }

    public function testGetGranted(): void
    {
        $history = $this->createHistory($this->createCommand());

        $controller = new HistoryController(
            $this->createStub(HistoryRepository::class),
            $this->createCommandService(true)
        );

        $response = $controller->get($history);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('secret output', (string) $response->getContent());
    }

    public function testGetAccessDenied(): void
    {
        $history = $this->createHistory($this->createCommand());

        $controller = new HistoryController(
            $this->createStub(HistoryRepository::class),
            $this->createCommandService(false)
        );

        $response = $controller->get($history);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringNotContainsString('secret output', (string) $response->getContent());
    }

    public function testListGranted(): void
    {
        $command = $this->createCommand();
        $history = $this->createHistory($command);

        $historyRepository = $this->createStub(HistoryRepository::class);
        $historyRepository->method('findBy')->willReturn([$history]);
        $historyRepository->method('count')->willReturn(11);

        $controller = new HistoryController($historyRepository, $this->createCommandService(true));

        $response = $controller->list(new Request(['page' => 2]), $command);

        /** @var array{histories: array<mixed>, totalPages: int, currentPage: int} $data */
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertCount(1, $data['histories']);
        $this->assertSame(2, $data['totalPages']);
        $this->assertSame(2, $data['currentPage']);
    }

    public function testListAccessDenied(): void
    {
        $historyRepository = $this->createMock(HistoryRepository::class);
        $historyRepository->expects($this->never())->method('findBy');

        $controller = new HistoryController($historyRepository, $this->createCommandService(false));

        $response = $controller->list(new Request, $this->createCommand());

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
}
