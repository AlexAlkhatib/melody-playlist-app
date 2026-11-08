<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\ContactRepository;
use App\Repository\GenreRepository;
use App\Repository\MorceauxRepository;
use App\Repository\PlaylistRepository;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly MorceauxRepository $morceauxRepository,
        private readonly PlaylistRepository $playlistRepository,
        private readonly GenreRepository $genreRepository,
        private readonly UserRepository $userRepository,
        private readonly ContactRepository $contactRepository,
    ) {
    }

    #[Route('/admin', name: 'admin', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'nbMorceaux' => $this->morceauxRepository->countAll(),
            'nbPlaylists' => $this->playlistRepository->countAll(),
            'nbGenres' => $this->genreRepository->countAll(),
            'nbUtilisateurs' => $this->userRepository->countAll(),
            'nbMessages' => $this->contactRepository->countAll(),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Melody — Administration');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');

        yield MenuItem::section('Catalogue');
        yield MenuItem::linkTo(MorceauxCrudController::class, 'Morceaux', 'fas fa-music');
        yield MenuItem::linkTo(GenreCrudController::class, 'Genres', 'fas fa-tags');

        yield MenuItem::section('Communauté');
        yield MenuItem::linkTo(PlaylistCrudController::class, 'Playlists', 'fas fa-list');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fas fa-users');

        yield MenuItem::section('Messages');
        yield MenuItem::linkTo(ContactCrudController::class, 'Messages de contact', 'fas fa-envelope');

        yield MenuItem::section();
        yield MenuItem::linkToRoute('Retour au site', 'fas fa-arrow-left', 'home.index');
    }
}
