<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Repository\MorceauxRepository;
use App\Repository\PlaylistRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profil')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile', methods: ['GET'])]
    public function show(PlaylistRepository $playlistRepository, MorceauxRepository $morceauxRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('profile/show.html.twig', [
            'user' => $user,
            'nbPlaylists' => $playlistRepository->countByOwner($user),
            'nbMorceaux' => $morceauxRepository->countAll(),
        ]);
    }

    #[Route('/modifier', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UserRepository $userRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository->save($user, true);

            $this->addFlash('success', 'Vos informations ont été mises à jour.');

            return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/mot-de-passe', name: 'app_profile_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = (string) $form->get('currentPassword')->getData();

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $form->get('currentPassword')->addError(
                    new \Symfony\Component\Form\FormError('Le mot de passe actuel est incorrect.')
                );
            } else {
                /** @var string $newPassword */
                $newPassword = $form->get('plainPassword')->getData();
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                $userRepository->save($user, true);

                $this->addFlash('success', 'Votre mot de passe a été modifié.');

                return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('profile/password.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
}
