<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\PendingTotpUser;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Activation/désactivation de la 2FA (TOTP, scheb/2fa-bundle) depuis
 * /desk/profile, réservée ROLE_ADMIN (accès le plus sensible du site, cf.
 * ROADMAP.md phase 8 "2FA"). Le secret est généré à l'affichage mais
 * seulement persisté sur User une fois qu'un code valide a été saisi
 * (App\Security\PendingTotpUser) : évite d'activer une 2FA sur un appareil
 * mal configuré et de bloquer le compte au prochain login.
 */
#[Route('/desk/profile/2fa')]
class TwoFactorController extends AbstractController
{
    private const SESSION_KEY = 'totp_pending_secret';

    #[Route('', name: 'two_factor_setup', methods: ['GET'])]
    public function index(Request $request, TotpAuthenticatorInterface $totpAuthenticator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user = $this->requireUser();

        if ($user->isTotpAuthenticationEnabled()) {
            return $this->render('desk/two_factor/enabled.html.twig');
        }

        $session = $request->getSession();
        $secret = $session->get(self::SESSION_KEY);
        if (!\is_string($secret)) {
            $secret = $totpAuthenticator->generateSecret();
            $session->set(self::SESSION_KEY, $secret);
        }

        $pendingUser = new PendingTotpUser($user->getUserIdentifier(), $secret);
        $qrSvg = (new Builder(writer: new SvgWriter()))->build(
            data: $totpAuthenticator->getQRContent($pendingUser),
            size: 240,
            margin: 10,
        );

        return $this->render('desk/two_factor/setup.html.twig', [
            'secret' => $secret,
            'qrDataUri' => $qrSvg->getDataUri(),
        ]);
    }

    #[Route('/enable', name: 'two_factor_enable', methods: ['POST'])]
    public function enable(Request $request, TotpAuthenticatorInterface $totpAuthenticator, EntityManagerInterface $manager): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('two_factor_enable', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $session = $request->getSession();
        $secret = $session->get(self::SESSION_KEY);
        if (!\is_string($secret)) {
            $this->addFlash('danger', 'Le code a expiré, recommence la configuration.');

            return $this->redirectToRoute('two_factor_setup');
        }

        $user = $this->requireUser();
        $pendingUser = new PendingTotpUser($user->getUserIdentifier(), $secret);
        $code = (string) $request->request->get('code', '');

        if (!$totpAuthenticator->checkCode($pendingUser, $code)) {
            $this->addFlash('danger', 'Code invalide, réessaie.');

            return $this->redirectToRoute('two_factor_setup');
        }

        $user->setTotpSecret($secret);
        $manager->flush();
        $session->remove(self::SESSION_KEY);
        $this->addFlash('success', 'Double authentification activée.');

        return $this->redirectToRoute('two_factor_setup');
    }

    #[Route('/disable', name: 'two_factor_disable', methods: ['POST'])]
    public function disable(Request $request, EntityManagerInterface $manager): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('two_factor_disable', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->requireUser();
        $user->setTotpSecret(null);
        $manager->flush();
        $this->addFlash('success', 'Double authentification désactivée.');

        return $this->redirectToRoute('two_factor_setup');
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
