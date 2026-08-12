<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Inscription simplifiée (2026-08-12, cf. ROADMAP.md "Facilitons
 * l'inscription") : juste pseudo/email/mot de passe. Tout le reste
 * (prénom/nom, genre, date de naissance, ville, pays, instrument, photo,
 * adhésion...) déménage sur /desk/profile, facultatif, à compléter après
 * coup si la personne le souhaite. "wish" (rôle souhaité) et le numéro de
 * carte d'adhérent·e sont retirés : le rôle se décide après validation du
 * compte par un·e admin, décorrélé de l'inscription elle-même.
 */
class RegistrationType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nickname', TextType::class, $this->getConfiguration('register.field_username', 'register.field_username_placeholder'))
            ->add('email', EmailType::class, $this->getConfiguration('register.field_email', 'register.field_email_placeholder'))
            ->add('hash', PasswordType::class, $this->getConfiguration('register.field_password', 'register.field_password_placeholder'))
            ->add('passwordConfirm', PasswordType::class, $this->getConfiguration('register.field_password_confirm', 'register.field_password_confirm_placeholder'))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
