<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;

class AccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username')
            ->add('gender', ChoiceType::class, ['choices' => ['Male' => 'male', 'Female' => 'female', 'Still looking for...' => 'unknown']])
            ->add('firstName')
            ->add('lastName')
            ->add('birth', DateType::class, ['format' => 'dd-MM-yyyy', 'years' => range('1940', '2015')])
            ->add('email')
            ->add('city')
            ->add('country', CountryType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
