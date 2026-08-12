<?php

namespace App\Controller;

use App\Entity\StorySection;
use App\Form\StorySectionType;
use App\Repository\StorySectionRepository;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CRUD des sections de la page Histoire, réservé à ROLE_ADMIN (préfixe
 * /admin déjà couvert par access_control dans security.yaml).
 */
#[Route('/admin/story')]
class StorySectionController extends AbstractController
{
    #[Route('/', name: 'story_section_index', methods: ['GET'])]
    public function index(StorySectionRepository $storySectionRepository): Response
    {
        return $this->render('story_section/index.html.twig', [
            'sections' => $storySectionRepository->findAllOrderedByPosition(),
        ]);
    }

    #[Route('/new', name: 'story_section_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, StorySectionRepository $storySectionRepository): Response
    {
        $section = new StorySection();
        $form = $this->createForm(StorySectionType::class, $section);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $section->setSlug($this->generateUniqueSlug($section->getTitle(), $storySectionRepository));
            $section->setPosition($storySectionRepository->findMaxPosition() + 1);

            $entityManager->persist($section);
            $entityManager->flush();

            return $this->redirectToRoute('story_section_index');
        }

        return $this->render('story_section/new.html.twig', [
            'section' => $section,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'story_section_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, StorySection $section, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(StorySectionType::class, $section);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('story_section_index');
        }

        return $this->render('story_section/edit.html.twig', [
            'section' => $section,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'story_section_delete', methods: ['DELETE'])]
    public function delete(Request $request, StorySection $section, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$section->getId(), $request->request->get('_token'))) {
            $entityManager->remove($section);
            $entityManager->flush();
        }

        return $this->redirectToRoute('story_section_index');
    }

    #[Route('/{id}/move-up', name: 'story_section_move_up', methods: ['POST'])]
    public function moveUp(Request $request, StorySection $section, StorySectionRepository $storySectionRepository, EntityManagerInterface $entityManager): Response
    {
        return $this->swapPosition($request, $section, $storySectionRepository, $entityManager, -1);
    }

    #[Route('/{id}/move-down', name: 'story_section_move_down', methods: ['POST'])]
    public function moveDown(Request $request, StorySection $section, StorySectionRepository $storySectionRepository, EntityManagerInterface $entityManager): Response
    {
        return $this->swapPosition($request, $section, $storySectionRepository, $entityManager, 1);
    }

    /**
     * Boutons "monter"/"descendre" plutôt qu'un glisser-déposer non
     * atteignable au clavier (même choix que le déplacement de fichiers du
     * gestionnaire de dossiers, ROADMAP.md Phase 7).
     */
    private function swapPosition(Request $request, StorySection $section, StorySectionRepository $storySectionRepository, EntityManagerInterface $entityManager, int $direction): Response
    {
        if (!$this->isCsrfTokenValid('move'.$section->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('story_section_index');
        }

        $sections = $storySectionRepository->findAllOrderedByPosition();
        $index = array_search($section, $sections, true);
        $neighborIndex = $index + $direction;

        if (false !== $index && isset($sections[$neighborIndex])) {
            $neighbor = $sections[$neighborIndex];
            [$positionA, $positionB] = [$section->getPosition(), $neighbor->getPosition()];
            $section->setPosition($positionB);
            $neighbor->setPosition($positionA);
            $entityManager->flush();
        }

        return $this->redirectToRoute('story_section_index');
    }

    private function generateUniqueSlug(string $title, StorySectionRepository $storySectionRepository): string
    {
        $slugify = new Slugify();
        $base = $slugify->slugify($title);
        $slug = $base;
        $suffix = 2;

        while ($storySectionRepository->isSlugTaken($slug)) {
            $slug = $base.'-'.$suffix;
            ++$suffix;
        }

        return $slug;
    }
}
