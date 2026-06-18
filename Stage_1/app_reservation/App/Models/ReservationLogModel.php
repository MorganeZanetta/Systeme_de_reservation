<?php
namespace App\Models;

use App\Core\Model; 
use PDO; 

class ReservationLogModel extends Model {
    protected PDO $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }    

    public function getLogsByReservation(int $idReservation): array {
        $sql = "SELECT * FROM reservation_log 
                WHERE Id_reservation = :id 
                ORDER BY timestamp_reservation_log DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $idReservation]);
        
        // Retourne un tableau associatif ou peut hydrater des objets ReservationLog
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

/**
 * Récupère l'historique complet des logs, 
 * y compris les logs orphelins (sans réservation liée).
 */
public function getAllLogsWithUsers(): array {
    $sql = "SELECT rl.*, r.Id_utilisateur
            FROM reservation_log rl
            LEFT JOIN reservation r ON rl.Id_reservation = r.Id_reservation
            LEFT JOIN utilisateur u ON r.Id_utilisateur = u.Id_utilisateur
            ORDER BY rl.timestamp_reservation_log DESC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $logs = [];
    foreach ($rows as $row) {
        $log = new \App\Models\ReservationLog();
        
        // Hydratation de base
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
}
}

?>