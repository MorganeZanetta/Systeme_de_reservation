<?php

namespace App\Models;

use App\Core\Model;
use PDO;
use PDOException;
use Exception;

//----------------------------------------------------------------------------------------------
// Classe RoleModel : Gère les interactions persistantes avec la table "role".
// Elle permet de manipuler les données des rôles via l'entité Role.
//----------------------------------------------------------------------------------------------

class RoleModel extends Model {

    /**
     * Récupère tous les rôles enregistrés en base.
     */
    public function findAll(): array {
        try {
            // Exécution d'une requête simple de lecture.
            $stmt = $this->db->query("SELECT * FROM role");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $roles = [];

            // Hydratation : conversion des lignes de résultats (tableaux) en objets (instances de Role).
            foreach ($data as $row) {
                $role = new Role();
                $role->setIdRol((int)$row['Id_role']);
                $role->setLib($row['libelle_role']);
                $roles[] = $role;
            }
            return $roles;

        } catch (PDOException $e) {
   
            error_log("Erreur SQL dans RoleModel::findAll : " . $e->getMessage());
            return [];
        } catch (Exception $e) {
 
            error_log("Erreur générale dans RoleModel::findAll : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Insère un nouveau rôle en base de données.
     */
    public function creerUnRole(Role $role): bool {
        try {
            $sql = "INSERT INTO role (libelle_role) VALUES (:libelle)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':libelle' => $role->getLib()]);
            
            // On met à jour l'ID de l'objet avec l'identifiant généré par MySQL (Auto-incrément).
            $role->setIdRol((int)$this->db->lastInsertId());
            return true;

        } catch (PDOException $e) {
            error_log("Erreur SQL lors de la création du rôle : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour le libellé d'un rôle existant.
     */
    public function modifierUnRole(Role $role): bool {
        
        try {
            $sql = "UPDATE role SET libelle_role = :libelle WHERE Id_role = :id";
            $stmt = $this->db->prepare($sql);
            
            // Mise à jour sécurisée via requête préparée.
            return $stmt->execute([
                ':libelle' => $role->getLib(),
                ':id'      => $role->getIdRol()
            ]);

        } catch (PDOException $e) {
            error_log("Erreur SQL lors de la modification du rôle ID {$role->getIdRol()} : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime un rôle par son identifiant.
     */
    public function supprimerUnRole(Role $role): bool {
        
        try {
            $stmt = $this->db->prepare("DELETE FROM role WHERE Id_role = :id");
            return $stmt->execute([':id' => $role->getIdRol()]);

        } catch (PDOException $e) {

            error_log("Erreur SQL lors de la suppression du rôle ID {$role->getIdRol()} : " . $e->getMessage());
            return false;
        }
    }
}

?>