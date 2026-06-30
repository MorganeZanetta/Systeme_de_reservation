<?php
namespace App\Models;

use App\Core\Model;
use PDO; 
use PDOException;
use Exception;

//----------------------------------------------------------------------------------------------
// Classe ReservationLogModel : pour la gestion des logs de réservation.
// Gère l'accès aux données de la table 'reservation_log' et leur hydratation.
//----------------------------------------------------------------------------------------------

class ReservationLogModel extends Model {

    /**
     * Récupère les logs pour une réservation spécifique.
     */
    public function getLogsByReservation(int $idReservation): array {
        try {
            $sql = "SELECT * FROM reservation_log 
                    WHERE Id_reservation = :id 
                    ORDER BY timestamp_reservation_log DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $idReservation]);
            // Retourne un tableau associatif brut.
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Journalisation de l'erreur technique et envoi d'une exception générique utilisateur.
            error_log("Erreur dans ReservationLogModel::getLogsByReservation : " . $e->getMessage());
            throw new Exception("Impossible de récupérer l'historique de cette réservation.");
        }
    }

    /**
     * Récupère l'historique complet des logs avec les informations utilisateurs associées.
     * @return ReservationLog[]
     * @throws Exception
     */
    public function getAllLogsWithUsers(): array {
        try {
            $sql = "SELECT rl.*, r.Id_utilisateur
                    FROM reservation_log rl
                    LEFT JOIN reservation r ON rl.Id_reservation = r.Id_reservation
                    LEFT JOIN utilisateur u ON r.Id_utilisateur = u.Id_utilisateur
                    ORDER BY rl.timestamp_reservation_log DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $logs = [];
            // "Hydratation" manuelle : transformation des lignes SQL en objets PHP
            foreach ($rows as $row) {
                $log = new \App\Models\ReservationLog();
                
                // Hydratation de l'objet avec vérification des clés
                $log->setIdReservationLog((int)($row['Id_reservation_log'] ?? 0));
                $log->setIdUtilisateur(isset($row['Id_utilisateur']) ? (int)$row['Id_utilisateur'] : null);
                $log->setActionReservationLog($row['action_reservation_log'] ?? '');
                $log->setDescriptionReservationLog($row['description_reservation_log'] ?? '');
                $log->setOldDataReservationLog($row['old_data_reservation_log'] ?? null);
                $log->setNewDataReservationLog($row['new_data_reservation_log'] ?? null);
                $log->setTimestampReservationLog($row['timestamp_reservation_log'] ?? '');
                
                $logs[] = $log;
            }
            
            return $logs;

        } catch (PDOException $e) {
            error_log("Erreur dans ReservationLogModel::getAllLogsWithUsers : " . $e->getMessage());
            throw new Exception("Impossible de charger la liste complète des logs.");
        }
    }

    /**
    * Recherche avancée : Filtre les logs en interrogeant les colonnes JSON (old/new_data).
    * Utilise les fonctions natives JSON_CONTAINS et JSON_EXTRACT.
    */
    public function chercherParCriteres(array $criteres, array $options) {
        try {
            // Initialisation de la requête de base
            $sql = "SELECT * FROM reservation_log WHERE 1=1";
            $params = [];

            // Détermination des colonnes JSON ciblées (côte à côte).
            // Par défaut, si aucune case n'est cochée, la recherche est intégrale.
            $cibles = [];
            if (!empty($options['recherche_new_data'])) $cibles[] = 'new_data_reservation_log';
            if (!empty($options['recherche_old_data'])) $cibles[] = 'old_data_reservation_log';
        
            if (empty($cibles)) {
            $cibles = ['new_data_reservation_log', 'old_data_reservation_log'];
            }

            // Filtre utilisateur via accès direct au champ JSON (Syntaxe ->>).
            if (!empty($criteres['id_utilisateur'])) {
                $sql .= " AND new_data_reservation_log->>'$.utilisateur_id' = :id_utilisateur";
                $params[':id_utilisateur'] = (int)$criteres['id_utilisateur'];
            }

            // Filtres complexes sur Salles/Matériel dans les tableaux JSON.
        $conditions = [];
        $i = 0;

        foreach ($cibles as $col) {
        $i++;
        $sub = [];

            if (!empty($criteres['salle'])) {
                $paramName = ":salle_" . $i;
                // On extrait tous les IDs des objets contenus dans le tableau "salles".
                // Le chemin '$.salles[*].id' pointe directement sur la liste des IDs.
                // JSON_CONTAINS vérifie si l'ID passé existe dans la liste JSON extraite.
                $sub[] = "JSON_CONTAINS(JSON_EXTRACT($col, '$.salles[*].id'), CAST($paramName AS JSON))";
                $params[$paramName] = (int)$criteres['salle'];
        }

            if (!empty($criteres['materiel'])) {
                $paramName = ":materiel_" . $i;
                $sub[] = "JSON_CONTAINS(JSON_EXTRACT($col, '$.materiels[*].id'), CAST($paramName AS JSON))";
                $params[$paramName] = (int)$criteres['materiel'];
        }
            
            if (!empty($sub)) {
                $conditions[] = "(" . implode(" OR ", $sub) . ")";
        }

    }
        
            // Ajout des conditions au SQL principal.
            if (!empty($conditions)) {
                $sql .= " AND (" . implode(" OR ", $conditions) . ")";
        }

        // Filtre sur les dates (extraction depuis le JSON 'reservation' -> 'debut'/'fin').
        if (!empty($criteres['date_debut'])) {
            $sql .= " AND new_data_reservation_log->>'$.reservation.debut' >= :date_debut";
            $params[':date_debut'] = $criteres['date_debut'];
        }
        
        if (!empty($criteres['date_fin'])) {
            $sql .= " AND new_data_reservation_log->>'$.reservation.fin' <= :date_fin";
            $params[':date_fin'] = $criteres['date_fin'];
        }

        // Exécution et hydratation automatique via votre classe.
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        // PDO crée directement des objets de type ReservationLog.
        return $stmt->fetchAll(PDO::FETCH_CLASS, '\App\Models\ReservationLog');

    } catch (PDOException $e) {
        // Log l'erreur réelle pour le débogage.
        error_log("Erreur SQL dans ReservationLogModel : " . $e->getMessage());
        throw new Exception("Impossible de charger les logs : " . $e->getMessage());
        }
    }
}

?>