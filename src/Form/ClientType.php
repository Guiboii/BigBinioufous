<?php

namespace App\Form;

use App\Entity\Client;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, $this->getConfiguration('accounting.field_client_name', 'accounting.field_client_name_placeholder'))
            ->add('address', TextareaType::class, array_merge(
                $this->getConfiguration('accounting.field_client_address', 'accounting.field_client_address_placeholder'),
                ['required' => false]
            ))
            ->add('contact', TextType::class, array_merge(
                $this->getConfiguration('accounting.field_client_contact', 'accounting.field_client_contact_placeholder'),
                ['required' => false]
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
        ]);
    }
}
