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
        // Pas d'id forcé dans attr : le widget_attributes de Bootstrap 4
        // imprime déjà "id" à partir de vars.id, un attr.id en plus aurait
        // juste dupliqué l'attribut HTML sans l'écraser. story-admin.js
        // cible donc l'id auto-généré par Symfony (story_section_content,
        // stable entre new/edit : dérivé du nom du FormType, pas de l'id
        // de l'entité).
        $contentConfig = $this->getConfiguration('story_admin.field_content', 'story_admin.field_content_placeholder');
        $contentConfig['attr'] = array_merge($contentConfig['attr'], ['rows' => 16]);

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
