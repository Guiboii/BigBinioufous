<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ValidRoleType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('validation', CheckboxType::class, [
                'label' => 'Valider l\'utilisateur?',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
