<?php

namespace App\Tests\Controller;

use App\Entity\AccountingDocument;
use App\Entity\AccountingDocumentLine;
use App\Entity\Client;
use App\Tests\Support\AppWebTestCase;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Member area disabled on the prod_vitrine branch (see MemberAreaDisabledSubscriber): every route this class
 * exercises is redirected to the homepage, so these assertions no longer apply here.
 *
 * @group member-area
 */
class AccountingControllerTest extends AppWebTestCase
{
    public function testDocumentsIndexIsForbiddenWithoutRoleCompta(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->client->request('GET', '/desk/files/accounting/documents');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreatingAQuotePersistsItWithItsLineAndANumber(): void
    {
        $accountant = $this->createUser(['ROLE_COMPTA']);
        $this->client->loginUser($accountant);

        $crawler = $this->client->request('GET', '/desk/files/accounting/documents/new/quote');
        $form = $crawler->filter('form')->form();
        $form['accounting_document[date]'] = (new \DateTimeImmutable())->format('Y-m-d');
        $form['accounting_document[clientName]'] = 'Mairie de Quimper';
        $form['accounting_document[clientAddress]'] = '1 place de la mairie';
        $form['accounting_document[correspondentName]'] = $accountant->getFullName();
        $form['accounting_document[lines][0][label]'] = 'Prestation fanfare';
        $form['accounting_document[lines][0][unitPrice]'] = '350';
        $form['accounting_document[lines][0][quantity]'] = '1';
        $this->client->submit($form);

        $document = $this->entityManager->getRepository(AccountingDocument::class)->findOneBy(['clientName' => 'Mairie de Quimper']);
        $this->assertNotNull($document);
        $this->assertCount(1, $document->getLines());
        $this->assertSame(350.0, $document->getTotal());
    }

    public function testInvoiceFromQuoteCopiesClientAndLines(): void
    {
        $accountant = $this->createUser(['ROLE_COMPTA']);
        $this->client->loginUser($accountant);
        $quote = $this->persistQuote();

        $crawler = $this->client->request('GET', '/desk/files/accounting/documents/'.$quote->getId().'/invoice');
        $form = $crawler->filter('form')->form();
        $this->client->submit($form);

        $invoice = $this->entityManager->getRepository(AccountingDocument::class)->findOneBy(['type' => AccountingDocument::TYPE_INVOICE]);
        $this->assertNotNull($invoice);
        $this->assertSame('Mairie de Quimper', $invoice->getClientName());
        $this->assertCount(1, $invoice->getLines());
    }

    public function testCreatingAndDeletingAClient(): void
    {
        $accountant = $this->createUser(['ROLE_COMPTA']);
        $this->client->loginUser($accountant);

        $crawler = $this->client->request('GET', '/desk/files/accounting/clients/new');
        $form = $crawler->filter('form')->form();
        $form['client[name]'] = 'Comité des fêtes';
        $this->client->submit($form);
        $client = $this->entityManager->getRepository(Client::class)->findOneBy(['name' => 'Comité des fêtes']);
        $this->assertNotNull($client);

        $this->client->request('DELETE', '/desk/files/accounting/clients/'.$client->getId(), [
            '_token' => $this->csrfToken('delete_client'.$client->getId()),
        ]);

        $freshEntityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->assertNull($freshEntityManager->find(Client::class, $client->getId()));
    }

    private function persistQuote(): AccountingDocument
    {
        $quote = (new AccountingDocument())
            ->setType(AccountingDocument::TYPE_QUOTE)
            ->setNumber(1)
            ->setDate(new \DateTimeImmutable())
            ->setClientName('Mairie de Quimper')
            ->setClientAddress('1 place de la mairie')
            ->setCorrespondentName('Marine');
        $quote->addLine((new AccountingDocumentLine())->setLabel('Prestation')->setUnitPrice(350)->setQuantity(1));

        $this->entityManager->persist($quote);
        $this->entityManager->flush();

        return $quote;
    }
}
