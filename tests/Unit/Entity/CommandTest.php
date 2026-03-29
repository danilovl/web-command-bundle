<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Tests\Unit\Entity;

use Danilovl\WebCommandBundle\Entity\Command;
use PHPUnit\Framework\TestCase;

class CommandTest extends TestCase
{
    public function testGettersSetters(): void
    {
        $command = new Command;
        $command->setName('test:cmd');
        $command->setCommand('actual:cmd');
        $command->setActive(false);
        $command->setAsync(true);
        $command->setParameters(['--test']);
        $command->setAllowCustomParameters(false);
        $command->setVoterClass('Voter\Namespace');
        $command->setDescription('desc');

        $this->assertEquals('test:cmd', $command->getName());
        $this->assertEquals('actual:cmd', $command->getCommand());
        $this->assertFalse($command->isActive());
        $this->assertTrue($command->isAsync());
        $this->assertEquals(['--test'], $command->getParameters());
        $this->assertFalse($command->isAllowCustomParameters());
        $this->assertEquals('Voter\Namespace', $command->getVoterClass());
        $this->assertEquals('desc', $command->getDescription());
        $this->assertEquals('test:cmd', (string) $command);
    }

    public function testJsonSerialize(): void
    {
        $command = new Command;
        $command->setId(1);
        $command->timestampAblePrePersist();
        $command->setName('test:cmd');
        $command->setCommand('actual:cmd');
        $command->setAllowCustomParameters(true);
        $command->setVoterClass('Voter\Namespace');
        $command->setParameters(['--test']);

        $json = $command->jsonSerialize();

        $this->assertEquals('test:cmd', $json['name']);
        $this->assertEquals('actual:cmd', $json['command']);
        $this->assertTrue($json['allowCustomParameters']);
        $this->assertEquals('Voter\Namespace', $json['voterClass']);
        $this->assertEquals(['--test'], $json['parameters']);
    }
}
