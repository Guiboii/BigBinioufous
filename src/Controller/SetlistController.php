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

        $folder = $folderRepository->findOrCreateByPath(Folder::SPACE_MUSIC, (string) $request->request->get('path'), $manager);

        $item = new SetlistItem();
        $item->setTitle($title)
            ->setArtist($artist)
            ->setYoutubeUrl('' !== (string) $request->request->get('youtubeUrl') ? $request->request->get('youtubeUrl') : null)
            ->setPosition($setlistItemRepository->nextPosition())
            ->setFolder($folder);

        $manager->persist($item);
        $manager->flush();

        $this->addFlash('success', 'Morceau ajouté à la setlist');

        return $this->redirectToRoute('music');
    }

    #[Route('/{id}', name: 'setlist_edit', methods: ['POST'])]
    public function edit(SetlistItem $item, Request $request, EntityManagerInterface $manager, ArtistRepository $artistRepository): Response
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

        $item->setTitle($title)
            ->setArtist($artist)
            ->setYoutubeUrl('' !== (string) $request->request->get('youtubeUrl') ? $request->request->get('youtubeUrl') : null);

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
