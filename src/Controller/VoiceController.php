<?php

namespace App\Controller;

use App\Entity\Track;
use App\Entity\Voice;
use App\Form\VoiceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class VoiceController extends AbstractController
{
    #[Route('/music/{trackId}/voice', name: 'voice_new', methods: ['POST'])]
    public function new(#[MapEntity(mapping: ['trackId' => 'id'])] Track $track, Request $request, EntityManagerInterface $manager, SluggerInterface $slugger): Response
    {
        $voice = new Voice();
        $form = $this->createForm(VoiceType::class, $voice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                try {
                    $file->move($this->getParameter('voices_directory'), $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Le fichier audio n\'a pas pu être enregistré : '.$e->getMessage());

                    return $this->redirectToRoute('track_edit', ['id' => $track->getId()]);
                }
                $voice->setFilename($newFilename);
            }

            $track->addVoice($voice);
            $manager->persist($voice);
            $manager->flush();

            $this->addFlash('success', 'Voix ajoutée');
        }

        return $this->redirectToRoute('track_edit', ['id' => $track->getId()]);
    }

    #[Route('/music/{trackId}/voice/{voiceId}', name: 'voice_delete', methods: ['DELETE'])]
    public function delete(#[MapEntity(mapping: ['trackId' => 'id'])] Track $track, #[MapEntity(mapping: ['voiceId' => 'id'])] Voice $voice, Request $request, EntityManagerInterface $manager): Response
    {
        if ($this->isCsrfTokenValid('delete_voice'.$voice->getId(), $request->request->get('_token'))) {
            $manager->remove($voice);
            $manager->flush();

            $this->addFlash('success', 'Voix supprimée');
        }

        return $this->redirectToRoute('track_edit', ['id' => $track->getId()]);
    }
}
