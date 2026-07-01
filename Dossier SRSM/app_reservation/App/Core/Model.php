<?php
namespace App\Core;

use PDO;

//----------------------------------------------------------------------------------------------
// abstract class Model : Permet aux classes d'enfants d'accéder à $db
//----------------------------------------------------------------------------------------------

abstract class Model {
    protected PDO $db;

    // Le constructeur exige maintenant une instance de PDO
    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }
}