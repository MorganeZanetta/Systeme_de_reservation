<?php
namespace App\Models;

use PDO;

class UtilisateurModel {
    
    // AJOUT/CORRECTION : Le constructeur enfant doit appeler le parent
    protected PDO $db;

    // Le modèle reçoit la connexion ici
    public function __construct(PDO $pdo) {
        $this->db = $pdo;

    }
     /* Identifie un utilisateur SANS vérification de mot de passe (MODE TEST)
     */
public function identificationUtilisateur(string $identifiant, string $motDePasse): ?Utilisateur { 
    // 1. On cherche l'utilisateur par son identifiant
    $stmt = $this->db->prepare("SELECT * FROM utilisateur WHERE identifiant_utilisateur = :identifiant");
    $stmt->execute([':identifiant' => $identifiant]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Vérification combinée : existe-t-il ET le mot de passe correspond-il ?
    // Note : si vos mots de passe sont en clair, on utilise ===. 
    // Si vous passez au hashage plus tard, utilisez password_verify().
    if ($userData && ($motDePasse === $userData['mot_de_passe_utilisateur'])) {
        
        $utilisateur = new Utilisateur();
        $utilisateur->setIdUtilisateur($userData['Id_utilisateur']);
        $utilisateur->setIdentifiant($userData['identifiant_utilisateur']);
        $utilisateur->setNomUtilisateur($userData['nom_utilisateur']);
        $utilisateur->setPrenomUtilisateur($userData['prenom_utilisateur']);
        $utilisateur->setEmailUtilisateur($userData['e_mail_utilisateur']);
        $utilisateur->setMdpUtilisateur($userData['mot_de_passe_utilisateur']);
        
        // 3. IMPORTANT : Hydratation du rôle pour la redirection
        if (!empty($userData['Id_role'])) {
            $role = new Role();
            $role->setIdRol($userData['Id_role']);
            $utilisateur->setRole($role);
        }
        
        return $utilisateur; 
    }

    // Retourne null si l'identifiant n'existe pas OU si le mot de passe est faux
    return null; 
}

 /*
    public function identificationUtilisateur(string $identifiant, string $motDePasse): ?Utilisateur { 
        $stmt = $this->db->prepare("SELECT * FROM utilisateur WHERE identifiant_utilisateur = :identifiant");
        
        $stmt->execute([
            ':identifiant' => $identifiant
        ]);

        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si l'utilisateur existe et que le mot de passe est correct
        if ($userData && password_verify($motDePasse, $userData['mot_de_passe_utilisateur'])) {
            
            // On transforme le tableau associatif en Objet "Utilisateur"
            $utilisateur = new Utilisateur();
            $utilisateur->setIdUtilisateur($userData['Id_utilisateur']);
            $utilisateur->setIdentifiant($userData['identifiant_utilisateur']);
            $utilisateur->setNomUtilisateur($userData['nom_utilisateur']);
            $utilisateur->setPrenomUtilisateur($userData['prenom_utilisateur']);
            $utilisateur->setEmailUtilisateur($userData['e_mail_utilisateur']);
            $utilisateur->setMdpUtilisateur($userData['mot_de_passe_utilisateur']);
            // (Optionnel) Tu peux aussi hydrater le rôle si ta requête fait une jointure
            
            return $utilisateur; 
        }

        return null; // Plus propre que false si on attend un objet ou rien
    }

   */
    /**
     * Récupère tous les utilisateurs de la BDD
     * @return array
     */
    public function getAll(): array {
        $sql = "SELECT Id_utilisateur, identifiant_utilisateur, nom_utilisateur, prenom_utilisateur, e_mail_utilisateur FROM utilisateur";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

/**
 * Récupère l'identifiant des utilisateurs de la BDD
 * y compris les logs orphelins (sans réservation liée).
 */

    public function getAllUsernames() {
        $sql = "SELECT identifiant_utilisateur FROM utilisateur";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

} // Cette accolade ferme bien la classe, elle doit rester tout à la fin !