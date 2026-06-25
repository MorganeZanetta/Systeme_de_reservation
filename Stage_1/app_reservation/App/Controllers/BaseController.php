<?php
namespace App\Controllers;

use PDO;

/**
 * BaseController : Classe parente pour tous les contrôleurs de l'application.
 * Elle mutualise la gestion de la base de données, la sécurité et le rendu des vues.
 */
class BaseController {
    protected PDO $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
        
        // Démarrage centralisé de la session si nécessaire
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Génère ou récupère le token CSRF unique pour la session en cours.
     */
    protected function getCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

/**
     * Vérification intelligente Anti-CSRF.
     * NE bloque que si la requête est en POST.
     */
    protected function verifierCsrf(): void {
        // Si la requête n'est pas en POST, on laisse passer (ex: liens, navigation)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        // Si c'est un POST, on vérifie strictement la présence et la validité du jeton
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            error_log("Tentative CSRF bloquée sur l'action : " . ($_GET['action'] ?? 'inconnue'));
            die("Erreur de sécurité : Jeton CSRF invalide. Veuillez rafraîchir la page.");
        }
    }

    /**
     * Vérifie si l'utilisateur est authentifié.
     * Redirige vers la page de login si ce n'est pas le cas.
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

    protected function verifierAccesReservation($reservation): bool {
    // 1. Sécurité : Vérifier si la réservation et l'utilisateur existent
    if (!$reservation || !$reservation->getUtilisateur()) {
        return false;
    }

    // 2. Si l'utilisateur est admin (Role ID 1), on autorise tout
    if (isset($_SESSION['Id_role']) && $_SESSION['Id_role'] == 1) {
        return true; 
    }
    
    // 3. Sinon, on vérifie si l'ID en session correspond au propriétaire
    return (isset($_SESSION['Id_utilisateur']) && 
            $reservation->getUtilisateur()->getIdUtilisateur() == $_SESSION['Id_utilisateur']);
}

    /**
     * Gère l'affichage des vues.
     * Injecte automatiquement le token CSRF dans les données transmises à la vue.
     */
    protected function render(string $view, array $data = []): void {
        // Ajout automatique du token aux données disponibles dans la vue
        $data['csrf_token'] = $this->getCsrfToken();
        
        // Extrait les variables du tableau pour qu'elles soient utilisables dans la vue
        extract($data);
        
        $file = __DIR__ . '/../Views/' . $view . '.php';
        if (file_exists($file)) {
            require_once $file;
        } else {
            // Utilisation d'un message d'erreur clair
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
