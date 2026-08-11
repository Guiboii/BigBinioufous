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
     * Songs menus/favorite voices (racine uniquement) + navigateur de
     * fichiers façon Drive (?folder=ID pour descendre dans un dossier).
     */
    #[Route('/desk/music', name: 'deskmusic')]
    public function favoritesSongs(Request $request, TrackRepository $trackRepository, DocumentRepository $documentRepository, FolderRepository $folderRepository)
    {
        $folderId = $request->query->get('folder');
        $currentFolder = $folderId ? $folderRepository->find($folderId) : null;

        if ($folderId && !$currentFolder) {
            throw $this->createNotFoundException();
        }

        $breadcrumb = [];
        $ancestor = $currentFolder;
        while ($ancestor) {
            array_unshift($breadcrumb, $ancestor);
            $ancestor = $ancestor->getParent();
        }

        return $this->render('desk/music.html.twig', [
            'tracks' => $currentFolder ? [] : $trackRepository->findAll(),
            'currentFolder' => $currentFolder,
            'currentFolderPath' => implode('/', array_map(static fn (Folder $f) => $f->getName(), $breadcrumb)),
            'breadcrumb' => $breadcrumb,
            'subfolders' => $folderRepository->findBy(['parent' => $currentFolder], ['name' => 'ASC']),
            'documents' => $documentRepository->findBy(['folder' => $currentFolder], ['name' => 'ASC']),
        ]);
    }

    /**
     * Coche/décoche la voix courante comme jouée par le membre connecté.
     */
    #[Route('/desk/music/voice/{voiceId}/toggle', name: 'desk_voice_toggle', methods: ['POST'])]
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

        return $this->redirectToRoute('deskmusic');
    }
}
