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

#[Route('/desk/files/{space}/folders', requirements: ['space' => 'music|admin|accounting|other'])]
class FolderController extends AbstractController
{
    /**
     * Crée un sous-dossier vide dans le dossier courant (formulaire dédié,
     * cf. templates/desk/files.html.twig) : sert notamment à préparer une
     * destination avant de déplacer des fichiers dedans (document_move/
     * folder_move). Refuse un doublon plutôt que de fusionner en silence
     * (contrairement à DocumentController::resolveFolder(), utilisé lui pour
     * reconstituer un chemin de glisser-déposer sans intervention explicite).
     */
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

    /**
     * Met un dossier à la corbeille (non récursif : cf. Folder::$deletedAt,
     * ses sous-dossiers/documents ne sont pas touchés, la restauration
     * ramène tout l'arbre d'un coup). Suppression définitive : voir
     * purge() ci-dessous.
     */
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

    /**
     * Sort un dossier de la corbeille. Si son parent (ou un ancêtre plus
     * lointain) est lui-même toujours supprimé, il repart à la racine de
     * l'espace plutôt que de rester un "orphelin caché" invisible
     * (cf. FolderRepository::hasDeletedAncestor()).
     */
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

    /**
     * Suppression définitive d'un dossier et de tout son contenu : seul
     * endroit du gestionnaire de fichiers qui touche vraiment au disque
     * (les fichiers physiques des documents descendants sont supprimés
     * après le flush Doctrine, une fois la suppression en base confirmée).
     * La cascade Doctrine (Folder::$children/$documents,
     * cascade+orphanRemoval) supprime déjà les lignes en base.
     */
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
     * @return string[] noms de fichiers physiques de tous les documents
     *                  sous ce dossier, récursivement, à récupérer avant
     *                  le remove() Doctrine qui les efface de la base
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

    /**
     * Déplace un dossier (et tout son contenu) vers un autre dossier du
     * même espace. Refuse un déplacement dans lui-même ou un de ses propres
     * descendants (cycle dans l'arbre), cf. FolderRepository::isSelfOrDescendantOf().
     */
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
