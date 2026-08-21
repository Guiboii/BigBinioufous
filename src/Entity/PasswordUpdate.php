<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;

// Not a Doctrine entity: a plain form model for the change-password form (old/new/confirm).
class PasswordUpdate
{
    private $oldPassword;

    #[Assert\Length(min: 8, minMessage: 'Please insert 8 characters at least')]
    private $newPassword;

    #[Assert\EqualTo(propertyPath: 'newPassword', message: 'Please confirm')]
    private $confirmPassword;

    public function getOldPassword(): ?string
    {
        return $this->oldPassword;
    }

    public function setOldPassword(string $oldPassword): self
    {
        $this->oldPassword = $oldPassword;

        return $this;
    }

    public function getNewPassword(): ?string
    {
        return $this->newPassword;
    }

    public function setNewPassword(string $newPassword): self
    {
        $this->newPassword = $newPassword;

        return $this;
    }

    public function getConfirmPassword(): ?string
    {
        return $this->confirmPassword;
    }

    public function setConfirmPassword(string $confirmPassword): self
    {
        $this->confirmPassword = $confirmPassword;

        return $this;
    }
}
