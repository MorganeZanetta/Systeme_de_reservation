<?php
namespace App\Core;

use PDO;

// abstract est parfait ici !
abstract class Model {
    // Accessible par tous les modèles enfants
    protected PDO $db;

    // Constructeur unique pour injecter la BDD
    public function __construct(PDO $db) {
        $this->db = $db;
    }
}