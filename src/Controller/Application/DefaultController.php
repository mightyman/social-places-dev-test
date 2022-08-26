<?php

namespace App\Controller\Application;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class DefaultController extends AbstractController
{
    public function __construct(private readonly RouterInterface $router) {
    }

    #[Route('/', name: 'index')]
    #[Route('/login', name: 'login')]
    #[Route('/dashboard', name: 'dashboard', defaults: ['nav' => ['name' => 'Dashboard', 'icon' => 'fas fa-users', 'dashboard' => 'admin', 'divider' => true]])]
    #[Route('/users', name: 'users', defaults: ['nav' => ['name' => 'Users', 'icon' => 'fas fa-users', 'dashboard' => 'admin']])]
    #[Route('/users/create', name: 'users_create')]
    #[Route('/users/edit/{id}', name: 'users_edit')]
    #[Route('/stores', name: 'stores', defaults: ['nav' => ['name' => 'Stores', 'icon' => 'fas fa-store', 'dashboard' => 'admin']])]
    #[Route('/stores/import', name: 'stores_import')]
    public function index(Request $request): RedirectResponse|Response {
        if ($this->getUser() === null && $request->attributes->get('_route') !== 'login') {
            return $this->redirect($this->generateUrl('login'));
        }
        if ($this->getUser() !== null && in_array($request->attributes->get('_route'), ['login', 'index'], true)) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        return $this->render('base.html.twig');
    }

    #[Route('/api/dashboard-links', name: 'api_dashboard_links')]
    public function getDashboardLinks(Request $request): JsonResponse {
        $routes = $this->router->getRouteCollection()->all();
        $navRoutes = [];
        /** @var \Symfony\Component\Routing\Route $route */
        foreach ($routes as $route) {
            if ($nav = $route->getDefault('nav')) {
                $navRoutes[] = [
                    'name' => $nav['name'],
                    'icon' => $nav['icon'],
                    'path' => $route->getPath()
                ];
                if ($nav['divider'] ?? false) {
                    $navRoutes[] = [
                        'divider' => true,
                    ];
                }
            }
        }

        return $this->json([
            'links' => $navRoutes
        ]);
    }
}
