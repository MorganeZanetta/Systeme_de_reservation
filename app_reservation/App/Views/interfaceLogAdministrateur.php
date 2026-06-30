<?php
/**
 * @var array $utilisateurs
 * @var array $salles
 * @var array $materiels
 * @var array $port
 * @var array $logs
 */

// On récupère l'action actuelle, par défaut vide
$actionActuelle = $_GET['action'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Administration - Système de réservation</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../../Public/css/style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
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
        <a class="lien_onglet <?php echo ($actionActuelle === 'interfaceAdministrateur') ? 'active' : ''; ?>" href="index.php?action=interfaceAdministrateur">Actions sur les utilisateurs</a>
        <a class="lien_onglet <?php echo ($actionActuelle === 'interfaceRolePortAdministrateur') ? 'active' : ''; ?>" href="index.php?action=interfaceRolePortAdministrateur">Actions sur les rôles / ports</a>
        <a class="lien_onglet <?php echo ($actionActuelle === 'interfaceSalleMaterielAdministrateur') ? 'active' : ''; ?>" href="index.php?action=interfaceSalleMaterielAdministrateur">Actions sur les salles / matériel</a>
        <a class="lien_onglet <?php echo ($actionActuelle === 'interfaceReservationAdministrateur') ? 'active' : ''; ?>" href="index.php?action=interfaceReservationAdministrateur">Formulaire de réservation</a>
        <a class="lien_onglet <?php echo ($actionActuelle === 'interfaceListeReservationAdministrateur') ? 'active' : ''; ?>" href="index.php?action=interfaceListeReservationAdministrateur">Liste des réservations</a>
        <a class="lien_onglet <?php echo ($actionActuelle === 'interfaceLogAdministrateur') ? 'active' : ''; ?>" href="index.php?action=interfaceLogAdministrateur">Historique des logs</a>
    </div>

    <div id="interface_admin_log">
        <main id="log">
            <form method="GET" action="index.php">
                <input type="hidden" name="action" value="interfaceLogAdministrateur">
                <fieldset class="fieldset_admin">   
                    <legend>Filtre de recherche par utilisateur, salle, matériel et date</legend>

                            <div id="filtre">

                            <div id="case_cocher">

                            <label for="recherche_new_data">
                            <input type="checkbox" id="recherche_new_data" name="recherche_new_data" value="1"
                            <?= !empty($_GET['recherche_new_data']) ? 'checked' : '' ?>>
                            Appliquer la recherche aux nouvelles données
                            </label>

                            <label for="recherche_old_data">
                            <input type="checkbox" id="recherche_old_data" name="recherche_old_data" value="1"
                            <?= !empty($_GET['recherche_old_data']) ? 'checked' : '' ?>>
                            Appliquer la recherche aux anciennes données
                            </label>

                            </div>

                            <div id="filtre_utilisateur_equipement">

                            <label for="filtre_utilisateur">Filtrer par :</label>
                            <select name="criteres[id_utilisateur]" id="filtre_utilisateur">
                                <option value="">--- utilisateur ---</option>
                                    <?php foreach ($utilisateurs as $utilisateur): ?>
                                        <?php $uid = $utilisateur->getIdUtilisateur(); ?>
                                <option value="<?= htmlspecialchars($uid) ?>" 
                                    <?= (isset($_GET['criteres']['id_utilisateur']) && $_GET['criteres']['id_utilisateur'] == $uid) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($utilisateur->getNomUtilisateur() . ' ' . $utilisateur->getPrenomUtilisateur()) ?>
                                </option>
                            <?php endforeach; ?>
                            </select> 

                            <label for="filtre_salle">Filtrer par :</label>
                            <select name="criteres[salle]" id="filtre_salle">
                                <option value="">--- salle ---</option>
                                <?php foreach ($salles as $salle): ?>
                                    <?php 
                                        // 1. On stocke l'ID dans une variable pour plus de lisibilité
                                        $sid = $salle->getIdSalle(); 
                                ?>
                                <option value="<?= htmlspecialchars($sid) ?>" 
                                    <?php 
                                        // 2. On vérifie si l'ID correspond à celui envoyé dans l'URL
                                        echo (isset($_GET['criteres']['salle']) && $_GET['criteres']['salle'] == $sid) ? 'selected' : ''; 
                                    ?>>
                                    <?= htmlspecialchars($salle->getNomSalle()) . ' | Capacité: ' . htmlspecialchars($salle->getCapaciteSalle()) . ' | Port: ' . htmlspecialchars($salle->getPort() ? $salle->getPort()->getLibPort() : 'Aucun') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                            <label for="filtre_materiel">Filtrer par :</label>
                            <select name="criteres[materiel]" id="filtre_materiel">
                                <option value="">--- matériel ---</option>
                                <?php foreach ($materiels as $materiel): ?>
                                    <?php 
                                        // 1. Stockage de l'ID pour plus de clarté
                                        $mid = $materiel->getIdMateriel(); 
                                    ?>
                                    <option value="<?= htmlspecialchars($mid) ?>" 
                                        <?php 
                                            // 2. Vérification pour maintenir la sélection active
                                            echo (isset($_GET['criteres']['materiel']) && $_GET['criteres']['materiel'] == $mid) ? 'selected' : ''; 
                                        ?>>
                                        <?= htmlspecialchars($materiel->getTypMat()) . ' | Numero: ' . htmlspecialchars($materiel->getNumMat()) . ' | Port: ' . htmlspecialchars($materiel->getPort() ? $materiel->getPort()->getLibPort() : 'Aucun') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            </div>

                            <div id="filtre_date">

                                <label for="filtre_date_debut">Filtrer par période du :</label>
                                <input id="filtre_date_debut" type="date" name="criteres[date_debut]" 
                                        value="<?= htmlspecialchars($_GET['criteres']['date_debut'] ?? '') ?>">

                                <label for="filtre_date_fin"> au :</label>
                                <input id="filtre_date_fin" type="date" name="criteres[date_fin]" 
                                        value="<?= htmlspecialchars($_GET['criteres']['date_fin'] ?? '') ?>">

                                <button class = "bouton_administrateur" type="submit">Validez la recherche</button>
                                <a href="index.php?action=interfaceLogAdministrateur" class = "bouton_administrateur">Réinitialiser</a>

                            </div>

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
                        <tbody id="log_table_body">
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