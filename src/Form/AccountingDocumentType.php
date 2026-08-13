<?php

namespace App\Form;

use App\Entity\AccountingDocument;
use App\Entity\Client;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type/numéro ne sont pas dans ce formulaire : le type est choisi via la
 * route (accounting_document_new/quote ou /invoice), le numéro est
 * attribué par AccountingDocumentRepository::findNextNumber() côté
 * contrôleur, aucun des deux n'a de raison d'être modifié après coup.
 */
class AccountingDocumentType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date', DateType::class, array_merge(
                $this->getConfiguration('accounting.field_date', ''),
                ['widget' => 'single_text']
            ))
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'name',
                'label' => $this->trans('accounting.field_client'),
                'required' => false,
                'placeholder' => 'accounting.field_client_placeholder',
                /*
                 * clientName/clientAddress/clientContact restent les champs
                 * réellement soumis (cf. AccountingDocument) : ce select ne
                 * sert qu'à les préremplir côté client (assets/accounting/
                 * client-autofill.js), d'où ces attributs data plutôt qu'un
                 * mapping recalculé côté serveur au submit.
                 */
                'choice_attr' => fn (Client $client) => [
                    'data-address' => $client->getAddress(),
                    'data-contact' => $client->getContact(),
                ],
            ])
            ->add('clientName', TextType::class, $this->getConfiguration('accounting.field_client_name', 'accounting.field_client_name_placeholder'))
            ->add('clientAddress', TextareaType::class, $this->getConfiguration('accounting.field_client_address', 'accounting.field_client_address_placeholder'))
            ->add('clientContact', TextType::class, array_merge(
                $this->getConfiguration('accounting.field_client_contact', 'accounting.field_client_contact_placeholder'),
                ['required' => false]
            ))
            ->add('correspondentName', TextType::class, $this->getConfiguration('accounting.field_correspondent_name', ''))
            ->add('correspondentEmail', TextType::class, array_merge(
                $this->getConfiguration('accounting.field_correspondent_email', ''),
                ['required' => false]
            ))
            ->add('correspondentPhone', TextType::class, array_merge(
                $this->getConfiguration('accounting.field_correspondent_phone', ''),
                ['required' => false]
            ))
            ->add('lines', CollectionType::class, [
                'entry_type' => AccountingDocumentLineType::class,
                'label' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'prototype_name' => '__line__',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AccountingDocument::class,
        ]);
    }
}
