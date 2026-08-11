<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Folder;
use App\Repository\FolderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class DocumentController extends AbstractController
{
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'video/mp4',
        'video/quicktime',
        'video/webm',
        'video/x-msvideo',
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'audio/mpeg',
        'audio/mp3',
        'audio/mp4',
        'audio/x-m4a',
        'audio/wav',
        'audio/x-wav',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
    ];

    /**
     * Dépose glisser-déposer, cf. TrackController::quickUpload() pour le
     * même mécanisme côté mp3 : un fichier = un Document créé direct (nom
     * = nom de fichier), pas de métadonnées à saisir avant coup. Le champ
     * "path" (optionnel, ex. "02 Medley XXI/Hautbois Facile") vient du
     * glisser-déposer d'un dossier entier (assets/desk/quick-upload.js,
     * FileSystemEntry.fullPath) : reconstitue la même arborescence de
     * Folder côté site plutôt que de tout aplatir à la racine.
     */
    #[Route('/desk/music/documents', name: 'document_upload', methods: ['POST'])]
    public function upload(Request $request, EntityManagerInterface $manager, SluggerInterface $slugger, FolderRepository $folderRepository): JsonResponse
    {
        if (!$this->isCsrfTokenValid('quick_upload', $request->request->get('_token'))) {
            return $this->json(['error' => 'invalid_token'], 403);
        }

        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'invalid_input'], 400);
        }

        // Capturé avant move() : File::move() renvoie un nouvel objet et ne
        // modifie pas $file sur place, un 2e appel à $file->getMimeType()
        // après coup viserait le fichier temporaire déjà déplacé/disparu.
        $mimeType = $file->getMimeType();

        if (!\in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            return $this->json(['error' => 'invalid_mimetype'], 400);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->getParameter('documents_directory'), $newFilename);
        } catch (FileException $e) {
            return $this->json(['error' => 'upload_failed'], 500);
        }

        $folder = $this->resolveFolder((string) $request->request->get('path'), $manager, $folderRepository);

        $document = new Document();
        $document->setName($originalFilename)
            ->setFilename($newFilename)
            ->setMimeType($mimeType)
            ->setUploadedBy($this->getUser())
            ->setFolder($folder);

        $manager->persist($document);
        $manager->flush();

        return $this->json(['success' => true, 'id' => $document->getId()]);
    }

    /**
     * "02 Medley XXI/Hautbois Facile" -> crée/retrouve les 2 Folder imbriqués
     * et renvoie le plus profond. Vide -> null (racine).
     */
    private function resolveFolder(string $path, EntityManagerInterface $manager, FolderRepository $folderRepository): ?Folder
    {
        $segments = array_filter(explode('/', $path), static fn (string $s) => '' !== trim($s));

        $parent = null;
        foreach ($segments as $name) {
            $name = trim($name);
            $existing = $folderRepository->findOneBy(['parent' => $parent, 'name' => $name]);
            if ($existing) {
                $parent = $existing;
                continue;
            }

            $folder = new Folder();
            $folder->setName($name)->setParent($parent);
            $manager->persist($folder);
            $manager->flush();
            $parent = $folder;
        }

        return $parent;
    }

    #[Route('/desk/music/documents/{id}', name: 'document_delete', methods: ['DELETE'])]
    public function delete(Document $document, Request $request, EntityManagerInterface $manager): Response
    {
        if ($this->isCsrfTokenValid('delete_document'.$document->getId(), $request->request->get('_token'))) {
            $manager->remove($document);
            $manager->flush();

            $this->addFlash('success', 'Document supprimé');
        }

        return $this->redirectToRoute('deskmusic', $request->query->all());
    }

    /**
     * Étoile/désétoile un document audio pour le membre connecté. Ouvert à
     * tout ROLE_USER (pas juste ROLE_BINIOUFOUS) : c'est une préférence
     * personnelle, pas une action d'écriture sur le fichier lui-même, même
     * logique que DeskController::toggleVoice().
     */
    #[Route('/desk/music/documents/{id}/favorite', name: 'document_favorite_toggle', methods: ['POST'])]
    public function toggleFavorite(Document $document, Request $request, EntityManagerInterface $manager): Response
    {
        if ($this->isCsrfTokenValid('toggle_favorite'.$document->getId(), $request->request->get('_token'))) {
            $user = $this->getUser();
            if ($document->getFavoritedBy()->contains($user)) {
                $document->removeFavoritedBy($user);
            } else {
                $document->addFavoritedBy($user);
            }
            $manager->flush();
        }

        return $this->redirectToRoute('deskmusic', $request->query->all());
    }
}
