# SRSM - Système de Réservation de Salles et Matériel

SRSM est une plateforme de gestion de ressources (salles et matériel) développée en PHP 8.1+. Elle permet aux utilisateurs de gérer leurs réservations de manière intuitive et aux administrateurs de piloter l'ensemble du système tout en assurant une traçabilité complète des actions.

## 🚀 Architecture technique

L'application repose sur une architecture **MVC (Modèle-Vue-Contrôleur)** robuste, garantissant une séparation claire entre la logique métier, l'accès aux données et l'interface utilisateur.

* **Backend** : PHP 8.1+, MySQL 5.7+ (pour le support JSON natif).
* **Autoloading** : Standard PSR-4 via Composer.
* **Sécurité** : Protection SQL (PDO), CSRF (Jetons), XSS (Filtrage).
* **Traçabilité** : Système d'audit (`ReservationLog`) stockant les historiques d'états au format JSON.

## 📦 Installation

### Prérequis
- **PHP** >= 8.1
- **Composer**
- **MySQL** >= 5.7 (ou MariaDB 10.2+)
- *Note : L'extension PHP `json` est incluse par défaut dans les installations PHP modernes.*

### Étapes d'installation
1. **Cloner le projet** sur votre machine.
2. **Installer les dépendances** :
   Ouvrez un terminal à la racine du dossier et exécutez :
   ```bash
   composer install
  Cela générera automatiquement le dossier vendor/ et l'autoloader nécessaire au bon fonctionnement du projet.

Configuration :
Configurez vos accès à la base de données dans App/Core/Database.php.
Importez le fichier de schéma SQL (mpd_resa.sql) dans votre serveur MySQL.

Serveur Web :
Configurez votre hôte virtuel (Apache/Nginx) pour que la racine (DocumentRoot) pointe vers le dossier /Public.

🛡️ Sécurité
Le système intègre plusieurs couches de protection :
Injection SQL : Toutes les requêtes sont préparées via PDO.
CSRF : Chaque formulaire POST utilise un jeton csrf_token unique, vérifié côté contrôleur.
XSS : Toutes les entrées utilisateur sont traitées par htmlspecialchars() lors de l'affichage.
Accès : Séparation stricte et vérification des sessions entre les accès Utilisateur et Administrateur.

🛠️ Maintenance & Audit
Système de Logs
Chaque action critique (création, modification, suppression) génère un log dans la table reservation_log. 
Les données old_data et new_data sont stockées au format JSON, permettant une comparaison précise des états.

Ajout de fonctionnalités :
Créez vos classes dans App/Models/.
Développez la logique métier dans les contrôleurs (App/Controllers/).
Déclarez la nouvelle route dans le routeur centralisé (public/index.php).
Exécutez composer dump-autoload -o si vous avez créé de nouveaux espaces de noms pour optimiser le chargement.

⚠️ Notes importantes :
Le dossier /vendor/ est généré automatiquement par Composer.

👥 Auteur
[Morgane ZANETTA]
Projet réalisé dans le cadre de mon stage de 1ère année en Bachelor développement, intelligence artificielle et cybersécurité.
Ce projet est maintenu selon les standards PSR-4 et utilise les fonctionnalités modernes de PHP 8.
