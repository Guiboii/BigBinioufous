<?php

namespace App\Security;

use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;

/**
 * Représente un secret TOTP généré mais pas encore confirmé (affiché en QR
 * code le temps que la personne prouve qu'elle sait le lire avec son appli,
 * cf. TwoFactorController::enable()). Ne touche jamais l'entité User réelle
 * tant que le code n'est pas vérifié : évite qu'un flush ailleurs dans la
 * requête persiste un secret jamais confirmé.
 */
final readonly class PendingTotpUser implements TwoFactorInterface
{
    public function __construct(private string $username, private string $secret)
    {
    }

    public function isTotpAuthenticationEnabled(): bool
    {
        return true;
    }

    public function getTotpAuthenticationUsername(): ?string
    {
        return $this->username;
    }

    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
        return new TotpConfiguration($this->secret, TotpConfiguration::ALGORITHM_SHA1, 30, 6);
    }
}
