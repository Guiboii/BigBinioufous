<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Entity\Folder;
use App\Entity\SetlistItem;
use App\Repository\ArtistRepository;
use App\Repository\FolderRepository;
use App\Repository\SetlistItemRepository;
use App\Security\FolderWriteVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Setlist affichée sur /music (titre/artiste/lien YouTube, réordonnable) :
 * écriture réservée aux binioufous/admins comme le reste de l'espace
 * musique (FolderWriteVoter, cf. Folder::WRITE_ROLES), lecture publique
 * gérée par MusicController::index() plutôt qu'ici.
 */
#[Route('/desk/files/music/setlist')]
class SetlistController extends AbstractController
{
    #[Route('', name: 'setlist_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $manager, FolderRepository $folderRepository, SetlistItemRepository $setlistItemRepository, ArtistRepository $artistRepository): Response
    {
        $this->denyAccessUnlessGranted(FolderWriteVoter::WRITE, Folder::SPACE_MUSIC);

        if (!$this->isCsrfTokenValid('create_setlist_item', $request->request->get('_token'))) {
            return $this->redirectToRoute('music');
        }

        $title = trim((string) $request->request->get('title'));
        if ('' === $title) {
            $this->addFlash('error', 'Le titre ne peut pas être vide');

            return $this->redirectToRoute('music');
        }

        $artist = $this->resolveArtist($request, $manager, $artistRepository);
        $folder = $this->resolveFolder($request, $folderRepository);

        $item = new SetlistItem();
        $item->setTitle($title)
            ->setArtist($artist)
            ->setYoutubeUrl($this->resolveYoutubeUrl($request))
            ->setPosition($setlistItemRepository->nextPosition())
            ->setFolder($folder);

        $manager->persist($item);
        $manager->flush();

        $this->addFlash('success', 'Morceau ajouté à la setlist');

        return $this->redirectToRoute('music');
    }

    #[Route('/{id}', name: 'setlist_edit', methods: ['POST'])]
    public function edit(SetlistItem $item, Request $request, EntityManagerInterface $manager, ArtistRepository $artistRepository, FolderRepository $folderRepository): Response
    {
        $this->denyAccessUnlessGranted(FolderWriteVoter::WRITE, Folder::SPACE_MUSIC);

        if (!$this->isCsrfTokenValid('edit_setlist_item'.$item->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('music');
        }

        $title = trim((string) $request->request->get('title'));
        if ('' === $title) {
            $this->addFlash('error', 'Le titre ne peut pas être vide');

            return $this->redirectToRoute('music');
        }

        $artist = $this->resolveArtist($request, $manager, $artistRepository);
        $folder = $this->resolveFolder($request, $folderRepository);

        $item->setTitle($title)
            ->setArtist($artist)
            ->setFolder($folder)
            ->setYoutubeUrl($this->resolveYoutubeUrl($request));

        $manager->flush();

        $this->addFlash('success', 'Morceau modifié');

        return $this->redirectToRoute('music');
    }

    /**
     * "new_artist" (texte libre, cf. templates/music/index.html.twig) a
     * priorité sur "artist" (id du select) : permet de créer un artiste à
     * la volée depuis le formulaire de la setlist plutôt que de dépendre
     * uniquement des fixtures. Réutilise un artiste existant du même nom
     * (insensible à la casse, cf. ArtistRepository::findOneByName()) au
     * lieu d'en dupliquer un.
     */
    private function resolveArtist(Request $request, EntityManagerInterface $manager, ArtistRepository $artistRepository): ?Artist
    {
        $newArtistName = trim((string) $request->request->get('new_artist'));
        if ('' !== $newArtistName) {
            $existing = $artistRepository->findOneByName($newArtistName);
            if ($existing) {
                return $existing;
            }

            $artist = new Artist();
            $artist->setName($newArtistName);
            $manager->persist($artist);

            return $artist;
        }

        if ($artistId = $request->request->get('artist')) {
            return $artistRepository->find($artistId);
        }

        return null;
    }

    /**
     * "folder" (id, select rempli par MusicController::index() via
     * FolderRepository::findTopLevel()) : lie le morceau à un dossier déjà
     * créé et déjà rempli depuis /desk/files/music, plutôt que d'en créer
     * un à la volée par un champ texte libre (source de doublons par
     * faute de frappe, retour utilisatrice le 2026-08-13, cf.
     * templates/music/index.html.twig). Vide/id invalide/dossier d'un
     * autre espace ou en corbeille -> pas de dossier lié.
     */
    private function resolveFolder(Request $request, FolderRepository $folderRepository): ?Folder
    {
        $folderId = $request->request->get('folder');
        if (!$folderId) {
            return null;
        }

        $folder = $folderRepository->find($folderId);
        if (!$folder || Folder::SPACE_MUSIC !== $folder->getSpace() || $folder->isDeleted()) {
            return null;
        }

        return $folder;
    }

    /**
     * Restreint aux liens YouTube en https (allowlist de domaine/schéma) :
     * champ libre auparavant, ça permettait de stocker n'importe quelle URL
     * (y compris javascript:...) affichée ensuite en `href` sur /music,
     * publique (XSS stockée, cf. /security-review du 2026-08-17). Lien
     * invalide -> pas de lien enregistré, plutôt qu'une erreur bloquante.
     */
    private function resolveYoutubeUrl(Request $request): ?string
    {
        $url = trim((string) $request->request->get('youtubeUrl'));
        if ('' === $url || !preg_match('#^https://(www\.)?(youtube\.com/watch\?v=|youtu\.be/)#', $url)) {
            return null;
        }

        return $url;
    }

    /**
     * Supprime l'entrée de setlist uniquement : le Folder et ses Document
     * restent (même choix que FolderController::delete()), accessibles
     * depuis /desk/files/music si besoin de les récupérer/réassigner.
     */
    #[Route('/{id}', name: 'setlist_delete', methods: ['DELETE'])]
    public function delete(SetlistItem $item, Request $request, EntityManagerInterface $manager): Response
    {
        $this->denyAccessUnlessGranted(FolderWriteVoter::WRITE, Folder::SPACE_MUSIC);

        if ($this->isCsrfTokenValid('delete_setlist_item'.$item->getId(), $request->request->get('_token'))) {
            $manager->remove($item);
            $manager->flush();

            $this->addFlash('success', 'Morceau retiré de la setlist');
        }

        return $this->redirectToRoute('music');
    }

    #[Route('/{id}/move-up', name: 'setlist_move_up', methods: ['POST'])]
    public function moveUp(SetlistItem $item, Request $request, EntityManagerInterface $manager, SetlistItemRepository $setlistItemRepository): Response
    {
        return $this->swapWithNeighbor($item, $request, $manager, $setlistItemRepository, -1);
    }

    #[Route('/{id}/move-down', name: 'setlist_move_down', methods: ['POST'])]
    public function moveDown(SetlistItem $item, Request $request, EntityManagerInterface $manager, SetlistItemRepository $setlistItemRepository): Response
    {
        return $this->swapWithNeighbor($item, $request, $manager, $setlistItemRepository, 1);
    }

    /**
     * Échange la position avec le voisin immédiat (précédent si $direction
     * < 0, suivant sinon) : pas de champ "position" à ressaisir, deux
     * boutons suffisent pour l'ordre de la setlist (todoMusique : "monter/
     * descendre les musiques").
     */
    private function swapWithNeighbor(SetlistItem $item, Request $request, EntityManagerInterface $manager, SetlistItemRepository $setlistItemRepository, int $direction): Response
    {
        $this->denyAccessUnlessGranted(FolderWriteVoter::WRITE, Folder::SPACE_MUSIC);

        if (!$this->isCsrfTokenValid('move_setlist_item'.$item->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('music');
        }

        $items = $setlistItemRepository->findAllOrdered();
        $index = array_search($item, $items, true);
        $neighborIndex = false === $index ? null : $index + $direction;

        if (null !== $neighborIndex && isset($items[$neighborIndex])) {
            $neighbor = $items[$neighborIndex];
            $position = $item->getPosition();
            $item->setPosition($neighbor->getPosition());
            $neighbor->setPosition($position);
            $manager->flush();
        }

        return $this->redirectToRoute('music');
    }
}
