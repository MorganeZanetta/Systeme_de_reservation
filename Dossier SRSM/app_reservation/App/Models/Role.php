<?php
namespace App\Models;

//----------------------------------------------------------------------------------------------
// Classe Role : Représente un rôle utilisateur dans l'application.
// Elle permet de définir les permissions associées à un groupe d'utilisateurs.
//----------------------------------------------------------------------------------------------

class Role {
    // Identifiant unique du rôle (nullable car l'objet peut être nouvellement créé).
    private ?int $Id_role = null;
    // Libellé du rôle (ex: "Administrateur", "Utilisateur", "Gestionnaire").
    private string $libelle_role = '';
    
    // Relation : Un rôle peut être attribué à plusieurs utilisateurs (Relation 1 à N).
    private array $utilisateurs = []; 

    // --- Getters et Setters ---

    // Accesseur et mutateur pour l'ID.
    public function setIdRol(?int $id): void {
        $this->Id_role = $id;
    }
    public function getIdRol(): ?int {
        return $this->Id_role;
    }
    // Accesseur et mutateur pour le nom du rôle.
    public function setLib(?string $libelle): void {
        // Utilisation de l'opérateur null coalescing pour garantir une chaîne vide si null.
        $this->libelle_role = $libelle ?? '';
    }
    public function getLib(): string {
        return $this->libelle_role;
    }

    // Gestion de la liste des utilisateurs possédant ce rôle.
    public function getUtilisateurs(): array {
        return $this->utilisateurs;
    }
    // Ajoute un utilisateur à la collection des membres de ce rôle.
    public function addUtilisateur(Utilisateur $utilisateur): void {
        $this->utilisateurs[] = $utilisateur;
    }
}