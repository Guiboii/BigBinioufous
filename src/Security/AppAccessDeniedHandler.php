<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

/**
 * Only reached for a logged-in user missing a required role: an anonymous visitor hitting
 * the same URL is already redirected to /join by the form_login entry point before this
 * handler ever runs. Without it, Symfony renders its bare, unexplained 403 error page,
 * which a member has no way to act on (no link back, no reason given).
 */
class AppAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        $request->getSession()->getFlashBag()->add(
            'danger',
            "Tu n'as pas accès à cette page avec ton compte actuel."
        );

        return new RedirectResponse($this->urlGenerator->generate('desk'));
    }
}
