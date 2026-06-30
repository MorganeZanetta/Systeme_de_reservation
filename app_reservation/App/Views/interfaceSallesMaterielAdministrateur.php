<?php
/**
 * @var array $salles
 * @var array $materiel
 * @var array $ports
 * @var string $csrf_token
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
    <script src="../../Public/js/script.js" defer></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="en_tete">
            <img id="logo" src="../../Public/images/logo-epcic.png" alt="logo_epcic">
            <h2 id="titre_administrateur">Administration du système de réservation</h2>
            <a id="deconnexion" href="index.php?action=deconnexion" >Déconnexion</a>
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

    <div id="interface_salle_materiel_admin">
    <main id="gestion_salle_materiel">
        <section id="gestion_salles">
            
            <form class="formulaire_salle_admin" action="index.php?action=creerSallesAdmin" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <fieldset class="fieldset_salle_materiel_admin">
                    <legend>Formulaire de création des salles</legend>
                    <input type="text" name="nom_salle" placeholder="Nom" required>
                    <input type="number" name="capacite_salle" placeholder="Capacité" required>
                    <input type="text" name="localisation_salle" placeholder="Localisation" required>

                    <select name="id_port" required>
                        <option value="">Choix du port d'attache</option>
                        <?php foreach ($ports as $port): ?>
                            <option value="<?= $port->getIdPort() ?>">
                                <?= htmlspecialchars($port->getLibPort()) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit">Ajouter la salle</button>
                </fieldset>
            </form>

            <?php if (empty($salles)): ?>
                <p>Il n'y a aucune salle répertoriée.</p>
            <?php else: ?>
                <div class="table_container_petit">
                <table id="table_salles">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Nom</th>
                            <th>Capacité</th>
                            <th>Localisation</th>
                            <th>Port d'attache</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salles as $salle): ?>
                        <tr>
                            <td class="Id_salle"><?= htmlspecialchars($salle->getIdSalle()) ?></td>
                            <td class="nom_salle"><?= htmlspecialchars($salle->getNomSalle()) ?></td>
                            <td><?= htmlspecialchars($salle->getCapaciteSalle()) ?></td>
                            <td><?= htmlspecialchars($salle->getLocalisationSalle()) ?></td>
                            <td><?= $salle->getPort() ? htmlspecialchars($salle->getPort()->getLibPort()) : 'Aucun' ?></td>
                            <td>
                                <button onclick="document.getElementById('edit-salle-<?= $salle->getIdSalle() ?>').style.display='table-row'">✏️</button>
                                <form action="index.php?action=supprimerSallesAdmin" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="Id_salle" value="<?= $salle->getIdSalle() ?>">
                                    <button type="submit" onclick="return confirm('Confirmer la suppression ?')">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <tr id="edit-salle-<?= $salle->getIdSalle() ?>" class="ligne-edition">
                            <form action="index.php?action=modifierSallesAdmin" method="POST">
                                <td><?= htmlspecialchars($salle->getIdSalle()) ?></td>
                                <td><input type="text" name="nom_salle" value="<?= htmlspecialchars($salle->getNomSalle()) ?>" required></td>
                                <td><input type="number" name="capacite_salle" value="<?= htmlspecialchars($salle->getCapaciteSalle()) ?>" required></td>
                                <td><input type="text" name="localisation_salle" value="<?= htmlspecialchars($salle->getLocalisationSalle()) ?>" required></td>
                                <td>
                                    <select name="id_port" required>
                                        <?php foreach ($ports as $port): ?>
                                            <option value="<?= $port->getIdPort() ?>" <?= ($salle->getPort() && $salle->getPort()->getIdPort() == $port->getIdPort()) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($port->getLibPort()) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="Id_salle" value="<?= $salle->getIdSalle() ?>">
                                    <button type="submit">💾</button>
                                    <button type="button" onclick="this.closest('tr').style.display='none'">❌</button>
                                </td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </section>

        <hr>

        <section id="gestion_materiel">
            
            <form id="formulaire_materiel_admin" action="index.php?action=creerMaterielAdmin" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <fieldset class="fieldset_salle_materiel_admin">
                    <legend>Formulaire de création du matériel</legend>
                    <input type="text" name="type_materiel" placeholder="Type" required>
                    <input type="text" name="numero_materiel" placeholder="Numéro" required>
                    <input type="file" name="photo_materiel">
                    
                    <select name="id_port" required>
                        <option value="">Choix du port d'attache</option>
                            <?php foreach ($ports as $port): ?>
                            <option value="<?= $port->getIdPort() ?>">
                            <?= htmlspecialchars($port->getLibPort()) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit">Ajouter le matériel</button>
                </fieldset>
            </form>

            <?php if (empty($materiel)): ?>
                <p>Il n'y a aucun matériel répertorié.</p>
            <?php else: ?>
                <div class="table_container_petit">
                <table id="table_materiel">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Type</th>
                            <th>Numéro</th>
                            <th>Photographie</th>
                            <th>Port d'attache</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materiel as $mat): ?>
                        <tr>
                            <td><?= htmlspecialchars($mat->getIdMateriel()) ?></td>
                            <td><?= htmlspecialchars($mat->getTypMat()) ?></td>
                            <td><?= htmlspecialchars($mat->getNumMat()) ?></td>
                            <td>
                                <?php if (!empty($mat->getPhoMat())): ?>
                               <img src="/Public/images/<?= htmlspecialchars($mat->getPhoMat()) ?>" alt="Image matériel">
                                <?php else: ?>
                                Aucune photo
                                <?php endif; ?>
                            </td>
                            <td><?= $mat->getPort() ? htmlspecialchars($mat->getPort()->getLibPort()) : 'Aucun' ?></td>
                            <td>
                                <button onclick="document.getElementById('edit-mat-<?= $mat->getIdMateriel() ?>').style.display='table-row'">✏️</button>
                                <form action="index.php?action=supprimerMaterielAdmin" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="Id_materiel" value="<?= $mat->getIdMateriel() ?>">
                                    <button type="submit" onclick="return confirm('Confirmer la suppression ?')">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <tr id="edit-mat-<?= $mat->getIdMateriel() ?>" class="ligne-edition">
                            <form action="index.php?action=modifierMaterielAdmin" method="POST" enctype="multipart/form-data">
                                <td><?= htmlspecialchars($mat->getIdMateriel()) ?></td>
                                <td>
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="Id_materiel" value="<?= $mat->getIdMateriel() ?>">
                                    <input type="text" name="type_materiel" value="<?= htmlspecialchars($mat->getTypMat()) ?>" required>
                                </td>
                                <td><input type="text" name="numero_materiel" value="<?= htmlspecialchars($mat->getNumMat()) ?>" required></td>
                                <td><input type="file" name="photo_materiel"></td>
                                <td>
                                    <select name="id_port" required>
                                        <?php foreach ($ports as $port): ?>
                                            <option value="<?= $port->getIdPort() ?>" <?= ($mat->getPort() && $mat->getPort()->getIdPort() == $port->getIdPort()) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($port->getLibPort()) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <button type="submit">💾</button>
                                    <button type="button" onclick="this.closest('tr').style.display='none'">❌</button>
                                </td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                 </div>
            <?php endif; ?>
        </section>
    </main>
    </div>
</body>
</html>