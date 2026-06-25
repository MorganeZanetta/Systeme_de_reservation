<?php
/**
 * @var array $utilisateurs
 * @var array $salles
 * @var array $materiels
 */
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Système de réservation des salles et du matériel</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../../Public/css/style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
</head>
</head>
<body>
    <header>
        <div class="en_tete">
            <img id="logo" src="../../Public/images/logo-epcic.png" alt="logo_epcic">
            <h2 id="titre_administrateur">Administration du système de réservation</h2>
            <a id="deconnexion" href="index.php?action=deconnexion">Déconnexion</a>
        </div>
    </header>

    <div id="onglet">
        <a class="lien_onglet" href="index.php?action=interfaceAdministrateur">Actions sur les utilisateurs</a>
        <a class="lien_onglet" href="index.php?action=interfaceRolePortAdministrateur">Actions sur les rôles / ports</a>
        <a class="lien_onglet" href="index.php?action=interfaceSalleMaterielAdministrateur">Actions sur les salles / matériel</a>
        <a class="lien_onglet" href="index.php?action=interfaceReservationAdministrateur">Formulaire de réservation</a>
        <a class="lien_onglet" href="index.php?action=interfaceListeReservationAdministrateur">Liste des réservations</a>
        <a class="lien_onglet" href="index.php?action=interfaceLogAdministrateur">Historique</a>
    </div>

    <div id="interface_admin_log">
        <main id="log">
            <form method="GET" action="index.php">
                <input type="hidden" name="action" value="interfaceLogAdministrateur">
                <fieldset class="fieldset_admin">   
                    <legend>Filtre de recherche par N°ID utilisateur, réservations, salles, matériel et dates</legend>
                    <ul>
                        <li>
                            <label for="choix_utilisateur">Filtrer par utilisateur :</label>
                            <select name="criteres[id_user]" id="choix_utilisateur">
                                <option value="">-- Tous les utilisateurs --</option>
                                    <?php foreach ($utilisateurs as $utilisateur): ?>
                                <option value="<?= htmlspecialchars($utilisateur->getIdUtilisateur()) ?>">
                                    <?= htmlspecialchars($utilisateur->getNomUtilisateur()) . ' ' . htmlspecialchars($utilisateur->getPrenomUtilisateur()) ?>
                                </option>
                            <?php endforeach; ?>
                            </select>
                        </li>
                        <li>
                            <label for="choix_salle">Filtrer par salle :</label>
                            <select name="criteres[salle]" id="choix_salle">
                                <option value="">-- Toutes les salles --</option>
                                    <?php foreach ($salles as $salle): ?>
                                <option value="<?= htmlspecialchars($salle->getIdSalle()) ?>">
                                    <?= htmlspecialchars($salle->getNomSalle()) . ' ' . htmlspecialchars($salle->getLocalisationSalle()) ?>
                                </option>
                             <?php endforeach; ?>
                            </select>
                        </li>
                        <li>
                            <label for="choix_materiel">Filtrer par matériel :</label>
                            <select name="criteres[materiel]" id="choix_materiel">
                                <option value="">-- Tout le matériel --</option>
                                <?php foreach ($materiels as $materiel): ?>
                                    <option value="<?= htmlspecialchars($materiel->getIdMateriel()) ?>">
                                        <?= htmlspecialchars($materiel->getTypMat()) . ' ' . htmlspecialchars($materiel->getNumMat()) . ' ' . htmlspecialchars($materiel->getLocalisationMateriel())?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </li>
                        <li>
                            <label for="filtre_date_debut">Filtrer par date de début :</label>
                            <input class = "input_filtre_log" type="search" name="criteres[date_debut]" placeholder="Date de début...">
                        </li>
                        <li>
                            <label for="filtre_date_fin">Filtrer par date de fin :</label>
                            <input class = "input_filtre_log" type="search" name="criteres[date_fin]" placeholder="Date de fin...">
                        </li>
                        <li>
                            <button class = "bouton_administrateur" type="submit">Appliquer les filtres</button>
                        </li>
                    </ul>
                </fieldset>
            </form>
                
            <?php if (empty($logs)): ?>
                <p>Il n'y a aucun historique répertorié.</p>
            <?php else: ?>
                <div class="tableau_logs">
                    <table>
                        <thead>
                            <tr>
                                <th>N° Log</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Anciennes données</th>
                                <th>Nouvelles données</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody id="log-table-body">
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log->getIdReservationLog() ?? '') ?></td>
                                    <td><?= htmlspecialchars($log->getActionReservationLog() ?? '') ?></td>
                                    <td><?= htmlspecialchars($log->getDescriptionReservationLog() ?? '') ?></td>
                                    <td><?= htmlspecialchars($log->getOldDataReservationLog() ?? '') ?></td>
                                    <td><?= htmlspecialchars($log->getNewDataReservationLog() ?? '') ?></td>
                                    <td><?= htmlspecialchars($log->getTimestampReservationLog() ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?> 
        </main>
    </div>
</body>
</html>