<?php
namespace App\Models;

use PDO;
use Exception;
use PDOException;

/**
 * Classe MaterielModel
 * Gère les interactions avec la table "materiel" dans la base de données.
 */
class MaterielModel {

    protected PDO $db; // Instance de connexion PDO

    // Injection de dépendance de la connexion PDO via le constructeur
    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    /**
     * Récupère tous les enregistrements de la table materiel
     * @return Materiel[] Retourne un tableau d'objets Materiel
     */
    /**
     * Récupère tous les enregistrements avec leur port associé.
     */
    public function voirListeMateriel(): array {
        try {
            // Jointure pour récupérer les infos du port
            $sql = "SELECT m.*, p.identite_port 
                    FROM materiel m 
                    LEFT JOIN port p ON m.Id_port = p.Id_port 
                    ORDER BY m.Id_materiel ASC";
            
            $stmt = $this->db->query($sql);
            $materiels = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $materiel = new Materiel();
                $materiel->setIdMateriel((int)$row['Id_materiel']);
                $materiel->setTypMat($row['type_materiel']);
                $materiel->setNumMat((int)$row['numero_materiel']);
                $materiel->setPhoMat($row['photo_materiel']);
                
                // Hydratation de l'objet Port
                if (!empty($row['Id_port'])) {
                    $port = new Port();
                    $port->setIdPort((int)$row['Id_port']);
                    $port->setLibPort($row['identite_port']);
                    $materiel->setPort($port);
                }
                
                $materiels[] = $materiel;
            }
            return $materiels;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la liste : " . $e->getMessage());
            throw new Exception("Impossible de charger la liste du matériel.");
        }
    }

    /**
     * Recherche un matériel spécifique par son identifiant
     */
    public function findById(int $id): ?Materiel {
        try {
            $sql = "SELECT m.*, p.identite_port 
                    FROM materiel m 
                    LEFT JOIN port p ON m.Id_port = p.Id_port 
                    WHERE m.Id_materiel = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) return null;

            $materiel = new Materiel();
            $materiel->setIdMateriel((int)$data['Id_materiel']);
            $materiel->setTypMat($data['type_materiel']);
            $materiel->setNumMat((int)$data['numero_materiel']);
            $materiel->setPhoMat($data['photo_materiel']);
            
            if (!empty($data['Id_port'])) {
                $port = new Port();
                $port->setIdPort((int)$data['Id_port']);
                $port->setLibPort($data['identite_port']);
                $materiel->setPort($port);
            }
            
            return $materiel;
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche par ID ($id) : " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération du matériel.");
        }
    }

    /**
     * Récupère uniquement le matériel appartenant au port spécifié.
     * @param int $idPort L'ID du port de l'utilisateur.
     * @return Materiel[] Tableau d'objets Materiel filtrés.
     */
    public function voirMaterielParPort(int $idPort): array {
        try {
            // Jointure pour récupérer les infos du port et filtrage par Id_port
            $sql = "SELECT m.*, p.identite_port 
                    FROM materiel m 
                    LEFT JOIN port p ON m.Id_port = p.Id_port 
                    WHERE m.Id_port = :idPort 
                    ORDER BY m.Id_materiel ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':idPort' => $idPort]);
            
            $materiels = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $materiel = new Materiel();
                $materiel->setIdMateriel((int)$row['Id_materiel']);
                $materiel->setTypMat($row['type_materiel']);
                $materiel->setNumMat((int)$row['numero_materiel']);
                $materiel->setPhoMat($row['photo_materiel']);
                
                // Hydratation de l'objet Port associé
                if (!empty($row['Id_port'])) {
                    $port = new Port();
                    $port->setIdPort((int)$row['Id_port']);
                    $port->setLibPort($row['identite_port']);
                    $materiel->setPort($port);
                }
                
                $materiels[] = $materiel;
            }
            return $materiels;
        } catch (PDOException $e) {
            error_log("Erreur dans MaterielModel::voirMaterielParPort (Port: $idPort) : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Insère un nouveau matériel dans la base de données
     */
    public function ajouterUnMateriel(Materiel $materiel): void {
        try {
            $sql = "INSERT INTO materiel (type_materiel, numero_materiel, photo_materiel, Id_port) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $materiel->getTypMat(),
                $materiel->getNumMat(),
                $materiel->getPhoMat(),
                $materiel->getPort()->getIdPort()
            ]);
            
            // Met à jour l'objet avec l'ID généré automatiquement par MySQL
            $materiel->setIdMateriel((int)$this->db->lastInsertId());
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout : " . $e->getMessage());
            throw new Exception("Impossible d'ajouter le matériel.");
        }
    }
    
    /**
     * Met à jour les informations d'un matériel existant
     */
    public function modifierUnMateriel(Materiel $materiel): void {
        try {
            $sql = "UPDATE materiel SET type_materiel = ?, numero_materiel = ?, photo_materiel = ?, Id_port = ? WHERE Id_materiel = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $materiel->getTypMat(),
                $materiel->getNumMat(),
                $materiel->getPhoMat(),
                $materiel->getPort()->getIdPort(),
                $materiel->getIdMateriel()
            ]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la modification : " . $e->getMessage());
            throw new Exception("Impossible de modifier le matériel.");
        }
    }

    /**
     * Supprime un matériel de la base via son ID
     */
    public function supprimerUnMateriel(int $id): void {
        try {
            $sql = "DELETE FROM materiel WHERE Id_materiel = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression : " . $e->getMessage());
            throw new Exception("Impossible de supprimer le matériel.");
        }
    }

    /**
     * Récupère une liste unique des types de matériel (utile pour des filtres)
     */
    public function getMaterialTypes(): array {
        try {
            $stmt = $this->db->query("SELECT DISTINCT type_materiel FROM materiel");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Erreur types : " . $e->getMessage());
            return []; // Retourne un tableau vide par défaut en cas d'échec
        }
    }

    /**
     * Récupère tous les numéros de matériel existants
     */
    public function getMaterialNumbers(): array {
        try {
            $stmt = $this->db->query("SELECT numero_materiel FROM materiel");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Erreur numéros : " . $e->getMessage());
            return [];
        }
    }
}

?>