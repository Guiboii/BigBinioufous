<?php

namespace App\Controller;

use App\Repository\StorySectionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class StoryController extends AbstractController
{
    #[Route('/story', name: 'story')]
    public function index()
    {
        return $this->render('story/index.html.twig');
    }

    #[Route('/story/mini', name: 'minisite')]
    public function mini(StorySectionRepository $storySectionRepository)
    {
        return $this->render('story/minisite.html.twig', [
            'sections' => $storySectionRepository->findAllOrderedByPosition(),
        ]);
    }
}
