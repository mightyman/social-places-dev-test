<?php

namespace App\Controller\Application;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiLoginController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: 'POST')]
    public function login(): Response {
        if ($this->getUser() === null) {
            return $this->json(['error' => 'Unable to authenticate user'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([]);
    }

    #[Route('/api/logout', name: 'api_logout', methods: 'POST')]
    public function logout(): Response {
        return $this->json([]);
    }
}
