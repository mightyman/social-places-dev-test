<?php

namespace App\Controller\Application;

use App\Entity\User;
use App\ViewModels\UserViewModel;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends BaseVueController
{
    protected ?string $entity = UserViewModel::class;

    #[Route('/api/users/index', name: 'api_users', methods: ['POST', 'GET'])]
    public function index(Request $request): JsonResponse {
        return $this->_index($request);
    }

    /**
     * @throws \Exception
     */
    protected function getBaseResults(Request $request): array {
        return $this->_getBaseResults($request);
    }

    #[Route('/api/users/filters', name: 'api_users_filters', methods: ['POST', 'GET'])]
    public function getFilters(): JsonResponse {
        return $this->_getFilters();
    }

    #[Route('/api/users/me', name: 'api_users_me', methods: 'GET')]
    public function getMyProfile(): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();
        return $this->json([
            'user' => [
                'fullname' => $user->getFullname(),
                'primaryRole' => sp_string_humanize_role($user->getPrimaryRole()),
                'profilePicture' => $user->getProfilePicture()
            ]
        ]);
    }

    #[Route('/api/users/unique', name: 'api_user_unique_email', methods: 'POST')]
    public function checkUserUniqueness(Request $request): JsonResponse {
        $id = $request->get('id');
        $username = $request->get('value');

        $usersWithUserName = $this->entityManager->getRepository(User::class)->findBy(['username' => $username]);
        $countUsersWithUserName = count($usersWithUserName);
        if ($id) {
            $exists = true;
            if ($countUsersWithUserName === 0) {
                $exists = false;
            } elseif ($countUsersWithUserName === 1) {
                foreach ($usersWithUserName as $user) {
                    if ($user->getId() === (int)$id) {
                        $exists = false;
                        break;
                    }
                }
            }
        } else {
            $exists = $countUsersWithUserName > 0;
        }

        if ($exists) {
            return $this->json(['errors' => ['This value is already used.']], Response::HTTP_PRECONDITION_FAILED);
        }
        return $this->json([]);
    }

    #[Route('/api/users/create', name: 'api_user_create', methods: 'POST')]
    #[Route('/api/users/edit/{user}', name: 'api_user_edit', methods: 'POST')]
    public function createEditUser(Request $request, ValidatorInterface $validator, UserPasswordHasherInterface $userPasswordHasher, MailerInterface $mailer, ?User $user = null): JsonResponse {
        $creating = false;
        if (!$user) {
            $user = new User();
            $creating = true;
        }

        foreach (['username', 'name', 'surname', 'primaryRole'] as $field) {
            $user->{sp_setter($field)}($request->get($field) ?? $user->{sp_getter($field)}());
        }
        if (!empty(trim($password = $request->get('password')))) {
            $user->setPassword($userPasswordHasher->hashPassword($user, $password));
        }

        $errors = sp_extract_errors($validator->validate($user));
        if (!empty($errors)) {
            return $this->json(compact('errors'), Response::HTTP_PRECONDITION_FAILED);
        }

        if ($creating) {
            $this->entityManager->persist($user);
        }
        $this->entityManager->flush();

        if ($creating) {
            $email = (new TemplatedEmail())
                ->from(new Address('no-reply@socialplaces.io', 'SocialPlaces'))
                ->to(new Address($user->getUsername(), $user->getFullname()))
                ->subject('Welcome ' . $user->getName())
                ->htmlTemplate('mail/user-created.html.twig')
                ->context([
                    'password' => $request->get('password')
                ]);
            try {
                $mailer->send($email);
            } catch (\Exception $e) {
                //ignore error
            }
        }
        $this->entityManager->refresh($user);
        return $this->getUserInformation($user);
    }

    #[Route('/api/users/{user}', name: 'api_user_information', methods: 'GET')]
    public function getUserInformation(?User $user = null): JsonResponse {
        if (!$user) {
            return $this->json([
                'user' => [
                    'primaryRole' => 'ROLE_USER',
                    'password' => sp_random_str(10),
                ]
            ]);
        }

        return $this->json([
            'user' => [
                'username' => $user->getUsername(),
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'primaryRole' => $user->getPrimaryRole(),
                'profilePicture' => $user->getProfilePicture(),
            ]
        ]);
    }

    #[Route('/api/users/fetch/roles', name: 'api_users_primary_roles', methods: 'GET')]
    public function getPrimaryRoles(): JsonResponse {
        return $this->json(array_map(static fn ($item) => ['id' => $item, 'name' => sp_string_humanize_role($item)], ['ROLE_ADMIN', 'ROLE_USER']));
    }
}
