<?php

namespace App\Form;

use App\Entity\Instrument;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/**
 * Mêmes champs qu'AccountType (profil self-service), utilisé par
 * AdminController::showUser() pour l'édition côté admin, sauf nickname/email
 * (identifiants de connexion) et claimsMembership : pas de raison qu'un·e
 * admin puisse changer l'identifiant de connexion de quelqu'un d'autre en
 * éditant son profil (retour utilisatrice 2026-08-12), affichés en lecture
 * seule côté template (templates/desk/_user_form_fields.html.twig) à la
 * place.
 */
class EditUserType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
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
            // Pas de claimsMembership ici, contrairement à AccountType : la
            // question "Es-tu déjà adhérent·e ?" est écrite à la 2e personne,
            // adressée au membre lui-même sur son propre profil, ça n'a pas
            // de sens affiché à un·e admin qui édite le profil de quelqu'un
            // d'autre. L'admin vérifie la cotisation par ses propres moyens
            // (HelloAsso) puis bascule le rôle via le toggle Membre/Pas
            // membre, sans passer par ce champ (retour utilisatrice
            // 2026-08-12).
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
