<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Refonte 2.0 :
 *  - ajout du propriétaire d'un morceau (morceaux.user_id) ;
 *  - le fichier audio devient facultatif (métadonnées importées) ;
 *  - index sur la nouvelle clé étrangère.
 */
final class Version20261108120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute morceaux.user_id (propriétaire) et rend music_name nullable.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE morceaux CHANGE music_name music_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE morceaux ADD user_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_823125C3A76ED395 ON morceaux (user_id)');
        $this->addSql('ALTER TABLE morceaux ADD CONSTRAINT FK_823125C3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE morceaux DROP FOREIGN KEY FK_823125C3A76ED395');
        $this->addSql('DROP INDEX IDX_823125C3A76ED395 ON morceaux');
        $this->addSql('ALTER TABLE morceaux DROP user_id');
        $this->addSql('ALTER TABLE morceaux CHANGE music_name music_name VARCHAR(255) NOT NULL');
    }
}
