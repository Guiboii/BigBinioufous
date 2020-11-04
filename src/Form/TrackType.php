<?php

namespace App\Form;

use App\Entity\Track;
use App\Entity\Artist;
use App\Form\ApplicationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class TrackType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, $this->getConfiguration("Title", "title of the file"))
            ->add('minutes', ChoiceType::class, [
                'choices' => [
                    '0'=>0,'1'=>1, '2'=>2, '3'=>3, '4'=>4, '5'=>5, '6'=>6, '7'=>7, '8'=>8, '9'=>9, '10'=>10, '11'=>11, '12'=>12, '13'=>13, '14'=>14, '15'=>15]
                ], $this->getConfiguration("Minutes", ""))
            ->add('seconds', ChoiceType::class, [
                'choices' => [
                    '0'=>0,'1'=>1, '2'=>2, '3'=>3, '4'=>4, '5'=>5, '6'=>6, '7'=>7, '8'=>8, '9'=>9, '10'=>10, '11'=>11, '12'=>12, '13'=>13, '14'=>14, '15'=>15, '16'=>16, '17'=>17, '18'=>18, '19'=>19, '20'=>20, '21'=>21, '22'=>22, '23' =>23, '24'=>24, '25'=>25, '26'=>26, '27'=>27, '28'=>28, '29'=>29, '30'=>30, '31'=>31, '32'=>32, '33'=>33, '34'=>34, '35'=>35, '36'=>36, '37'=>37, '38'=>38, '39'=>39, '40'=>40, '41'=>41, '42'=>42, '43'=>43, '44'=>44, '45'=>45, '46'=>46, '47'=>47, '48'=>48, '49'=>49, '50'=>50, '51'=>51, '52'=>52, '53'=>53, '54'=>54, '55'=>55, '56'=>56, '57'=>57, '58'=>58, '59'=>59]
                ], $this->getConfiguration("Seconds", ""))
            ->add('artist',
            			EntityType::class, [
							'class' => Artist::class,
							'choice_label' => 'name'            			
            			]
            			)
            ->add('file', FileType::class, [
                'label' => 'track (mp3 file)',
                'mapped' => false,
                'required' => true,
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
