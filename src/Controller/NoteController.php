<?php

namespace App\Controller;

use App\Entity\Note;
use App\Form\NoteType;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Outil de prise de note du bureau/conseil (ROADMAP.md "espace
 * administratif"), réservé ROLE_ADMIN/ROLE_COMPTA (^/desk/notes dans
 * security.yaml). Pas collaboratif : seul l'auteur·ice d'une note peut la
 * modifier/supprimer, même partagée (cf. denyUnlessAuthor()).
 */
#[Route('/desk/notes')]
class NoteController extends AbstractController
{
    #[Route('/', name: 'note_index', methods: ['GET'])]
    public function index(NoteRepository $noteRepository): Response
    {
        return $this->render('note/index.html.twig', [
            'notes' => $noteRepository->findVisibleForUser($this->getUser()),
        ]);
    }

    #[Route('/new', name: 'note_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $note = new Note();
        $note->setAuthor($this->getUser());

        $form = $this->createForm(NoteType::class, $note);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($note);
            $entityManager->flush();

            return $this->redirectToRoute('note_index');
        }

        return $this->render('note/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'note_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Note $note): Response
    {
        if (!$note->isShared() && !$note->isAuthoredBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('note/show.html.twig', [
            'note' => $note,
        ]);
    }

    #[Route('/{id}/edit', name: 'note_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Note $note, EntityManagerInterface $entityManager): Response
    {
        $this->denyUnlessAuthor($note);

        $form = $this->createForm(NoteType::class, $note);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('note_index');
        }

        return $this->render('note/edit.html.twig', [
            'note' => $note,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'note_delete', methods: ['DELETE'])]
    public function delete(Request $request, Note $note, EntityManagerInterface $entityManager): Response
    {
        $this->denyUnlessAuthor($note);

        if ($this->isCsrfTokenValid('delete'.$note->getId(), $request->request->get('_token'))) {
            $entityManager->remove($note);
            $entityManager->flush();
        }

        return $this->redirectToRoute('note_index');
    }

    private function denyUnlessAuthor(Note $note): void
    {
        if (!$note->isAuthoredBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }
    }
}
