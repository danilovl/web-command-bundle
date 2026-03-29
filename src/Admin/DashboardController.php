<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\{
    Dashboard,
    MenuItem
};
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin/danilovl/web-command', routeName: 'danilovl_web_command_admin_dashboard')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(private readonly AdminUrlGenerator $adminUrlGenerator) {}

    public function index(): Response
    {
        $url = $this->adminUrlGenerator
            ->setController(CommandCrudController::class)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Web Command');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkTo(CommandCrudController::class, 'Command', 'fas fa-terminal');
        yield MenuItem::linkTo(HistoryCrudController::class, 'History', 'fas fa-history');
        yield MenuItem::linkTo(JobCrudController::class, 'Job', 'fas fa-tasks');
    }
}
