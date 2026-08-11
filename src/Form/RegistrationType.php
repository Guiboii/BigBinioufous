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
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class RegistrationType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // Question posée en tête de formulaire (direction validée le
            // 2026-08-10, cf. ROADMAP.md "Flux d'inscription à clarifier") :
            // avant, le bouton de cotisation HelloAsso et le choix `wish`
            // étaient visibles en même temps sans ordre clair ("on sait pas
            // trop où s'inscrire"). Ce champ non mappé (juste une question,
            // pas une propriété de User) détermine côté JS/template si on
            // affiche le champ numéro de carte ou qu'on met en avant
            // HelloAsso/le souhait "Simple".
            //
            // Bug trouvé en implémentant ce champ (pré-existant, pas
            // introduit ici) : gender/birth/wish ci-dessous passaient le
            // résultat de getConfiguration() en 4e argument de add() au lieu
            // de le fusionner dans le tableau d'options (3e argument).
            // FormBuilderInterface::add() n'accepte que 3 paramètres ; un 4e
            // argument est silencieusement ignoré par PHP (pas d'erreur),
            // donc label/placeholder traduits jamais appliqués : ces 3 champs
            // affichaient leur nom de champ auto-humanisé en anglais
            // ("Gender"/"Birth"/"Wish") au lieu de la traduction attendue.
            // Corrigé ici en passant les options additionnelles via le 3e
            // paramètre de getConfiguration(), prévu pour ça.
            ->add('alreadyMember', ChoiceType::class, $this->getConfiguration('register.already_member_question', 'register.already_member_hint', [
                'mapped' => false,
                'expanded' => true,
                'choices' => ['Yes' => 'yes', 'No' => 'no'],
            ]))
            // Rempli seulement si alreadyMember = yes (affiché/masqué en JS,
            // cf. assets/main/app.js). Vérifié manuellement par un·e admin
            // sur /admin/{wish}/{slug}/valid, pas de validation automatique
            // (pas d'API HelloAsso disponible pour vérifier en direct).
            ->add('memberCardNumber', TextType::class, $this->getConfiguration('register.member_card_number', 'register.member_card_number_placeholder', ['required' => false]))
            ->add('gender', ChoiceType::class, $this->getConfiguration('Your gender', '(sorry for that)', ['choices' => ['Male' => 'male', 'Female' => 'female', 'Still looking for...' => 'unknown']]))
            ->add('firstName', TextType::class, $this->getConfiguration('First Name', 'Your first name'))
            ->add('lastName', TextType::class, $this->getConfiguration('Last Name', 'Your last name'))
            ->add('nickname', TextType::class, $this->getConfiguration('Nickname', "choose your artist's name"))
            ->add('email', EmailType::class, $this->getConfiguration('Email', 'Your email address'))
            ->add('instrument', EntityType::class, ['class' => Instrument::class, 'choice_label' => 'title'])
            ->add('hash', PasswordType::class, $this->getConfiguration('Password', 'Choose a strong one'))
            ->add('passwordConfirm', PasswordType::class, $this->getConfiguration('Confirm Password', 'Please confirm your password'))
            ->add('country', CountryType::class, $this->getConfiguration('Your country', 'choose your counrty'))
            ->add('city', TextType::class, $this->getConfiguration('Your city', 'The city you live in, bro !'))
            ->add('birth', DateType::class, $this->getConfiguration('The date of your birth', 'To whish your Birthday, of course !!', ['widget' => 'choice', 'format' => 'dd-MM-yyyy', 'years' => range('1940', '2015')]))
            ->add('wish', ChoiceType::class, $this->getConfiguration('You want to be', 'the place you have in the team', ['choices' => ['A real Binioufous !!' => 'Binioufous', 'A Binioufous, but I cannot come to the rehearsals...' => 'Member', 'A big Fan !!' => 'Simple']]))
            ->add('picture', FileType::class, [
                'label' => 'A picture of you (jpeg)',

                // unmapped means that this field is not associated to any entity property
                'mapped' => false,

                // make it optional so you don't have to re-upload the PDF file
                // every time you edit the Product details
                'required' => false,

                // unmapped fields can't define their validation using annotations
                // in the associated entity, so you can use the PHP constraint classes
                'constraints' => [
                    new File([
                        'maxSize' => '5000k',
                        'mimeTypes' => [
                            'image/jpeg',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid JPEG document',
                    ]),
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
