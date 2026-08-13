<?php

namespace App\Controller;

use App\Repository\DocumentRepository;
use App\Repository\FolderRepository;
use App\Security\FolderWriteVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Actions groupées sur une sélection de dossiers/documents (multi-sélection,
 * cf. templates/desk/files.html.twig, formulaire "bulk-form"). Contrôleur
 * séparé de FolderController/DocumentController plutôt que d'y ajouter une
 * 2e logique : les routes individuelles existantes (folder_delete,
 * document_move...) restent inchangées, ceci n'est qu'un ajout qui les
 * appelle en boucle.
 */
#[Route('/desk/files/{space}/bulk', requirements: ['space' => 'music|admin|accounting|other'])]
class BulkActionController extends AbstractController
{
    /**
     * Met à la corbeille tous les dossiers/documents cochés (même soft
     * delete non récursif que FolderController::delete()/
     * DocumentController::delete(), un par un).
     */
    #[Route('/delete', name: 'bulk_delete', methods: ['POST'])]
    public function delete(string $space, Request $request, EntityManagerInterface $manager, FolderRepository $folderRepository, DocumentRepository $documentRepository): Response
    {
        $this->denyAccessUnlessGranted(FolderWriteVoter::WRITE, $space);

        if ($this->isCsrfTokenValid('bulk_delete'.$space, $request->request->get('_token'))) {
            $count = 0;

            foreach ((array) $request->request->all('folder_ids') as $id) {
                $folder = $folderRepository->find($id);
                if ($folder && $space === $folder->getSpace() && !$folder->isDeleted()) {
                    $folder->setDeletedAt(new \DateTime());
                    ++$count;
                }
            }

            foreach ((array) $request->request->all('document_ids') as $id) {
                $document = $documentRepository->find($id);
                if ($document && $space === $document->getFolder()->getSpace() && !$document->isDeleted()) {
                    $document->setDeletedAt(new \DateTime());
                    ++$count;
                }
            }

            $manager->flush();

            if ($count > 0) {
                $this->addFlash('success', $count.' élément(s) déplacé(s) dans la corbeille');
            }
        }

        return $this->redirectToRoute('desk_files', ['space' => $space, 'folder' => $request->request->get('folder')]);
    }

    /**
     * Déplace tous les dossiers/documents cochés vers $target (même
     * validation anti-cycle que FolderController::move() pour chaque
     * dossier de la sélection).
     */
    #[Route('/move', name: 'bulk_move', methods: ['POST'])]
    public function move(string $space, Request $request, EntityManagerInterface $manager, FolderRepository $folderRepository, DocumentRepository $documentRepository): Response
    {
        $this->denyAccessUnlessGranted(FolderWriteVoter::WRITE, $space);

        if (!$this->isCsrfTokenValid('bulk_move'.$space, $request->request->get('_token'))) {
            return $this->redirectToRoute('desk_files', ['space' => $space]);
        }

        $targetId = $request->request->get('target');
        $target = $targetId ? $folderRepository->find($targetId) : $folderRepository->findOrCreateRoot($space, $manager);

        if (!$target || $target->getSpace() !== $space || $target->isDeleted()) {
            throw $this->createNotFoundException();
        }

        $count = 0;
        $skipped = 0;

        foreach ((array) $request->request->all('folder_ids') as $id) {
            $folder = $folderRepository->find($id);
            if (!$folder || $space !== $folder->getSpace() || $folder->isDeleted()) {
                continue;
            }
            if ($folderRepository->isSelfOrDescendantOf($target, $folder)) {
                ++$skipped;
                continue;
            }
            $folder->setParent($target);
            ++$count;
        }

        foreach ((array) $request->request->all('document_ids') as $id) {
            $document = $documentRepository->find($id);
            if ($document && $space === $document->getFolder()->getSpace() && !$document->isDeleted()) {
                $document->setFolder($target);
                ++$count;
            }
        }

        $manager->flush();

        if ($count > 0) {
            $this->addFlash('success', $count.' élément(s) déplacé(s)');
        }
        if ($skipped > 0) {
            $this->addFlash('error', $skipped.' dossier(s) n\'ont pas pu être déplacés dans eux-mêmes ou un de leurs sous-dossiers');
        }

        return $this->redirectToRoute('desk_files', ['space' => $space, 'folder' => $target->getId()]);
    }
}
