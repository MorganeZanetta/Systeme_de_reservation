<?php

namespace App\Models;

use PDO;
use PDOException;
use Exception;

/**
 * Classe RoleModel : Gère les interactions avec la table "role"
 */
class RoleModel {
    private PDO $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    /**
     * Récupère tous les rôles enregistrés en base.
     * @return Role[] Retourne un tableau d'objets Role ou un tableau vide en cas d'erreur.
     */
    public function findAll(): array {
        try {
            // Requête simple sans paramètre, donc query() suffit
            $stmt = $this->db->query("SELECT * FROM role");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $roles = [];

            // Hydratation : conversion des lignes de résultats en objets du modèle
            foreach ($data as $row) {
                $role = new Role();
                $role->setIdRol((int)$row['Id_role']);
                $role->setLib($row['libelle_role']);
                $roles[] = $role;
            }
            return $roles;

        } catch (PDOException $e) {
            // Capture spécifique aux erreurs SQL (ex: table manquante, serveur inaccessible)
            error_log("Erreur SQL dans RoleModel::findAll : " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            // Capture toute autre erreur métier
            error_log("Erreur générale dans RoleModel::findAll : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Insère un nouveau rôle en base de données.
     * @param Role $role Objet Role à persister
     * @return bool True si succès, False sinon
     */
    public function creerUnRole(Role $role): bool {
        try {
            $sql = "INSERT INTO role (libelle_role) VALUES (:libelle)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':libelle' => $role->getLib()]);
            
            // Mise à jour de l'ID de l'objet avec la valeur auto-incrémentée par MySQL
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
            
            // L'exécution retourne true en cas de succès, false sinon
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
            // Note : Cette méthode échouera si le rôle est utilisé par un utilisateur (contrainte de clé étrangère).
            // Le log permet de savoir pourquoi la suppression a été refusée par MySQL.
            error_log("Erreur SQL lors de la suppression du rôle ID {$role->getIdRol()} : " . $e->getMessage());
            return false;
        }
    }
}

?>