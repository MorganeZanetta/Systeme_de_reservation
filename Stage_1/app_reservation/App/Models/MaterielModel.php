<?php
namespace App\Models;

use PDO;

class MaterielModel {

    protected PDO $db;

    // Le modèle reçoit la connexion PDO directement dans son constructeur
    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    /**
     * Récupère la liste de tout le matériel pour le formulaire
     */

public function voirListeMateriel(): array {
    $stmt = $this->db->query("SELECT * FROM materiel ORDER BY type_materiel ASC");
    $materiels = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $materiel = new Materiel();
        $materiel->setIdMateriel((int)$row['Id_materiel']);
        $materiel->setTypMat($row['type_materiel']);
        $materiel->setNumMat($row['numero_materiel']);
        $materiel->setPhoMat($row['photo_materiel']);
        $materiels[] = $materiel;
    }
    return $materiels;
}


public function findById(int $id): ?Materiel {
    $stmt = $this->db->prepare("SELECT * FROM materiel WHERE Id_materiel = :id");
    $stmt->execute([':id' => $id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) return null;

    $materiel = new Materiel();
    $materiel->setIdMateriel((int)$data['Id_materiel']);
    $materiel->setTypMat($data['type_materiel']);
    $materiel->setNumMat($data['numero_materiel']);
    $materiel->setPhoMat($data['photo_materiel']);
    
    return $materiel;
}

public function ajouterUnMateriel(Materiel $materiel): void {
        $sql = "INSERT INTO materiel (type_materiel, numero_materiel, photo_materiel) VALUES (?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $materiel->getTypMat(),
            $materiel->getNumMat(),
            $materiel->getPhoMat()
        ]);
        
        // Optionnel : récupérer l'ID généré
        $materiel->setIdMateriel((int)$this->db->lastInsertId());
    }
    
public function modifierUnMateriel(Materiel $materiel): void {
        $sql = "UPDATE materiel 
                SET type_materiel = ?, numero_materiel = ?, photo_materiel = ? 
                WHERE Id_materiel = ?";
    
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $materiel->getTypMat(),
            $materiel->getNumMat(),
            $materiel->getPhoMat(),
            $materiel->getIdMateriel()
        ]);
    }

public function supprimerUnMateriel(int $id): void {
        $sql = "DELETE FROM materiel WHERE Id_materiel = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
}

public function getMaterialTypes() {
    $stmt = $this->db->query("SELECT DISTINCT type_materiel FROM materiel");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

public function getMaterialNumbers() {
    $stmt = $this->db->query("SELECT numero_materiel FROM materiel");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

}