<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\GenreRepository;
use App\Repository\MorceauxRepository;
use App\Repository\PlaylistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home.index', methods: ['GET'])]
    public function index(
        MorceauxRepository $morceauxRepository,
        PlaylistRepository $playlistRepository,
        GenreRepository $genreRepository
    ): Response {
        $user = $this->getUser();

        return $this->render('home.html.twig', [
            'nbMorceaux' => $morceauxRepository->countAll(),
            'nbPlaylists' => $playlistRepository->countAll(),
            'nbGenres' => $genreRepository->countAll(),
            'derniersMorceaux' => $morceauxRepository->findLatest(8),
            'genres' => $genreRepository->findAllOrdered(),
            'mesPlaylists' => $user instanceof User
                ? $playlistRepository->findBy(['userid' => $user], ['id' => 'DESC'], 3)
                : [],
        ]);
    }
}
