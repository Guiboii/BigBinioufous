<?php

namespace App\Form;

use App\Entity\StorySection;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StorySectionType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Textarea brute par défaut, transformée en éditeur Markdown avec
        // aperçu live par assets/desk/story-admin.js (EasyMDE) : reste
        // utilisable même si le JS ne charge pas pour une raison ou une autre.
        $contentConfig = $this->getConfiguration('story_admin.field_content', 'story_admin.field_content_placeholder');
        $contentConfig['attr'] = array_merge($contentConfig['attr'], ['id' => 'story-section-content', 'rows' => 16]);

        $builder
            ->add('title', TextType::class, $this->getConfiguration('story_admin.field_title', 'story_admin.field_title_placeholder'))
            ->add('content', TextareaType::class, $contentConfig)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => StorySection::class,
        ]);
    }
}
