<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Système de réservation des salles et du matériel</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../../Public/css/style.css" />
</head>
<body>
    <header>
        <div class="en_tete">
            <img id="logo" src="../../Public/images/logo-epcic.png" alt="logo_epcic">
            <h2 id="titre_administrateur">Administration du système de réservation <br/> des salles et du matériel</h2>
            <a id="deconnexion" href="index.php?action=deconnexion">Déconnexion</a>
        </div>
    </header>

    <div id="onglet">
        <a class="lien_onglet" href="index.php?action=interfaceAdministrateur">Actions sur les utilisateurs</a>
        <a class="lien_onglet" href="index.php?action=interfaceListeAdministrateur">Actions sur les réservations</a>
        <a class="lien_onglet" href="index.php?action=interfaceSalleMaterielAdministrateur">Actions sur les salles et le matériel</a>
        <a class="lien_onglet" href="index.php?action=interfaceLogAdministrateur">Historique des évènements sur les réservations</a>
    </div>

    <div id="interface_admin_log">
        <main id="log">
            <form method="GET" action="index.php">
                <input type="hidden" name="action" value="interfaceLogAdministrateur">
                <fieldset class="fieldset_admin">   
                    <legend>Filtre de recherche par N°ID, réservations, salles, matériel et dates</legend>
                    <ul>
                        <li>
                            <input type="search" name="criteres[id_user]" placeholder="N°ID...">
                            <input type="checkbox" id="filtre_id_utilisateur" name="actifs[]" value="id_user">
                            <label for="filtre_id_utilisateur">N°ID</label>
                        </li>
                        <li>
                            <input type="search" name="criteres[reservation]" placeholder="Réservations...">
                            <input type="checkbox" id="filtre_reservation" name="actifs[]" value="reservation">
                            <label for="filtre_reservation">Réservations</label>
                        </li>
                        <li>
                            <input type="search" name="criteres[salle]" placeholder="Salles...">
                            <input type="checkbox" id="filtre_salle" name="actifs[]" value="salle">
                            <label for="filtre_salle">Salles</label>
                        </li>
                        <li>
                            <input type="search" name="criteres[materiel]" placeholder="Matériel...">
                            <input type="checkbox" id="filtre_type_mat" name="actifs[]" value="materiel">
                            <label for="filtre_type_mat">Matériel</label>
                        </li>
                        <li>
                            <input type="search" name="criteres[num_materiel]" placeholder="N°matériel...">
                            <input type="checkbox" id="filtre_num_materiel" name="actifs[]" value="num_materiel">
                            <label for="filtre_num_materiel">N°matériel</label>
                        </li>
                        <li>
                            <input type="search" name="criteres[date_debut]" placeholder="Date de début...">
                            <input type="checkbox" id="filtre_date_debut" name="actifs[]" value="date_debut">
                            <label for="filtre_date_debut">Date de début</label>
                        </li>
                        <li>
                            <input type="search" name="criteres[date_fin]" placeholder="Date de fin...">
                            <input type="checkbox" id="filtre_date_fin" name="actifs[]" value="date_fin">
                            <label for="filtre_date_fin">Date de fin</label>
                        </li>
                        <li>
                            <button type="submit">Appliquer les filtres</button>
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
                                <th>ID Utilisateur</th>
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
                                    <td><?= htmlspecialchars($log->getIdUtilisateur() ?? '') ?></td>
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