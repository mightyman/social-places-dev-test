<?php

namespace App\Controller\Application;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Contact;
use App\Entity\Store;
use Symfony\Component\Mime\Email;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ContactController extends AbstractController
{
    #[Route('/api/contact-forms/create', name: 'api_contact_form_create', methods: 'POST')]
    public function createContact(Request $request, ValidatorInterface $validator, MailerInterface $mailer, UserRepository $userRepository, ?Contact $contact = null): JsonResponse 
    {
        $store = $this->getDoctrine()->getRepository(Store::class)->find($request->get('store_id'));
        if (!$store) {
            return $this->json([
                'success' => false,
                'message' => 'Store not found',
            ], 404);
        } 
        
        
        $contact_form = new Contact();
        $contact_form->setStore($store);
        $contact_form->setFirstname($request->get("firstname"));
        $contact_form->setLastname($request->get("lastname"));
        $contact_form->setEmail($request->get("email"));
        $contact_form->setMessage($request->get("message"));
        
        // Validate the ContactForm entity
        $errors = $validator->validate($contact_form);

         // If there are errors, return them as a JSON response
         if (count($errors) > 0) {
            $errorMessages = [];

            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errorMessages,
            ], 400);
        }
        
        // If there are no errors, save the ContactForm entity
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($contact_form);
        $entityManager->flush();

        $admins = $this->usersWithAdminRole($userRepository);
        
        // Send mail to each admin
        foreach ($admins as $admin) {
            $email = (new Email())
                ->from('no-reply@socialplaces.io')
                ->to($admin->getUsername())
                ->subject('New Contact Added')
                ->text('Hello You have new contact added!');
            $mailer->send($email);
        }
        
        // Return a success JSON response
        return $this->json([
            'success' => true,
            'message' => 'Contact form submitted successfully',
        ], 201);        
    }

    /**
     * users with adminroles
     */
    public function usersWithAdminRole(UserRepository $userRepository)
    {
        return $userRepository->findByAdminRole();        
    }

}
