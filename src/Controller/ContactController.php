<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use App\Repository\ContactRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    public function __construct(
        private readonly string $contactEmail,
        private readonly string $mailerFrom,
    ) {
    }

    #[Route('/contact', name: 'contact.index', methods: ['GET', 'POST'])]
    public function index(Request $request, ContactRepository $repository, MailerInterface $mailer): Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $repository->save($contact, true);

            try {
                $email = (new Email())
                    ->from($this->mailerFrom)
                    ->replyTo((string) $contact->getEmail())
                    ->to($this->contactEmail)
                    ->subject('[Melody] '.$contact->getSubject())
                    ->text(sprintf(
                        "Nouveau message depuis le formulaire de contact.\n\nDe : %s <%s>\nSujet : %s\n\n%s",
                        (string) $contact->getPrenom(),
                        (string) $contact->getEmail(),
                        (string) $contact->getSubject(),
                        (string) $contact->getMessage()
                    ));

                $mailer->send($email);
                $this->addFlash('success', 'Merci ! Votre message a bien été envoyé, nous vous répondrons rapidement.');
            } catch (TransportExceptionInterface $e) {
                // Le message reste enregistré : il est visible dans le back-office.
                $this->addFlash('warning', 'Votre message a été enregistré, mais l\'email n\'a pas pu être envoyé.');
            }

            return $this->redirectToRoute('contact.index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form,
        ]);
    }
}
