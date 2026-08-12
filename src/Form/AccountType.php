<?php

namespace App\Form;

use App\Entity\Instrument;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/**
 * Formulaire de /desk/profile : identité/instrument/adhésion, tous
 * facultatifs (cf. User, "Facilitons l'inscription", 2026-08-12) sauf le
 * pseudo et l'email, seuls champs déjà obligatoires à l'inscription.
 */
class AccountType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nickname', TextType::class, $this->getConfiguration('register.field_username', 'register.field_username_placeholder'))
            ->add('email', EmailType::class, $this->getConfiguration('register.field_email', 'register.field_email_placeholder'))
            ->add('firstName', TextType::class, $this->getConfiguration('profile.field_first_name', 'profile.field_first_name_placeholder', ['required' => false]))
            ->add('lastName', TextType::class, $this->getConfiguration('profile.field_last_name', 'profile.field_last_name_placeholder', ['required' => false]))
            ->add('gender', ChoiceType::class, array_merge(
                $this->getConfiguration('profile.field_gender', ''),
                [
                    'required' => false,
                    'placeholder' => 'profile.field_gender_placeholder',
                    'choices' => [
                        $this->trans('profile.gender_male') => 'male',
                        $this->trans('profile.gender_female') => 'female',
                        $this->trans('profile.gender_unknown') => 'unknown',
                    ],
                ]
            ))
            ->add('birth', DateType::class, array_merge(
                $this->getConfiguration('profile.field_birth', ''),
                ['required' => false, 'widget' => 'choice', 'format' => 'dd-MM-yyyy', 'years' => range('1940', '2015')]
            ))
            ->add('instrument', EntityType::class, [
                'class' => Instrument::class,
                'choice_label' => 'title',
                'label' => $this->trans('profile.field_instrument'),
                'required' => false,
                'placeholder' => 'profile.field_instrument_placeholder',
            ])
            ->add('otherInstrumentDetail', TextType::class, $this->getConfiguration('profile.field_other_instrument', 'profile.field_other_instrument_placeholder', ['required' => false]))
            ->add('city', TextType::class, $this->getConfiguration('profile.field_city', 'profile.field_city_placeholder', ['required' => false]))
            ->add('country', CountryType::class, array_merge(
                $this->getConfiguration('profile.field_country', ''),
                ['required' => false, 'placeholder' => 'profile.field_country_placeholder']
            ))
            ->add('picture', FileType::class, [
                'label' => $this->trans('profile.field_picture'),
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        [
                            'mimeTypes' => [
                                'image/jpeg',
                            ],
                            'mimeTypesMessage' => $this->trans('profile.picture_invalid_type'),
                        ]
                    ),
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
