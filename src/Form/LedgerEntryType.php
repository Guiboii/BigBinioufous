<?php

namespace App\Form;

use App\Entity\LedgerEntry;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LedgerEntryType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $amountConfig = $this->getConfiguration('accounting.field_entry_amount', '');
        $amountConfig['attr']['min'] = 0.01;
        $amountConfig['attr']['step'] = 0.01;

        $builder
            ->add('date', DateType::class, array_merge(
                $this->getConfiguration('accounting.field_date', ''),
                ['widget' => 'single_text']
            ))
            ->add('type', ChoiceType::class, array_merge(
                $this->getConfiguration('accounting.field_entry_type', ''),
                ['choices' => [
                    $this->trans('accounting.entry_type_income') => LedgerEntry::TYPE_INCOME,
                    $this->trans('accounting.entry_type_expense') => LedgerEntry::TYPE_EXPENSE,
                ]]
            ))
            ->add('label', TextType::class, $this->getConfiguration('accounting.field_entry_label', 'accounting.field_entry_label_placeholder'))
            ->add('amount', NumberType::class, array_merge($amountConfig, ['scale' => 2, 'html5' => true]))
            ->add('category', TextType::class, array_merge(
                $this->getConfiguration('accounting.field_entry_category', 'accounting.field_entry_category_placeholder'),
                ['required' => false]
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LedgerEntry::class,
        ]);
    }
}
