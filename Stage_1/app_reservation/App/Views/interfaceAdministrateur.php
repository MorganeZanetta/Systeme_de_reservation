<?php
/**
 * @var array $salle_liste
 * @var array $materiel_liste
 * @var array $reservations
 */

$affichageActuel = $_POST['type_affichage'] ?? 'personnel';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Système de réservation des salles et du matériel</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../../Public/css/style.css" />
    <link rel="stylesheet" href="../../Public/js/script.js" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="en_tete">
            <img id="logo" src="../../Public/images/logo-epcic.png" alt="logo_epcic">
            <h2 id="titre_administrateur">Administration du système de réservation <br/> des salles et du matériel</h2>
        <a id="deconnexion" href="index.php?action=deconnexion" >Déconnexion</a>
        </div>
    </header>

<div id="onglet">
        <a class="lien_onglet" href="http://127.0.0.1:3000/Public/index.php?action=interfaceAdministrateur">Actions sur les utilisateurs</a>
        <a class="lien_onglet" href="http://127.0.0.1:3000/Public/index.php?action=interfaceListeAdministrateur">Actions sur les réservations</a>
        <a class="lien_onglet" href="http://127.0.0.1:3000/Public/index.php?action=interfaceSalleMaterielAdministrateur">Actions sur les salles et le matériel</a>
        <a class="lien_onglet" href="http://127.0.0.1:3000/Public/index.php?action=interfaceLogAdministrateur">Historique des évènements sur les réservations</a>
</div>

    <div id="interface_admin">

        <div id="interface1">
            <fieldset class="fieldset_admin">
                <legend>Filtre de recherche par identifiants, ports, salles et matériel</legend>
                <ul>
                    <li>
                        <input type="checkbox" id="filtre_date" class="input_admin" name="options">
                        <label for="filtre_date">Date</label>
                    </li>
                    <li>
                        <input type="checkbox" id="filtre_identifiant" class="input_admin" name="options">
                        <label for="filtre_identifiant">Identifiant</label>
                    </li>
                    <li>
                        <input type="checkbox" id="filtre_prenom" class="input_admin" name="options">
                        <label for="filtre_prenom">Prénom</label>
                    </li>
                    <li>
                        <input type="checkbox" id="filtre_nom" class="input_admin" name="options">
                        <label for="filtre_nom">Nom</label>
                    </li>
                    <li>
                        <input type="checkbox" id="filtre_port" class="input_admin" name="options">
                        <label for="filtre_port">Port</label>
                    </li>
                    <li>
                        <input type="checkbox" id="filtre_salle" class="input_admin" name="options">
                        <label for="filtre_salle">Salle</label>

                        <input type="checkbox" id="filtre_capacite" class="input_admin" name="options">
                        <label for="filtre_capacite">Capacité de la salle</label>
                    </li>
                    <li>
                        <input type="checkbox" id="filtre_materiel" class="input_admin" name="options">
                        <label for="filtre_materiel">Matériel</label>

                        <input type="checkbox" id="filtre_type_mat" class="input_admin" name="options">
                        <label for="filtre_type_mat">Type de Matériel</label>

                        <input type="checkbox" id="filtre_etat_mat" class="input_admin" name="options">
                        <label for="filtre_etat_mat">État du matériel</label>
                    </li>
                </ul>
            </fieldset>

            <div id="zone_de_recherche">
                <div id="zone_recherche">
                    <input type="text" id="recherche_admin" class="recherche_admin" placeholder="Filtrer en temps réel..." onkeyup="filtrerTableau()">
                </div>
                <div>
                    <button type="button" class="button_admin">Valider la recherche</button>
                </div>
            </div>
        </div>

  
        <div id="interface2">
            <fieldset class="fieldset_admin">
                <legend>Ajout de port, de salle ou de matériel</legend>
                <ul>
                    <li>
                        <input class="input_admin" type="checkbox" id="ajout_port" name="options">
                        <label for="ajout_port">Port</label>
                    </li>
                    <li>
                        <input class="input_admin" type="checkbox" id="ajout_salle" name="options">
                        <label for="ajout_salle">Salle</label>
                    </li>
                    <li>
                        <input class="input_admin" type="checkbox" id="ajout_materiel" name="options">
                        <label for="ajout_materiel">Matériel</label>
                    </li>
                </ul>
            </fieldset>

            <div class="zone_action_ajout">
                <div>
                    <label for="ajout_port_ressource"></label>
                    <input type="text" name="ajout" id="ajout_port_ressource" class="recherche_admin" placeholder="Ajoutez un port ou une ressource" required autofocus>
                </div>
                <div>
                    <button type="submit" class="button_admin">Ajouter</button>
                </div>
            </div>
        </div>
    </div>
                    
 <section id="liste_reservation">
            
            <div id="reservation">
                <h3>Liste des réservations</h3>
                <?php if (empty($reservations)): ?>
                    <p>Il n'y a aucune réservation répertoriée.</p>
                <?php else: ?>
                    <div class="tableau_reservations">
                        <table>
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Prénom</th>
                                    <th>Nom</th>
                                    <th>Port</th>
                                    <th>Motif</th>
                                    <th>Salle(s)</th>
                                    <th>Matériel</th>
                                    <th>Début</th>
                                    <th>Fin</th>
                                    <th>Créneau</th>
                                    <th>Modifier</th>
                                    <th>Supprimer</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reservations as $row): ?>
                                    <?php $user = $row->getUtilisateur(); ?>
                                    <tr id="vue-<?= $row->getIdRes() ?>">
                                        <td><?= $row->getIdRes() ?></td>
                                        <td><?= htmlspecialchars($user ? $user->getPrenomUtilisateur() : '') ?></td>
                                        <td><?= htmlspecialchars($user ? $user->getNomUtilisateur() : '') ?></td>
                                        <td><?= htmlspecialchars(($user && $user->getPort()) ? $user->getPort()->getLibPort() : '') ?></td>
                                        <td><?= htmlspecialchars($row->getMotif()) ?></td>
                                        <td>
<?php 
$salles = $row->getSalles();

if (!empty($salles)) {
    $nomsSalles = [];
    foreach ($salles as $salle) {
        $nomsSalles[] = htmlspecialchars($salle->getNomSalle());
    }
    
    // 1. Suppression des doublons
    $nomsUniques = array_unique($nomsSalles);
    
    // 2. Utilisation de implode sans htmlspecialchars autour
    // On affiche directement le résultat car les noms sont déjà sécurisés un par un
    echo implode(" - ", $nomsUniques);
    
} else { 
    echo "-"; 
}
?>
                                        </td>
                                        <td>
<?php 
$materiels = $row->getMateriels();

if (!empty($materiels)) {

    $nomsMateriels = [];
    foreach ($materiels as $mat) { 
        // On récupère le type et le numéro en les transformant en chaîne
        $type = $mat->getTypMat();
        $numero = $mat->getNumMat();
        
        // On concatène : Type + Espace + Numéro
        $description = $type . " " . $numero;
        
        // On sécurise pour le HTML et on ajoute au tableau
        $nomsMateriels[] = htmlspecialchars($description);
    }
    
    // On enlève les doublons
    $nomsUniques = array_unique($nomsMateriels);
    
    // Affichage avec le tiret et le saut de ligne
    echo implode(" - ", $nomsUniques);
    
} else { 
    echo "-"; 
}
?>
                                        </td>
                                        <td><?= htmlspecialchars($row->getDebut()) ?></td>
                                        <td><?= htmlspecialchars($row->getFin()) ?></td>
                                        <td><?= htmlspecialchars($row->getCre()) ?></td>

<td>
    <button class="boutons_actions" type="button" onclick="activerEdition(<?= $row->getIdRes() ?>)">✏️</button>
</td>

<td>
    <form action="index.php?action=supprimerReservation" method="POST">
        <input type="hidden" name="action" value="supprimer">
        <input type="hidden" name="id" value="<?= $row->getIdRes() ?>">
        <button class="boutons_actions" type="submit" onclick="return confirm('Confirmer la suppression ?')">🗑️</button>
    </form>
</td>
                                    </tr>

                                    <tr id="edit-<?= $row->getIdRes() ?>" style="display:none; background:#f4f4f4;">
                                        <td colspan="12">
                                            <form action="index.php?action=modifierReservation" method="POST">
                                                <input type="hidden" name="action" value="modifier">
                                                <input type="hidden" name="id" value="<?= $row->getIdRes() ?>">
                                                
                                                <input type="text" name="motif_reservation" value="<?= htmlspecialchars($row->getMotif()) ?>" required>
                                                <input type="date" name="date_debut_reservation" value="<?= htmlspecialchars($row->getDebut()) ?>" required>
                                                <input type="date" name="date_fin_reservation" value="<?= htmlspecialchars($row->getFin()) ?>" required>
                                                
                                                <select name="creneau_horaire" required>
                                                    <option value="Matin (09H00 / 12H00)" <?= $row->getCre() === 'Matin (09H00 / 12H00)' ? 'selected' : '' ?>>Matin</option>
                                                    <option value="Après-midi (13H00 / 17H00)" <?= $row->getCre() === 'Après-midi (13H00 / 17H00)' ? 'selected' : '' ?>>Après-midi</option>
                                                    <option value="Journée complète" <?= $row->getCre() === 'Journée complète' ? 'selected' : '' ?>>Journée complète</option>
                                                </select>

                                                <button type="submit">Sauvegarder</button>
                                                <button type="button" onclick="annulerEdition(<?= $row->getIdRes() ?>)">Annuler</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?> 
            </div>
        </section>

    <script>
    function filtrerTableau() {
        const input = document.getElementById('recherche_admin');
        const saisie = input ? input.value.toLowerCase() : '';
        const lignes = document.querySelectorAll('#table_salles tbody tr');

        lignes.forEach(ligne => {
            const nomSalleCell = ligne.querySelector('.nom_salle');
            if (nomSalleCell) {
                const nomSalle = nomSalleCell.innerText.toLowerCase();
                if (nomSalle.includes(saisie)) {
                    ligne.style.display = '';
                } else {
                    ligne.style.display = 'none';
                }
            }
        });
    }
    </script>
</body>
</html>