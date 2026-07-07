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
2. **Dépendances** :
   Assurez-vous d'avoir un fichier composer.json valide, puis exécutez dans un terminal à la racine du dossier :
   ```bash
   composer install
  Cela générera automatiquement le dossier vendor/ et l'autoloader nécessaire au bon fonctionnement du projet.  
  
🔧 Configuration :  
Configurez vos accès à la base de données dans App/Core/Database.php.  
Importez le fichier de schéma SQL (mpd_resa.sql) dans votre serveur MySQL.  
  
🗄️ Serveur Web :  
Configurez votre VirtualHost pour pointer vers le dossier /Public.  
  
🛡️ Sécurité :  
Le système intègre plusieurs couches de protection.  
Injection SQL : Toutes les requêtes sont préparées via PDO.  
CSRF : Chaque formulaire POST utilise un jeton csrf_token unique, vérifié côté contrôleur.  
XSS : Toutes les entrées utilisateur sont traitées par htmlspecialchars() lors de l'affichage.  
Authentification : Implémentation d'un hachage des mots de passe avec l'algorithme bcrypt (password_hash et password_verify).  
Accès : Séparation stricte et vérification des sessions entre les accès Utilisateur et Administrateur.  
  
🛠️ Maintenance & Audit :  
Système de Logs :  
Chaque action critique (création, modification, suppression) génère un log dans la table reservation_log.   
Les données old_data et new_data sont stockées au format JSON, permettant une comparaison précise des états.  
  
💡 Ajout de fonctionnalités :  
Pour étendre l'application, suivez ces étapes :  
Données (Modèles) : Créez vos classes dans App/Models/ pour gérer l'interaction avec la base de données.  
Logique (Contrôleurs) : Développez le traitement des données et la logique métier dans App/Controllers/.  
Routes : Déclarez votre nouvelle route dans le routeur centralisé (Public/index.php).  
Interface (Vues) : Créez ou modifiez le fichier de vue correspondant dans App/Views/.  
Note : Si votre nouvelle vue nécessite des fonctionnalités interactives (comme le calendrier Flatpickr), n'oubliez pas d'inclure les assets JS/CSS nécessaires et de définir la logique onDayCreate dans votre fichier script dédié.  
Style (CSS) : Apportez vos modifications de mise en forme dans votre fichier de style (Public/css/style.css).  
Utilisez composer dump-autoload -o après avoir créé de nouvelles classes ou espaces de noms pour optimiser le chargement automatique.  
Utilisez composer update pour télécharger les dernières versions autorisées de vos dépendances et synchroniser votre fichier composer.lock.  
  
⚠️ Note importante :  
Le dossier /vendor/ est généré automatiquement par Composer (ne pas tenir compte du fichier vendor présent).  
  
👥 Auteur :  
Morgane ZANETTA  
Projet réalisé dans le cadre de mon stage de 1ère année en Bachelor développement, intelligence artificielle et cybersécurité au sein de l'école d'ingénieurs MIRA spécialisée en développement, robotique et intelligence artificielle à Ajaccio.  
Ce projet est maintenu selon les standards PSR-4 et utilise les fonctionnalités modernes de PHP 8.  
