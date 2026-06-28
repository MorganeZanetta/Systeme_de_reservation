<?php
namespace App\Controllers;

use PDO;
use App\Models\UtilisateurModel;

/**
 * AuthController : Gère exclusivement l'authentification et les sessions.
 */
class AuthController extends BaseController {

    private UtilisateurModel $utilisateurModel;

    public function __construct(PDO $pdo) {
        parent::__construct($pdo);
        $this->utilisateurModel = new UtilisateurModel($pdo);
    }

    /**
     * Affiche le formulaire de connexion et traite la soumission.
     */
    public function connexion(): void {
        // Si déjà connecté, on redirige
        if (isset($_SESSION["Id_utilisateur"])) {
            $this->redirigerSelonRole();
        }

        $error_message = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["login_bouton"])) {
            
            $identifiant = trim($_POST["identifiant_utilisateur"] ?? '');
            $mdpSaisi = trim($_POST["mot_de_passe_utilisateur"] ?? '');

            // 1. On récupère l'utilisateur
            $utilisateur = $this->utilisateurModel->identificationUtilisateur($identifiant);

            // 2. VÉRIFICATION SÉCURISÉE AVEC PASSWORD_VERIFY
            if ($utilisateur && password_verify($mdpSaisi, $utilisateur->getMdpUtilisateur())) {
                
                // Régénération de l'ID de session pour prévenir la fixation de session
                session_regenerate_id(true);

                // Stockage des informations en session
                $_SESSION['Id_utilisateur'] = $utilisateur->getIdUtilisateur();
                $_SESSION['Id_role'] = ($utilisateur->getRole()) ? $utilisateur->getRole()->getIdRol() : 2;
  
                // Redirection après succès
                $this->redirigerSelonRole();
                exit(); // Important après un header/redirection
            } else {
                // Mot de passe ou utilisateur incorrect
                $error_message = "Identifiant et/ou mot de passe incorrect(s).";
            }
        }

        $this->render('login', ['error_message' => $error_message]);
    }

    /**
     * Déconnecte l'utilisateur et redirige vers la page de connexion.
     */
    public function deconnexion(): void {
        session_unset();
        session_destroy();
        header("Location: index.php?action=connexion");
        exit();
    }

    /**
     * Helper pour rediriger en fonction du rôle.
     */
    private function redirigerSelonRole(): void {
        $action = ($_SESSION['Id_role'] == 1) ? "interfaceAdministrateur" : "interfaceUtilisateur";
        header("Location: index.php?action=" . $action);
        exit();
    }
}

?>