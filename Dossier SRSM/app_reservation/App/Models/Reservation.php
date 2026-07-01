<?php
namespace App\Models;

// On importe les autres entités pour pouvoir les utiliser comme types
use App\Models\Utilisateur;
use App\Models\Salle;
use App\Models\Materiel;

//----------------------------------------------------------------------------------------------
// Classe Reservation : Représente une réservation dans le système.
// Elle gère les attributs de la réservation et ses relations avec les utilisateurs,
// les salles et le matériel (gestion des relations 1-N et N-N).
//----------------------------------------------------------------------------------------------

class Reservation {

    // 1. Propriétés directes (Colonnes de la table SQL)
    // Initialisées à vide ou null pour garantir la sécurité des types
    private ?int $Id_reservation = null;
    private string $motif_reservation = '';
    private string $date_debut_reservation = '';
    private string $date_fin_reservation = '';
    private string $creneau_reservation = '';

    // 2. Propriétés de relation (Objets associés)
    private ?Utilisateur $utilisateur = null; // Une réservation appartient à un utilisateur
    private array $salles = []; // Liste d'objets Salle (Relation Many-to-Many)
    private array $materiels = []; // Liste d'objets Materiel (Relation Many-to-Many)

    // 3. Accesseurs et Mutateurs (Getters et Setters)
    // Identifiant de la réservation
    public function setIdRes(?int $id): void {
        $this->Id_reservation = $id;
    }
    public function getIdRes(): ?int {
        return $this->Id_reservation;
    }
    // Motif de la réservation
    public function setMotif(?string $motif): void {
        $this->motif_reservation = $motif ?? '';
    }
    public function getMotif(): string {
        return $this->motif_reservation;
    }
    // Date de début
    public function setDebut(?string $date): void {
        $this->date_debut_reservation = $date ?? '';
    }
    public function getDebut(): string {
        return $this->date_debut_reservation;
    }
    // Date de fin
    public function setFin(?string $date): void {
        $this->date_fin_reservation = $date ?? '';
    }
    public function getFin(): string {
        return $this->date_fin_reservation;
    }
    // Créneau horaire
    public function setCre(?string $creneau): void {
        $this->creneau_reservation = $creneau ?? '';
    }
    public function getCre(): string {
        return $this->creneau_reservation;
    }

    // Gestion de l'utilisateur lié à la réservation (autorise un objet de la classe Utilisateur)
    public function setUtilisateur(?Utilisateur $utilisateur): void {
        $this->utilisateur = $utilisateur;
    }
    public function getUtilisateur(): ?Utilisateur {
        return $this->utilisateur;
    }

    // Fonctions pour ajouter des salles et matériels (Many-to-Many)
    // Fonctions pour manipuler les listes (Collections)
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
     * Vérifie la cohérence des données :
     * Une réservation doit impérativement contenir au moins une ressource.
     */
    public function estValide(): bool {
        // Une réservation est valide si elle contient au moins une salle OU un matériel
        return !empty($this->salles) || !empty($this->materiels);
    }
    // Récupère uniquement les IDs des salles associées (utile pour les requêtes SQL)
    public function getSallesIds(): array {
        return array_map(fn($s) => $s->getIdSalle(), $this->salles);
    }
    // Retourne une chaîne de caractères listant les noms des salles
    public function getNomsSallesConcatennes(string $separateur = ', '): string {
    $noms = [];
    foreach ($this->salles as $salle) {
        $noms[] = $salle->getNomSalle();
    }
    return implode($separateur, $noms);
    }
    // Permet de remplacer ou réinitialiser la liste complète des salles
    public function setSalles(array $salles): void {
        $this->salles = $salles;
    }
    // Permet de remplacer ou réinitialiser la liste complète du matériel
    public function setMateriels(array $materiels): void {
        $this->materiels = $materiels;
    }
}