<?php

namespace App\Controller;

use App\Entity\Folder;
use App\Repository\ArtistRepository;
use App\Repository\FolderRepository;
use App\Repository\SetlistItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Page publique /music : la setlist (titre/artiste/lien YouTube, cf.
 * SetlistItem) est visible par tout le monde. Les binioufous/admins ont en
 * plus un lien vers /desk/files/music (arbre de fichiers complet) plutôt
 * qu'un second rendu de l'arbre ici : remplace TrackController, fusionné
 * dans Folder/Document (cf. plan "Nettoyage de la gestion de
 * fichiers/dossiers").
 */
class MusicController extends AbstractController
{
    #[Route('/music', name: 'music', methods: ['GET'])]
    public function index(SetlistItemRepository $setlistItemRepository, ArtistRepository $artistRepository, FolderRepository $folderRepository): Response
    {
        return $this->render('music/index.html.twig', [
            'items' => $setlistItemRepository->findAllOrdered(),
            'artists' => $artistRepository->findAll(),
            'musicFolders' => $folderRepository->findTopLevel(Folder::SPACE_MUSIC),
        ]);
    }
}
