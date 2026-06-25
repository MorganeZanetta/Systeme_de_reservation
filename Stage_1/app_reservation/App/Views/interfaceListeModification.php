<?php
/**
 * @var Reservation $reservation
 * @var array $salle_liste
 * @var array $materiel_liste
 * @var array $idsSallesDejaReservees
 * @var array $idsMaterielsDejaReserves
 */
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
        <h3 id="titre_modification">Modification de la réservation <?= htmlspecialchars($reservation->getIdRes()); ?></h3>
    </div>

    <div class="interface_utilisateur">
        <div id="calendrier">
            <div id="mon_calendrier_fixe"></div>
            <div id="info_reservation"></div>
        </div>

        <div id="formulaire">
            <form id="formulaire_reservation" action="index.php?action=modifierReservation" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="action" value="modifier">
                <input type="hidden" name="id" value="<?= htmlspecialchars($reservation->getIdRes()); ?>">
                <fieldset>
                    <legend>Modifier la réservation (Accès Utilisateur)</legend>
                    <section id="formulaire_colonne">
                        <div class="formulaire_input">
                            <input type="text" name="motif_reservation" id="motif_reservation" class="input_user" 
                                   value="<?= htmlspecialchars($reservation->getMotif()) ?>" placeholder="-- Motif de la réservation --" required autofocus>
                        </div>

                        <div class="formulaire_input">
                            <label for="choix_salle_reservation">Sélectionnez les salles :</label>
                            <select name="salles[]" id="choix_salle_reservation" multiple>
                                <?php foreach ($salle_liste as $item): 
                                    $id = is_object($item) ? $item->getIdSalle() : $item["Id_salle"];
                                    $selected = in_array($id, $idsSallesDejaReservees) ? 'selected' : '';
                                ?>
                                    <option value="<?= htmlspecialchars($id) ?>" <?= $selected ?>>
                                        <?= htmlspecialchars(is_object($item) ? $item->getNomSalle() . ' | Capacité: ' . $item->getCapaciteSalle() . ' | ' . $item->getLocalisationSalle() : $item["nom_salle"] . ' | Capacité: ' . $item["capacite_salle"] . ' | ' . $item["localisation_salle"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="formulaire_input">
                            <label for="choix_materiel_reservation">Sélectionnez le matériel :</label>
                            <select name="materiels[]" id="choix_materiel_reservation" multiple>
                                <?php foreach ($materiel_liste as $row_mat): 
                                    $isObj = is_object($row_mat);
                                    $id = $isObj ? $row_mat->getIdMateriel() : $row_mat["Id_materiel"];
                                    $label = ($isObj ? $row_mat->getTypMat() : $row_mat["type_materiel"]) . ' - ' . ($isObj ? $row_mat->getNumMat() : $row_mat["numero_materiel"]);
                                    $selected = in_array($id, $idsMaterielsDejaReserves) ? 'selected' : '';
                                ?>
                                    <option value="<?= htmlspecialchars($id) ?>" <?= $selected ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="formulaire_input">
                            <label for="date_debut_reservation">Date de début :</label>
                            <input class="input_user" type="text" id="date_debut_reservation" 
                                   value="<?= htmlspecialchars($reservation->getDebut()) ?>" placeholder="-- Sélectionnez une période --" required readonly>
                            <input type="hidden" id="date_debut_iso" name="date_debut_reservation" value="<?= htmlspecialchars($reservation->getDebut()) ?>">
                        </div>
                        
                        <div class="formulaire_input">
                            <label for="date_fin_reservation">Date de fin :</label>
                            <input class="input_user" type="text" id="date_fin_reservation" 
                                   value="<?= htmlspecialchars($reservation->getFin()) ?>" placeholder="-- Sélectionnez une période --" required readonly>
                            <input type="hidden" id="date_fin_iso" name="date_fin_reservation" value="<?= htmlspecialchars($reservation->getFin()) ?>">                       
                        </div>

                        <div class="formulaire_input">
                            <select name="creneau_reservation" id="creneau_reservation" required>
                                <option value="">-- Choisissez un créneau --</option>
                                <option value="Matin (09H00 / 12H00)" <?= $reservation->getCre() == "Matin (09H00 / 12H00)" ? 'selected' : '' ?>>Matin (09H00 / 12H00)</option>
                                <option value="Après-midi (13H00 / 17H00)" <?= $reservation->getCre() == "Après-midi (13H00 / 17H00)" ? 'selected' : '' ?>>Après-midi (13H00 / 17H00)</option>
                                <option value="Journée complète" <?= $reservation->getCre() == "Journée complète" ? 'selected' : '' ?>>Journée complète</option>
                            </select>
                        </div>

                        <div class="formulaire_input">
                            <div id="bouton_utilisateur_modification">
                                <a name="bouton_utilisateur" class="bouton_modification" href="index.php?action=interfaceListeUtilisateur" >Revenir à la liste des réservations</a>
                                <button name="bouton_utilisateur" class="bouton_modification" type="submit">Modifier la réservation</button>
                            </div>
                        </div>
                    </section>
                </fieldset>
            </form>
        </div>
    </div> 
    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
    <script src="/App/Views/js/script.js"></script>
</body>
</html>