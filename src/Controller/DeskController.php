<?php

namespace App\Controller;

use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DeskController extends AbstractController
{
    /**
     * @Route("/desk", name="desk")
     */
    public function index(EntityManagerInterface $manager, RoleRepository $repo, UserRepository $repoUser)
    {
        $roles = $repo->findAll($manager, $repo);
        $roleAdmin = "Admin";
        $roleAccountant = "Accountant";
        $roleBinioufous = "Binioufous";
        $roleUser = "User";
        $roleMember = "Member";

        $unvalids = $repoUser->findUnvalids($manager, $repoUser);
        $binioufous = $repoUser->findBinioufous($roleBinioufous);
        $members = $repoUser->findMembers($roleMember);
        
        $users = $repoUser->findUsers($roleUser);
        $admins = $repoUser->findAdmins($roleAdmin);
        $accountants = $repoUser->findAccountants($roleAccountant);
    

        return $this->render('desk/index.html.twig', [
            'roles' => $roles,
            'unvalids' => $unvalids,
            'binioufous' =>$binioufous,
            /*
            'members' =>$members,
            'users' =>$users
            */
        ]);
    }
}
