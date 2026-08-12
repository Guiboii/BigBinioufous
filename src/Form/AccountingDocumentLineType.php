<?php

namespace App\Form;

use App\Entity\AccountingDocumentLine;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountingDocumentLineType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $unitPriceConfig = $this->getConfiguration('accounting.field_line_unit_price', '');
        $unitPriceConfig['attr']['min'] = 0;
        $unitPriceConfig['attr']['step'] = 0.01;

        $quantityConfig = $this->getConfiguration('accounting.field_line_quantity', '');
        $quantityConfig['attr']['min'] = 1;

        $builder
            ->add('label', TextType::class, $this->getConfiguration('accounting.field_line_label', 'accounting.field_line_label_placeholder'))
            ->add('unitPrice', NumberType::class, array_merge($unitPriceConfig, ['scale' => 2, 'html5' => true]))
            ->add('quantity', IntegerType::class, $quantityConfig)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AccountingDocumentLine::class,
        ]);
    }
}
