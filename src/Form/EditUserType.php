<?php

namespace App\Form;

use App\Entity\Role;
use App\Entity\User;
use App\Entity\Instrument;
use App\Form\ApplicationType;
use App\Repository\RoleRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;

class EditUserType extends ApplicationType
{
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nickname', TextType::class, $this->getConfiguration("Your nickname", "Your artist's name"))
            ->add('gender', ChoiceType::class, ['choices' => ['Male' => 'male', 'Female' => 'female', 'Still looking for...' => 'unknown']], $this->getConfiguration("Your gender", "(sorry for that)"))
            ->add('firstName', TextType::class, $this->getConfiguration("First Name", "Your first name"))
            ->add('lastName', TextType::class, $this->getConfiguration("Last Name", "Your last name"))
            ->add('birth', DateType::class, ['format' => 'dd-MM-yyyy', 'years' => range('1940', '2015')], $this->getConfiguration("The date of your Birth", "To wish your Birhtday, of course"))
            ->add('email', EmailType::class, $this->getConfiguration("Email", "Your email address"))
            ->add('instrument', EntityType::class, ['class' => Instrument::class, 'choice_label' => 'title'])
            ->add('city', TextType::class, $this->getConfiguration("Your city", "The city you live in, bro !"))
            ->add('country', CountryType::class, $this->getConfiguration("Your country", "choose your country"))
            ->add('picture', FileType::class, [
                'label' => 'A picture of you (jpg)',

                // unmapped means that this field is not associated to any entity property
                'mapped' => false,

                // make it optional so you don't have to re-upload the PDF file
                // every time you edit the Product details
                'required' => false,

                // unmapped fields can't define their validation using annotations
                // in the associated entity, so you can use the PHP constraint classes
                'constraints' => [
                    new File(
                        [
                        'mimeTypes' => [
                            'image/jpeg',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid JPEG document',
                    ]
                    )
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
