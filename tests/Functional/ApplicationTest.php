<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Genre;
use App\Entity\Morceaux;
use App\Entity\Playlist;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Tests fonctionnels de bout en bout sur une base SQLite jetable.
 *
 * Lancement : php bin/phpunit
 */
class ApplicationTest extends WebTestCase
{
    private const PASSWORD = 'Password123!';

    public static function setUpBeforeClass(): void
    {
        $varDir = \dirname(__DIR__, 2).'/var';
        if (!is_dir($varDir) && !mkdir($varDir, 0777, true) && !is_dir($varDir)) {
            throw new \RuntimeException(sprintf('Impossible de créer le répertoire "%s".', $varDir));
        }

        $dbFile = $varDir.'/test.db';
        if (file_exists($dbFile)) {
            unlink($dbFile);
        }

        self::ensureKernelShutdown();
        static::bootKernel();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());

        self::ensureKernelShutdown();
    }

    public function testHomePageIsPublicAndShowsHero(): void
    {
        $client = self::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Votre musique');
    }

    public function testPublicPagesAreReachableWithoutLogin(): void
    {
        $client = self::createClient();

        foreach (['/login', '/register', '/contact'] as $uri) {
            $client->request('GET', $uri);
            self::assertResponseIsSuccessful(sprintf('La page %s doit être publique.', $uri));
        }
    }

    public function testCatalogueRedirectsAnonymousVisitorsToLogin(): void
    {
        $client = self::createClient();
        $client->request('GET', '/morceaux');

        self::assertResponseRedirects();
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testUserCanLogInAndBrowseTheCatalogue(): void
    {
        $client = self::createClient();
        $this->createUser('member@melody.test');

        $client->request('GET', '/login');
        $client->submitForm('Se connecter', [
            'email' => 'member@melody.test',
            'password' => self::PASSWORD,
        ]);

        self::assertResponseRedirects();
        $client->followRedirect();
        self::assertResponseIsSuccessful();

        $client->request('GET', '/morceaux');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Explorer');
    }

    public function testWrongPasswordDisplaysAnErrorMessage(): void
    {
        $client = self::createClient();
        $this->createUser('wrong@melody.test');

        $client->request('GET', '/login');
        $client->submitForm('Se connecter', [
            'email' => 'wrong@melody.test',
            'password' => 'not-the-right-password',
        ]);

        $client->followRedirect();
        self::assertSelectorExists('.toast--danger');
    }

    public function testConnectedUserCanAddATrack(): void
    {
        $client = self::createClient();
        $user = $this->createUser('creator@melody.test');
        $genre = $this->createGenre('Rock');

        $client->loginUser($user);

        $client->request('GET', '/morceaux/nouveau');
        self::assertResponseIsSuccessful();

        $client->submitForm('Enregistrer', [
            'morceaux[nomMorceaux]' => 'Smoke Test Track',
            'morceaux[artiste]' => 'Test Artist',
            'morceaux[genreid]' => (string) $genre->getId(),
        ]);

        self::assertResponseRedirects();

        $track = $this->entityManager()->getRepository(Morceaux::class)
            ->findOneBy(['nomMorceaux' => 'Smoke Test Track']);

        self::assertInstanceOf(Morceaux::class, $track);
        self::assertSame('Test Artist', $track->getArtiste());
        self::assertSame('creator@melody.test', $track->getCreatedBy()?->getEmail());
    }

    public function testPlaylistOfAnotherUserIsForbidden(): void
    {
        $client = self::createClient();
        $owner = $this->createUser('owner@melody.test');
        $intruder = $this->createUser('intruder@melody.test');

        $playlist = (new Playlist())->setNomPlaylist('Playlist privée')->setUserid($owner);
        $em = $this->entityManager();
        $em->persist($playlist);
        $em->flush();

        $client->loginUser($intruder);
        $client->request('GET', '/playlist/'.$playlist->getId());

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminAreaRequiresTheAdminRole(): void
    {
        $client = self::createClient();
        $member = $this->createUser('simple@melody.test');
        $admin = $this->createUser('admin@melody.test', ['ROLE_ADMIN']);

        $client->loginUser($member);
        $client->request('GET', '/admin');
        self::assertResponseStatusCodeSame(403);

        $client->loginUser($admin);
        $client->request('GET', '/admin');
        self::assertResponseIsSuccessful();
    }

    public function testContactFormSendsAMessage(): void
    {
        $client = self::createClient();
        $client->request('GET', '/contact');

        $client->submitForm('Envoyer le message', [
            'contact[prenom]' => 'Camille',
            'contact[email]' => 'camille@example.com',
            'contact[subject]' => 'Question',
            'contact[message]' => 'Bonjour, ceci est un message de test suffisamment long.',
        ]);

        self::assertResponseRedirects();
        $client->followRedirect();
        self::assertSelectorExists('.toast--success');
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(string $email, array $roles = ['ROLE_USER']): User
    {
        $container = static::getContainer();
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        $user = (new User())
            ->setEmail($email)
            ->setNom('Test')
            ->setPrenom('Utilisateur')
            ->setRoles($roles);
        $user->setPassword($hasher->hashPassword($user, self::PASSWORD));

        $em = $this->entityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function createGenre(string $nom): Genre
    {
        $genre = (new Genre())->setNomGenre($nom);
        $em = $this->entityManager();
        $em->persist($genre);
        $em->flush();

        return $genre;
    }

    private function entityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        return $em;
    }

    protected function tearDown(): void
    {
        $client = static::getClient();
        if ($client instanceof KernelBrowser) {
            $client->getKernel()->shutdown();
        }

        parent::tearDown();
    }
}
