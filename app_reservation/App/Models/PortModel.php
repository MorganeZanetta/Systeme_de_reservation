<?php
namespace App\Models;

use App\Core\Model;
use PDO;
use PDOException;
use Exception;

//----------------------------------------------------------------------------------------------
// Classe PortModel : Gère les interactions avec la table "port".
//----------------------------------------------------------------------------------------------

class PortModel extends Model {

    /**
     * Récupère tous les ports enregistrés en base
     * @return Port[]
     * @throws Exception
     */
    public function findAll(): array {
        try {
            $stmt = $this->db->query("SELECT * FROM port");
            $ports = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $port = new Port();
                $port->setIdPort($row['Id_port']);
                $port->setLibPort($row['identite_port']);
                $ports[] = $port;
            }
            return $ports;
        } catch (PDOException $e) {
            error_log("Erreur dans PortModel::findAll : " . $e->getMessage());
            throw new Exception("Impossible de récupérer la liste des ports.");
        }
    }

    /**
     * Insère un nouveau port en base de données
     */
    public function creerUnPort(Port $port): void {
        try {
            $sql = "INSERT INTO port (identite_port) VALUES (:libelle)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['libelle' => $port->getLibPort()]);
            
            $port->setIdPort((int)$this->db->lastInsertId());
        } catch (PDOException $e) {
            error_log("Erreur dans PortModel::creerUnPort : " . $e->getMessage());
            throw new Exception("Erreur lors de la création du port.");
        }
    }

    /**
     * Met à jour un port existant
     */
    public function modifierUnPort(Port $port): void {
        try {
            $sql = "UPDATE port SET identite_port = :libelle WHERE Id_port = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'libelle' => $port->getLibPort(),
                'id'      => $port->getIdPort()
            ]);
        } catch (PDOException $e) {
            error_log("Erreur dans PortModel::modifierUnPort : " . $e->getMessage());
            throw new Exception("Erreur lors de la mise à jour du port.");
        }
    }

    /**
     * Supprime un port par son identifiant
     */
    public function supprimerUnPort(int $id): void {
        try {
            $stmt = $this->db->prepare("DELETE FROM port WHERE Id_port = :id");
            $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Erreur dans PortModel::supprimerUnPort : " . $e->getMessage());
            throw new Exception("Erreur lors de la suppression du port.");
        }
    }

}

?>