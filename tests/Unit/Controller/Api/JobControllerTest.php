<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Tests\Unit\Controller\Api;

use Danilovl\WebCommandBundle\Controller\Api\JobController;
use Danilovl\WebCommandBundle\Entity\{Command, Job};
use PHPUnit\Framework\TestCase;

class JobControllerTest extends TestCase
{
    public function testStatus(): void
    {
        $command = new Command;
        $command->setName('test:cmd');
        $job = new Job($command);
        $job->setStatus('running');

        $controller = new JobController;

        $response = $controller->status($job);
        $content = $response->getContent();
        $this->assertIsString($content);

        /** @var array{status: string} $data */
        $data = json_decode($content, true);

        $this->assertArrayHasKey('status', $data);
        $this->assertEquals('running', $data['status']);
    }
}
