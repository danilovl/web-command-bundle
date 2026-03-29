<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Tests\Unit\Service;

use Danilovl\WebCommandBundle\Entity\Command;
use Danilovl\WebCommandBundle\Repository\CommandRepository;
use Danilovl\WebCommandBundle\Service\CommandService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AllowMockObjectsWithoutExpectations]
class CommandServiceTest extends TestCase
{
    private MockObject&CommandRepository $commandRepository;

    private MockObject&AuthorizationCheckerInterface $authorizationChecker;

    private CommandService $commandService;

    protected function setUp(): void
    {
        $this->commandRepository = $this->createMock(CommandRepository::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->commandService = new CommandService($this->commandRepository, $this->authorizationChecker);
    }

    public function testIsGrantedNoVoter(): void
    {
        $command = new Command;
        $command->setVoterClass(null);

        $this->assertTrue($this->commandService->isGranted($command));
    }

    public function testIsGrantedWithVoterAllowed(): void
    {
        $command = new Command;
        $command->setVoterClass('ROLE_ADMIN');

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN', $command)
            ->willReturn(true);

        $this->assertTrue($this->commandService->isGranted($command));
    }

    public function testIsGrantedWithVoterDenied(): void
    {
        $command = new Command;
        $command->setVoterClass('ROLE_ADMIN');

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN', $command)
            ->willReturn(false);

        $this->assertFalse($this->commandService->isGranted($command));
    }

    public function testGetPublicCommands(): void
    {
        $command1 = new Command;
        $command1->setName('a');
        $command1->setVoterClass(null);

        $command2 = new Command;
        $command2->setName('b');
        $command2->setVoterClass('ROLE_USER');

        $command3 = new Command;
        $command3->setName('c');
        $command3->setVoterClass('ROLE_ADMIN');

        $this->commandRepository
            ->method('findBy')
            ->willReturn([$command1, $command2, $command3]);

        $this->authorizationChecker
            ->method('isGranted')
            ->willReturnMap([
                ['ROLE_USER', $command2, null, true],
                ['ROLE_ADMIN', $command3, null, false],
            ]);

        $result = $this->commandService->getPublicCommands();

        $this->assertCount(2, $result);
        $this->assertSame($command1, $result[0]);
        $this->assertSame($command2, $result[1]);
    }
}
