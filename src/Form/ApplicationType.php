<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Contracts\Translation\TranslatorInterface;

// Base class for form types, providing a shared helper to build a translated label + placeholder pair.
class ApplicationType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    // Builds the standard label/placeholder option array for a field from translation keys.
    protected function getConfiguration($label, $placeholder, $options = [])
    {
        return array_merge([
            'label' => $this->translator->trans($label),
            'attr' => [
                'placeholder' => $this->translator->trans($placeholder),
            ],
        ], $options);
    }

    // For field options that don't go through getConfiguration() (e.g. FileType::label, constraint messages).
    protected function trans(string $key): string
    {
        return $this->translator->trans($key);
    }
}
