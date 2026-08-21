<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

// Registration form: just nickname/email/password. Everything else (identity, instrument, picture...) is optional and filled in later on the profile page.
class RegistrationType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nickname', TextType::class, $this->getConfiguration('register.field_username', 'register.field_username_placeholder'))
            ->add('email', EmailType::class, $this->getConfiguration('register.field_email', 'register.field_email_placeholder'))
            ->add('hash', PasswordType::class, $this->getConfiguration('register.field_password', 'register.field_password_placeholder'))
            ->add('passwordConfirm', PasswordType::class, $this->getConfiguration('register.field_password_confirm', 'register.field_password_confirm_placeholder'))
            // The underlying column is a NOT NULL boolean, so "no answer" isn't a valid state. required: true, otherwise ChoiceType adds an untranslated 3rd "None" radio for that meaningless state.
            ->add('claimsMembership', ChoiceType::class, [
                'label' => $this->trans('register.claims_membership_question'),
                'required' => true,
                'expanded' => true,
                'choices' => [
                    $this->trans('register.claims_membership_yes') => true,
                    $this->trans('register.claims_membership_no') => false,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
