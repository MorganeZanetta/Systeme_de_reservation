<?php
namespace App\Models;

class Role {
    private ?int $Id_role = null;
    private string $libelle_role = '';
    
    // Un rôle peut être attribué à plusieurs utilisateurs
    private array $utilisateurs = []; 

    // Getters et Setters
    public function setIdRol(?int $id): void {
        $this->Id_role = $id;
    }
    public function getIdRol(): ?int {
        return $this->Id_role;
    }

    public function setLib(?string $libelle): void {
        $this->libelle_role = $libelle ?? '';
    }
    public function getLib(): string {
        return $this->libelle_role;
    }

    // Gestion de la relation 1,n
    public function getUtilisateurs(): array {
        return $this->utilisateurs;
    }
    
    public function addUtilisateur(Utilisateur $utilisateur): void {
        $this->utilisateurs[] = $utilisateur;
    }
}