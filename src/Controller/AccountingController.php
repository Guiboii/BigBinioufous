<?php

namespace App\Controller;

use App\Entity\AccountingDocument;
use App\Entity\AccountingDocumentLine;
use App\Entity\Client;
use App\Entity\LedgerEntry;
use App\Form\AccountingDocumentType;
use App\Form\ClientType;
use App\Form\LedgerEntryType;
use App\Repository\AccountingDocumentRepository;
use App\Repository\ClientRepository;
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
        $document = $this->initializeNewDocument($type, null);

        return $this->handleDocumentForm($document, $request, $manager, $repository);
    }

    /**
     * Étape intermédiaire avant la facture "libre" ci-dessus : proposer de
     * partir d'un devis existant plutôt que de foncer sur un formulaire
     * vide (cf. le lien "Nouvelle facture" de documents/index.html.twig).
     * Devis non filtrés sur "déjà facturé ou non" : rien n'empêche de
     * facturer un même devis en plusieurs fois (acompte, tranches...), donc
     * pas de notion de devis "consommé" à ce stade. En revanche
     * findQuotesForInvoicing() exclut les devis explicitement retirés de
     * cette liste (cf. toggleQuoteInvoicing() ci-dessous).
     */
    #[Route('/documents/new/invoice/choose-quote', name: 'accounting_invoice_choose_quote', methods: ['GET'])]
    public function invoiceChooseQuote(AccountingDocumentRepository $repository): Response
    {
        return $this->render('accounting/documents/choose_quote.html.twig', [
            'quotes' => $repository->findQuotesForInvoicing(),
        ]);
    }

    /**
     * Retire/remet un devis dans le sélecteur ci-dessus (pas une
     * suppression, cf. AccountingDocument::$excludedFromInvoicing) :
     * accessible depuis le sélecteur lui-même (pour l'alléger tout de
     * suite) et depuis la liste complète (pour annuler, un devis masqué n'y
     * disparaît pas).
     */
    #[Route('/documents/{id}/toggle-invoicing', name: 'accounting_document_toggle_invoicing', methods: ['POST'])]
    public function toggleQuoteInvoicing(AccountingDocument $document, Request $request, EntityManagerInterface $manager): Response
    {
        if (AccountingDocument::TYPE_QUOTE === $document->getType()
            && $this->isCsrfTokenValid('toggle_invoicing'.$document->getId(), $request->request->get('_token'))
        ) {
            $document->setExcludedFromInvoicing(!$document->isExcludedFromInvoicing());
            $manager->flush();
        }

        // 'redirect' contrôlé par le template (pas la requête utilisatrice
        // au sens large) : reste volontairement une valeur d'une liste
        // fermée de noms de route plutôt qu'une URL brute, pas de redirection
        // ouverte possible.
        $redirectRoute = 'choose_quote' === $request->request->get('redirect')
            ? 'accounting_invoice_choose_quote'
            : 'accounting_documents_index';

        return $this->redirectToRoute($redirectRoute);
    }

    /**
     * Facture créée à partir d'un devis existant (cf. le bouton "Facturer ce
     * devis" sur accounting/documents/show.html.twig) : client et lignes
     * copiés dans initializeNewDocument() plutôt que de forcer la ressaisie,
     * l'inscription à la volée (route ci-dessus, sans devis) reste possible
     * en parallèle.
     */
    #[Route('/documents/{quote}/invoice', name: 'accounting_document_new_from_quote', methods: ['GET', 'POST'])]
    public function documentNewFromQuote(AccountingDocument $quote, Request $request, EntityManagerInterface $manager, AccountingDocumentRepository $repository): Response
    {
        if (AccountingDocument::TYPE_QUOTE !== $quote->getType()) {
            throw $this->createNotFoundException();
        }

        $document = $this->initializeNewDocument(AccountingDocument::TYPE_INVOICE, $quote);

        return $this->handleDocumentForm($document, $request, $manager, $repository);
    }

    private function initializeNewDocument(string $type, ?AccountingDocument $sourceQuote): AccountingDocument
    {
        $user = $this->getUser();

        $document = new AccountingDocument();
        $document->setType($type)
            ->setDate(new \DateTimeImmutable())
            ->setCorrespondentName($user->getFullName())
            ->setCorrespondentEmail($user->getEmail());

        if (null === $sourceQuote) {
            $document->addLine(new AccountingDocumentLine());

            return $document;
        }

        $document->setSourceQuote($sourceQuote)
            ->setClient($sourceQuote->getClient())
            ->setClientName($sourceQuote->getClientName())
            ->setClientAddress($sourceQuote->getClientAddress())
            ->setClientContact($sourceQuote->getClientContact());

        foreach ($sourceQuote->getLines() as $line) {
            $document->addLine((new AccountingDocumentLine())
                ->setLabel($line->getLabel())
                ->setUnitPrice($line->getUnitPrice())
                ->setQuantity($line->getQuantity()));
        }

        return $document;
    }

    private function handleDocumentForm(AccountingDocument $document, Request $request, EntityManagerInterface $manager, AccountingDocumentRepository $repository): Response
    {
        $form = $this->createForm(AccountingDocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->reorderLines($document);

            $isNew = null === $document->getId();
            if ($isNew) {
                $document->setNumber($repository->findNextNumber($document->getType()))
                    ->setCreatedBy($this->getUser());
            }

            $manager->persist($document);
            $manager->flush();

            if ($isNew) {
                $this->addFlash('success', AccountingDocument::TYPE_QUOTE === $document->getType() ? 'Devis créé' : 'Facture créée');
            }

            return $this->redirectToRoute('accounting_document_show', ['id' => $document->getId()]);
        }

        return $this->render('accounting/documents/form.html.twig', [
            'document' => $document,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/documents/{id}/edit', name: 'accounting_document_edit', methods: ['GET', 'POST'])]
    public function documentEdit(AccountingDocument $document, Request $request, EntityManagerInterface $manager, AccountingDocumentRepository $repository): Response
    {
        return $this->handleDocumentForm($document, $request, $manager, $repository);
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

    #[Route('/clients', name: 'accounting_clients_index', methods: ['GET'])]
    public function clientsIndex(ClientRepository $repository): Response
    {
        return $this->render('accounting/clients/index.html.twig', [
            'clients' => $repository->findAllOrdered(),
        ]);
    }

    #[Route('/clients/new', name: 'accounting_client_new', methods: ['GET', 'POST'])]
    public function clientNew(Request $request, EntityManagerInterface $manager): Response
    {
        $client = new Client();

        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($client);
            $manager->flush();

            $this->addFlash('success', 'Client créé');

            return $this->redirectToRoute('accounting_clients_index');
        }

        return $this->render('accounting/clients/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/clients/{id}/edit', name: 'accounting_client_edit', methods: ['GET', 'POST'])]
    public function clientEdit(Client $client, Request $request, EntityManagerInterface $manager): Response
    {
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();

            return $this->redirectToRoute('accounting_clients_index');
        }

        return $this->render('accounting/clients/form.html.twig', [
            'client' => $client,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/clients/{id}', name: 'accounting_client_delete', methods: ['DELETE'])]
    public function clientDelete(Client $client, Request $request, EntityManagerInterface $manager): Response
    {
        if ($this->isCsrfTokenValid('delete_client'.$client->getId(), $request->request->get('_token'))) {
            $manager->remove($client);
            $manager->flush();

            $this->addFlash('success', 'Client supprimé');
        }

        return $this->redirectToRoute('accounting_clients_index');
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
