<?php

namespace App\Controller;

use App\Entity\CarpoolOffer;
use App\Form\CarpoolOfferType;
use App\Repository\CarpoolOfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Carpool board for upcoming events: any logged-in member (roadmap "espace adhérent"), not gated by a specific role, cf. /desk generic access_control.
#[Route('/desk/carpool')]
class CarpoolController extends AbstractController
{
    #[Route('/', name: 'carpool_index', methods: ['GET'])]
    public function index(CarpoolOfferRepository $carpoolOfferRepository): Response
    {
        return $this->render('carpool/index.html.twig', [
            'offers' => $carpoolOfferRepository->findUpcomingOrderedByEventDate(),
        ]);
    }

    #[Route('/new', name: 'carpool_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $offer = new CarpoolOffer();
        $offer->setDriver($this->getUser());

        $form = $this->createForm(CarpoolOfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($offer);
            $entityManager->flush();

            $this->addFlash('success', 'Covoiturage proposé');

            return $this->redirectToRoute('carpool_index');
        }

        return $this->render('carpool/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Toggles the current user as a passenger. Not open to the driver (already implicitly "in") or once the offer is full.
    #[Route('/{id}/join', name: 'carpool_join_toggle', methods: ['POST'])]
    public function toggleJoin(CarpoolOffer $offer, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle_join'.$offer->getId(), $request->request->get('_token'))) {
            $user = $this->getUser();

            if ($offer->isDriver($user)) {
                $this->addFlash('info', 'Tu es déjà le·la conducteur·rice de ce trajet');
            } elseif ($offer->hasPassenger($user)) {
                $offer->removePassenger($user);
                $entityManager->flush();
            } elseif ($offer->isFull()) {
                $this->addFlash('info', 'Plus de place disponible sur ce trajet');
            } else {
                $offer->addPassenger($user);
                $entityManager->flush();
            }
        }

        return $this->redirectToRoute('carpool_index');
    }

    #[Route('/{id}', name: 'carpool_delete', methods: ['DELETE'])]
    public function delete(CarpoolOffer $offer, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$offer->isDriver($this->getUser()) && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$offer->getId(), $request->request->get('_token'))) {
            $entityManager->remove($offer);
            $entityManager->flush();

            $this->addFlash('success', 'Covoiturage supprimé');
        }

        return $this->redirectToRoute('carpool_index');
    }
}
