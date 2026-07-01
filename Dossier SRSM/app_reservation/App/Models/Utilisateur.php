<?php

namespace App\Models;

//----------------------------------------------------------------------------------------------
// Classe Utilisateur : Représente un compte utilisateur dans le système.
// Elle mappe les colonnes de la table 'utilisateur' et gère ses associations.
//----------------------------------------------------------------------------------------------

class Utilisateur {

    // Les propriétés directes de la table SQL (passées en optionnelles pour éviter les erreurs d'initialisation).
    // Le typage nullable (?) permet une initialisation souple (ex: lors d'un nouveau compte en cours de saisie).
    private ?int $Id_utilisateur = null;
    private ?string $identifiant_utilisateur = null;
    private ?string $nom_utilisateur = null;
    private ?string $prenom_utilisateur = null;
    private ?string $e_mail_utilisateur = null;
    private ?string $mot_de_passe_utilisateur = null;
    
    // Les relations (Objets liés) : Permet de naviguer dans les entités associées.
    private ?Role $role = null;
    private ?Port $port = null; 

    // --- Getters et Setters ---

    // Accès à l'identifiant.
    public function getIdUtilisateur(): ?int {
        return $this->Id_utilisateur;
    }
    public function setIdUtilisateur(?int $id): void {
        $this->Id_utilisateur = $id;
    }
    // Identifiant de connexion.
    public function getIdentifiant(): ?string {
        return $this->identifiant_utilisateur;
    }
    public function setIdentifiant(?string $identifiant): void {
        $this->identifiant_utilisateur = $identifiant;
    }
    // Nom de famille.
    public function getNomUtilisateur(): ?string {
        return $this->nom_utilisateur;
    }
    public function setNomUtilisateur(?string $nom): void {
        $this->nom_utilisateur = $nom;
    }
    // Prénom.
    public function getPrenomUtilisateur(): ?string {
        return $this->prenom_utilisateur;
    }
    public function setPrenomUtilisateur(?string $prenom): void {
        $this->prenom_utilisateur = $prenom;
    }
    // Adresse email.
    public function getEmailUtilisateur(): ?string {
        return $this->e_mail_utilisateur;
    }
    public function setEmailUtilisateur(?string $email): void {
        $this->e_mail_utilisateur = $email;
    }
    // Mot de passe (doit être stocké sous forme de hash via password_hash).
    public function getMdpUtilisateur(): ?string {
        return $this->mot_de_passe_utilisateur;
    }
    public function setMdpUtilisateur(?string $mdp): void {
        $this->mot_de_passe_utilisateur = $mdp;
    }
    // Relation : Obtient le rôle associé à l'utilisateur.
    public function getRole(): ?Role {
        return $this->role;
    }

    public function setRole(?Role $role): void {
        $this->role = $role;
    }
    // Relation : Obtient le port auquel l'utilisateur est rattaché.
    public function getPort(): ?Port {
        return $this->port;
    }
    public function setPort(?Port $port): void {
        $this->port = $port;
    }
}

?>