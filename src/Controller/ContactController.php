<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact_submit', methods: ['POST'])]
    public function submit(Request $request, MailerInterface $mailer, string $contactEmail): JsonResponse
    {
        if (!$this->isCsrfTokenValid('contact', $request->request->get('_token'))) {
            return $this->json(['error' => 'invalid_token'], 403);
        }

        // Honeypot: hidden field a real visitor never fills in, bots often do.
        // Pretend success so bots don't learn to skip it.
        if ($request->request->get('website')) {
            return $this->json(['success' => true]);
        }

        $name = trim((string) $request->request->get('name'));
        $email = trim((string) $request->request->get('email'));
        $message = trim((string) $request->request->get('message'));

        if ('' === $name || '' === $message || !filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'invalid_input'], 400);
        }

        $mail = (new Email())
            ->from(new Address($contactEmail, 'Binioufous - formulaire de contact'))
            ->to($contactEmail)
            ->replyTo(new Address($email, $name))
            ->subject('Nouveau message de contact de '.$name)
            ->text($message."\n\n---\n{$name} <{$email}>");

        try {
            $mailer->send($mail);
        } catch (TransportExceptionInterface $e) {
            return $this->json(['error' => 'send_failed'], 500);
        }

        return $this->json(['success' => true]);
    }
}
