<?php

namespace App\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire de confirmation sur /admin/{wish}/{slug}/valid : plus de champ
 * ("Valider l'utilisateur ?") depuis le 2026-08-11, cf. AdminController::validUser().
 * Ce champ ne servait à rien de fiable : le rôle était accordé au submit
 * quelle que soit sa valeur, seul User::$validation en dépendait, et il
 * n'était pas coché par défaut. Un admin qui validait sans cocher (le cas
 * courant, rien ne l'incitait à cocher) obtenait un membre avec son rôle
 * mais display.validation resté à false : "en attente de validation"
 * affiché indéfiniment sur son /desk malgré un accès déjà complet, et
 * toujours listé comme en attente sur /admin/valid. Un seul bouton
 * "Valider" sans ambiguïté désormais (le refus a de toute façon sa propre
 * action dédiée, AdminController::refuseUser()).
 */
class ValidRoleType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
