<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

// Public /contact page: contact form plus the HelloAsso membership/donation widgets embedded as-is.
class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('contact/index.html.twig');
    }

    // Minimum delay between the form being rendered (contact_ts) and submitted: a bot posting directly without ever "seeing" the page is faster than this, a human isn't.
    private const MIN_SUBMIT_SECONDS = 3;

    #[Route('/contact', name: 'contact_submit', methods: ['POST'])]
    public function submit(Request $request, MailerInterface $mailer, LoggerInterface $logger, RateLimiterFactoryInterface $contactLimiter, string $contactEmail): JsonResponse
    {
        if (!$this->isCsrfTokenValid('contact', $request->request->get('_token'))) {
            return $this->json(['error' => 'invalid_token'], 403);
        }

        // Anti-flood (1 submission/minute/IP): unlike the honeypot/timing trap, a human hitting this limit deserves to know why their message didn't send.
        if (!$contactLimiter->create($request->getClientIp())->consume(1)->isAccepted()) {
            return $this->json(['error' => 'rate_limited'], 429);
        }

        // A filled honeypot or a too-fast submission is a bot signature. Respond with a fake success without sending anything, never reveal the trap to the caller.
        $submittedAt = (int) $request->request->get('contact_ts', 0);
        $tooFast = $submittedAt <= 0 || (time() - $submittedAt) < self::MIN_SUBMIT_SECONDS;
        if ('' !== (string) $request->request->get('contact_hp', '') || $tooFast) {
            return $this->json(['success' => true]);
        }

        $name = trim((string) $request->request->get('name', ''));
        $email = trim((string) $request->request->get('email', ''));
        $message = trim((string) $request->request->get('message', ''));

        if ('' === $email || !filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'invalid_email'], 422);
        }
        if ('' === $message) {
            return $this->json(['error' => 'empty_message'], 422);
        }

        try {
            $mailMessage = (new Email())
                ->from($contactEmail)
                ->to($contactEmail)
                ->replyTo($email)
                ->subject('Nouveau message de contact'.('' !== $name ? ' de '.$name : ''))
                ->text(sprintf("De : %s <%s>\n\n%s", '' !== $name ? $name : '(sans nom)', $email, $message));

            $mailer->send($mailMessage);
        } catch (\Throwable $e) {
            $logger->error('Échec de l\'envoi du mail de contact : '.$e->getMessage(), ['exception' => $e]);

            return $this->json(['error' => 'send_failed'], 500);
        }

        return $this->json(['success' => true]);
    }
}
