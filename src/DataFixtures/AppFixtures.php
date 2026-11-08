<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Contact;
use App\Entity\Genre;
use App\Entity\Morceaux;
use App\Entity\Playlist;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Jeu de données de démonstration.
 *
 *   php bin/console doctrine:fixtures:load
 *
 * Comptes créés : admin@melody.test / user@melody.test (mot de passe : Password123!)
 */
class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $genres = [];
        foreach (['Rock', 'Pop', 'Jazz', 'Électro', 'Hip-Hop', 'Classique'] as $nomGenre) {
            $genre = (new Genre())->setNomGenre($nomGenre);
            $manager->persist($genre);
            $genres[$nomGenre] = $genre;
        }

        $admin = $this->createUser($manager, 'admin@melody.test', 'Alkhatib', 'Alex', ['ROLE_ADMIN']);
        $melomane = $this->createUser($manager, 'user@melody.test', 'Durand', 'Camille', ['ROLE_USER']);
        $third = $this->createUser($manager, 'lea@melody.test', 'Martin', 'Léa', ['ROLE_USER']);

        $catalogue = [
            ['Bohemian Rhapsody', 'Queen', '1975-10-31', 'Rock'],
            ['Imagine', 'John Lennon', '1971-10-11', 'Pop'],
            ['Take Five', 'The Dave Brubeck Quartet', '1959-09-21', 'Jazz'],
            ['Around the World', 'Daft Punk', '1997-03-17', 'Électro'],
            ['Lose Yourself', 'Eminem', '2002-10-28', 'Hip-Hop'],
            ['Clair de Lune', 'Claude Debussy', '1905-01-01', 'Classique'],
            ['Smells Like Teen Spirit', 'Nirvana', '1991-09-10', 'Rock'],
            ['Billie Jean', 'Michael Jackson', '1983-01-02', 'Pop'],
            ['So What', 'Miles Davis', '1959-08-17', 'Jazz'],
            ['One More Time', 'Daft Punk', '2000-10-30', 'Électro'],
            ['Juicy', 'The Notorious B.I.G.', '1994-08-09', 'Hip-Hop'],
            ['Nocturne op. 9 n°2', 'Frédéric Chopin', '1832-01-01', 'Classique'],
        ];

        $morceaux = [];
        foreach ($catalogue as $index => [$titre, $artiste, $date, $genre]) {
            $morceau = (new Morceaux())
                ->setNomMorceaux($titre)
                ->setArtiste($artiste)
                ->setDateSortie(new \DateTimeImmutable($date))
                ->setGenreid($genres[$genre])
                ->setCreatedBy($admin)
                // Pistes de démonstration générées par docs/tools/make_demo_audio.py
                ->setMusicName(sprintf('demo-%02d.wav', $index + 1));

            $manager->persist($morceau);
            $morceaux[] = $morceau;
        }

        $playlists = [
            ['Classiques du rock', $melomane, [0, 6]],
            ['Chill & jazz', $melomane, [2, 8]],
            ['Électro française', $third, [3, 9]],
            ['Le meilleur du hip-hop', $third, [4, 10]],
            ['Concentration classique', $admin, [5, 11]],
        ];

        foreach ($playlists as [$nom, $owner, $indexes]) {
            $playlist = (new Playlist())->setNomPlaylist($nom)->setUserid($owner);
            foreach ($indexes as $index) {
                $playlist->addMorceauxid($morceaux[$index]);
            }
            $playlist->syncNbMorceaux();
            $manager->persist($playlist);
        }

        $message = (new Contact())
            ->setPrenom('Chloé')
            ->setEmail('chloe@example.com')
            ->setSubject('Question sur les playlists')
            ->setMessage('Bonjour, est-il possible de rendre une playlist publique ? Merci !');
        $manager->persist($message);

        $manager->flush();
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(
        ObjectManager $manager,
        string $email,
        string $nom,
        string $prenom,
        array $roles
    ): User {
        $user = (new User())
            ->setEmail($email)
            ->setNom($nom)
            ->setPrenom($prenom)
            ->setRoles($roles);

        $user->setPassword($this->passwordHasher->hashPassword($user, 'Password123!'));

        $manager->persist($user);

        return $user;
    }
}
