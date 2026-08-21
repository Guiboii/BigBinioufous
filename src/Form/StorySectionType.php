<?php

namespace App\Form;

use App\Entity\StorySection;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

// Form for creating/editing a StorySection.
class StorySectionType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Plain textarea by default, upgraded to a Markdown editor with live preview by story-admin.js (EasyMDE), still usable if the JS fails to load. No forced id here: story-admin.js targets Symfony's auto-generated id (story_section_content), stable between new/edit since it's derived from the form type name, not the entity id.
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
