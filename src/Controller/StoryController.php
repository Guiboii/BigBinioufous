<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class StoryController extends AbstractController
{
    /**
     * @Route("/story", name="story")
     */
    public function index()
    {
        return $this->render('story/index.html.twig');
    }

/**
     * @Route("/story/mini", name="minisite")
     */
    public function mini()
    {
        return $this->render('story/minisite.html.twig');
    }
}
