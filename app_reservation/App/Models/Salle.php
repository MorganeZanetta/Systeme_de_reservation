<?php
namespace App\Models;

//----------------------------------------------------------------------------------------------
// Classe Salle : Représente une salle physique dans l'application.
// Cette classe est utilisée pour mapper les données SQL provenant de la table 'salle'.
//----------------------------------------------------------------------------------------------

class Salle {

    // Les propriétés : correspondent aux colonnes SQL.
    // L'ID est typé nullable car, lors de la création d'un objet en PHP avant insertion, il n'existe pas en BDD.
    private ?int $Id_salle = null;
    private string $nom_salle = '';
    private int $capacite_salle = 0;
    private string $localisation_salle = '';

    // Relation : Une salle est généralement rattachée à un port (Objet Port).
    private ?Port $port = null;  

    // --- Getters et Setters ---

    // Accès et modification de l'identifiant.
    public function getIdSalle(): ?int {
        return $this->Id_salle;
    }
    public function setIdSalle(?int $id): void {
        $this->Id_salle = $id;
    }

    // Accès et modification du nom.
    public function getNomSalle(): string {
        return $this->nom_salle;
    }
    public function setNomSalle(string $nom): void {
        $this->nom_salle = $nom;
    }

    // Accès et modification de la capacité d'accueil.
    public function getCapaciteSalle(): int {
        return $this->capacite_salle;
    }
    public function setCapaciteSalle(int $capacite): void {
        $this->capacite_salle = $capacite;
    }

    // Accès et modification de la localisation.
    public function getLocalisationSalle(): string {
        return $this->localisation_salle;
    }
    
    public function setLocalisationSalle(string $localisation): void {
        $this->localisation_salle = $localisation;
    }

    // Accès et modification de la relation avec l'objet Port.
    public function getPort(): ?Port {
        return $this->port;
    }
    public function setPort(?Port $port): void {
        $this->port = $port;
    
    }
}

?>