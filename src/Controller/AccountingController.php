<?php

namespace App\Controller;

use App\Entity\AccountingDocument;
use App\Entity\AccountingDocumentLine;
use App\Entity\LedgerEntry;
use App\Form\AccountingDocumentType;
use App\Form\LedgerEntryType;
use App\Repository\AccountingDocumentRepository;
use App\Repository\LedgerEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Fusion de l'ancien /accountant (page d'accueil comptable, quasi vide)
 * dans l'espace /desk/files/accounting (cf. ROADMAP.md "Espace compta") :
 * devis/factures et journal de trésorerie viennent compléter les relevés
 * déjà gérés par DeskController::files()/FolderController/DocumentController
 * pour cet espace. Accès déjà couvert par security.yaml
 * (^/desk/files/accounting -> ROLE_COMPTA, ROLE_ADMIN), pas de vérification
 * de rôle supplémentaire ici, même choix que le reste de /desk/files.
 */
#[Route('/desk/files/accounting')]
class AccountingController extends AbstractController
{
    #[Route('/documents', name: 'accounting_documents_index', methods: ['GET'])]
    public function documentsIndex(AccountingDocumentRepository $repository): Response
    {
        return $this->render('accounting/documents/index.html.twig', [
            'documents' => $repository->findAllOrdered(),
        ]);
    }

    #[Route('/documents/new/{type}', name: 'accounting_document_new', requirements: ['type' => 'quote|invoice'], methods: ['GET', 'POST'])]
    public function documentNew(string $type, Request $request, EntityManagerInterface $manager, AccountingDocumentRepository $repository): Response
    {
        $user = $this->getUser();

        $document = new AccountingDocument();
        $document->setType($type)
            ->setDate(new \DateTimeImmutable())
            ->setCorrespondentName($user->getFullName())
            ->setCorrespondentEmail($user->getEmail())
            ->addLine(new AccountingDocumentLine());

        $form = $this->createForm(AccountingDocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->reorderLines($document);

            $document->setNumber($repository->findNextNumber($type))
                ->setCreatedBy($user);

            $manager->persist($document);
            $manager->flush();

            $this->addFlash('success', AccountingDocument::TYPE_QUOTE === $type ? 'Devis créé' : 'Facture créée');

            return $this->redirectToRoute('accounting_document_show', ['id' => $document->getId()]);
        }

        return $this->render('accounting/documents/form.html.twig', [
            'document' => $document,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/documents/{id}/edit', name: 'accounting_document_edit', methods: ['GET', 'POST'])]
    public function documentEdit(AccountingDocument $document, Request $request, EntityManagerInterface $manager): Response
    {
        $form = $this->createForm(AccountingDocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->reorderLines($document);
            $manager->flush();

            return $this->redirectToRoute('accounting_document_show', ['id' => $document->getId()]);
        }

        return $this->render('accounting/documents/form.html.twig', [
            'document' => $document,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Page imprimable (bouton "Imprimer / Enregistrer en PDF" côté client,
     * cf. templates/accounting/documents/show.html.twig et
     * assets/accounting/document-print.css) : pas de génération PDF côté
     * serveur, choix fait avec l'utilisatrice pour ne pas ajouter de
     * dépendance PHP juste pour ça.
     */
    #[Route('/documents/{id}', name: 'accounting_document_show', methods: ['GET'])]
    public function documentShow(AccountingDocument $document): Response
    {
        return $this->render('accounting/documents/show.html.twig', [
            'document' => $document,
        ]);
    }

    #[Route('/documents/{id}', name: 'accounting_document_delete', methods: ['DELETE'])]
    public function documentDelete(AccountingDocument $document, Request $request, EntityManagerInterface $manager): Response
    {
        if ($this->isCsrfTokenValid('delete_accounting_document'.$document->getId(), $request->request->get('_token'))) {
            $manager->remove($document);
            $manager->flush();

            $this->addFlash('success', 'Document supprimé');
        }

        return $this->redirectToRoute('accounting_documents_index');
    }

    #[Route('/treasury', name: 'accounting_treasury_index', methods: ['GET'])]
    public function treasuryIndex(LedgerEntryRepository $repository): Response
    {
        return $this->render('accounting/treasury/index.html.twig', [
            'entries' => $repository->findAllOrdered(),
            'balance' => $repository->getBalance(),
        ]);
    }

    #[Route('/treasury/new', name: 'accounting_treasury_new', methods: ['GET', 'POST'])]
    public function treasuryNew(Request $request, EntityManagerInterface $manager): Response
    {
        $entry = new LedgerEntry();
        $entry->setDate(new \DateTimeImmutable());

        $form = $this->createForm(LedgerEntryType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entry->setCreatedBy($this->getUser());

            $manager->persist($entry);
            $manager->flush();

            return $this->redirectToRoute('accounting_treasury_index');
        }

        return $this->render('accounting/treasury/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/treasury/{id}', name: 'accounting_treasury_delete', methods: ['DELETE'])]
    public function treasuryDelete(LedgerEntry $entry, Request $request, EntityManagerInterface $manager): Response
    {
        if ($this->isCsrfTokenValid('delete_ledger_entry'.$entry->getId(), $request->request->get('_token'))) {
            $manager->remove($entry);
            $manager->flush();
        }

        return $this->redirectToRoute('accounting_treasury_index');
    }

    /**
     * CollectionType (lines) arrive dans l'ordre du formulaire mais sans
     * notion de position tant qu'on ne la fixe pas explicitement :
     * AccountingDocumentLine::$position (affichage, cf. show.html.twig)
     * recalculée à chaque sauvegarde plutôt que gérée par un JS de tri
     * séparé, la seule source d'ordre étant l'ordre de saisie/suppression.
     */
    private function reorderLines(AccountingDocument $document): void
    {
        foreach ($document->getLines() as $index => $line) {
            $line->setPosition($index);
        }
    }
}
