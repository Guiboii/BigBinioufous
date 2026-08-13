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

/**
 * Espaces disponibles sous /desk/files/{space}, cf. Folder::SPACES. L'accès
 * (lecture ET écriture, même règle) est géré par space dans security.yaml,
 * pas ici : ce contrôleur ne revérifie pas les rôles, il fait confiance à
 * access_control comme le reste du projet (DocumentController/FolderController
 * pareil).
 */
class DeskController extends AbstractController
{
    #[Route('/desk', name: 'desk')]
    public function index(EntityManagerInterface $manager, RoleRepository $repo, UserRepository $repoUser)
    {
        // Liste "Les Membres" (ROLE_MEMBER) retirée de cette page le
        // 2026-08-12 : rôle jamais attribué par le toggle Membre/Pas membre
        // (ROLE_BINIOUFOUS, cf. ROADMAP.md "Facilitons l'inscription"),
        // gardait juste des comptes historiques ne débloquant aucune
        // permission propre dans le code, confusion repérée par
        // l'utilisatrice ("pour moi membre, ben c'est binioufous !").
        // ROLE_MEMBER supprimé entièrement le même jour (cf. ROADMAP.md
        // "Rôles legacy et implicite nettoyés", migration
        // Version20260812180000) : UserRepository::findMembers() retiré,
        // plus aucun compte ne peut l'avoir.
        $roles = $repo->findAll($manager, $repo);
        $unvalids = $repoUser->findUnvalids($manager, $repoUser);

        $roleAdmin = $repo->findOneByDescription('Administrator');
        $roleAccountant = $repo->findOneByDescription('Accountant');
        $roleBinioufous = $repo->findOneByDescription('Binioufous');

        $admins = $repoUser->findAdmins($roleAdmin);
        $accountants = $repoUser->findAccountants($roleAccountant);
        $binioufous = $repoUser->findBinioufous($roleBinioufous);
        // ROLE_SIMPLE fusionné avec ROLE_USER (cf. ROADMAP.md "Rôles
        // fusionnés") : plus de rôle à passer, "simple" = validé sans
        // ROLE_BINIOUFOUS.
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

    /**
     * Hub "Dossiers" : cartes vers les espaces auxquels le membre connecté a
     * accès. L'accès réel est vérifié par security.yaml sur chaque espace ;
     * ici on ne fait qu'afficher/masquer les cartes (is_granted côté Twig),
     * même pattern que les liens conditionnels de desk/partials/header.
     */
    #[Route('/desk/files', name: 'desk_files_hub')]
    public function filesHub(): Response
    {
        return $this->render('desk/files_hub.html.twig');
    }

    /**
     * Gestionnaire de fichiers façon Drive pour un espace donné
     * (?folder=ID pour descendre dans un dossier). L'espace musique n'a
     * plus de cas particulier depuis la fusion Track/Voice dans
     * Folder/Document (cf. plan "Nettoyage de la gestion de
     * fichiers/dossiers") : la setlist se gère désormais sur /music
     * (MusicController), ce classeur reste une vue Drive comme les autres
     * espaces.
     */
    #[Route('/desk/files/{space}', name: 'desk_files', requirements: ['space' => 'music|admin|accounting|other'])]
    public function files(string $space, Request $request, DocumentRepository $documentRepository, FolderRepository $folderRepository, EntityManagerInterface $manager)
    {
        $root = $folderRepository->findOrCreateRoot($space, $manager);

        $folderId = $request->query->get('folder');
        $currentFolder = $folderId ? $folderRepository->find($folderId) : $root;

        if (!$currentFolder || $space !== $currentFolder->getSpace() || $currentFolder->isDeleted()) {
            throw $this->createNotFoundException();
        }

        // Un dossier dont le parent (ou un ancêtre plus lointain) est en
        // corbeille ne doit pas rester consultable par URL directe
        // (?folder=ID) : il n'apparaît plus nulle part dans la navigation
        // normale, cf. FolderRepository::hasDeletedAncestor().
        if ($folderRepository->hasDeletedAncestor($currentFolder)) {
            throw $this->createNotFoundException();
        }

        $breadcrumb = $currentFolder->getAncestors();

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

        // Mode recherche : ?q= bascule l'affichage sur les résultats de tout
        // l'espace (récursif) plutôt que le contenu du dossier courant.
        $query = trim((string) $request->query->get('q', ''));
        $searching = '' !== $query;

        $sort = $request->query->get('sort', 'name');
        $dir = $request->query->get('dir', 'asc');

        // Déplacement groupé : ?bulk_move=1 + folder_ids[]/document_ids[]
        // (cochés dans templates/desk/files.html.twig, soumis en GET pour
        // rester une simple navigation, cf. le formulaire "bulk-form").
        // Généralise le mode de déplacement "clic à clic" déjà en place
        // (movingDocument/movingFolder ci-dessus) à un ensemble d'éléments,
        // sans toucher à ce mode existant (2 mécanismes distincts, cf. plan).
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
            'atRoot' => $currentFolder->getId() === $root->getId(),
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

    /**
     * Corbeille d'un espace (dossiers/documents avec deletedAt non nul,
     * cf. Folder::$deletedAt) : liste à plat, pas d'arborescence puisqu'un
     * élément en corbeille n'a par définition plus sa place dans l'arbre
     * actif. Chaque ligne affiche son chemin d'origine pour se repérer.
     */
    #[Route('/desk/files/{space}/trash', name: 'desk_files_trash', requirements: ['space' => 'music|admin|accounting|other'])]
    public function trash(string $space, FolderRepository $folderRepository, DocumentRepository $documentRepository): Response
    {
        return $this->render('desk/files_trash.html.twig', [
            'space' => $space,
            'trashedFolders' => $folderRepository->findTrashed($space),
            'trashedDocuments' => $documentRepository->findTrashed($space),
        ]);
    }

    /**
     * Vide la corbeille d'un espace d'un coup (dossiers en corbeille +
     * documents supprimés individuellement) : même logique de suppression
     * définitive que FolderController::purge()/DocumentController::purge()
     * (fichiers physiques retirés après le flush Doctrine confirmé).
     */
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
     * @return string[] noms de fichiers physiques de tous les documents
     *                  sous ce dossier, récursivement (même logique que
     *                  FolderController::collectDescendantFilenames(), un
     *                  dossier en corbeille peut avoir des enfants actifs
     *                  dont les fichiers doivent aussi disparaître du disque)
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
