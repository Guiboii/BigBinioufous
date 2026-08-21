<?php

namespace App\Controller;

use App\Entity\Folder;
use App\Repository\DocumentRepository;
use App\Repository\FolderRepository;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Security\FolderWriteVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// The member area: /desk hub and its /desk/files/{space} file manager. Access per space is enforced by access_control in security.yaml, this controller trusts it rather than re-checking roles.
class DeskController extends AbstractController
{
    #[Route('/desk', name: 'desk')]
    public function index(EntityManagerInterface $manager, RoleRepository $repo, UserRepository $repoUser)
    {
        $roles = $repo->findAll($manager, $repo);
        $unvalids = $repoUser->findUnvalids($manager, $repoUser);

        $roleAdmin = $repo->findOneByDescription('Administrator');
        $roleAccountant = $repo->findOneByDescription('Accountant');
        $roleBinioufous = $repo->findOneByDescription('Binioufous');

        $admins = $repoUser->findAdmins($roleAdmin);
        $accountants = $repoUser->findAccountants($roleAccountant);
        $binioufous = $repoUser->findBinioufous($roleBinioufous);
        // "Simple" is not a stored role: validated without ROLE_BINIOUFOUS.
        $simples = $repoUser->findSimples();

        return $this->render('desk/index.html.twig', [
            'roles' => $roles,
            'unvalids' => $unvalids,
            'admins' => $admins,
            'accountants' => $accountants,
            'binioufous' => $binioufous,
            'simples' => $simples,
        ]);
    }

    // Card hub linking to the spaces the member has access to; actual access is enforced by security.yaml, this only shows/hides cards via is_granted in Twig.
    #[Route('/desk/files', name: 'desk_files_hub')]
    public function filesHub(): Response
    {
        return $this->render('desk/files_hub.html.twig');
    }

    // Drive-like file manager for a given space (?folder=ID to browse into a subfolder).
    #[Route('/desk/files/{space}', name: 'desk_files', requirements: ['space' => 'music|admin|accounting|other'])]
    public function files(string $space, Request $request, DocumentRepository $documentRepository, FolderRepository $folderRepository, EntityManagerInterface $manager)
    {
        $root = $folderRepository->findOrCreateRoot($space, $manager);

        $folderId = $request->query->get('folder');
        $currentFolder = $folderId ? $folderRepository->find($folderId) : $root;

        if (!$currentFolder || $space !== $currentFolder->getSpace() || $currentFolder->isDeleted()) {
            throw $this->createNotFoundException();
        }

        // A folder whose parent is trashed shouldn't stay reachable via a direct URL, since it no longer appears in normal navigation.
        if ($folderRepository->hasDeletedAncestor($currentFolder)) {
            throw $this->createNotFoundException();
        }

        // getAncestors() includes the root, which is already shown separately as the first breadcrumb link in the template, so it's sliced off here and the current folder itself is appended.
        $atRoot = $currentFolder->getId() === $root->getId();
        $breadcrumb = $atRoot ? [] : array_merge(array_slice($currentFolder->getAncestors(), 1), [$currentFolder]);

        $movingDocument = null;
        if ($moveDocumentId = $request->query->get('move_document')) {
            $candidate = $documentRepository->find($moveDocumentId);
            if ($candidate && $space === $candidate->getFolder()->getSpace()) {
                $movingDocument = $candidate;
            }
        }

        $movingFolder = null;
        if ($moveFolderId = $request->query->get('move_folder')) {
            $candidate = $folderRepository->find($moveFolderId);
            if ($candidate && $space === $candidate->getSpace() && $candidate->getId() !== $root->getId()) {
                $movingFolder = $candidate;
            }
        }

        // Search mode: ?q= switches the display to results across the whole space (recursive) instead of the current folder's contents.
        $query = trim((string) $request->query->get('q', ''));
        $searching = '' !== $query;

        $sort = $request->query->get('sort', 'name');
        $dir = $request->query->get('dir', 'asc');

        // Bulk move: ?bulk_move=1 + folder_ids[]/document_ids[], submitted as a plain GET navigation. Coexists with the click-to-move mode above as a separate mechanism.
        $bulkMovingFolders = [];
        $bulkMovingDocuments = [];
        if ($request->query->getBoolean('bulk_move')) {
            foreach ((array) $request->query->all('folder_ids') as $id) {
                $candidate = $folderRepository->find($id);
                if ($candidate && $space === $candidate->getSpace() && !$candidate->isDeleted() && $candidate->getId() !== $root->getId()) {
                    $bulkMovingFolders[] = $candidate;
                }
            }
            foreach ((array) $request->query->all('document_ids') as $id) {
                $candidate = $documentRepository->find($id);
                if ($candidate && $space === $candidate->getFolder()->getSpace() && !$candidate->isDeleted()) {
                    $bulkMovingDocuments[] = $candidate;
                }
            }
        }
        $bulkMoving = [] !== $bulkMovingFolders || [] !== $bulkMovingDocuments;

        return $this->render('desk/files.html.twig', [
            'space' => $space,
            'root' => $root,
            'atRoot' => $atRoot,
            'currentFolder' => $currentFolder,
            'currentFolderPath' => implode('/', array_map(static fn (Folder $f) => $f->getName(), $breadcrumb)),
            'breadcrumb' => $breadcrumb,
            'query' => $query,
            'searching' => $searching,
            'sort' => $sort,
            'dir' => $dir,
            'subfolders' => $searching ? $folderRepository->search($space, $query) : $folderRepository->findActiveChildren($currentFolder),
            'documents' => $searching ? $documentRepository->search($space, $query) : $documentRepository->findActiveByFolder($currentFolder, $sort, $dir),
            'movingDocument' => $movingDocument,
            'movingFolder' => $movingFolder,
            'bulkMovingFolders' => $bulkMovingFolders,
            'bulkMovingDocuments' => $bulkMovingDocuments,
            'bulkMoving' => $bulkMoving,
        ]);
    }

    // Trash of a space: a flat list, since a trashed item by definition no longer has a place in the active tree.
    #[Route('/desk/files/{space}/trash', name: 'desk_files_trash', requirements: ['space' => 'music|admin|accounting|other'])]
    public function trash(string $space, FolderRepository $folderRepository, DocumentRepository $documentRepository): Response
    {
        return $this->render('desk/files_trash.html.twig', [
            'space' => $space,
            'trashedFolders' => $folderRepository->findTrashed($space),
            'trashedDocuments' => $documentRepository->findTrashed($space),
        ]);
    }

    // Empties a space's trash at once. Physical files are removed only after the Doctrine flush is confirmed.
    #[Route('/desk/files/{space}/trash/empty', name: 'desk_files_trash_empty', methods: ['POST'], requirements: ['space' => 'music|admin|accounting|other'])]
    public function emptyTrash(string $space, Request $request, EntityManagerInterface $manager, FolderRepository $folderRepository, DocumentRepository $documentRepository): Response
    {
        $this->denyAccessUnlessGranted(FolderWriteVoter::WRITE, $space);

        if ($this->isCsrfTokenValid('empty_trash'.$space, $request->request->get('_token'))) {
            $filenames = [];

            foreach ($folderRepository->findTrashed($space) as $folder) {
                $filenames = array_merge($filenames, $this->collectDescendantFilenames($folder));
                $manager->remove($folder);
            }

            foreach ($documentRepository->findTrashed($space) as $document) {
                $filenames[] = $document->getFilename();
                $manager->remove($document);
            }

            $manager->flush();

            foreach ($filenames as $filename) {
                $path = $this->getParameter('documents_directory').'/'.$filename;
                if (is_file($path)) {
                    unlink($path);
                }
            }

            $this->addFlash('success', 'Corbeille vidée');
        }

        return $this->redirectToRoute('desk_files_trash', ['space' => $space]);
    }

    /**
     * Physical filenames of every document under this folder, recursively: a trashed folder can have active children whose files must also be removed from disk.
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
}
