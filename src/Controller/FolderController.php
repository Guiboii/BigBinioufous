<?php

namespace App\Controller;

use App\Entity\Folder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FolderController extends AbstractController
{
    /**
     * Supprime un dossier et tout son contenu (sous-dossiers + documents en
     * cascade, cf. Folder::$children/$documents : cascade+orphanRemoval).
     * Les fichiers restent sur le disque (même choix que Track/Voice/
     * Document ailleurs dans le projet, rien ne nettoie public/uploads/).
     */
    #[Route('/desk/music/folders/{id}', name: 'folder_delete', methods: ['DELETE'])]
    public function delete(Folder $folder, Request $request, EntityManagerInterface $manager): Response
    {
        if ($this->isCsrfTokenValid('delete_folder'.$folder->getId(), $request->request->get('_token'))) {
            $manager->remove($folder);
            $manager->flush();

            $this->addFlash('success', 'Dossier supprimé');
        }

        return $this->redirectToRoute('deskmusic', $request->query->all());
    }
}
