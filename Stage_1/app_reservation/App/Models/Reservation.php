<?php
namespace App\Models;

// On importe les autres entités pour pouvoir les utiliser comme types
use App\Models\Utilisateur;
use App\Models\Salle;
use App\Models\Materiel;

class Reservation {

    // 1. Les colonnes directes de la table SQL (Initialisées à vide pour éviter le crash PHP)
    private ?int $Id_reservation = null;
    private string $motif_reservation = '';
    private string $date_debut_reservation = '';
    private string $date_fin_reservation = '';
    private string $creneau_reservation = '';

    // Les jointures
    private ?Utilisateur $utilisateur = null;
    private array $salles = [];     // Contiendra les objets Salle
    private array $materiels = [];  // Contiendra les objets Materiel

    // 3. Les GETTERS et SETTERS (Pour remplir et récupérer les données)
    
    public function setIdRes(?int $id): void {
        $this->Id_reservation = $id;
    }
    public function getIdRes(): ?int {
        return $this->Id_reservation;
    }

    public function setMotif(?string $motif): void {
        $this->motif_reservation = $motif ?? '';
    }
    public function getMotif(): string {
        return $this->motif_reservation;
    }

    public function setDebut(?string $date): void {
        $this->date_debut_reservation = $date ?? '';
    }
    public function getDebut(): string {
        return $this->date_debut_reservation;
    }

    public function setFin(?string $date): void {
        $this->date_fin_reservation = $date ?? '';
    }
    public function getFin(): string {
        return $this->date_fin_reservation;
    }

    public function setCre(?string $creneau): void {
        $this->creneau_reservation = $creneau ?? '';
    }
    public function getCre(): string {
        return $this->creneau_reservation;
    }

    // Autorise uniquement un objet de la classe Utilisateur
    public function setUtilisateur(?Utilisateur $utilisateur): void {
        $this->utilisateur = $utilisateur;
    }
    public function getUtilisateur(): ?Utilisateur {
        return $this->utilisateur;
    }

    // Fonctions pour ajouter des salles et matériels (Many-to-Many)
    public function addSalle(Salle $salle): void {
        $this->salles[] = $salle;
    }
    public function getSalles(): array {
        return $this->salles;
    }

    public function addMateriel(Materiel $materiel): void {
        $this->materiels[] = $materiel;
    }
    public function getMateriels(): array {
        return $this->materiels;
    }

    /**
     * Vérifie si la réservation est valide pour être enregistrée
     * @return bool
     */
    public function estValide(): bool {
        // Une réservation est valide si elle contient au moins une salle OU un matériel
        return !empty($this->salles) || !empty($this->materiels);
    }

public function getSallesIds(): array {
    return array_map(fn($s) => $s->getIdSalle(), $this->salles);
}

public function getNomsSallesConcatennes(string $separateur = ', '): string {
    $noms = [];
    foreach ($this->salles as $salle) {
        $noms[] = $salle->getNomSalle();
    }
    return implode($separateur, $noms);
}
// Ajout de ces méthodes pour permettre de vider ou remplacer la liste
public function setSalles(array $salles): void {
    $this->salles = $salles;
}

public function setMateriels(array $materiels): void {
    $this->materiels = $materiels;
}
}