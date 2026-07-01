<?php
namespace App\Models;

use App\Core\Model;
use PDO;
use Exception;
use PDOException;
use App\Models\{Port, Materiel};

//----------------------------------------------------------------------------------------------
// Classe MaterielModel : Data Access Layer (DAL).
// Cette classe gère la persistance des données liées au matériel.
// Elle assure le mapping entre les enregistrements SQL et les objets PHP (Hydratation).
//----------------------------------------------------------------------------------------------

class MaterielModel extends Model {
    /**
     * Récupère la liste complète des matériels avec leurs ports associés.
     * @return Materiel[] Tableau d'objets Materiel.
     */
    public function voirListeMateriel(): array {
        try {
            // Utilisation d'une jointure LEFT JOIN pour inclure les matériels sans port
            $sql = "SELECT m.*, p.identite_port 
                    FROM materiel m 
                    LEFT JOIN port p ON m.Id_port = p.Id_port 
                    ORDER BY m.Id_materiel ASC";
            
            $stmt = $this->db->query($sql);
            $materiels = [];
            // Hydratation : conversion de chaque ligne SQL en objet Materiel
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $materiel = new Materiel();
                $materiel->setIdMateriel((int)$row['Id_materiel']);
                $materiel->setTypMat($row['type_materiel']);
                $materiel->setNumMat((int)$row['numero_materiel']);
                $materiel->setPhoMat($row['photo_materiel']);
                
                // Si le matériel est lié à un port, on crée et hydrate l'objet Port associé
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
            // Log de l'erreur technique pour le développeur
            error_log("Erreur lors de la récupération de la liste : " . $e->getMessage());
            // Lancement d'une exception générique pour l'utilisateur
            throw new Exception("Impossible de charger la liste du matériel.");
        }
    }

    /**
     * Récupère un matériel spécifique par son ID (Recherche par identifiant).
     */
    public function findById(int $id): ?Materiel {
        try {
            // Utilisation de requêtes préparées pour éviter les injections SQL
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
     * Insère un nouveau matériel.
     */
    public function ajouterUnMateriel(Materiel $materiel): void {
        try {
            $sql = "INSERT INTO materiel (type_materiel, numero_materiel, photo_materiel, Id_port) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $materiel->getTypMat(),
                $materiel->getNumMat(),
                $materiel->getPhoMat(),
                $materiel->getPort()->getIdPort() // Accès à l'objet Port associé
            ]);
            
            // Mise à jour de l'objet avec l'ID généré par la BDD
            $materiel->setIdMateriel((int)$this->db->lastInsertId());
        
            } catch (PDOException $e) {
        // 1. Log de l'erreur réelle (invisible pour l'utilisateur, visible dans vos fichiers logs)
        error_log("Erreur PDO dans ajouterUnMateriel : " . $e->getMessage());

        // 2. Gestion de l'erreur métier
        if ($e->getCode() === '23000') {
            throw new Exception("Le numéro de matériel est déjà utilisé.");
        }
        
        // 3. Message générique pour l'utilisateur
        throw new Exception("Une erreur technique est survenue lors de l'enregistrement du matériel.");
    }
}
    
    /**
     * Met à jour les informations d'un matériel.
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
        // 1. Log de l'erreur réelle (invisible pour l'utilisateur, visible dans vos fichiers logs)
        error_log("Erreur PDO dans modifierUnMateriel : " . $e->getMessage());

        // 2. Gestion de l'erreur métier
        if ($e->getCode() === '23000') {
            throw new Exception("Le numéro de matériel est déjà utilisé.");
        }
        
        // 3. Message générique pour l'utilisateur
        throw new Exception("Une erreur technique est survenue lors de l'enregistrement du matériel.");
    }
            
}

    /**
     * Supprime un matériel via son ID.
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
}

?>