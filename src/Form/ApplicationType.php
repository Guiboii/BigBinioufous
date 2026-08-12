<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApplicationType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * Active la configuration de base pour un champ.
     *
     * @param string $label
     * @param string $placeholder
     * @param array  $options
     *
     * @return array
     */
    protected function getConfiguration($label, $placeholder, $options = [])
    {
        return array_merge([
            'label' => $this->translator->trans($label),
            'attr' => [
                'placeholder' => $this->translator->trans($placeholder),
            ],
        ], $options);
    }

    /**
     * Pour les options de champ qui ne passent pas par getConfiguration()
     * (ex. FileType::label, messages de contrainte).
     */
    protected function trans(string $key): string
    {
        return $this->translator->trans($key);
    }
}
