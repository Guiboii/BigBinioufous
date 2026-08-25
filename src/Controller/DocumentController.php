<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Folder;
use App\Repository\FolderRepository;
use App\Security\FolderWriteVoter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

// Upload, trash, restore, purge, move, and per-user toggles (favorite/played) on Document.
#[Route('/desk/files/{space}/documents', requirements: ['space' => 'music|admin|accounting|other'])]
class DocumentController extends AbstractController
{
    // Drag-and-drop upload: one file becomes one Document, named after the file itself. The optional "path" field comes from dropping a whole folder (FileSystemEntry.fullPath) and rebuilds the same Folder tree instead of flattening everything to the root.
    #[Route('', name: 'document_upload', methods: ['POST'])]
    public function upload(string $space, Request $request, EntityManagerInterface $manager, SluggerInterface $slugger, FolderRepository $folderRepository, LoggerInterface $logger): JsonResponse
    {
        $this->denyAccessUnlessGranted(FolderWriteVoter::WRITE, $space);

        if (!$this->isCsrfTokenValid('quick_upload', $request->request->get('_token'))) {
            return $this->json(['error' => 'invalid_token'], 403);
        }

        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'invalid_input'], 400);
        }

        // Without this check, a file over upload_max_filesize/post_max_size still reaches here (PHP fills $_FILES with an error code rather than omitting it), and getMimeType()/move() below would target a missing temp file and throw an unhandled exception.
        if (!$file->isValid()) {
            $logger->warning('Upload refusé : fichier trop volumineux', ['space' => $space, 'user' => $this->getUser()?->getUserIdentifier()]);

            return $this->json(['error' => 'file_too_large'], 400);
        }

        // Captured before move(): File::move() returns a new object rather than mutating $file, so a later call to getMimeType()/getSize() would target the already-moved temp file.
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        if (!\in_array($mimeType, Folder::ALLOWED_MIME_TYPES[$space] ?? [], true)) {
            $logger->warning('Upload refusé : type de fichier non autorisé', ['space' => $space, 'mimeType' => $mimeType, 'user' => $this->getUser()?->getUserIdentifier()]);

            return $this->json(['error' => 'invalid_mimetype'], 400);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->getParameter('documents_directory'), $newFilename);
        } catch (FileException $e) {
            $logger->error('Échec de l\'upload : '.$e->getMessage(), ['space' => $space, 'user' => $this->getUser()?->getUserIdentifier()]);

            return $this->json(['error' => 'upload_failed'], 500);
        }

        $folder = $folderRepository->findOrCreateByPath($space, (string) $request->request->get('path'), $manager);

        $document = new Document();
        $document->setName($originalFilename)
            ->setFilename($newFilename)
            ->setMimeType($mimeType)
            ->setSize($size)
            ->setUploadedBy($this->getUser())
            ->setFolder($folder);

        $manager->persist($document);
        $manager->flush();

        $logger->info('Fichier uploadé', ['space' => $space, 'document' => $document->getId(), 'user' => $this->getUser()?->getUserIdentifier()]);

        return $this->json(['success' => true, 'id' => $document->getId()]);
    }

    // Moves a document to the trash; see purge() below for permanent deletion.
    #[Route('/{id}', name: 'document_delete', methods: ['DELETE'])]
    public function delete(string $space, Document $document, Request $request, EntityManagerInterface $manager): Response
    {
        $this->denyAccessUnlessGranted(FolderWriteVoter::WRITE, $space);

        if ($document->getFolder()->getSpace() !== $space) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete_document'.$document->getId(), $request->request->get('_token'))) {
            $document->setDeletedAt(new \DateTime());
            $manager->flush();

            $this->addFlash('success', 'Document déplacé dans la corbeille');
        }

        return $this->redirectToRoute('desk_files', array_merge(['space' => $space], $request->query->all()));
    }

    // Restores a trashed document. If its folder (or an ancestor) is also trashed, the document goes back to the space's root instead of staying invisible.
    #[Route('/{id}/restore', name: 'document_restore', methods: ['POST'])]
    public function restore(string $space, Document $document, Request $request, EntityManagerInterface $manager, FolderRepository $folderRepository): Response
    {
        $this->denyAccessUnlessGranted(FolderWriteVoter::WRITE, $space);

        if ($document->getFolder()->getSpace() !== $space) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('restore_document'.$document->getId(), $request->request->get('_token'))) {
            $folder = $document->getFolder();
            if ($folder->isDeleted() || $folderRepository->hasDeletedAncestor($folder)) {
                $document->setFolder($folderRepository->findOrCreateRoot($space, $manager));
            }

            $document->setDeletedAt(null);
            $manager->flush();

            $this->addFlash('success', 'Document restauré');
        }

        return $this->redirectToRoute('desk_files_trash', ['space' => $space]);
    }

    // Permanent deletion: the only place that actually removes the physical file from disk, and only after the database row is confirmed removed.
    #[Route('/{id}/purge', name: 'document_purge', methods: ['DELETE'])]
    public function purge(string $space, Document $document, Request $request, EntityManagerInterface $manager): Response
    {
        $this->denyAccessUnlessGranted(FolderWriteVoter::WRITE, $space);

        if ($document->getFolder()->getSpace() !== $space) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('purge_document'.$document->getId(), $request->request->get('_token'))) {
            $filename = $document->getFilename();

            $manager->remove($document);
            $manager->flush();

            $path = $this->getParameter('documents_directory').'/'.$filename;
            if (is_file($path)) {
                unlink($path);
            }

            $this->addFlash('success', 'Document supprimé définitivement');
        }

        return $this->redirectToRoute('desk_files_trash', ['space' => $space]);
    }

    // Stars/unstars a document for the logged-in member. Open to anyone who can read the space: it's a personal preference, not a write on the file itself.
    #[Route('/{id}/favorite', name: 'document_favorite_toggle', methods: ['POST'])]
    public function toggleFavorite(string $space, Document $document, Request $request, EntityManagerInterface $manager): Response
    {
        if ($document->getFolder()->getSpace() !== $space) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('toggle_favorite'.$document->getId(), $request->request->get('_token'))) {
            $user = $this->getUser();
            if ($document->getFavoritedBy()->contains($user)) {
                $document->removeFavoritedBy($user);
            } else {
                $document->addFavoritedBy($user);
            }
            $manager->flush();
        }

        return $this->redirectToRoute('desk_files', array_merge(['space' => $space], $request->query->all()));
    }

    // "I play this part": same access rule as toggleFavorite above. Only used from /music, hence redirecting back to the referer rather than desk_files.
    #[Route('/{id}/played', name: 'document_played_toggle', methods: ['POST'])]
    public function togglePlayed(string $space, Document $document, Request $request, EntityManagerInterface $manager): Response
    {
        if ($document->getFolder()->getSpace() !== $space) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('toggle_played'.$document->getId(), $request->request->get('_token'))) {
            $user = $this->getUser();
            if ($document->getPlayedBy()->contains($user)) {
                $document->removePlayedBy($user);
            } else {
                $document->addPlayedBy($user);

                // Instrument is optional on the profile, but flagging a part without it leaves the info incomplete.
                if (!$user->getInstrument()) {
                    $this->addFlash(
                        'info',
                        'N\'oublie pas de renseigner ton instrument sur ton profil, pour qu\'on sache qui joue quoi !'
                    );
                }
            }
            $manager->flush();
        }

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('music'));
    }

    // Moves a document to another folder in the same space. No cycle risk here unlike FolderController::move(), a document has no descendants.
    #[Route('/{id}/move', name: 'document_move', methods: ['POST'])]
    public function move(string $space, Document $document, Request $request, EntityManagerInterface $manager, FolderRepository $folderRepository): Response
    {
        $this->denyAccessUnlessGranted(FolderWriteVoter::WRITE, $space);

        if ($document->getFolder()->getSpace() !== $space) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('move_document'.$document->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('desk_files', ['space' => $space]);
        }

        $targetId = $request->request->get('target');
        $target = $targetId ? $folderRepository->find($targetId) : $folderRepository->findOrCreateRoot($space, $manager);

        if (!$target || $target->getSpace() !== $space || $target->isDeleted()) {
            throw $this->createNotFoundException();
        }

        $document->setFolder($target);
        $manager->flush();

        $this->addFlash('success', 'Document déplacé');

        return $this->redirectToRoute('desk_files', ['space' => $space, 'folder' => $target->getId()]);
    }
}
