<?php
namespace App\Models;

use PDO;
use App\Models\Salle;

class SalleModel {

    protected PDO $db;

    // Le modèle reçoit la connexion PDO directement dans son constructeur
    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    /**
     * Récupère la liste de toutes les salles pour le formulaire
     */
// 1. Pour le formulaire (Liste complète)
public function voirListeSalles(): array {
    $stmt = $this->db->query("SELECT * FROM salle ORDER BY Id_salle ASC");
    $salles = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $salle = new Salle();
        $salle->setIdSalle((int)$row['Id_salle']);
        $salle->setNomSalle($row['nom_salle']);
        $salle->setCapaciteSalle($row['capacite_salle']);
        $salle->setLocalisationSalle($row['localisation_salle']);
        $salles[] = $salle;
    }
    return $salles;
}

// 2. Pour récupérer une seule salle (par ID)
public function findById(int $id): ?Salle {
    $stmt = $this->db->prepare("SELECT * FROM salle WHERE Id_salle = :id");
    $stmt->execute([':id' => $id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) return null;

    $salle = new Salle();
    $salle->setIdSalle((int)$data['Id_salle']);
    $salle->setNomSalle($data['nom_salle']);
    $salle->setCapaciteSalle($data['capacite_salle']);
    $salle->setLocalisationSalle($data['localisation_salle']);
    return $salle;
}
public function ajouterUneSalle(Salle $salle): void {
        $sql = "INSERT INTO salle (nom_salle, capacite_salle, localisation_salle) VALUES (?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $salle->getNomSalle(),
            $salle->getCapaciteSalle(),
            $salle->getLocalisationSalle()
        ]);
        
        // Optionnel : récupérer l'ID généré
        $salle->setIdSalle((int)$this->db->lastInsertId());
    }
    
public function modifierUneSalle(Salle $salle): void {
        $sql = "UPDATE salle 
                SET nom_salle = ?, capacite_salle = ?, localisation_salle = ? 
                WHERE Id_salle = ?";
    
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $salle->getNomSalle(),
            $salle->getCapaciteSalle(),
            $salle->getLocalisationSalle(),
            $salle->getIdSalle()
        ]);
    }

public function supprimerUneSalle(int $id): void {
        $sql = "DELETE FROM salle WHERE Id_salle = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
}
}