<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Tests\Unit\Controller\Api;

use Danilovl\WebCommandBundle\Controller\Api\JobController;
use Danilovl\WebCommandBundle\Entity\{Command, Job};
use Danilovl\WebCommandBundle\Repository\JobRepository;
use PHPUnit\Framework\TestCase;

class JobControllerTest extends TestCase
{
    public function testStatus(): void
    {
        $command = new Command;
        $command->setName('test:cmd');
        $job = new Job($command);
        $job->setStatus('running');

        $jobRepository = $this->createStub(JobRepository::class);
        $controller = new JobController($jobRepository);

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

        $controller = new JobController($jobRepository);

        $response = $controller->active();
        $content = $response->getContent();
        $this->assertIsString($content);

        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertCount(1, $data);

        /** @var array<array{status: string}> $data */
        $this->assertEquals('running', $data[0]['status']);
    }
}
