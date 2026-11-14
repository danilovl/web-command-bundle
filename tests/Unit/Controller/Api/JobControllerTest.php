<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Tests\Unit\Controller\Api;

use Danilovl\WebCommandBundle\Controller\Api\JobController;
use Danilovl\WebCommandBundle\Entity\{Command, Job};
use Danilovl\WebCommandBundle\Repository\JobRepository;
use Danilovl\WebCommandBundle\Service\CommandService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class JobControllerTest extends TestCase
{
    public function testStatus(): void
    {
        $command = new Command;
        $command->setName('test:cmd');
        $job = new Job($command);
        $job->setStatus('running');

        $jobRepository = $this->createStub(JobRepository::class);
        $controller = new JobController($jobRepository, $this->createGrantingCommandService(true));

        $response = $controller->status($job);
        $content = $response->getContent();
        $this->assertIsString($content);

        /** @var array{status: string} $data */
        $data = json_decode($content, true);

        $this->assertArrayHasKey('status', $data);
        $this->assertEquals('running', $data['status']);
    }

    public function testActive(): void
    {
        $command = new Command;
        $command->setId(1);
        $command->setName('test:cmd');
        $job = new Job($command);
        $job->setId(1);
        $job->setStatus('running');

        $jobRepository = $this->createMock(JobRepository::class);
        $jobRepository->expects($this->once())
            ->method('getActiveJobs')
            ->willReturn([$job]);

        $controller = new JobController($jobRepository, $this->createGrantingCommandService(true));

        $response = $controller->active();
        $content = $response->getContent();
        $this->assertIsString($content);

        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertCount(1, $data);

        /** @var array<array{status: string}> $data */
        $this->assertEquals('running', $data[0]['status']);
    }

    private function createGrantingCommandService(bool $isGranted): CommandService
    {
        $commandService = $this->createStub(CommandService::class);
        $commandService->method('isGranted')->willReturn($isGranted);

        return $commandService;
    }

    public function testStatusAccessDenied(): void
    {
        $command = new Command;
        $command->setName('test:cmd');
        $job = new Job($command);

        $controller = new JobController(
            $this->createStub(JobRepository::class),
            $this->createGrantingCommandService(false)
        );

        $response = $controller->status($job);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringNotContainsString('queued', (string) $response->getContent());
    }

    public function testActiveFiltersNotGrantedJobs(): void
    {
        $allowedCommand = new Command;
        $allowedCommand->setId(1);
        $allowedCommand->setName('allowed');
        $allowedJob = new Job($allowedCommand);
        $allowedJob->setId(1);

        $deniedCommand = new Command;
        $deniedCommand->setId(2);
        $deniedCommand->setName('denied');
        $deniedJob = new Job($deniedCommand);
        $deniedJob->setId(2);

        $jobRepository = $this->createStub(JobRepository::class);
        $jobRepository->method('getActiveJobs')->willReturn([$deniedJob, $allowedJob]);

        $commandService = $this->createStub(CommandService::class);
        $commandService
            ->method('isGranted')
            ->willReturnCallback(static fn (Command $command): bool => $command === $allowedCommand);

        $controller = new JobController($jobRepository, $commandService);

        /** @var array<array{id: int}> $data */
        $data = json_decode((string) $controller->active()->getContent(), true);

        $this->assertCount(1, $data);
        $this->assertSame(1, $data[0]['id']);
    }
}
