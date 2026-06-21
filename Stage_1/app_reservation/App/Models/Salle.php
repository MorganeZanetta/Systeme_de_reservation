<?php
namespace App\Models;

class Salle {

    // 1. Les propriétés (qui correspondent EXACTEMENT aux colonnes SQL)
    // On met un "?" devant int pour l'id car au début, une nouvelle salle n'a pas encore d'id SQL
    private ?int $Id_salle = null;
    private string $nom_salle = '';
    private int $capacite_salle = 0;
    private string $localisation_salle = '';

    // 2. Les GETTERS et SETTERS

    // Pour l'ID
    public function getIdSalle(): ?int {
        return $this->Id_salle;
    }
    public function setIdSalle(?int $id): void {
        $this->Id_salle = $id;
    }

    // Pour le Nom
    public function getNomSalle(): string {
        return $this->nom_salle;
    }
    public function setNomSalle(string $nom): void {
        $this->nom_salle = $nom;
    }

    // Pour la Capacité
    public function getCapaciteSalle(): int {
        return $this->capacite_salle;
    }
    public function setCapaciteSalle(int $capacite): void {
        $this->capacite_salle = $capacite;
    }

    public function getLocalisationSalle(): string {
        return $this->localisation_salle;
    }
    
    public function setLocalisationSalle(string $localisation): void {
        $this->localisation_salle = $localisation;
    }
}

//----------------------------------------------------------------------------------------------
// 12. REQUETE ADMINISTRATEUR : Trie des salles par Id_salle
//----------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------
// 11. REQUETE ADMINISTRATEUR : Trie de la liste des réservations par Id_salle
//----------------------------------------------------------------------------------------------
//----------------------------------------------------------------------------------------------
// 17. REQUETE ADMINISTRATEUR : Ajout d'une salle
//----------------------------------------------------------------------------------------------

//----------------------------------------------------------------------------------------------
// 18. REQUETE ADMINISTRATEUR : Modification d'une salle
//----------------------------------------------------------------------------------------------

//----------------------------------------------------------------------------------------------
// 19. REQUETE ADMINISTRATEUR : Suppression d'une salle
//----------------------------------------------------------------------------------------------

?>