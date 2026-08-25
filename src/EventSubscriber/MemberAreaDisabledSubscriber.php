<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Temporary member area cutoff for the prod_vitrine branch: no mail
 * handling in place (MAILER_DSN on null://null), so registration,
 * validation, notifications and login can't work. Rather than commenting
 * out every affected controller (LoginController, DeskController,
 * AdminController, NoteController, AccountingController, EventController,
 * TwoFactorController, FolderController, DocumentController,
 * BulkActionController, SetlistController), a single cutoff point here:
 * every request to these prefixes is redirected to the homepage before it
 * reaches the controller or the firewall. /contact stays reachable on
 * purpose: its HelloAsso widgets (membership, donation) work without a
 * mailer, only the mail form itself is hidden in the template. To
 * re-enable the member area once mail is configured: delete this file.
 */
class MemberAreaDisabledSubscriber implements EventSubscriberInterface
{
    private const DISABLED_PATH_PREFIXES = [
        '/login',
        '/join',
        '/register',
        '/logout',
        '/desk',
        '/admin',
        '/2fa',
    ];

    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        foreach (self::DISABLED_PATH_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                if ($request->hasSession()) {
                    $request->getSession()->getFlashBag()->add(
                        'warning',
                        'Espace membre indisponible pour le moment (pas encore de gestion des mails en place).'
                    );
                }

                $event->setResponse(new RedirectResponse($this->urlGenerator->generate('home')));

                return;
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
