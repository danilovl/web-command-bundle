<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Tests\Unit\EventListener;

use Danilovl\WebCommandBundle\EventListener\RequiredRoleRequestListener;
use Danilovl\WebCommandBundle\Service\ConfigurationProvider;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class RequiredRoleRequestListenerTest extends TestCase
{
    private function getConfigurationProvider(?string $requiredRole): ConfigurationProvider
    {
        return new ConfigurationProvider(
            apiPrefix: '/api',
            consolePath: 'bin/console',
            enableAsync: false,
            defaultTimeout: null,
            defaultTimeLimit: null,
            defaultMemoryLimit: null,
            enabledAdminDashboard: false,
            enabledDashboardLiveStatus: true,
            requiredRole: $requiredRole
        );
    }

    private function createEvent(
        string $route = 'danilovl_web_command_dashboard_index',
        int $requestType = HttpKernelInterface::MAIN_REQUEST
    ): RequestEvent {
        $request = Request::create('http://localhost/');
        $request->attributes->set('_route', $route);

        return new RequestEvent($this->createStub(HttpKernelInterface::class), $request, $requestType);
    }

    public function testWithoutRequiredRoleDoesNothing(): void
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects($this->never())->method('isGranted');

        $listener = new RequiredRoleRequestListener($this->getConfigurationProvider(null), $authorizationChecker);
        $listener($this->createEvent());

        $this->addToAssertionCount(1);
    }

    public function testWithoutRequiredRoleAndSecurityDoesNothing(): void
    {
        $listener = new RequiredRoleRequestListener($this->getConfigurationProvider(null));
        $listener($this->createEvent());

        $this->addToAssertionCount(1);
    }

    public function testGranted(): void
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true);

        $listener = new RequiredRoleRequestListener($this->getConfigurationProvider('ROLE_ADMIN'), $authorizationChecker);
        $listener($this->createEvent());
    }

    public function testDenied(): void
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false);

        $listener = new RequiredRoleRequestListener($this->getConfigurationProvider('ROLE_ADMIN'), $authorizationChecker);

        $this->expectException(AccessDeniedException::class);

        $listener($this->createEvent());
    }

    public function testDeniedForApiRoute(): void
    {
        $authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $authorizationChecker->method('isGranted')->willReturn(false);

        $listener = new RequiredRoleRequestListener($this->getConfigurationProvider('ROLE_ADMIN'), $authorizationChecker);

        $this->expectException(AccessDeniedException::class);

        $listener($this->createEvent('danilovl_web_commands'));
    }

    public function testForeignRouteIsIgnored(): void
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects($this->never())->method('isGranted');

        $listener = new RequiredRoleRequestListener($this->getConfigurationProvider('ROLE_ADMIN'), $authorizationChecker);
        $listener($this->createEvent('app_homepage'));
    }

    public function testSubRequestIsIgnored(): void
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects($this->never())->method('isGranted');

        $listener = new RequiredRoleRequestListener($this->getConfigurationProvider('ROLE_ADMIN'), $authorizationChecker);
        $listener($this->createEvent(requestType: HttpKernelInterface::SUB_REQUEST));
    }

    public function testRequiredRoleWithoutSecurityThrows(): void
    {
        $listener = new RequiredRoleRequestListener($this->getConfigurationProvider('ROLE_ADMIN'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('symfony/security-bundle');

        $listener($this->createEvent());
    }
}
