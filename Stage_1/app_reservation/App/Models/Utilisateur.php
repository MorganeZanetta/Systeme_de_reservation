<?php

namespace App\Models;

class Utilisateur {

    // 1. Les propriétés directes de la table SQL (passées en optionnelles pour éviter les erreurs d'initialisation)
    private ?int $Id_utilisateur = null;
    private ?string $identifiant_utilisateur = null;
    private ?string $nom_utilisateur = null;
    private ?string $prenom_utilisateur = null;
    private ?string $e_mail_utilisateur = null;
    private ?string $mot_de_passe_utilisateur = null;
    
    // 2. Les relations (Clés étrangères / Tables de liaison)
    private ?Role $role = null;
    private ?Port $port = null; 

    // 3. GETTERS et SETTERS

    public function getIdUtilisateur(): ?int {
        return $this->Id_utilisateur;
    }
    public function setIdUtilisateur(?int $id): void {
        $this->Id_utilisateur = $id;
    }

    public function getIdentifiant(): ?string {
        return $this->identifiant_utilisateur;
    }
    public function setIdentifiant(?string $identifiant): void {
        $this->identifiant_utilisateur = $identifiant;
    }

    public function getNomUtilisateur(): ?string {
        return $this->nom_utilisateur;
    }
    public function setNomUtilisateur(?string $nom): void {
        $this->nom_utilisateur = $nom;
    }

    public function getPrenomUtilisateur(): ?string {
        return $this->prenom_utilisateur;
    }
    public function setPrenomUtilisateur(?string $prenom): void {
        $this->prenom_utilisateur = $prenom;
    }

    public function getEmailUtilisateur(): ?string {
        return $this->e_mail_utilisateur;
    }
    public function setEmailUtilisateur(?string $email): void {
        $this->e_mail_utilisateur = $email;
    }

    public function getMdpUtilisateur(): ?string {
        return $this->mot_de_passe_utilisateur;
    }
    public function setMdpUtilisateur(?string $mdp): void {
        $this->mot_de_passe_utilisateur = $mdp;
    }
    
    public function getRole(): ?Role {
        return $this->role;
    }

    public function setRole(?Role $role): void {
        $this->role = $role;
    }

    public function getPort(): ?Port {
        return $this->port;
    }
    public function setPort(?Port $port): void {
        $this->port = $port;
    }
}

?>