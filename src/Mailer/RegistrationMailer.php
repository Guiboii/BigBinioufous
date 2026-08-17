<?php

namespace App\Mailer;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class RegistrationMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $contactEmail,
    ) {
    }

    public function sendAutoValidated(User $user): void
    {
        $this->send(
            $user,
            'Bienvenue chez les Binioufous !',
            "Salut {$user->getFirstName()},\n\nTon compte est créé, tu peux te connecter dès maintenant.\n\nÀ bientôt !"
        );
    }

    public function sendPendingValidation(User $user): void
    {
        $this->send(
            $user,
            'Inscription bien reçue',
            "Salut {$user->getFirstName()},\n\nTon inscription a bien été reçue. Un·e admin doit encore la valider, tu recevras un email dès que ce sera fait.\n\nÀ bientôt !"
        );
    }

    public function sendValidated(User $user): void
    {
        $this->send(
            $user,
            'Ton inscription a été validée !',
            "Salut {$user->getFirstName()},\n\nTon inscription a été validée par un·e admin, tu peux maintenant te connecter.\n\nÀ bientôt !"
        );
    }

    private function send(User $user, string $subject, string $text): void
    {
        $email = (new Email())
            ->from(new Address($this->contactEmail, 'Binioufous'))
            ->to($user->getEmail())
            ->subject($subject)
            ->text($text);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            // Never block registration/validation because the notification email failed to send.
            $this->logger->error('Échec envoi email inscription à '.$user->getEmail().' : '.$e->getMessage());
        }
    }
}
