<?php
/**
 * @var array $salle_liste
 * @var array $materiel_liste
 * @var array $reservations
 * @var string $csrf_token
 */

$affichageActuel = $_POST['type_affichage'] ?? 'personnel';

// On récupère l'action actuelle, par défaut vide
$actionActuelle = $_GET['action'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Utilisateur - Système de réservation</title>
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
        <a class="lien_onglet <?php echo ($actionActuelle === 'interfaceListeUtilisateur') ? 'active' : ''; ?>" href="http://127.0.0.1:3000/Public/index.php?action=interfaceListeUtilisateur">Liste des réservations</a>
        <a class="lien_onglet <?php echo ($actionActuelle === 'interfaceUtilisateur') ? 'active' : ''; ?>" href="http://127.0.0.1:3000/Public/index.php?action=interfaceUtilisateur">Formulaire de réservation</a>
    </div>

    <div class="interface_utilisateur">
       <div id="calendrier">
            <div id="mon_calendrier_fixe"></div>
                <div class="legend_calendrier_footer">
                <ul style="text-align: left; padding-left: 20px;">
                    <li><strong>Saisie :</strong> Indiquez le motif et sélectionnez vos ressources (salle(s) et/ou matériel).</li>
                    <li><strong>Dates :</strong> Cliquez sur une date de début, puis sur une date de fin.</li>
                    <li><strong>Créneau :</strong> Choisissez entre Matin (M), Après-midi (AM) ou Journée complète (J).</li>
                                <p style="margin-top: 10px; font-size: 0.9em;">
                    <em>Les dates <strong>grisées</strong> sont indisponibles. Les lettres (M/AM/J) indiquent les créneaux réservés. Les dates sans indications sont libres.</em>
                </ul>

                <hr style="border: 0; border-top: 1px solid #ddd; margin: 10px 0;">

                <ul style="text-align: left; padding-left: 20px; list-style: none;">
                    <li>🟩 <strong>Salle(s)/Matériel réservé(e)s :</strong> Deux éléments sélectionnés sont déjà réservés.</li>
                    <li>🟧 <strong>Salle occupée :</strong> La salle sélectionnée est déjà réservée.</li>
                    <li>🟨 <strong>Matériel indisponible :</strong> L'équipement sélectionné est déjà réservé.</li>
                </ul>

            </div>
        </div>

        <div id="formulaire">
            <form id="formulaire_reservation" action="index.php?action=creerReservation" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <fieldset>
                    <legend>Formulaire de réservation</legend>
                    <section id="formulaire_colonne">
                        <div class="formulaire_input">
                            <input type="text" name="motif_reservation" id="motif_reservation" class="input_user" placeholder="-- Motif de la réservation --" required autofocus>
                        </div>

                        <div class="formulaire_input">
                            <label for="choix_salle_reservation">Sélectionnez les salles :</label>
                            <select name="salles[]" id="choix_salle_reservation" multiple >
                                <?php foreach ($salle_liste as $item): ?>
                                    <option value="<?= htmlspecialchars(is_object($item) ? $item->getIdSalle() : $item["Id_salle"]) ?>">
                                        <?= htmlspecialchars(is_object($item) ? $item->getNomSalle() . ' | Capacité: ' . $item->getCapaciteSalle() . ' | ' . $item->getLocalisationSalle() : $item["nom_salle"] . ' | Capacité: ' . $item["capacite_salle"] . ' | ' . $item["localisation_salle"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="formulaire_input">
                            <label for="choix_materiel_reservation">Sélectionnez le matériel :</label>
                            <select name="materiels[]" id="choix_materiel_reservation" multiple >
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

                        <div class="formulaire_input">
                            <label for="date_debut_reservation">Date de début :</label>
                            <input class="input_user" type="text" id="date_debut_reservation" placeholder="-- Sélectionnez une période dans le calendrier --" required readonly>
                            <input type="hidden" id="date_debut_iso" name="date_debut_reservation">
                        </div>
                        
                        <div class="formulaire_input">
                            <label for="date_fin_reservation">Date de fin :</label>
                            <input class="input_user" type="text" id="date_fin_reservation" placeholder="-- Sélectionnez une période dans le calendrier --" required readonly>
                            <input type="hidden" id="date_fin_iso" name="date_fin_reservation">                       
                        </div>

                        <div class="formulaire_input">
                            <select name="creneau_reservation" id="creneau_reservation" required>
                                <option value="">-- Choisissez un créneau --</option>
                                <option value="Matin (09H00 / 12H00)">Matin (09H00 / 12H00)</option>
                                <option value="Après-midi (13H00 / 17H00)">Après-midi (13H00 / 17H00)</option>
                                <option value="Journée complète">Journée complète</option>
                            </select>
                        </div>

                        <div class="formulaire_input">
                            <button name="bouton_utilisateur" class="bouton_utilisateur" type="submit" style="width: 100%;">Valider la réservation</button>
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