<?php

namespace App\Controller;

use App\Entity\Track;
use App\Entity\Voice;
use App\Form\TrackType;
use App\Form\VoiceType;
use App\Repository\TrackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/music')]
class TrackController extends AbstractController
{
    #[Route('/', name: 'music', methods: ['GET'])]
    public function index(TrackRepository $trackRepository): Response
    {
        return $this->render('music/index.html.twig', [
            'tracks' => $trackRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'track_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SluggerInterface $slugger): Response
    {
        $track = new Track();
        $form = $this->createForm(TrackType::class, $track);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $trackFile = $form->get('file')->getData();
            if ($trackFile) {
                $originalFilename = pathinfo($trackFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'.'.$trackFile->guessExtension();

                try {
                    $trackFile->move(
                        $this->getParameter('mp3'),
                        $newFilename
                    );
                } catch (FileException $e) {
                }
            }
            $track->setTrackFilename($newFilename);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($track);
            $entityManager->flush();

            return $this->redirectToRoute('music');
        }

        return $this->render('music/new.html.twig', [
            'track' => $track,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Dépose glisser-déposer : un fichier = un morceau créé directement
     * (titre = nom de fichier, durée lue côté JS via l'API Audio, artiste
     * laissé vide). À corriger ensuite via track_edit si besoin.
     */
    #[Route('/quick-upload', name: 'track_quick_upload', methods: ['POST'])]
    public function quickUpload(Request $request, EntityManagerInterface $manager, SluggerInterface $slugger): JsonResponse
    {
        if (!$this->isCsrfTokenValid('quick_upload', $request->request->get('_token'))) {
            return $this->json(['error' => 'invalid_token'], 403);
        }

        $file = $request->files->get('file');
        $title = trim((string) $request->request->get('title'));
        $minutes = (int) $request->request->get('minutes');
        $seconds = (int) $request->request->get('seconds');

        if (!$file || '' === $title) {
            return $this->json(['error' => 'invalid_input'], 400);
        }

        if (!\in_array($file->getMimeType(), ['audio/mp3', 'audio/mpeg'], true)) {
            return $this->json(['error' => 'invalid_mimetype'], 400);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->getParameter('mp3'), $newFilename);
        } catch (FileException $e) {
            return $this->json(['error' => 'upload_failed'], 500);
        }

        $track = new Track();
        $track->setTitle($title)
            ->setMinutes($minutes)
            ->setSeconds($seconds)
            ->setTrackFilename($newFilename);

        $manager->persist($track);
        $manager->flush();

        return $this->json(['success' => true, 'id' => $track->getId()]);
    }

    #[Route('/{id}', name: 'track_show', methods: ['GET'])]
    public function show(Track $track): Response
    {
        return $this->render('music/show.html.twig', [
            'track' => $track,
        ]);
    }

    #[Route('/{id}/edit', name: 'track_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Track $track): Response
    {
        $form = $this->createForm(TrackType::class, $track);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('music');
        }

        $voiceForm = $this->createForm(VoiceType::class, new Voice(), [
            'action' => $this->generateUrl('voice_new', ['trackId' => $track->getId()]),
            'method' => 'POST',
        ]);

        return $this->render('music/edit.html.twig', [
            'track' => $track,
            'form' => $form->createView(),
            'voiceForm' => $voiceForm->createView(),
        ]);
    }

    #[Route('/{id}', name: 'track_delete', methods: ['DELETE'])]
    public function delete(Request $request, Track $track): Response
    {
        if ($this->isCsrfTokenValid('delete'.$track->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($track);
            $entityManager->flush();
        }

        return $this->redirectToRoute('music');
    }
}
