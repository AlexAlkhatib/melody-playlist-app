<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Morceaux;
use App\Entity\User;
use App\Form\MorceauxType;
use App\Repository\GenreRepository;
use App\Repository\MorceauxRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/morceaux')]
#[IsGranted('ROLE_USER')]
class MorceauxController extends AbstractController
{
    private const PER_PAGE = 9;

    #[Route('', name: 'morceaux.index', methods: ['GET'])]
    public function index(
        MorceauxRepository $repository,
        GenreRepository $genreRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $term = (string) $request->query->get('q', '');

        $morceaux = $paginator->paginate(
            $repository->searchQuery($term),
            $request->query->getInt('page', 1),
            self::PER_PAGE
        );

        return $this->render('morceaux/index.html.twig', [
            'morceaux' => $morceaux,
            'q' => $term,
            'genres' => $genreRepository->findAllOrdered(),
        ]);
    }

    #[Route('/nouveau', name: 'morceaux.new', methods: ['GET', 'POST'])]
    public function new(Request $request, MorceauxRepository $repository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $morceaux = new Morceaux();
        $morceaux->setCreatedBy($user);

        $form = $this->createForm(MorceauxType::class, $morceaux);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $repository->save($morceaux, true);

            $this->addFlash('success', sprintf('Le morceau « %s » a été ajouté au catalogue.', $morceaux->getNomMorceaux()));

            return $this->redirectToRoute('morceaux.show', ['id' => $morceaux->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('morceaux/new.html.twig', [
            'morceaux' => $morceaux,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'morceaux.show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Morceaux $morceaux): Response
    {
        return $this->render('morceaux/show.html.twig', [
            'morceaux' => $morceaux,
        ]);
    }

    #[Route('/{id}/modifier', name: 'morceaux.edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Morceaux $morceaux, MorceauxRepository $repository): Response
    {
        $this->assertCanManage($morceaux);

        $form = $this->createForm(MorceauxType::class, $morceaux);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $repository->save($morceaux, true);

            $this->addFlash('success', 'Le morceau a été mis à jour.');

            return $this->redirectToRoute('morceaux.show', ['id' => $morceaux->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('morceaux/edit.html.twig', [
            'morceaux' => $morceaux,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'morceaux.delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Morceaux $morceaux, MorceauxRepository $repository): Response
    {
        $this->assertCanManage($morceaux);

        if ($this->isCsrfTokenValid('delete-morceaux-'.$morceaux->getId(), (string) $request->request->get('_token'))) {
            $repository->remove($morceaux, true);
            $this->addFlash('success', 'Le morceau a été supprimé.');
        } else {
            $this->addFlash('danger', 'Jeton de sécurité invalide : suppression annulée.');
        }

        return $this->redirectToRoute('morceaux.index', [], Response::HTTP_SEE_OTHER);
    }

    private function assertCanManage(Morceaux $morceaux): void
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$morceaux->isEditableBy($user)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce morceau.');
        }
    }
}
