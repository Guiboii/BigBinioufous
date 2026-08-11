<?php

namespace App\Controller;

use App\Entity\Folder;
use App\Entity\Voice;
use App\Repository\DocumentRepository;
use App\Repository\FolderRepository;
use App\Repository\RoleRepository;
use App\Repository\TrackRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
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
        $roles = $repo->findAll($manager, $repo);
        $unvalids = $repoUser->findUnvalids($manager, $repoUser);

        $roleAdmin = $repo->findOneByDescription('Administrator');
        $roleAccountant = $repo->findOneByDescription('Accountant');
        $roleBinioufous = $repo->findOneByDescription('Binioufous');
        $roleMember = $repo->findOneByDescription('Member');
        $roleSimple = $repo->findOneByDescription('Simple');

        $admins = $repoUser->findAdmins($roleAdmin);
        $accountants = $repoUser->findAccountants($roleAccountant);
        $binioufous = $repoUser->findBinioufous($roleBinioufous);
        $members = $repoUser->findMembers($roleMember);
        $simples = $repoUser->findSimples($roleSimple);

        return $this->render('desk/index.html.twig', [
            'roles' => $roles,
            'unvalids' => $unvalids,
            'admins' => $admins,
            'accountants' => $accountants,
            'binioufous' => $binioufous,
            'members' => $members,
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
     * (?folder=ID pour descendre dans un dossier). L'espace musique affiche
     * en plus le tas "morceaux + parties favorites", uniquement à sa racine.
     */
    #[Route('/desk/files/{space}', name: 'desk_files', requirements: ['space' => 'music|admin|accounting'])]
    public function files(string $space, Request $request, TrackRepository $trackRepository, DocumentRepository $documentRepository, FolderRepository $folderRepository, EntityManagerInterface $manager)
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
            'tracks' => ('music' === $space && $currentFolder->getId() === $root->getId()) ? $trackRepository->findAll() : [],
            'currentFolder' => $currentFolder,
            'currentFolderPath' => implode('/', array_map(static fn (Folder $f) => $f->getName(), $breadcrumb)),
            'breadcrumb' => $breadcrumb,
            'subfolders' => $folderRepository->findBy(['parent' => $currentFolder], ['name' => 'ASC']),
            'documents' => $documentRepository->findBy(['folder' => $currentFolder], ['name' => 'ASC']),
            'movingDocument' => $movingDocument,
            'movingFolder' => $movingFolder,
        ]);
    }

    /**
     * Coche/décoche la voix courante comme jouée par le membre connecté.
     */
    #[Route('/desk/files/music/voices/{voiceId}/toggle', name: 'desk_voice_toggle', methods: ['POST'])]
    public function toggleVoice(#[MapEntity(mapping: ['voiceId' => 'id'])] Voice $voice, Request $request, EntityManagerInterface $manager): Response
    {
        if ($this->isCsrfTokenValid('toggle_voice'.$voice->getId(), $request->request->get('_token'))) {
            $user = $this->getUser();
            if ($voice->getUsers()->contains($user)) {
                $voice->removeUser($user);
            } else {
                $voice->addUser($user);
            }
            $manager->flush();
        }

        return $this->redirectToRoute('desk_files', ['space' => 'music']);
    }
}
