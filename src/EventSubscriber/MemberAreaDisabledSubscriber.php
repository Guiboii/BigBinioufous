<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Coupure temporaire de l'espace membre pour la branche prod_prov : pas de
 * gestion des mails en place (MAILER_DSN sur null://null), donc
 * inscription/validation/notifications/contact ne peuvent pas fonctionner.
 * Plutôt que de commenter chaque contrôleur concerné (LoginController,
 * DeskController, AdminController, NoteController, AccountingController,
 * EventController, TwoFactorController, FolderController, DocumentController,
 * BulkActionController, SetlistController, ContactController), un seul point
 * de coupure ici : toute requête vers ces préfixes est redirigée vers
 * l'accueil avant d'atteindre le contrôleur ou le firewall. Pour réactiver
 * l'espace membre une fois les mails configurés : supprimer ce fichier.
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
        '/contact',
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
