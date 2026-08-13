<?php

namespace App\Form;

use App\Entity\Note;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NoteType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Textarea brute par défaut, transformée en éditeur Markdown avec
        // aperçu live par assets/desk/note-admin.js (EasyMDE), même
        // fabrique que StorySectionType (assets/desk/markdown-editor.js).
        $contentConfig = $this->getConfiguration('note.field_content', 'note.field_content_placeholder');
        $contentConfig['attr'] = array_merge($contentConfig['attr'], ['rows' => 16]);

        $builder
            ->add('title', TextType::class, $this->getConfiguration('note.field_title', 'note.field_title_placeholder'))
            ->add('content', TextareaType::class, $contentConfig)
            ->add('shared', CheckboxType::class, [
                'label' => $this->trans('note.field_shared'),
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Note::class,
        ]);
    }
}
