<?php

namespace App\Form;

use App\Entity\Artist;
use App\Entity\Track;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class TrackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('duration')
            ->add('artist',
            			EntityType::class, [
							'class' => Artist::class,
							'choice_label' => 'name'            			
            			]
            			)
            ->add('file', FileType::class, [
                'label' => 'track (mp3 file)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '200000k',
                        'mimeTypes' => [
                            'audio/mp3',
                            'audio/mpeg'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid MP3 document'
                    ])]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Track::class,
        ]);
    }
}
