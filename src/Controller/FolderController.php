<?php

namespace App\Controller;

use App\Entity\Folder;
use App\Repository\FolderRepository;
use App\Security\FolderWriteVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Create, trash, restore, purge, and move on Folder.
#[Route('/desk/files/{space}/folders', requirements: ['space' => 'music|admin|accounting|other'])]
class FolderController extends AbstractController
{
    // Creates an empty subfolder. Rejects a duplicate name rather than silently merging, unlike the path-based auto-creation used for drag-and-drop uploads.
    #[Route('', name: 'folder_create', methods: ['POST'])]
    public function create(string $space, Request $request, EntityManagerInterface $manager, FolderRepository $folderRepository): Response
    {
        $this->denyAccessUnlessGranted(FolderWriteVoter::WRITE, $space);

        $parentId = $request->request->get('parent');
        $parent = $parentId ? $folderRepository->find($parentId) : $folderRepository->findOrCreateRoot($space, $manager);

        if (!$parent || $parent->getSpace() !== $space) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('create_folder'.$parent->getId(), $request->request->get('_token'))) {
            $name = trim((string) $request->request->get('name'));

            if ('' === $name) {
                $this->addFlash('error', 'Le nom du dossier ne peut pas être vide');
            } elseif ($folderRepository->findOneBy(['parent' => $parent, 'name' => $name, 'deletedAt' => null])) {
                $this->addFlash('error', 'Un dossier « '.$name.' » existe déjà ici');
            } else {
                $folder = new Folder();
                $folder->setName($name)->setParent($parent)->setSpace($space);
                $manager->persist($folder);
                $manager->flush();

                $this->addFlash('success', 'Dossier créé');
            }
        }

        return $this->redirectToRoute('desk_files', ['space' => $space, 'folder' => $parent->getId()]);
    }

    // Moves a folder to the trash, non-recursively: children are untouched so a restore brings the whole subtree back at once. See purge() for permanent deletion.
    #[Route('/{id}', name: 'folder_delete', methods: ['DELETE'])]
    public function delete(string $space, Folder $folder, Request $request, EntityManagerInterface $manager): Response
    {
        $this->denyAccessUnlessGranted(FolderWriteVoter::WRITE, $space);

        if ($folder->getSpace() !== $space) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete_folder'.$folder->getId(), $request->request->get('_token'))) {
            $folder->setDeletedAt(new \DateTime());
            $manager->flush();

            $this->addFlash('success', 'Dossier déplacé dans la corbeille');
        }

        return $this->redirectToRoute('desk_files', array_merge(['space' => $space], $request->query->all()));
    }

    // Restores a trashed folder. If its parent is still trashed, it goes back to the space's root instead of staying a hidden orphan.
    #[Route('/{id}/restore', name: 'folder_restore', methods: ['POST'])]
    public function restore(string $space, Folder $folder, Request $request, EntityManagerInterface $manager, FolderRepository $folderRepository): Response
    {
        $this->denyAccessUnlessGranted(FolderWriteVoter::WRITE, $space);

        if ($folder->getSpace() !== $space) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('restore_folder'.$folder->getId(), $request->request->get('_token'))) {
            if ($folderRepository->hasDeletedAncestor($folder)) {
                $folder->setParent($folderRepository->findOrCreateRoot($space, $manager));
            }

            $folder->setDeletedAt(null);
            $manager->flush();

            $this->addFlash('success', 'Dossier restauré');
        }

        return $this->redirectToRoute('desk_files_trash', ['space' => $space]);
    }

    // Permanent deletion of a folder and everything under it. Doctrine cascade removes the rows; physical files are removed only after that flush is confirmed.
    #[Route('/{id}/purge', name: 'folder_purge', methods: ['DELETE'])]
    public function purge(string $space, Folder $folder, Request $request, EntityManagerInterface $manager): Response
    {
        $this->denyAccessUnlessGranted(FolderWriteVoter::WRITE, $space);

        if ($folder->getSpace() !== $space) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('purge_folder'.$folder->getId(), $request->request->get('_token'))) {
            $filenames = $this->collectDescendantFilenames($folder);

            $manager->remove($folder);
            $manager->flush();

            foreach ($filenames as $filename) {
                $path = $this->getParameter('documents_directory').'/'.$filename;
                if (is_file($path)) {
                    unlink($path);
                }
            }

            $this->addFlash('success', 'Dossier supprimé définitivement');
        }

        return $this->redirectToRoute('desk_files_trash', ['space' => $space]);
    }

    /**
     * Physical filenames of every document under this folder, recursively, collected before Doctrine's remove() erases the rows.
     *
     * @return string[]
     */
    private function collectDescendantFilenames(Folder $folder): array
    {
        $filenames = [];

        foreach ($folder->getDocuments() as $document) {
            $filenames[] = $document->getFilename();
        }

        foreach ($folder->getChildren() as $child) {
            $filenames = array_merge($filenames, $this->collectDescendantFilenames($child));
        }

        return $filenames;
    }

    // Moves a folder (and its contents) into another folder of the same space. Rejects a move into itself or one of its own descendants.
    #[Route('/{id}/move', name: 'folder_move', methods: ['POST'])]
    public function move(string $space, Folder $folder, Request $request, EntityManagerInterface $manager, FolderRepository $folderRepository): Response
    {
        $this->denyAccessUnlessGranted(FolderWriteVoter::WRITE, $space);

        if ($folder->getSpace() !== $space) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('move_folder'.$folder->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('desk_files', ['space' => $space]);
        }

        $targetId = $request->request->get('target');
        $target = $targetId ? $folderRepository->find($targetId) : $folderRepository->findOrCreateRoot($space, $manager);

        if (!$target || $target->getSpace() !== $space || $target->isDeleted()) {
            throw $this->createNotFoundException();
        }

        if ($folderRepository->isSelfOrDescendantOf($target, $folder)) {
            $this->addFlash('error', 'Impossible de déplacer un dossier dans lui-même ou un de ses sous-dossiers');

            return $this->redirectToRoute('desk_files', ['space' => $space, 'folder' => $folder->getId()]);
        }

        $folder->setParent($target);
        $manager->flush();

        $this->addFlash('success', 'Dossier déplacé');

        return $this->redirectToRoute('desk_files', ['space' => $space, 'folder' => $target->getId()]);
    }
}
