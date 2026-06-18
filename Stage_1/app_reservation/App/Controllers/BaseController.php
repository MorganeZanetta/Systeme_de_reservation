<?php
namespace App\Controllers;

use PDO;

class BaseController {
    protected PDO $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
        // Démarrage centralisé de la session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    // Vérifie si l'utilisateur est authentifié
    protected function checkAuth(): void {
        if (!isset($_SESSION["Id_utilisateur"])) {
            header("Location: index.php?action=login");
            exit();
        }
    }

    // Vérifie si l'utilisateur est admin
    protected function isAdmin(): bool {
        return isset($_SESSION["Id_role"]) && $_SESSION["Id_role"] == 1;
    }

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
}