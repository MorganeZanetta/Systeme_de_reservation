DROP DATABASE IF EXISTS SYSTEME_DE_RESERVATION_DES_SALLES_ET_MATERIEL;
CREATE DATABASE SYSTEME_DE_RESERVATION_DES_SALLES_ET_MATERIEL CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE SYSTEME_DE_RESERVATION_DES_SALLES_ET_MATERIEL;

-- 1. Table de référence : données statiques qui normalisent les choix dans d'autres tables 

CREATE TABLE role(
   Id_role INT UNSIGNED AUTO_INCREMENT NOT NULL, -- une colonne ne peut contenir que des entiers positifs et zéro et l'ID s'autoincrémente
   libelle_role VARCHAR(30) NOT NULL, -- rôles créés qui ne dépassent pas les 30 caractères espaces inclus et pas d'absence de valeur
   PRIMARY KEY(Id_role) -- chaque rôle aura un seul numéro -- entité role (cardinalité 1,n) liée à l'entité utilisateur (cardinalité 1,1)
) ENGINE=InnoDB; -- le moteur de stockage utilisé pour gérer physiquement la table

/*
InnoDB supporte les contraintes d'intégrité référentielle (vérifie la cohérence des liens)
InnoDB garantit que les opérations sont sécurisées (supporte les transactions ACID)
InnoDB permet à plusieurs personnes de travailler sur la même table simultanément sans ralentissement (verrouillage à la ligne)
InnoDB possède un journal (log) qui lui permet de terminer les écritures en cours ou de réparer la table automatiquement au redémarrage (récupération après crash)
*/

CREATE TABLE port(
   Id_port INT UNSIGNED AUTO_INCREMENT NOT NULL, -- une colonne ne peut contenir que des entiers positifs et zéro et l'ID s'autoincrémente
   identite_port VARCHAR(50) NOT NULL, -- rôles créés qui ne dépassent pas les 30 caractères espaces inclus et pas d'absence de valeur
   PRIMARY KEY(Id_port) -- chaque port aura un seul numéro -- entité port (cardinalité 1,n) liée à l'entité utilisateur (cardinalité 1,1)
) ENGINE=InnoDB;

-- 2. Tables principales -- c'est le coeur du système

CREATE TABLE utilisateur(
   Id_utilisateur INT UNSIGNED AUTO_INCREMENT NOT NULL, -- une colonne ne peut contenir que des entiers positifs et zéro et l'ID s'autoincrémente
   identifiant_utilisateur VARCHAR(50) NOT NULL UNIQUE, -- utilisateurs créés qui ne dépassent pas les 50 caractères espaces inclus et pas d'absence de valeur et l'identifiant doit être unique
   prenom_utilisateur VARCHAR(50) NOT NULL, -- prénoms créés qui ne dépassent pas les 50 caractères espaces inclus et pas d'absence de valeur
   nom_utilisateur VARCHAR(50) NOT NULL, -- noms créés qui ne dépassent pas les 50 caractères espaces inclus et pas d'absence de valeur
   e_mail_utilisateur VARCHAR(100) NOT NULL UNIQUE, -- adresses mails créées qui ne dépassent pas les 100 caractères espaces inclus et pas d'absence de valeur et une valeur unique
   mot_de_passe_utilisateur VARCHAR(255) NOT NULL, -- MDP créés qui ne dépassent pas les 255 caractères espaces inclus et pas d'absence de valeur
   Id_role INT UNSIGNED NOT NULL, -- entité utilisateur (cardinalité 1,1) liée à l'entité role (cardinalité 1,n), la cardinalité la plus forte s'insère dans l'entité de la cardinalité la plus faible
   Id_port INT UNSIGNED NOT NULL, -- entité utilisateur (cardinalité 1,1) liée à l'entité port (cardinalité 1,n), la cardinalité la plus forte s'insère dans l'entité de la cardinalité la plus faible
   PRIMARY KEY(Id_utilisateur), -- chaque utilisateur aura un seul numéro -- entité utilisateur (cardinalité 0,n) liée à l'entité reservation (cardinalité 1,1)
    -- Empêche de supprimer un rôle si des utilisateurs le possèdent !!!
   CONSTRAINT fk_uti_rol FOREIGN KEY(Id_role) REFERENCES role(Id_role) ON DELETE RESTRICT, -- dans la table utilisateur une clé étrangère (colonne Id_role) pointe vers une autre table role
      -- Empêche de supprimer un port si des utilisateurs le possèdent !!!
   CONSTRAINT fk_uti_por FOREIGN KEY(Id_port) REFERENCES port(Id_port) ON DELETE RESTRICT -- dans la table utilisateur une clé étrangère (colonne Id_port) pointe vers une autre table port
   ) ENGINE=InnoDB;


CREATE TABLE salle(
   Id_salle INT UNSIGNED AUTO_INCREMENT NOT NULL, -- une colonne ne peut contenir que des entiers positifs et zéro et l'ID s'autoincrémente
   nom_salle VARCHAR(50) NOT NULL, -- noms de salle qui ne dépassent pas les 30 caractères espaces inclus et pas d'absence de valeur
   capacite_salle INT UNSIGNED NOT NULL, -- une colonne ne peut contenir que des entiers positifs et zéro
   localisation_salle VARCHAR(50) NOT NULL, -- localisations de salle qui ne dépassent pas les 30 caractères espaces inclus et pas d'absence de valeur
   PRIMARY KEY(Id_salle) -- chaque salle aura un seul numéro -- entité salle (cardinalité 0,n) liée à l'entité reservation (cardinalité O,n)
)ENGINE=InnoDB;

CREATE TABLE materiel(
   Id_materiel INT UNSIGNED AUTO_INCREMENT NOT NULL, -- une colonne ne peut contenir que des entiers positifs et zéro et l'ID s'autoincrémente
   type_materiel VARCHAR(50) NOT NULL, -- type de matériel qui ne dépassent pas les 20 caractères espaces inclus et pas d'absence de valeur
   numero_materiel INT UNSIGNED NOT NULL UNIQUE, -- une colonne ne peut contenir que des entiers positifs et zéro
   photo_materiel MEDIUMBLOB NOT NULL, -- accepte les formats d'image standard
   PRIMARY KEY(Id_materiel) -- chaque matériel aura un seul numéro -- entité materiel (cardinalité 0,n) liée à l'entité reservation (cardinalité 0,n)
)ENGINE=InnoDB;

CREATE TABLE reservation(
   Id_reservation INT UNSIGNED AUTO_INCREMENT NOT NULL, -- une colonne ne peut contenir que des entiers positifs et zéro et l'ID s'autoincrémente
   motif_reservation VARCHAR(50) NOT NULL, -- motifs qui ne dépassent pas les 50 caractères espaces inclus et pas d'absence de valeur
   date_debut_reservation DATE NOT NULL, -- type de données qui stocke une date, obligation de contenir une valeur
   date_fin_reservation DATE NOT NULL, -- type de données qui stocke une date, obligation de contenir une valeur
   creneau_reservation ENUM('Matin (09H00 / 12H00)', 'Après-midi (13H00 / 17H00)', 'Journée complète') NOT NULL, -- créneau qui ne dépassent pas les 30 caractères espaces inclus et pas d'absence de valeur
   Id_utilisateur INT UNSIGNED NOT NULL, -- entité utilisateur (cardinalité O,n) liée à l'entité reservation (cardinalité 1,1), la cardinalité la plus forte s'insère dans l'entité de la cardinalité la plus faible
   PRIMARY KEY(Id_reservation), -- chaque réservation aura un seul numéro -- entité reservation (cardinalité 0,n) liée à l'entité salle et à l'entité materiel (cardinalité 0,n)
     -- Supprime la réservation si l'utilisateur associé est supprimé !!!
   CONSTRAINT fk_uti_res FOREIGN KEY(Id_utilisateur) REFERENCES utilisateur(Id_utilisateur) ON DELETE CASCADE,
   CONSTRAINT `check_dates` CHECK (`date_debut_reservation` <= `date_fin_reservation`)
)ENGINE=InnoDB;

-- 3. Table de journalisation : gère l'historique des changements à l'aide de logs

CREATE TABLE reservation_log(
   Id_reservation_log INT UNSIGNED AUTO_INCREMENT NOT NULL, -- une colonne ne peut contenir que des entiers positifs et zéro et l'ID s'autoincrémente
   action_reservation_log VARCHAR(30) NOT NULL, -- types d'action (insert, update, delete) créés qui ne dépassent pas les 30 caractères espaces inclus et pas d'absence de valeur
   description_reservation_log VARCHAR(255) NOT NULL, -- description créée qui ne dépasse pas les 255 caractères espaces inclus et pas d'absence de valeur
   old_data_reservation_log JSON, -- old data un type de données structuré, création d'index et fonctions comme JSON_EXTRACT, JSON_SET, ou des opérateurs (->>) pour manipuler les données
   new_data_reservation_log JSON, -- new data un type de données structuré, création d'index et fonctions comme JSON_EXTRACT, JSON_SET, ou des opérateurs (->>) pour manipuler les données
   timestamp_reservation_log TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, -- pour des événements précis dans le temps (ex : date de création d'une réservation, log système)
   Id_reservation INT UNSIGNED NULL, -- entité reservation_log (cardinalité 1,1) liée à l'entité reservation (cardinalité 0,n), la cardinalité la plus forte s'insère dans l'entité de la cardinalité la plus faible
   PRIMARY KEY(Id_reservation_log), -- chaque log aura un seul numéro -- entité reservation_log (cardinalité 1,1) liée à l'entité reservation (cardinalité 0,n)
    -- Garde une trace de l'historique même si l'objet parent n'existe plus
    CONSTRAINT fk_reservation FOREIGN KEY (Id_reservation) REFERENCES reservation(Id_reservation)
) ENGINE=InnoDB; 

-- 4. Tables de liaison : gèrent les relations de type Plusieurs-à-plusieurs

-- Supprime en cascade des données des tables de liaison !!!

CREATE TABLE lie_a_la_salle(
   Id_salle INT UNSIGNED NOT NULL,
   Id_port INT UNSIGNED NOT NULL,
   PRIMARY KEY(Id_salle, Id_port),
   CONSTRAINT fk_las_sal_Id FOREIGN KEY(Id_salle) REFERENCES salle(Id_salle) ON DELETE CASCADE,
   CONSTRAINT fk_las_por_Id FOREIGN KEY(Id_port) REFERENCES port(Id_port) ON DELETE CASCADE
)ENGINE=InnoDB;

CREATE TABLE lie_au_materiel(
   Id_materiel INT UNSIGNED NOT NULL,
   Id_port INT UNSIGNED NOT NULL,
   PRIMARY KEY(Id_materiel, Id_port),
   CONSTRAINT fk_lam_mat_Id FOREIGN KEY(Id_materiel) REFERENCES materiel(Id_materiel) ON DELETE CASCADE,
   CONSTRAINT fk_lam_por_Id FOREIGN KEY(Id_port) REFERENCES port(Id_port) ON DELETE CASCADE
)ENGINE=InnoDB;

CREATE TABLE reservation_salle(
   Id_salle INT UNSIGNED NOT NULL,
   Id_reservation INT UNSIGNED NOT NULL,
   PRIMARY KEY(Id_salle, Id_reservation),
   CONSTRAINT fk_rs_sal_Id FOREIGN KEY(Id_salle) REFERENCES salle(Id_salle) ON DELETE CASCADE,
   CONSTRAINT fk_rs_res_Id FOREIGN KEY(Id_reservation) REFERENCES reservation(Id_reservation) ON DELETE CASCADE
)ENGINE=InnoDB;

CREATE TABLE reservation_materiel(
   Id_reservation INT UNSIGNED NOT NULL,
   Id_materiel INT UNSIGNED NOT NULL,
   PRIMARY KEY(Id_reservation, Id_materiel),
   CONSTRAINT fk_rm_res_Id FOREIGN KEY(Id_reservation) REFERENCES reservation(Id_reservation) ON DELETE CASCADE, 
   CONSTRAINT fk_rm_mat_Id FOREIGN KEY(Id_materiel) REFERENCES materiel(Id_materiel) ON DELETE CASCADE
)ENGINE=InnoDB;


-- =====================================================================
-- JEU DE DONNÉES DE TEST : SYSTEME_DE_RESERVATION_DES_SALLES_ET_MATERIEL
-- =====================================================================

USE SYSTEME_DE_RESERVATION_DES_SALLES_ET_MATERIEL;

-- ============================================================================
-- 1. INSERTION DES TABLES DE RÉFÉRENCE (Obligatoire avant les utilisateurs)
-- ============================================================================

INSERT INTO role (Id_role, libelle_role) VALUES 
(1, 'Administrateur'),
(2, 'Utilisateur');

INSERT INTO port (Id_port, identite_port) VALUES 
(1, 'Terminal de Chargement 1'),
(2, 'Terminal Passagers 2'),
(3, 'Zone Technique 3'),
(4, 'Quai d''Honneur 4'),
(5, 'Zone de Fret 5');


-- ============================================================================
-- 2. INSERTION DES TABLES PRINCIPALES
-- ============================================================================

-- Les adresses e-mails passées de l'université au domaine du port (@port-maritime.fr)
-- Ajout de la colonne Id_port indispensable selon la contrainte de votre table
INSERT INTO utilisateur (identifiant_utilisateur, prenom_utilisateur, nom_utilisateur, e_mail_utilisateur, mot_de_passe_utilisateur, Id_role, Id_port) VALUES 
('jlebreton', 'Jean', 'Lebreton', 'j.lebreton@port-maritime.fr', '$2y$10$abc456...', 2, 1),   -- Affecté au Port 1
('amariani', 'Antoine', 'Mariani', 'a.mariani@port-maritime.fr', '$2y$10$def789...', 2, 2), -- Affecté au Port 2
('fcolin', 'Françoise', 'Colin', 'f.colin@port-maritime.fr', '$2y$10$ghi012...', 2, 3),     -- Affecté au Port 3
('capitaine', 'Marc', 'Durand', 'm.durand@port-maritime.fr', '$2y$10$jkl345...', 1, 4);      -- Affecté au Port 4

-- Insertion des Salles du port (Id_salle de 1 à 5)
INSERT INTO salle (nom_salle, capacite_salle, localisation_salle) VALUES 
('Zone de Briefing Nord', 50, 'Hangar 12 - RDC'),
('Salle de Crise', 20, 'Capitainerie - 2ème Étage'),
('Bureau des Douanes', 10, 'Terminal Passagers - RDC'),
('Salle de Formation Sécurité', 30, 'Bâtiment Technique - 1er Étage'),
('Espace Armateurs', 15, 'Maison du Port - RDC');

-- Insertion des Matériels portuaires (Id_materiel de 1 à 6)
INSERT INTO materiel (type_materiel, numero_materiel) VALUES 
('Grue Mobile Légère', 8001),
('Élévateur Thermique', 8002),
('Radio VHF Portative', 9001),
('Radio VHF Portative', 9002),
('Kit d''intervention Pollution', 3001),
('Projecteur de Zone LED', 4001);

-- Insertion des Réservations (Id_reservation de 1 à 5)
-- Dates adaptées au format DATE (sans les heures, non supportées par votre type DATE)
INSERT INTO reservation (motif_reservation, date_debut_reservation, date_fin_reservation, creneau_reservation, Id_utilisateur) VALUES 
('Briefing Déchargement Cargo', '2026-05-20', '2026-05-20', 'Matin (09H00 / 12H00)', 1),
('Inspection Douanière Urgente', '2026-05-20', '2026-05-20', 'Après-midi (13H00 / 17H00)', 2),
('Formation SST Dockers', '2026-05-21', '2026-05-21', 'Matin (09H00 / 12H00)', 3),
('Cellule de Crise Météo (Alerte)', '2026-05-22', '2026-05-22', 'Journée complète', 2),
('Réunion d''attribution des Postes', '2026-05-22', '2026-05-22', 'Matin (09H00 / 12H00)', 4);


-- ============================================================================
-- 3. INSERTION DES TABLES DE LIAISON (Plusieurs-à-Plusieurs)
-- ============================================================================

-- Liaison Salles <-> Infrastructures du Port
INSERT INTO lie_a_la_salle (Id_salle, Id_port) VALUES 
(1, 1), 
(3, 2), 
(4, 3), 
(5, 4), 
(3, 5); 

-- Liaison Matériels <-> Infrastructures du Port
INSERT INTO lie_au_materiel (Id_materiel, Id_port) VALUES 
(1, 1), 
(3, 2), 
(4, 3), 
(5, 4), 
(3, 5); 

-- Liaison Réservations <-> Salles
INSERT INTO reservation_salle (Id_salle, Id_reservation) VALUES 
(1, 1), 
(3, 2), 
(4, 3), 
(2, 4), 
(5, 5); 

-- Liaison Réservations <-> Matériels
INSERT INTO reservation_materiel (Id_reservation, Id_materiel) VALUES 
(1, 1), 
(1, 3), 
(2, 2), 
(4, 4), 
(4, 5);