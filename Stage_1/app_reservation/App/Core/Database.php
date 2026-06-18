<?php
// Définition de l'espace de noms (namespace) correspondant à la structure des dossiers (PSR-4)
namespace App\Core;

// Importation des classes natives de PHP pour la gestion de la base de données et des erreurs
use PDO; 
use PDOException;

/**
 * Class Database
 * Permet de gérer une connexion unique à la base de données (Pattern Singleton)
 */
class Database { 
    // Propriétés statiques contenant les identifiants de connexion
    // Elles sont "private" pour ne pas être accessibles depuis l'extérieur de cette classe
    private static string $host = "127.0.0.1"; 
    private static string $port = "8889";
    private static string $db_name = "SYSTEME_DE_RESERVATION_DES_SALLES_ET_MATERIEL"; 
    private static string $username = "root"; 
    private static string $password = "root";

    // Cette propriété statique va stocker l'unique instance de PDO (la connexion)
    // Le "?" signifie qu'elle peut être de type PDO ou égale à null au démarrage
    private static ?PDO $conn = null; 

    /**
     * Récupère l'instance unique de la connexion PDO
     * @return PDO
     */
    public static function getConnection(): PDO { 
        // ÉTAPE DE SÉCURITÉ DU SINGLETON : 
        // Si l'objet $conn est encore null, cela signifie qu'aucune connexion n'a été ouverte.
        if (self::$conn === null) {
            
            // Construction de la chaîne de connexion (Data Source Name)
            // On utilise "self::" car les propriétés de configuration sont statiques
            $dsn = "mysql:host=" . self::$host . ";port=" . self::$port . ";dbname=" . self::$db_name . ";charset=utf8mb4"; 

            try { 
                // Tentative d'instanciation de l'objet PDO
                self::$conn = new PDO($dsn, self::$username, self::$password, [
                    // Option 1 : Active le lancer d'exceptions en cas d'erreur SQL
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    
                    // Option 2 : Désactive la simulation des requêtes préparées pour utiliser les vraies requêtes de MySQL (plus sécurisé)
                    PDO::ATTR_EMULATE_PREPARES => false 
                ]); 
            } catch (PDOException $e) {
                // Si la connexion échoue, on attrape l'erreur et on lève une nouvelle exception claire
                // Cela évite de divulguer les identifiants en clair dans les logs par défaut
                throw new PDOException("Erreur de connexion à la base de données : " . $e->getMessage());
            }
        }

        // Si la connexion existait déjà, le bloc "if" est ignoré, et on renvoie directement la connexion existante.
        return self::$conn; 
    }
}