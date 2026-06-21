<?php
namespace App\Models;

use App\core\Database;
use PDO;

// Fichier : Model.php

abstract class Model {
    // Accessible dans les classes enfants
    protected PDO $db;

    public function __construct() {
        // Connexion automatique à la base de données
        $this->db = Database::getConnection();
    }

    /**
     * Identifie un utilisateur et vérifie son mot de passe
     */
    public function identificationUtilisateur(string $identifiant, string $motDePasse) { 
        // 1. On prépare la requête SQL en utilisant les propriétés héritées ($this->db)
        $stmt = $this->db->prepare("SELECT * FROM utilisateur WHERE identifiant_utilisateur = :identifiant");
        
        // 2. On exécute en passant les données qui correspondent aux marqueurs (ici :identifiant)
        $stmt->execute([
            ':identifiant' => $identifiant
        ]);

        // 3. On récupère le résultat
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 4. On vérifie si l'utilisateur existe et si le mot de passe correspond
        // (Idéalement, utilisez password_hash() à l'inscription et password_verify() ici)
        if ($user && password_verify($motDePasse, $user['mot_de_passe_utilisateur'])) {
            return $user; // Authentification réussie, on retourne les infos de l'utilisateur
        }

        return false; // Échec de l'authentification
    }
}