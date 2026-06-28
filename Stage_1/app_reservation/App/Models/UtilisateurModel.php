<?php

namespace App\Models;

use PDO;
use PDOException;
use Exception;

/**
 * Classe UtilisateurModel : Gère le cycle de vie et l'authentification des utilisateurs.
 */
class UtilisateurModel {
    
    protected PDO $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    /**
     * Récupère un utilisateur par son identifiant unique.
     * Cette méthode récupère uniquement les données brutes ; 
     * la vérification du mot de passe doit être effectuée dans le contrôleur via password_verify().
     * @param string $identifiant Identifiant saisi par l'utilisateur
     * @return Utilisateur|null Retourne l'objet utilisateur si trouvé, null sinon
     */
    public function identificationUtilisateur(string $identifiant): ?Utilisateur { 
        try {
            // AJOUT DE LA JOINTURE ICI :
            $sql = "SELECT u.*, p.identite_port 
                    FROM utilisateur u 
                    LEFT JOIN port p ON u.Id_port = p.Id_port 
                    WHERE u.identifiant_utilisateur = :identifiant";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':identifiant' => $identifiant]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userData) return null;

            $utilisateur = new Utilisateur();
            $utilisateur->setIdUtilisateur((int)$userData['Id_utilisateur']);
            $utilisateur->setIdentifiant($userData['identifiant_utilisateur']);
            $utilisateur->setNomUtilisateur($userData['nom_utilisateur']);
            $utilisateur->setPrenomUtilisateur($userData['prenom_utilisateur']);
            $utilisateur->setEmailUtilisateur($userData['e_mail_utilisateur']);
            $utilisateur->setMdpUtilisateur($userData['mot_de_passe_utilisateur']);
            
            if (!empty($userData['Id_role'])) {
                $role = new Role();
                $role->setIdRol((int)$userData['Id_role']);
                $utilisateur->setRole($role);
            }

            if (!empty($userData['Id_port'])) {
                $port = new Port();
                $port->setIdPort((int)$userData['Id_port']);
                $port->setLibPort($userData['identite_port'] ?? '');
                $utilisateur->setPort($port);
            }

            return $utilisateur; 

        } catch (PDOException $e) {
            error_log("Erreur SQL lors de l'identification : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère la liste complète des utilisateurs avec leurs rôles et ports associés.
     * @return Utilisateur[] Tableau d'objets Utilisateur.
     */
    public function recupererUtilisateurs(): array {
        try {
            $sql = "SELECT u.*, r.libelle_role, p.identite_port
                    FROM utilisateur u
                    LEFT JOIN role r ON u.Id_role = r.Id_role
                    LEFT JOIN port p ON u.Id_port = p.Id_port";
                    
            $stmt = $this->db->query($sql);
            $utilisateurs = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $user = new Utilisateur();
                $user->setIdUtilisateur((int)$row['Id_utilisateur']);
                $user->setIdentifiant($row['identifiant_utilisateur']);
                $user->setPrenomUtilisateur($row['prenom_utilisateur']);
                $user->setNomUtilisateur($row['nom_utilisateur']);
                $user->setEmailUtilisateur($row['e_mail_utilisateur']);

                // Hydratation du rôle associé
                if (!empty($row['Id_role'])) {
                    $role = new Role();
                    $role->setIdRol((int)$row['Id_role']);
                    $role->setLib($row['libelle_role']);
                    $user->setRole($role);
                }

                // Hydratation du port associé
                if (!empty($row['Id_port'])) {
                    $port = new Port();
                    $port->setIdPort((int)$row['Id_port']);
                    $port->setLibPort($row['identite_port']);
                    $user->setPort($port);
                }

                $utilisateurs[] = $user;
            }
            return $utilisateurs;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des utilisateurs : " . $e->getMessage());
            return [];
        }
    }

    /**
 * Récupère un utilisateur spécifique par son ID avec ses relations.
 */
public function recupererUtilisateursParId(int $id): ?Utilisateur {
    try {
        $sql = "SELECT u.*, r.libelle_role, p.identite_port
                FROM utilisateur u
                LEFT JOIN role r ON u.Id_role = r.Id_role
                LEFT JOIN port p ON u.Id_port = p.Id_port
                WHERE u.Id_utilisateur = :id";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        // Hydratation
        $user = new Utilisateur();
        $user->setIdUtilisateur((int)$row['Id_utilisateur']);
        $user->setIdentifiant($row['identifiant_utilisateur']);
        $user->setPrenomUtilisateur($row['prenom_utilisateur']);
        $user->setNomUtilisateur($row['nom_utilisateur']);
        $user->setEmailUtilisateur($row['e_mail_utilisateur']);

        // Hydratation Rôle
        if (!empty($row['Id_role'])) {
            $role = new Role();
            $role->setIdRol((int)$row['Id_role']);
            $role->setLib($row['libelle_role'] ?? '');
            $user->setRole($role);
        }

        // Hydratation Port
        if (!empty($row['Id_port'])) {
            $port = new Port();
            $port->setIdPort((int)$row['Id_port']);
            $port->setLibPort($row['identite_port'] ?? '');
            $user->setPort($port);
        }

        return $user;

    } catch (PDOException $e) {
        error_log("Erreur dans UtilisateurModel::findById : " . $e->getMessage());
        return null;
    }
}

    /**
     * Ajoute un nouvel utilisateur en base.
     */
    public function ajouterUnUtilisateur(Utilisateur $utilisateur): bool {
        try {
            $sql = "INSERT INTO utilisateur 
                    (identifiant_utilisateur, prenom_utilisateur, nom_utilisateur, e_mail_utilisateur, mot_de_passe_utilisateur, Id_role, Id_port) 
                    VALUES (:identifiant, :prenom, :nom, :email, :mdp, :id_role, :id_port)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':identifiant' => $utilisateur->getIdentifiant(),
                ':prenom'      => $utilisateur->getPrenomUtilisateur(),
                ':nom'         => $utilisateur->getNomUtilisateur(),
                ':email'       => $utilisateur->getEmailUtilisateur(),
                ':mdp'         => $utilisateur->getMdpUtilisateur(),
                ':id_role'     => $utilisateur->getRole() ? $utilisateur->getRole()->getIdRol() : null, 
                ':id_port'     => $utilisateur->getPort() ? $utilisateur->getPort()->getIdPort() : null
            ]);
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout de l'utilisateur : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour les informations d'un utilisateur existant.
     */
    public function modifierUnUtilisateur(Utilisateur $utilisateur): bool {
        try {
            $sql = "UPDATE utilisateur 
                    SET identifiant_utilisateur = :identifiant, 
                        prenom_utilisateur = :prenom, 
                        nom_utilisateur = :nom, 
                        e_mail_utilisateur = :email,
                        mot_de_passe_utilisateur = :mdp, 
                        Id_role = :id_role, 
                        Id_port = :id_port
                    WHERE Id_utilisateur = :id_utilisateur";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id_utilisateur' => $utilisateur->getIdUtilisateur(),
                ':identifiant'    => $utilisateur->getIdentifiant(),
                ':prenom'         => $utilisateur->getPrenomUtilisateur(),
                ':nom'            => $utilisateur->getNomUtilisateur(),
                ':email'          => $utilisateur->getEmailUtilisateur(),
                ':mdp'            => $utilisateur->getMdpUtilisateur(),
                ':id_role'        => $utilisateur->getRole() ? $utilisateur->getRole()->getIdRol() : null,
                ':id_port'        => $utilisateur->getPort() ? $utilisateur->getPort()->getIdPort() : null
            ]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la modification de l'utilisateur : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime un utilisateur par son ID.
     */
    public function supprimerUnUtilisateur(int $idUtilisateur): bool {
        try {
            $sql = "DELETE FROM utilisateur WHERE Id_utilisateur = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $idUtilisateur]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de l'utilisateur : " . $e->getMessage());
            return false;
        }
    }
}

?>