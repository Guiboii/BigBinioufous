<?php

namespace App\Form;

use App\Entity\Voice;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class VoiceType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, $this->getConfiguration('music.voice_name', 'music.voice_name_placeholder'))
            ->add('file', FileType::class, [
                'label' => $this->trans('music.voice_file'),
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '200000k',
                        'mimeTypes' => [
                            'audio/mp3',
                            'audio/mpeg',
                        ],
                        'mimeTypesMessage' => $this->trans('music.invalid_mp3'),
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Voice::class,
        ]);
    }
}
