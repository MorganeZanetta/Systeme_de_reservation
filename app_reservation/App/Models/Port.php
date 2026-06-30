<?php
namespace App\Models;

//----------------------------------------------------------------------------------------------
// Classe Port : représentant une entité "Port".
// Cette classe suit les principes de l'encapsulation en PHP.
//----------------------------------------------------------------------------------------------

class Port {
    // Propriétés privées pour garantir l'encapsulation (accès uniquement via méthodes).
    private ?int $Id_port = null; // Peut être un entier ou null.
    private string $libelle_port = ''; // Initialisé par défaut à une chaîne vide.

    /**
     * Retourne l'identifiant du port.
     */
    public function getIdPort(): ?int { 
        return $this->Id_port; 
    }
    /**
     * Définit l'identifiant du port.
     * @param int|null $id
     */
    public function setIdPort(?int $id): void { 
        $this->Id_port = $id; 
    }
    
    /**
     * Retourne le libellé du port.
     */
    public function getLibPort(): string { 
        return $this->libelle_port; 
    }

    /**
     * Définit le libellé du port.
     * Utilise l'opérateur de coalescence (??) pour s'assurer que si une valeur null est passée, elle est convertie en chaîne vide.
     * @param string|null $libelle
     */
    public function setLibPort(?string $libelle): void { 
        $this->libelle_port = $libelle ?? ''; 
    }
}

?>