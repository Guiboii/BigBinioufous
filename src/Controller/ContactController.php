<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    /**
     * Délai minimum entre l'affichage du formulaire (contact_ts, posé côté
     * Twig au rendu) et sa soumission : un bot qui poste directement sans
     * jamais avoir "vu" la page est plus rapide que ça, un humain non.
     */
    private const MIN_SUBMIT_SECONDS = 3;

    /**
     * $contactEmail : injecté via le bind global de config/services.yaml
     * (CONTACT_EMAIL, cf. branche mails fusionnée), même adresse que
     * App\Mailer\RegistrationMailer. Tant que MAILER_DSN n'est pas configuré
     * (cf. ROADMAP.md "Emails fonctionnels"), rien ne part réellement, mais
     * le circuit anti-spam (honeypot + piège temporel + CSRF) est bien réel
     * dès maintenant.
     */
    #[Route('/contact', name: 'contact_submit', methods: ['POST'])]
    public function submit(Request $request, MailerInterface $mailer, LoggerInterface $logger, string $contactEmail): JsonResponse
    {
        if (!$this->isCsrfTokenValid('contact', $request->request->get('_token'))) {
            return $this->json(['error' => 'invalid_token'], 403);
        }

        // Honeypot rempli, ou soumis trop vite : signature de bot. On répond
        // un faux succès (jamais révéler le piège à l'appelant) sans rien envoyer.
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
