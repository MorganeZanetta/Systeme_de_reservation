<?php
namespace App\Controllers;

use PDO;
use App\Models\UtilisateurModel;

//----------------------------------------------------------------------------------------------
// AuthController : Gère exclusivement l'authentification et les sessions.
//----------------------------------------------------------------------------------------------
class AuthController extends BaseController {

    private UtilisateurModel $utilisateurModel;

    public function __construct(PDO $pdo) {
        parent::__construct($pdo);
        // Injection de la dépendance PDO via le modèle pour accéder à la BDD
        $this->utilisateurModel = new UtilisateurModel($pdo);
    }

    /**
     * Affiche le formulaire de connexion et traite la soumission.
     */
    public function connexion(): void {
        // Protection : Si l'utilisateur est déjà authentifié, inutile de lui montrer le formulaire.
        if (isset($_SESSION["Id_utilisateur"])) {
            $this->redirigerSelonRole();
        }

        $error_message = null;

        // Traitement uniquement si le formulaire est soumis via POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["login_bouton"])) {
            
            // Nettoyage des entrées utilisateurs pour éviter les espaces inutiles
            $identifiant = trim($_POST["identifiant_utilisateur"] ?? '');
            $mdpSaisi = trim($_POST["mot_de_passe_utilisateur"] ?? '');

            // 1. Récupération de l'utilisateur en base de données par son identifiant
            $utilisateur = $this->utilisateurModel->identificationUtilisateur($identifiant);

            // 2. Vérification sécurisée du mot de passe
            // password_verify compare le mdp saisi avec le hash stocké en base (géré par password_hash)
            if ($utilisateur && password_verify($mdpSaisi, $utilisateur->getMdpUtilisateur())) {
                
                // Prévention contre la fixation de session : 
                // On détruit l'ancien ID et on en génère un nouveau après connexion réussie.
                session_regenerate_id(true);

                // Stockage des données en session pour persistance
                $_SESSION['Id_utilisateur'] = $utilisateur->getIdUtilisateur();
                // Affectation du rôle (avec fallback de sécurité vers un rôle par défaut "2")
                $_SESSION['Id_role'] = ($utilisateur->getRole()) ? $utilisateur->getRole()->getIdRol() : 2;
  
                // Redirection après succès
                $this->redirigerSelonRole();
                exit(); // Toujours terminer le script après une redirection
            } else {
                // Message générique pour éviter d'indiquer si c'est le mail ou le mot de passe qui est erroné
                $error_message = "Identifiant et/ou mot de passe incorrect(s).";
            }
        }
        // Chargement de la vue avec le message d'erreur éventuel
        $this->render('login', ['error_message' => $error_message]);
    }

    /**
     * Déconnecte l'utilisateur en purgeant la session.
     */
    public function deconnexion(): void {
        // Supprime toutes les variables de session
        session_unset();
        // Détruit le fichier de session sur le serveur
        session_destroy();

        // Redirection vers la page d'accueil/connexion
        header("Location: index.php?action=connexion");
        exit();
    }

    /**
     * Helper privé pour centraliser la logique de redirection post-connexion.
     */
    private function redirigerSelonRole(): void {
        // Utilisation d'une comparaison stricte (===) pour la sécurité des types
        $action = ($_SESSION['Id_role'] == 1) ? "interfaceAdministrateur" : "interfaceListeUtilisateur";
        header("Location: index.php?action=" . $action);
        exit();
    }
}

?>