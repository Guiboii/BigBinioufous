<?php

namespace App\Controller;

use App\Entity\Folder;
use App\Repository\DocumentRepository;
use App\Repository\FolderRepository;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
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

        if (!$currentFolder || $space !== $currentFolder->getSpace()) {
            throw $this->createNotFoundException();
        }

        $breadcrumb = [];
        $ancestor = $currentFolder;
        while ($ancestor && $ancestor->getParent()) {
            array_unshift($breadcrumb, $ancestor);
            $ancestor = $ancestor->getParent();
        }

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

        return $this->render('desk/files.html.twig', [
            'space' => $space,
            'root' => $root,
            'atRoot' => $currentFolder->getId() === $root->getId(),
            'currentFolder' => $currentFolder,
            'currentFolderPath' => implode('/', array_map(static fn (Folder $f) => $f->getName(), $breadcrumb)),
            'breadcrumb' => $breadcrumb,
            'subfolders' => $folderRepository->findBy(['parent' => $currentFolder], ['name' => 'ASC']),
            'documents' => $documentRepository->findBy(['folder' => $currentFolder], ['name' => 'ASC']),
            'movingDocument' => $movingDocument,
            'movingFolder' => $movingFolder,
        ]);
    }
}
