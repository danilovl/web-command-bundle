<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Tests\Unit\Entity;

use Danilovl\WebCommandBundle\Entity\{Command, Job};
use PHPUnit\Framework\TestCase;

class JobTest extends TestCase
{
    public function testStatus(): void
    {
        $command = new Command;
        $command->setName('test:command');
        
        $job = new Job($command, ['--arg']);
        
        $this->assertEquals('queued', $job->getStatus());
        $this->assertTrue($job->isQueued());
        
        $job->setStatus('running');
        $this->assertEquals('running', $job->getStatus());
        $this->assertTrue($job->isRunning());

        $job->setStatus('completed');
        $this->assertTrue($job->isCompleted());

        $job->setStatus('failed');
        $this->assertTrue($job->isFailed());
    }

    public function testJsonSerialize(): void
    {
        $command = new Command;
        $command->setId(1);
        $command->setName('test:command');
        
        $job = new Job($command, ['--arg']);
        $job->setId(1);
        $job->setStatus('running');
        
        $data = $job->jsonSerialize();
        
        $this->assertEquals(1, $data['id']);
        $this->assertEquals('test:command', $data['commandName']);
        $this->assertEquals('running', $data['status']);
        $this->assertArrayHasKey('input', $data);
        $this->assertEquals(['--arg'], $data['input']);
    }
}
