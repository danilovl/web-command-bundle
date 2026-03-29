<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Service;

use Danilovl\WebCommandBundle\Entity\Command;
use Danilovl\WebCommandBundle\Repository\CommandRepository;
use Doctrine\Common\Collections\Order;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

readonly class CommandService
{
    public function __construct(
        private CommandRepository $commandRepository,
        private ?AuthorizationCheckerInterface $authorizationChecker = null
    ) {}

    /**
     * @return Command[]
     */
    public function getPublicCommands(): array
    {
        $commands = $this->commandRepository->findBy(
            ['active' => true],
            ['name' => Order::Ascending->value]
        );

        return array_values(array_filter($commands, [$this, 'isGranted']));
    }

    public function isGranted(Command $command): bool
    {
        $voterClass = $command->getVoterClass();
        if (!$voterClass || !$this->authorizationChecker) {
            return true;
        }

        return $this->authorizationChecker->isGranted($voterClass, $command);
    }
}
