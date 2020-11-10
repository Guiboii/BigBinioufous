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
        $unvalids = $repoUser->findUnvalids($manager, $repoUser);
        
        
        $roleAdmin = "Admin";
        $roleAccountant = "Accountant";
        $roleBinioufous = $repo->findOneByDescription('Binioufous');
        $roleMember = "Member";
        $roleUser = "User";
        
        $admins = $repoUser->findAdmins($roleAdmin);
        $accountants = $repoUser->findAccountants($roleAccountant);
        $binioufous = $repoUser->findBinioufous($roleBinioufous);
        $members = $repoUser->findMembers($roleMember);
        $users = $repoUser->findUsers($roleUser);
        
        dump($binioufous);

        return $this->render('desk/index.html.twig', [
            'roles' => $roles,
            'unvalids' => $unvalids,
            'admins' => $admins,
            'accountants' => $accountants,
            'binioufous' =>$binioufous,
            'members' =>$members,
            'users' =>$users
        ]);
    }
}
