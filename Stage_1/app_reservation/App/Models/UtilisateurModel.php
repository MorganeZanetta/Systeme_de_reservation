<?php
namespace App\Models;

use PDO;

class UtilisateurModel {
    
    protected PDO $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    /**
     * Identifie un utilisateur
     */
    public function identificationUtilisateur(string $identifiant, string $motDePasse): ?Utilisateur { 
        $stmt = $this->db->prepare("SELECT * FROM utilisateur WHERE identifiant_utilisateur = :identifiant");
        $stmt->execute([':identifiant' => $identifiant]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData && ($motDePasse === $userData['mot_de_passe_utilisateur'])) {
            $utilisateur = new Utilisateur();
            $utilisateur->setIdUtilisateur($userData['Id_utilisateur']);
            $utilisateur->setIdentifiant($userData['identifiant_utilisateur']);
            $utilisateur->setNomUtilisateur($userData['nom_utilisateur']);
            $utilisateur->setPrenomUtilisateur($userData['prenom_utilisateur']);
            $utilisateur->setEmailUtilisateur($userData['e_mail_utilisateur']);
            $utilisateur->setMdpUtilisateur($userData['mot_de_passe_utilisateur']);
            
            if (!empty($userData['Id_role'])) {
                $role = new Role();
                $role->setIdRol($userData['Id_role']); // Utilisation de votre méthode getIdRol
                $utilisateur->setRole($role);
            }
            return $utilisateur; 
        }
        return null; 
    }

    /**
     * Récupère tous les utilisateurs avec leurs rôles et ports associés
     */
    public function RecupererUtilisateurs(): array {
        $sql = "SELECT u.*, r.libelle_role, p.identite_port
                FROM utilisateur u
                LEFT JOIN role r ON u.Id_role = r.Id_role
                LEFT JOIN port p ON u.Id_port = p.Id_port";
                
        $stmt = $this->db->query($sql);
        $utilisateurs = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $user = new Utilisateur();
            $user->setIdUtilisateur($row['Id_utilisateur']);
            $user->setIdentifiant($row['identifiant_utilisateur']);
            $user->setPrenomUtilisateur($row['prenom_utilisateur']);
            $user->setNomUtilisateur($row['nom_utilisateur']);
            $user->setEmailUtilisateur($row['e_mail_utilisateur']);

            if (!empty($row['Id_role'])) {
                $role = new Role();
                $role->setIdRol($row['Id_role']); // Utilisation de votre méthode
                $role->setLib($row['libelle_role']); // Utilisation de votre méthode setLib
                $user->setRole($role);
            }

            if (!empty($row['Id_port'])) {
                $port = new Port();
                $port->setIdPort($row['Id_port']);
                $port->setLibPort($row['identite_port']);
                $user->setPort($port);
            }

            $utilisateurs[] = $user;
        }
        return $utilisateurs;
    }

    /**
     * Ajout d'un utilisateur
     */
    public function ajouterUnUtilisateur(Utilisateur $utilisateur): bool {
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
    }

    /**
     * Modification d'un utilisateur
     */
    public function modifierUnUtilisateur(Utilisateur $utilisateur): bool {
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
    }

    public function supprimerUnUtilisateur(int $idUtilisateur): bool {
        $sql = "DELETE FROM utilisateur WHERE Id_utilisateur = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $idUtilisateur]);
    }
}