<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Playlist;
use App\Entity\User;
use App\Form\PlaylistType;
use App\Repository\PlaylistRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/playlist')]
#[IsGranted('ROLE_USER')]
class PlaylistController extends AbstractController
{
    private const PER_PAGE = 10;

    #[Route('', name: 'app_playlist_index', methods: ['GET'])]
    public function index(
        PlaylistRepository $playlistRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $playlists = $paginator->paginate(
            $playlistRepository->findByOwnerQuery($user),
            $request->query->getInt('page', 1),
            self::PER_PAGE
        );

        return $this->render('playlist/index.html.twig', [
            'playlists' => $playlists,
        ]);
    }

    #[Route('/nouvelle', name: 'app_playlist_new', methods: ['GET', 'POST'])]
    public function new(Request $request, PlaylistRepository $playlistRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $playlist = new Playlist();
        $playlist->setUserid($user);

        $form = $this->createForm(PlaylistType::class, $playlist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $playlist->syncNbMorceaux();
            $playlistRepository->save($playlist, true);

            $this->addFlash('success', sprintf('La playlist « %s » a été créée.', $playlist->getNomPlaylist()));

            return $this->redirectToRoute('app_playlist_show', ['id' => $playlist->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('playlist/new.html.twig', [
            'playlist' => $playlist,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_playlist_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Playlist $playlist): Response
    {
        $this->assertCanManage($playlist);

        return $this->render('playlist/show.html.twig', [
            'playlist' => $playlist,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_playlist_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Playlist $playlist, PlaylistRepository $playlistRepository): Response
    {
        $this->assertCanManage($playlist);

        $form = $this->createForm(PlaylistType::class, $playlist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $playlist->syncNbMorceaux();
            $playlistRepository->save($playlist, true);

            $this->addFlash('success', 'La playlist a été mise à jour.');

            return $this->redirectToRoute('app_playlist_show', ['id' => $playlist->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('playlist/edit.html.twig', [
            'playlist' => $playlist,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_playlist_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Playlist $playlist, PlaylistRepository $playlistRepository): Response
    {
        $this->assertCanManage($playlist);

        if ($this->isCsrfTokenValid('delete-playlist-'.$playlist->getId(), (string) $request->request->get('_token'))) {
            $playlistRepository->remove($playlist, true);
            $this->addFlash('success', 'La playlist a été supprimée.');
        } else {
            $this->addFlash('danger', 'Jeton de sécurité invalide : suppression annulée.');
        }

        return $this->redirectToRoute('app_playlist_index', [], Response::HTTP_SEE_OTHER);
    }

    private function assertCanManage(Playlist $playlist): void
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$playlist->isOwnedBy($user)) {
            throw $this->createAccessDeniedException('Cette playlist ne vous appartient pas.');
        }
    }
}
