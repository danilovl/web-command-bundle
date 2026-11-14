<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Tests\Unit\EventListener;

use Danilovl\WebCommandBundle\EventListener\SameOriginRequestListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\{
    Request,
    Response
};
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SameOriginRequestListenerTest extends TestCase
{
    /**
     * @param array<string, string> $headers
     */
    private function createEvent(
        string $method = 'POST',
        string $route = 'danilovl_web_command_run',
        array $headers = [],
        int $requestType = HttpKernelInterface::MAIN_REQUEST
    ): RequestEvent {
        $server = [];
        foreach ($headers as $name => $value) {
            $server['HTTP_' . mb_strtoupper(str_replace('-', '_', $name))] = $value;
        }

        $request = Request::create(
            uri: 'http://localhost/danilovl/web-command/api/commands/1/run',
            method: $method,
            server: $server
        );
        $request->attributes->set('_route', $route);

        return new RequestEvent($this->createStub(HttpKernelInterface::class), $request, $requestType);
    }

    /**
     * @param array<string, string> $headers
     */
    #[DataProvider('provideBlockedCases')]
    public function testBlocked(array $headers): void
    {
        $event = $this->createEvent(headers: $headers);

        (new SameOriginRequestListener)($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertStringContainsString('Cross-site request is not allowed', (string) $response->getContent());
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function provideBlockedCases(): iterable
    {
        yield 'cross-site fetch site' => [['Sec-Fetch-Site' => 'cross-site']];
        yield 'same-site fetch site' => [['Sec-Fetch-Site' => 'same-site']];
        yield 'foreign origin' => [['Origin' => 'http://evil.example']];
        yield 'origin with another scheme' => [['Origin' => 'https://localhost']];
        yield 'null origin' => [['Origin' => 'null']];
        yield 'same-origin fetch site with foreign origin' => [['Sec-Fetch-Site' => 'same-origin', 'Origin' => 'http://evil.example']];
    }

    /**
     * @param array<string, string> $headers
     */
    #[DataProvider('provideAllowedCases')]
    public function testAllowed(array $headers): void
    {
        $event = $this->createEvent(headers: $headers);

        (new SameOriginRequestListener)($event);

        $this->assertNull($event->getResponse());
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function provideAllowedCases(): iterable
    {
        yield 'no headers' => [[]];
        yield 'same-origin fetch site' => [['Sec-Fetch-Site' => 'same-origin']];
        yield 'none fetch site' => [['Sec-Fetch-Site' => 'none']];
        yield 'same origin' => [['Origin' => 'http://localhost']];
        yield 'same origin and fetch site' => [['Sec-Fetch-Site' => 'same-origin', 'Origin' => 'http://localhost']];
    }

    public function testSafeMethodIsIgnored(): void
    {
        $event = $this->createEvent(method: 'GET', headers: ['Sec-Fetch-Site' => 'cross-site']);

        (new SameOriginRequestListener)($event);

        $this->assertNull($event->getResponse());
    }

    public function testForeignRouteIsIgnored(): void
    {
        $event = $this->createEvent(route: 'app_homepage', headers: ['Sec-Fetch-Site' => 'cross-site']);

        (new SameOriginRequestListener)($event);

        $this->assertNull($event->getResponse());
    }

    public function testSubRequestIsIgnored(): void
    {
        $event = $this->createEvent(
            headers: ['Sec-Fetch-Site' => 'cross-site'],
            requestType: HttpKernelInterface::SUB_REQUEST
        );

        (new SameOriginRequestListener)($event);

        $this->assertNull($event->getResponse());
    }
}
