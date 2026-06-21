<?php
/**
 * @var array $salle_liste
 * @var array $materiel_liste
 * @var array $reservations
 */

$idUtilisateur = $_SESSION['Id_utilisateur'] ?? null;
$roleUtilisateur = $_SESSION['Id_role'] ?? null;

$afficherColonnesActions = false;
foreach ($reservations as $row) {
    $u = $row->getUtilisateur();
    if ($roleUtilisateur == 1 || ($u && $u->getIdUtilisateur() == $idUtilisateur)) {
        $afficherColonnesActions = true;
        break;
    }
}
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
            <h2 id="titre_administrateur">Administration du système de réservation <br/> des salles et du matériel</h2>
            <a id="deconnexion" href="index.php?action=deconnexion" >Déconnexion</a>
        </div>
    </header>

    <div id="onglet">
        <a class="lien_onglet" href="index.php?action=interfaceAdministrateur">Actions sur les utilisateurs</a>
        <a class="lien_onglet" href="index.php?action=interfaceListeAdministrateur">Actions sur les réservations</a>
        <a class="lien_onglet" href="index.php?action=interfaceSalleMaterielAdministrateur">Actions sur les salles et le matériel</a>
        <a class="lien_onglet" href="index.php?action=interfaceLogAdministrateur">Historique des évènements</a>
    </div>

    <div id="interface_liste_admin">
<div id="formulaire_admin">
            <form id="formulaire_reservation_admin" action="index.php?action=creerReservationAdmin" method="POST">
                <fieldset>
                    <legend>Formulaire de réservation</legend>
                    <section id="formulaire_colonne_admin">
                        <div class="formulaire_input_admin">
                            <input type="text" name="motif_reservation" id="motif_reservation_admin" class="input_admin" placeholder="-- Motif de la réservation --" required autofocus>
                        </div>

                        <div class="formulaire_input_admin">
                            <label for="choix_salle_reservation">Sélectionnez les salles :</label>
                            <select name="salles[]" id="choix_salle_reservation_admin" multiple >
                                <?php foreach ($salle_liste as $item): ?>
                                    <option value="<?= htmlspecialchars(is_object($item) ? $item->getIdSalle() : $item["Id_salle"]) ?>">
                                        <?= htmlspecialchars(is_object($item) ? $item->getNomSalle() . ' | Capacité: ' . $item->getCapaciteSalle() : $item["nom_salle"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="formulaire_input_admin">
                            <label for="choix_materiel_reservation">Sélectionnez le matériel :</label>
                            <select name="materiels[]" id="choix_materiel_reservation_admin" multiple >
                                <?php foreach ($materiel_liste as $row_mat): ?>
                                    <?php
                                    $isObj = is_object($row_mat);
                                    $id = $isObj ? $row_mat->getIdMateriel() : $row_mat["Id_materiel"];
                                    $label = ($isObj ? $row_mat->getTypMat() : $row_mat["type_materiel"]);
                                    ?>
                                    <option value="<?= htmlspecialchars($id) ?>">
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="formulaire_input_admin">
                            <label for="date_debut_reservation">Date de début :</label>
                            <input class="input_admin" type="date" id="date_debut_reservation_admin" placeholder="-- Date début --" required readonly>
                            <input type="hidden" id="date_debut_iso_admin" name="date_debut_reservation">
                        </div>
                        
                        <div class="formulaire_input_admin">
                            <label for="date_fin_reservation">Date de fin :</label>
                            <input class="input_admin" type="date" id="date_fin_reservation_admin" placeholder="-- Date fin --" required readonly>
                            <input type="hidden" id="date_fin_iso_admin" name="date_fin_reservation">                       
                        </div>

                        <div class="formulaire_input_admin">
                            <select name="creneau_reservation" id="creneau_reservation_admin" required>
                                <option value="">-- Choisissez un créneau --</option>
                                <option value="Matin">Matin</option>
                                <option value="Après-midi">Après-midi</option>
                                <option value="Journée Complète">Journée complète</option>
                            </select>
                        </div>

                        <div class="formulaire_input_admin">
                            <button name="bouton_utilisateur" class="bouton_utilisateur_admin" type="submit" style="width: 100%;">Valider la réservation</button>
                        </div>
                    </section>
                </fieldset>
            </form>
        </div>

                    <div id="reservation_admin">
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
            </div>
</body>
</html>