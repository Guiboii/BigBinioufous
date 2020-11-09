<?php

namespace App\Form;

use App\Entity\User;
use App\Form\ApplicationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class RegistrationType extends ApplicationType
{
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('gender', ChoiceType::class, ['choices' => ['Male' => 'male', 'Female' => 'female', 'Still looking for...' => 'unknown']], $this->getConfiguration("Your gender", "(sorry for that)"))
            ->add('firstName', TextType::class, $this->getConfiguration("First Name", "Your first name"))
            ->add('lastName', TextType::class, $this->getConfiguration("Last Name", "Your last name"))
            ->add('username', TextType::class, $this->getConfiguration("Username", "choose your username"))
            ->add('email', EmailType::class, $this->getConfiguration("Email", "Your email address"))
            ->add('hash', PasswordType::class, $this->getConfiguration("Password", "Choose a strong one"))
            ->add('passwordConfirm', PasswordType::class, $this->getConfiguration("Confirm Password", "Please confirm your password"))
            ->add('country', CountryType::class, $this->getConfiguration("Your country", "choose your counrty"))
            ->add('city', TextType::class, $this->getConfiguration("Your city", "The city you live in, bro !"))
            ->add('birth', DateType::class, ['format' => 'dd-MM-yyyy', 'years' => range('1940', '2015')], $this->getConfiguration("The date of your birth", "To whish your Birthday, of course !!"))
            ->add('wish', ChoiceType::class,['choices' => ['An administrator' => 'admin', 'A real Binioufous !!' => 'binioufous', 'A Binioufous, but I cannot come to the rehearsals...' => 'member', 'A big Fan !!' => 'simple']], $this->getConfiguration("You want to be", "the place you have in the team"))
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
                    ])
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
