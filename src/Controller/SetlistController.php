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

// Manages the setlist shown on /music (add/edit/delete/reorder). Write access is reserved to binioufous/admins like the rest of the music space; public read is handled by MusicController::index() instead.
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

    // "new_artist" (free text) takes priority over "artist" (select id), letting the form create an artist on the fly. Reuses an existing artist with the same name (case-insensitive) instead of duplicating it.
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

    // Links the song to a folder already created under /desk/files/music (picked from a select) rather than creating one from free text, which was prone to typo duplicates. Missing/invalid/wrong-space/trashed folder simply means no link.
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

    // Restricted to https YouTube links (domain/scheme allowlist): a free-text field previously allowed storing any URL, including javascript:..., later rendered as an href on the public /music page (stored XSS). An invalid link is simply dropped rather than blocking the save.
    private function resolveYoutubeUrl(Request $request): ?string
    {
        $url = trim((string) $request->request->get('youtubeUrl'));
        if ('' === $url || !preg_match('#^https://(www\.)?(youtube\.com/watch\?v=|youtu\.be/)#', $url)) {
            return null;
        }

        return $url;
    }

    // Removes the setlist entry only: the linked Folder and its Documents remain, reachable from /desk/files/music if needed.
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

    // Swaps position with the immediate neighbor (previous if $direction < 0, next otherwise): move up/down buttons rather than a position field to re-type.
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
