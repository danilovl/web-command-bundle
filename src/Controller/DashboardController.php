<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Controller;

use Danilovl\WebCommandBundle\Service\{
    CommandService,
    ConfigurationProvider
};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly CommandService $commandService,
        private readonly ConfigurationProvider $configurationProvider
    ) {}

    #[Route(
        path: '/danilovl/web-command/dashboard',
        name: 'danilovl_web_command_dashboard_index',
        methods: ['GET']
    )]
    public function index(): Response
    {
        $commands = $this->commandService->getPublicCommands();

        return $this->render('@WebCommand/dashboard/index.html.twig', [
            'commands' => $commands,
            'enabledDashboardLiveStatus' => $this->configurationProvider->enabledDashboardLiveStatus
        ]);
    }
}
