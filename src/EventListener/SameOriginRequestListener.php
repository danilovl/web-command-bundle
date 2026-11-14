<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\{
    Response,
    JsonResponse
};
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 0)]
final readonly class SameOriginRequestListener
{
    private const string ROUTE_PREFIX = 'danilovl_web_command';

    private const array ALLOWED_FETCH_SITES = ['same-origin', 'none'];

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->isMethodSafe()) {
            return;
        }

        $route = $request->attributes->getString('_route');
        if (!str_starts_with($route, self::ROUTE_PREFIX)) {
            return;
        }

        $fetchSite = $request->headers->get('Sec-Fetch-Site');
        $isForbiddenFetchSite = $fetchSite !== null && !in_array($fetchSite, self::ALLOWED_FETCH_SITES, true);

        $origin = $request->headers->get('Origin');
        $isForbiddenOrigin = $origin !== null && $origin !== $request->getSchemeAndHttpHost();

        if ($isForbiddenFetchSite || $isForbiddenOrigin) {
            $event->setResponse(new JsonResponse(
                data: ['error' => 'Cross-site request is not allowed'],
                status: Response::HTTP_FORBIDDEN
            ));
        }
    }
}
