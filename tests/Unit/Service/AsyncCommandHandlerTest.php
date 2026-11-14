<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Tests\Unit\Service;

use Danilovl\WebCommandBundle\Entity\{
    Command,
    Job
};
use Danilovl\WebCommandBundle\Message\RunCommandMessage;
use Danilovl\WebCommandBundle\Repository\JobRepository;
use Danilovl\WebCommandBundle\Service\{
    AsyncCommandHandler,
    CommandRunner
};
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\{
    AllowMockObjectsWithoutExpectations,
    DataProvider
};
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[AllowMockObjectsWithoutExpectations]
class AsyncCommandHandlerTest extends TestCase
{
    private MockObject&CommandRunner $commandRunner;

    private MockObject&EntityManagerInterface $entityManager;

    private MockObject&JobRepository $jobRepository;

    private AsyncCommandHandler $handler;

    protected function setUp(): void
    {
        $this->commandRunner = $this->createMock(CommandRunner::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->jobRepository = $this->createMock(JobRepository::class);

        $this->handler = new AsyncCommandHandler($this->commandRunner, $this->entityManager, $this->jobRepository);
    }

    private function createJob(): Job
    {
        $command = new Command;
        $command->setName('test');
        $command->setCommand('test:command');

        $job = new Job($command, ['--arg']);
        $job->setId(1);

        $this->jobRepository
            ->method('find')
            ->with(1)
            ->willReturn($job);

        return $job;
    }

    #[DataProvider('exitCodeProvider')]
    public function testStatusByExitCode(?int $exitCode, string $expectedStatus): void
    {
        $job = $this->createJob();

        $this->commandRunner
            ->expects($this->once())
            ->method('run')
            ->with($job->getCommand(), ['--arg'], null, null, true)
            ->willReturn(['output' => '', 'exitCode' => $exitCode, 'duration' => '0.1000']);

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('flush');

        ($this->handler)(new RunCommandMessage(1));

        $this->assertSame($expectedStatus, $job->getStatus());
    }

    /**
     * @return iterable<string, array{int|null, string}>
     */
    public static function exitCodeProvider(): iterable
    {
        yield 'success' => [0, 'completed'];
        yield 'error' => [1, 'failed'];
        yield 'cancelled' => [-1, 'failed'];
        yield 'unknown' => [null, 'failed'];
    }

    public function testExceptionMarksJobFailed(): void
    {
        $job = $this->createJob();

        $this->commandRunner
            ->method('run')
            ->willThrowException(new RuntimeException('Process error'));

        ($this->handler)(new RunCommandMessage(1));

        $this->assertSame('failed', $job->getStatus());
    }

    public function testNotQueuedJobIsSkipped(): void
    {
        $job = $this->createJob();
        $job->setStatus('running');

        $this->commandRunner
            ->expects($this->never())
            ->method('run');

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        ($this->handler)(new RunCommandMessage(1));

        $this->assertSame('running', $job->getStatus());
    }

    public function testMissingJobIsSkipped(): void
    {
        $this->jobRepository
            ->method('find')
            ->willReturn(null);

        $this->commandRunner
            ->expects($this->never())
            ->method('run');

        ($this->handler)(new RunCommandMessage(1));
    }
}
