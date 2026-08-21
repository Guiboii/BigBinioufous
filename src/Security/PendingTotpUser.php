<?php

namespace App\Security;

use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;

// A TOTP secret generated but not yet confirmed, shown as a QR code until the person proves they can read it with their app. Never touches the real User entity until the code is verified, so a flush elsewhere in the request can't persist an unconfirmed secret.
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
