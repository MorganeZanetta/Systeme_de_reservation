<?php

namespace App\Models;

use App\Core\Model;
use PDO;
use PDOException;
use App\Models\Salle;

//----------------------------------------------------------------------------------------------
// Classe SalleModel : Gère les opérations CRUD pour la table "salle".
//----------------------------------------------------------------------------------------------

class SalleModel extends Model {

        /**
        * Récupère la liste de toutes les salles pour affichage.
        */
        public function voirListeSalles(): array {
            
            try {
                // Utilisation d'un LEFT JOIN pour inclure les informations du port associé.
                $sql = "SELECT s.*, p.identite_port 
                    FROM salle s 
                    LEFT JOIN port p ON s.Id_port = p.Id_port 
                    ORDER BY s.Id_salle ASC";
        
                $stmt = $this->db->query($sql);
                $salles = [];
        
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Hydratation de l'objet Salle
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
        * Récupère une salle spécifique par son ID.
        */
        public function findById(int $id): ?Salle {
        
            try {

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
        * Récupère les salles appartenant à un port spécifique (filtre de sécurité/multi-tenant).
        */
        public function voirSallesParPort(int $idPort): array {
            
            try {
        
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
        * Ajoute une nouvelle salle et met à jour l'objet avec son nouvel ID.
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
                // Récupération de l'ID généré par la base de données.
                $salle->setIdSalle((int)$this->db->lastInsertId());
                }
            return $success;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout d'une salle : " . $e->getMessage());
            return false;
        }
    }
    
        /**
        * Met à jour une salle existante.
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
        * Supprime une salle par son identifiant.
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