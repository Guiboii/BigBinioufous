<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EventType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, $this->getConfiguration('event.field_title', 'event.field_title_placeholder'))
            ->add('location', TextType::class, $this->getConfiguration('event.field_location', 'event.field_location_placeholder'))
            ->add('type', ChoiceType::class, array_merge(
                $this->getConfiguration('event.field_type', ''),
                ['choices' => [
                    $this->trans('event.type_rehearsal') => 'rehearsal',
                    $this->trans('event.type_concert') => 'concert',
                    $this->trans('event.type_other') => 'other',
                ]]
            ))
            ->add('date', DateTimeType::class, array_merge(
                $this->getConfiguration('event.field_date', ''),
                ['widget' => 'single_text']
            ))
            ->add('endDate', DateTimeType::class, array_merge(
                $this->getConfiguration('event.field_end_date', ''),
                ['widget' => 'single_text', 'required' => false]
            ))
            ->add('description', TextareaType::class, array_merge(
                $this->getConfiguration('event.field_description', 'event.field_description_placeholder'),
                ['required' => false]
            ))
            ->add('poster', FileType::class, [
                'label' => $this->trans('event.field_poster'),
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5000k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => $this->trans('event.poster_invalid_type'),
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
