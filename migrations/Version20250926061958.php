<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250926061958 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE board (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, game_id INTEGER NOT NULL, user_id INTEGER NOT NULL, width INTEGER NOT NULL, height INTEGER NOT NULL, fleet_json CLOB NOT NULL --(DC2Type:json)
        , placed_at DATETIME DEFAULT NULL, CONSTRAINT FK_58562B47E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_58562B47A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_58562B47E48FD905 ON board (game_id)');
        $this->addSql('CREATE INDEX IDX_58562B47A76ED395 ON board (user_id)');
        $this->addSql('CREATE TABLE game (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, player1_id INTEGER DEFAULT NULL, player2_id INTEGER DEFAULT NULL, current_turn_user_id_id INTEGER DEFAULT NULL, winner_user_id_id INTEGER DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, started_at DATETIME DEFAULT NULL, finished_at DATETIME DEFAULT NULL, settings_json CLOB NOT NULL --(DC2Type:json)
        , CONSTRAINT FK_232B318CC0990423 FOREIGN KEY (player1_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_232B318CD22CABCD FOREIGN KEY (player2_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_232B318CFC42E940 FOREIGN KEY (current_turn_user_id_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_232B318CCF1F155E FOREIGN KEY (winner_user_id_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_232B318CC0990423 ON game (player1_id)');
        $this->addSql('CREATE INDEX IDX_232B318CD22CABCD ON game (player2_id)');
        $this->addSql('CREATE INDEX IDX_232B318CFC42E940 ON game (current_turn_user_id_id)');
        $this->addSql('CREATE INDEX IDX_232B318CCF1F155E ON game (winner_user_id_id)');
        $this->addSql('CREATE TABLE move (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, game_id INTEGER NOT NULL, attacker_user_id INTEGER NOT NULL, x INTEGER NOT NULL, y INTEGER NOT NULL, result VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL, turn_index INTEGER NOT NULL, CONSTRAINT FK_EF3E3778E48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_EF3E3778A4B2B16B FOREIGN KEY (attacker_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_EF3E3778E48FD905 ON move (game_id)');
        $this->addSql('CREATE INDEX IDX_EF3E3778A4B2B16B ON move (attacker_user_id)');
        $this->addSql('CREATE TABLE ranking_snapshot (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, points INTEGER NOT NULL, wins INTEGER NOT NULL, losses INTEGER NOT NULL, timestamp DATETIME NOT NULL, CONSTRAINT FK_81224C25A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_81224C25A76ED395 ON ranking_snapshot (user_id)');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, is_banned BOOLEAN NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, wins INTEGER NOT NULL, losses INTEGER NOT NULL, points INTEGER NOT NULL, hit_count_total INTEGER NOT NULL, games_played INTEGER NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON "user" (username)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE board');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE move');
        $this->addSql('DROP TABLE ranking_snapshot');
        $this->addSql('DROP TABLE "user"');
    }
}
