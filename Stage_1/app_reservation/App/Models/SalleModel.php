<?php

namespace App\Models;

use PDO;
use PDOException;
use App\Models\Salle;

/**
 * Classe SalleModel : Gère les opérations CRUD pour la table "salle"
 */
class SalleModel {

    protected PDO $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    /**
     * Récupère la liste de toutes les salles pour affichage.
     * @return Salle[] Retourne un tableau d'objets Salle.
     */
    public function voirListeSalles(): array {
    try {
        // Ajout d'un LEFT JOIN pour récupérer le port
        $sql = "SELECT s.*, p.identite_port 
                FROM salle s 
                LEFT JOIN port p ON s.Id_port = p.Id_port 
                ORDER BY s.Id_salle ASC";
        
        $stmt = $this->db->query($sql);
        $salles = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $salle = new Salle();
            $salle->setIdSalle((int)$row['Id_salle']);
            $salle->setNomSalle($row['nom_salle']);
            $salle->setCapaciteSalle($row['capacite_salle']);
            $salle->setLocalisationSalle($row['localisation_salle']);
            
            // Création et injection de l'objet Port
            if (!empty($row['Id_port'])) {
                $port = new Port();
                $port->setIdPort((int)$row['Id_port']);
                $port->setLibPort($row['identite_port']);
                $salle->setPort($port);
            }
            
            $salles[] = $salle;
        }
        return $salles;
    } catch (PDOException $e) {
        error_log("Erreur dans SalleModel::voirListeSalles : " . $e->getMessage());
        return [];
    }
}

    /**
    * Récupère une salle spécifique par son ID avec son port associé.
    */
    public function findById(int $id): ?Salle {
    try {
        // Ajout du JOIN pour récupérer les infos du port
        $sql = "SELECT s.*, p.identite_port 
                FROM salle s 
                LEFT JOIN port p ON s.Id_port = p.Id_port 
                WHERE s.Id_salle = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) return null;

        $salle = new Salle();
        $salle->setIdSalle((int)$data['Id_salle']);
        $salle->setNomSalle($data['nom_salle']);
        $salle->setCapaciteSalle($data['capacite_salle']);
        $salle->setLocalisationSalle($data['localisation_salle']);
        
        // Hydratation de l'objet Port
        if (!empty($data['Id_port'])) {
            $port = new Port();
            $port->setIdPort((int)$data['Id_port']);
            $port->setLibPort($data['identite_port']);
            $salle->setPort($port);
        }
        
        return $salle;
    } catch (PDOException $e) {
        error_log("Erreur dans SalleModel::findById (ID: $id) : " . $e->getMessage());
        return null;
    }
}

/**
     * Récupère uniquement les salles appartenant au port spécifié.
     * @param int $idPort L'ID du port de l'utilisateur.
     * @return Salle[] Tableau d'objets Salle filtrés.
     */
    public function voirSallesParPort(int $idPort): array {
        try {
            // Requête avec jointure pour récupérer les infos du port et filtrage par Id_port
            $sql = "SELECT s.*, p.identite_port 
                    FROM salle s 
                    LEFT JOIN port p ON s.Id_port = p.Id_port 
                    WHERE s.Id_port = :idPort 
                    ORDER BY s.Id_salle ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':idPort' => $idPort]);
            
            $salles = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $salle = new Salle();
                $salle->setIdSalle((int)$row['Id_salle']);
                $salle->setNomSalle($row['nom_salle']);
                $salle->setCapaciteSalle($row['capacite_salle']);
                $salle->setLocalisationSalle($row['localisation_salle']);
                
                // Hydratation de l'objet Port associé
                if (!empty($row['Id_port'])) {
                    $port = new Port();
                    $port->setIdPort((int)$row['Id_port']);
                    $port->setLibPort($row['identite_port']);
                    $salle->setPort($port);
                }
                
                $salles[] = $salle;
            }
            return $salles;
        } catch (PDOException $e) {
            error_log("Erreur dans SalleModel::voirSallesParPort (Port: $idPort) : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ajoute une nouvelle salle en base de données.
     */
    public function ajouterUneSalle(Salle $salle): bool {
        try {
            $sql = "INSERT INTO salle (nom_salle, capacite_salle, localisation_salle, Id_port) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                $salle->getNomSalle(),
                $salle->getCapaciteSalle(),
                $salle->getLocalisationSalle(),
                $salle->getPort()->getIdPort()
            ]);
            
            if ($success) {
                $salle->setIdSalle((int)$this->db->lastInsertId());
            }
            return $success;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout d'une salle : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Met à jour les informations d'une salle existante.
     */
    public function modifierUneSalle(Salle $salle): bool {
        try {
            $sql = "UPDATE salle 
                    SET nom_salle = ?, capacite_salle = ?, localisation_salle = ?, Id_port = ? 
                    WHERE Id_salle = ?";
        
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $salle->getNomSalle(),
                $salle->getCapaciteSalle(),
                $salle->getLocalisationSalle(),
                $salle->getPort()->getIdPort(),
                $salle->getIdSalle()
            ]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la modification de la salle ID {$salle->getIdSalle()} : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime une salle par son ID.
     */
    public function supprimerUneSalle(int $id): bool {
        try {
            $sql = "DELETE FROM salle WHERE Id_salle = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de la salle ID $id : " . $e->getMessage());
            return false;
        }
    }

}

?>