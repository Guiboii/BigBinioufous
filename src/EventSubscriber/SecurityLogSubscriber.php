<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

// Logs login attempts. form_login/2FA are handled entirely by the security bundles
// (no custom Authenticator class in this project), so these Symfony security events
// are the only hook point to log successes/failures without touching that flow.
class SecurityLogSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $this->logger->info('Connexion réussie', [
            'user' => $event->getUser()->getUserIdentifier(),
            'ip' => $event->getRequest()->getClientIp(),
        ]);
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $this->logger->warning('Échec de connexion', [
            'ip' => $event->getRequest()->getClientIp(),
            'reason' => $event->getException()->getMessageKey(),
        ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }
}
