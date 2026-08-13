<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Folder;
use App\Repository\FolderRepository;
use App\Security\FolderWriteVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/desk/files/{space}/documents', requirements: ['space' => 'music|admin|accounting|other'])]
class DocumentController extends AbstractController
{
    /**
     * Dépose glisser-déposer : un fichier = un Document créé direct (nom
     * = nom de fichier), pas de métadonnées à saisir avant coup. Le champ
     * "path" (optionnel, ex. "02 Medley XXI/Hautbois Facile") vient du
     * glisser-déposer d'un dossier entier (assets/desk/quick-upload.js,
     * FileSystemEntry.fullPath) : reconstitue la même arborescence de
     * Folder côté site plutôt que de tout aplatir à la racine.
     */
    #[Route('', name: 'document_upload', methods: ['POST'])]
    public function upload(string $space, Request $request, EntityManagerInterface $manager, SluggerInterface $slugger, FolderRepository $folderRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted(FolderWriteVoter::WRITE, $space);

        if (!$this->isCsrfTokenValid('quick_upload', $request->request->get('_token'))) {
            return $this->json(['error' => 'invalid_token'], 403);
        }

        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'invalid_input'], 400);
        }

        // Capturé avant move() : File::move() renvoie un nouvel objet et ne
        // modifie pas $file sur place, un 2e appel à $file->getMimeType()
        // après coup viserait le fichier temporaire déjà déplacé/disparu.
        $mimeType = $file->getMimeType();

        if (!\in_array($mimeType, Folder::ALLOWED_MIME_TYPES[$space] ?? [], true)) {
            return $this->json(['error' => 'invalid_mimetype'], 400);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->getParameter('documents_directory'), $newFilename);
        } catch (FileException $e) {
            return $this->json(['error' => 'upload_failed'], 500);
        }

        $folder = $folderRepository->findOrCreateByPath($space, (string) $request->request->get('path'), $manager);

        $document = new Document();
        $document->setName($originalFilename)
            ->setFilename($newFilename)
            ->setMimeType($mimeType)
            ->setUploadedBy($this->getUser())
            ->setFolder($folder);

        $manager->persist($document);
        $manager->flush();

        return $this->json(['success' => true, 'id' => $document->getId()]);
    }

    #[Route('/{id}', name: 'document_delete', methods: ['DELETE'])]
    public function delete(string $space, Document $document, Request $request, EntityManagerInterface $manager): Response
    {
        $this->denyAccessUnlessGranted(FolderWriteVoter::WRITE, $space);

        if ($document->getFolder()->getSpace() !== $space) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete_document'.$document->getId(), $request->request->get('_token'))) {
            $manager->remove($document);
            $manager->flush();

            $this->addFlash('success', 'Document supprimé');
        }

        return $this->redirectToRoute('desk_files', array_merge(['space' => $space], $request->query->all()));
    }

    /**
     * Étoile/désétoile un document audio pour le membre connecté. Ouvert à
     * tout membre pouvant voir l'espace (même règle d'accès que le reste de
     * /desk/files/{space}, cf. security.yaml) : c'est une préférence
     * personnelle, pas une action d'écriture sur le fichier lui-même.
     */
    #[Route('/{id}/favorite', name: 'document_favorite_toggle', methods: ['POST'])]
    public function toggleFavorite(string $space, Document $document, Request $request, EntityManagerInterface $manager): Response
    {
        if ($document->getFolder()->getSpace() !== $space) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('toggle_favorite'.$document->getId(), $request->request->get('_token'))) {
            $user = $this->getUser();
            if ($document->getFavoritedBy()->contains($user)) {
                $document->removeFavoritedBy($user);
            } else {
                $document->addFavoritedBy($user);
            }
            $manager->flush();
        }

        return $this->redirectToRoute('desk_files', array_merge(['space' => $space], $request->query->all()));
    }

    /**
     * "Je joue cette partie" : coché par un membre sur un document audio de
     * l'espace musique (remplace desk_voice_toggle, cf. User::$playedDocuments).
     * Ouvert à tout membre pouvant voir l'espace, même règle que toggleFavorite
     * ci-dessus : préférence personnelle, pas une écriture sur le fichier.
     * Utilisé uniquement depuis /music (MusicController), d'où le retour au
     * referer plutôt qu'à desk_files.
     */
    #[Route('/{id}/played', name: 'document_played_toggle', methods: ['POST'])]
    public function togglePlayed(string $space, Document $document, Request $request, EntityManagerInterface $manager): Response
    {
        if ($document->getFolder()->getSpace() !== $space) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('toggle_played'.$document->getId(), $request->request->get('_token'))) {
            $user = $this->getUser();
            if ($document->getPlayedBy()->contains($user)) {
                $document->removePlayedBy($user);
            } else {
                $document->addPlayedBy($user);

                // Même rappel que l'ancien desk_voice_toggle : l'instrument
                // est facultatif sur le profil, mais se déclarer sur une
                // partie sans l'avoir renseigné laisse l'info incomplète.
                if (!$user->getInstrument()) {
                    $this->addFlash(
                        'info',
                        'N\'oublie pas de renseigner ton instrument sur ton profil, pour qu\'on sache qui joue quoi !'
                    );
                }
            }
            $manager->flush();
        }

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('music'));
    }

    /**
     * Déplace un document vers un autre dossier du même espace (ou sa
     * racine si target absent). Pas de risque de cycle ici contrairement à
     * FolderController::move() : un document n'a pas de descendants.
     */
    #[Route('/{id}/move', name: 'document_move', methods: ['POST'])]
    public function move(string $space, Document $document, Request $request, EntityManagerInterface $manager, FolderRepository $folderRepository): Response
    {
        $this->denyAccessUnlessGranted(FolderWriteVoter::WRITE, $space);

        if ($document->getFolder()->getSpace() !== $space) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('move_document'.$document->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('desk_files', ['space' => $space]);
        }

        $targetId = $request->request->get('target');
        $target = $targetId ? $folderRepository->find($targetId) : $folderRepository->findOrCreateRoot($space, $manager);

        if (!$target || $target->getSpace() !== $space) {
            throw $this->createNotFoundException();
        }

        $document->setFolder($target);
        $manager->flush();

        $this->addFlash('success', 'Document déplacé');

        return $this->redirectToRoute('desk_files', ['space' => $space, 'folder' => $target->getId()]);
    }
}
