<?php
namespace App\Controllers;

use PDO;

//----------------------------------------------------------------------------------------------
// BaseController : Classe parente pour tous les contrôleurs de l'application.
// Elle mutualise la gestion de la base de données, la sécurité et le rendu des vues.
//----------------------------------------------------------------------------------------------

class BaseController {
    protected PDO $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
        
        // Démarrage centralisé de la session : garantit que $_SESSION est disponible partout
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Génère ou récupère le token CSRF unique pour la session en cours.
     * Utilise random_bytes pour une sécurité cryptographique robuste.
     */
    protected function getCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Vérification intelligente Anti-CSRF.
     * Protège vos formulaires contre les attaques où un tiers soumet des données à votre place.
     */
    protected function verifierCsrf(): void {
        // La protection n'est nécessaire que pour les requêtes modifiant des données (POST)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        // Vérification de la présence et de la correspondance du jeton
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            // Journalisation pour détecter des tentatives d'attaques potentielles    
            error_log("Tentative CSRF bloquée sur l'action : " . ($_GET['action'] ?? 'inconnue'));
            die("Erreur de sécurité : Jeton CSRF invalide. Veuillez rafraîchir la page.");
        }
    }

    /**
     * Vérifie si l'utilisateur est authentifié.
     * Méthode de garde : à appeler au début de chaque action sensible.
     */
    protected function checkAuth(): void {
        if (!isset($_SESSION["Id_utilisateur"])) {
            header("Location: index.php?action=login");
            exit();
        }
    }

    /**
     * Vérifie si l'utilisateur possède le rôle administrateur (Id 1).
     */
    protected function isAdmin(): bool {
        return isset($_SESSION["Id_role"]) && (int)$_SESSION["Id_role"] === 1;
    }

    /**
     * Gestion fine des droits d'accès. 
     * Vérifie si l'utilisateur connecté est bien le propriétaire de la ressource.
     */
    protected function verifierAccesReservation($reservation): bool {
    // 1. Vérification d'existence
    if (!$reservation || !$reservation->getUtilisateur()) {
        return false;
    }

    // 2. Privilege Admin : L'administrateur a tous les droits
    if (isset($_SESSION['Id_role']) && $_SESSION['Id_role'] == 1) {
        return true; 
    }
    
    // 3. Propriétaire : L'utilisateur ne peut agir que sur ses propres réservations
    return (isset($_SESSION['Id_utilisateur']) && 
            $reservation->getUtilisateur()->getIdUtilisateur() == $_SESSION['Id_utilisateur']);
}

    /**
     * Gère l'affichage des vues.
     * Automatise l'injection du token CSRF pour ne pas l'oublier dans chaque vue.
     */
    protected function render(string $view, array $data = []): void {
        // Ajout automatique du token aux données transmises à la vue
        $data['csrf_token'] = $this->getCsrfToken();
        
        // Extrait les variables (transforme $data['user'] en $user dans la vue)
        extract($data);
        
        $file = __DIR__ . '/../Views/' . $view . '.php';
        if (file_exists($file)) {
            require_once $file;
        } else {
            // Gestion explicite des erreurs de développement
            throw new \Exception("La vue $view est introuvable à l'adresse : $file");
        }
    }
}

    /*
    // Gère l'affichage des vues
    protected function render(string $view, array $data = []): void {
        extract($data);
        $file = __DIR__ . '/../Views/' . $view . '.php';
        if (file_exists($file)) {
            require_once $file;
        } else {
            throw new \Exception("La vue $view est introuvable.");
        }
    }
        */

    ?>
