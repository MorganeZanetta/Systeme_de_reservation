<?php
/**
 * @var array $salle_liste
 * @var array $materiel_liste
 * @var array $reservations
 */

// On récupère les variables de session pour la logique de droit
$idUtilisateur = $_SESSION['Id_utilisateur'] ?? null;
$roleUtilisateur = $_SESSION['Id_role'] ?? null;

// Pré-calcul : existe-t-il au moins une ligne modifiable par cet utilisateur ?
// Cela permet de ne pas afficher les colonnes "Actions" si personne ne peut les utiliser
$afficherColonnesActions = false;
foreach ($reservations as $row) {
    $u = $row->getUtilisateur();
    if ($roleUtilisateur == 1 || ($u && $u->getIdUtilisateur() == $idUtilisateur)) {
        $afficherColonnesActions = true;
        break;
    }
}

$affichageActuel = $_POST['type_affichage'] ?? 'personnel';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Système de réservation des salles et du matériel</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../../Public/css/style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="en_tete">
            <img id="logo" src="../../Public/images/logo-epcic.png" alt="logo_epcic">
            <h2 id="titre_utilisateur">Système de réservation des salles et du matériel</h2>
            <a id="deconnexion" href="index.php?action=deconnexion" >Déconnexion</a>
        </div>
    </header>

    <div id="onglet">
        <a class="lien_onglet" href="http://127.0.0.1:3000/Public/index.php?action=interfaceUtilisateur">Formulaire de réservation</a>
        <a class="lien_onglet" href="http://127.0.0.1:3000/Public/index.php?action=interfaceListeUtilisateur">Liste des réservations</a>
    </div>

    <div class="interface_utilisateur">
        <section id="liste_reservation">
            <div id="filtre_bouton">
                <form action="index.php?action=interfaceListeUtilisateur" method="POST">
                    <button class="bouton_utilisateur <?= ($affichageActuel === 'personnel') ? 'bouton-actif' : '' ?>" type="submit" name="type_affichage" value="personnel">Personnelles</button>
                    <button class="bouton_utilisateur <?= ($affichageActuel === 'global') ? 'bouton-actif' : '' ?>" type="submit" name="type_affichage" value="global">Globales</button>
                </form>
            </div> 
            
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
                                    <?php if ($afficherColonnesActions): ?>
                                        <th>Modifier</th>
                                        <th>Supprimer</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reservations as $row): 
                                    $user = $row->getUtilisateur();
                                    $estAutorise = ($roleUtilisateur == 1 || ($user && $user->getIdUtilisateur() == $idUtilisateur));
                                ?>
                                    <tr <?= $row->getIdRes() ?>>
                                        <td><?= $row->getIdRes() ?></td>
                                        <td><?= htmlspecialchars($user ? $user->getPrenomUtilisateur() : '') ?></td>
                                        <td><?= htmlspecialchars($user ? $user->getNomUtilisateur() : '') ?></td>
                                        <td><?= htmlspecialchars(($user && $user->getPort()) ? $user->getPort()->getLibPort() : '') ?></td>
                                        <td><?= htmlspecialchars($row->getMotif()) ?></td>
                                        <td>
                                            <?php 
                                            $salles = $row->getSalles();
                                            if (!empty($salles)) {
                                                $noms = array_map(fn($s) => htmlspecialchars($s->getNomSalle()), $salles);
                                                echo implode(" - ", array_unique($noms));
                                            } else { echo "-"; }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $materiels = $row->getMateriels();
                                            if (!empty($materiels)) {
                                                $noms = array_map(fn($m) => htmlspecialchars($m->getTypMat() . " " . $m->getNumMat()), $materiels);
                                                echo implode(" - ", array_unique($noms));
                                            } else { echo "-"; }
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($row->getDebut()) ?></td>
                                        <td><?= htmlspecialchars($row->getFin()) ?></td>
                                        <td><?= htmlspecialchars($row->getCre()) ?></td>
                                        
                                        <?php if ($afficherColonnesActions): ?>                                 
                                        <td>
                                        <?php if ($estAutorise): ?>
                                         <a id="lien_action" name="action" href="index.php?action=formulaireModifier&id=<?= $row->getIdRes() ?>">✏️</a>
                                        <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($estAutorise): ?>
                                                    <form action="index.php?action=supprimerReservation" method="POST">
                                                        <input type="hidden" name="action" value="supprimer">
                                                        <input type="hidden" name="id" value="<?= $row->getIdRes() ?>">
                                                        <button id="bouton_action" type="submit" onclick="return confirm('Confirmer la suppression ?')">🗑️</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>

                                    <?php if ($estAutorise): ?>
                                    <tr id="edit-<?= $row->getIdRes() ?>" style="display:none; background:#f4f4f4;">
                                        <td colspan="<?= $afficherColonnesActions ? 12 : 10 ?>">
                                            <form class="formulaire_hidden" action="index.php?action=modifierReservation" method="POST">
                                                <input type="hidden" name="action" value="modifier">
                                                <input type="hidden" name="id" value="<?= $row->getIdRes() ?>">
                                                <input type="text" name="motif_reservation" id="motif_hidden" value="<?= htmlspecialchars($row->getMotif()) ?>" required>
                             <div class="formulaire_input">
                            <label for="choix_salle_reservation">Sélectionnez les salles :</label>
<select name="salles[]" multiple required>
    <?php foreach ($salle_liste as $item): 
        $idSalle = is_object($item) ? $item->getIdSalle() : $item["Id_salle"];
        // Comparaison : est-ce que cet ID est dans notre liste récupérée ?
        $estSelectionne = in_array($idSalle, $idsSallesDejaReservees ?? []);
    ?>
        <option value="<?= htmlspecialchars($idSalle) ?>" <?= $estSelectionne ? 'selected' : '' ?>>
            <?= htmlspecialchars(is_object($item) ? $item->getNomSalle() : $item["nom_salle"]) ?>
        </option>
    <?php endforeach; ?>
</select>
</select>
                        </div>

                        <div class="formulaire_input">
                            <label for="choix_materiel_reservation">Sélectionnez le matériel :</label>
                            <select name="materiels[]" multiple required>
                                <?php foreach ($materiel_liste as $row_mat): ?>
                                     <?php
                                    $isObj = is_object($row_mat);
                                    $id = $isObj ? $row_mat->getIdMateriel() : $row_mat["Id_materiel"];
                                    $label = ($isObj ? $row_mat->getTypMat() : $row_mat["type_materiel"]) . ' - ' . ($isObj ? $row_mat->getNumMat() : $row_mat["numero_materiel"]);
                                    ?>
                                    <option value="<?= htmlspecialchars($id) ?>">
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>


                                                <input type="date" name="date_debut_reservation" id ="date_debut_hidden" value="<?= htmlspecialchars($row->getDebut()) ?>" required>
                                                <input type="date" name="date_fin_reservation" id ="date_fin_hidden" value="<?= htmlspecialchars($row->getFin()) ?>" required>
                                                <select name="creneau_hidden" required>
                                                    <option value="Matin (09H00 / 12H00)" <?= $row->getCre() === 'Matin (09H00 / 12H00)' ? 'selected' : '' ?>>Matin</option>
                                                    <option value="Après-midi (13H00 / 17H00)" <?= $row->getCre() === 'Après-midi (13H00 / 17H00)' ? 'selected' : '' ?>>Après-midi</option>
                                                    <option value="Journée complète" <?= $row->getCre() === 'Journée complète' ? 'selected' : '' ?>>Journée complète</option>
                                                </select>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?> 
            </div>
        </section>
    </div>

</body>
</html>