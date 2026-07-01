<?php
namespace App\Models;

//----------------------------------------------------------------------------------------------
// Classe ReservationLog : Représente un enregistrement de log pour une réservation.
// Elle permet de structurer les données extraites de la base de données.
//----------------------------------------------------------------------------------------------

class ReservationLog {

    // --- Propriétés privées (Encapsulation) ---
    // Le '?' devant le type indique que la valeur peut être 'null' (nullable).
    private ?int $Id_reservation_log = null;
    private ?string $action_reservation_log = null; // Type d'opération (INSERT, UPDATE, DELETE)
    private ?string $description_reservation_log = null; // Détail textuel de l'événement
    private ?string $old_data_reservation_log = null; // État de la donnée AVANT la modification
    private ?string $new_data_reservation_log = null; // État de la donnée APRÈS la modification
    private ?string $timestamp_reservation_log = null; // Moment précis de l'action
    private ?int $Id_reservation = null; // Lien vers la réservation concernée
    private ?int $Id_utilisateur = null; // Lien vers l'auteur de l'action
    
    // --- GETTERS et SETTERS ---
    // Ils permettent d'accéder aux propriétés privées tout en gardant le contrôle sur la lecture/écriture.

    /** @return int|null Identifiant unique du log */
    public function getIdReservationLog(): ?int {
        return $this->Id_reservation_log;
    }
    public function setIdReservationLog(?int $id): void {
        $this->Id_reservation_log = $id;
    }

    /** @return string|null Type d'action (ex: 'UPDATE', 'DELETE') */
    public function getActionReservationLog(): ?string {
        return $this->action_reservation_log;
    }
    public function setActionReservationLog(?string $action): void {
        $this->action_reservation_log = $action;
    }

    /** @return string|null Description textuelle de l'action */
    public function getDescriptionReservationLog(): ?string {
        return $this->description_reservation_log;
    }
    public function setDescriptionReservationLog(?string $description): void {
        $this->description_reservation_log = $description;
    }

    /** @return string|null Données avant la modification (souvent format JSON) */
    public function getOldDataReservationLog(): ?string {
        return $this->old_data_reservation_log;
    }
    public function setOldDataReservationLog(?string $old): void {
        $this->old_data_reservation_log = $old;
    }

    /** @return string|null Données après la modification (souvent format JSON) */
    public function getNewDataReservationLog(): ?string {
        return $this->new_data_reservation_log;
    }
    public function setNewDataReservationLog(?string $new): void { 
        $this->new_data_reservation_log = $new;
    }

    /** @return string|null Date et heure de l'événement */
    public function getTimestampReservationLog(): ?string {
        return $this->timestamp_reservation_log;
    }
    public function setTimestampReservationLog(?string $timestamp): void {
        $this->timestamp_reservation_log = $timestamp;
    }
    
    /** @return int|null ID de l'utilisateur ayant causé le log */
    public function getIdUtilisateur(): ?int {
        return $this->Id_utilisateur;
    }
    
    public function setIdUtilisateur(?int $id): void {
        $this->Id_utilisateur = $id;
    }
}