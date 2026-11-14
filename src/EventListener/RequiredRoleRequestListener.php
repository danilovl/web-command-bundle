<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\EventListener;

use Danilovl\WebCommandBundle\Service\ConfigurationProvider;
use LogicException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 0)]
final readonly class RequiredRoleRequestListener
{
    private const string ROUTE_PREFIX = 'danilovl_web_command';

    public function __construct(
        private ConfigurationProvider $configurationProvider,
        private ?AuthorizationCheckerInterface $authorizationChecker = null
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        $requiredRole = $this->configurationProvider->requiredRole;
        if ($requiredRole === null || !$event->isMainRequest()) {
            return;
        }

        $route = $event->getRequest()->attributes->getString('_route');
        if (!str_starts_with($route, self::ROUTE_PREFIX)) {
            return;
        }

        if ($this->authorizationChecker === null) {
            throw new LogicException('Option "required_role" requires "symfony/security-bundle".');
        }

        if (!$this->authorizationChecker->isGranted($requiredRole)) {
            $exception = new AccessDeniedException;
            $exception->setAttributes([$requiredRole]);

            throw $exception;
        }
    }
}
