<?php

namespace App\Controller;

use App\Entity\Folder;
use App\Repository\ArtistRepository;
use App\Repository\FolderRepository;
use App\Repository\SetlistItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// The public /music page: the setlist (title/artist/YouTube link) is visible to everyone. Binioufous/admins additionally get a link to the full /desk/files/music tree rather than a second render of it here.
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
