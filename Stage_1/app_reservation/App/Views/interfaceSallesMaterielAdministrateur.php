<?php
/**
 * @var array $salles
 * @var array $materiel
 */
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Système de réservation des salles et du matériel</title>
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

    <div id="interface_salle_materiel_admin">
    <main id="gestion_salle_materiel">
        <section id="gestion_salles">
            
            <form id="formulaire_salle_admin" action="index.php?action=creerSallesAdmin" method="POST">
                <fieldset class="fieldset_salle_materiel_admin">
                    <legend>Formulaire de création des salles</legend>
                    <input type="text" name="nom_salle" placeholder="Nom" required>
                    <input type="number" name="capacite_salle" placeholder="Capacité" required>
                    <input type="text" name="localisation_salle" placeholder="Localisation" required>
                    <button type="submit">Ajouter la salle</button>
                </fieldset>
            </form>

            <?php if (empty($salles)): ?>
                <p>Il n'y a aucune salle répertoriée.</p>
            <?php else: ?>
                <div class="table_container">
                <table id="table_salles">
                    <thead>
                        <tr>
                            <th>ID salle</th>
                            <th>Nom</th>
                            <th>Capacité</th>
                            <th>Localisation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salles as $row): ?>
                        <tr>
                            <td class="Id_salle"><?= htmlspecialchars($row->getIdSalle()) ?></td>
                            <td class="nom_salle"><?= htmlspecialchars($row->getNomSalle()) ?></td>
                            <td><?= htmlspecialchars($row->getCapaciteSalle()) ?></td>
                            <td><?= htmlspecialchars($row->getLocalisationSalle()) ?></td>
                            <td>
                                <button onclick="document.getElementById('edit-salle-<?= $row->getIdSalle() ?>').style.display='table-row'">✏️</button>
                                <form action="index.php?action=supprimerSallesAdmin" method="POST" style="display:inline;">
                                    <input type="hidden" name="Id_salle" value="<?= $row->getIdSalle() ?>">
                                    <button type="submit" onclick="return confirm('Confirmer la suppression ?')">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <tr id="edit-salle-<?= $row->getIdSalle() ?>" style="display:none; background:#f4f4f4;">
                            <td colspan="4">
                                <form action="index.php?action=modifierSallesAdmin" method="POST">
                                    <input type="hidden" name="Id_salle" value="<?= $row->getIdSalle() ?>">
                                    <input type="text" name="nom_salle" value="<?= htmlspecialchars($row->getNomSalle()) ?>" required>
                                    <input type="number" name="capacite_salle" value="<?= htmlspecialchars($row->getCapaciteSalle()) ?>" required>
                                    <input type="text" name="localisation_salle" value="<?= htmlspecialchars($row->getLocalisationSalle()) ?>" required>
                                    <button type="submit">Sauvegarder</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </section>

        <hr>

        <section id="gestion_materiel">
            
            <form id="formulaire_materiel_admin"  action="index.php?action=creerMaterielAdmin" method="POST" enctype="multipart/form-data">
                <fieldset class="fieldset_salle_materiel_admin">
                    <legend>Formulaire de création du matériel</legend>
                    <input type="text" name="type_materiel" placeholder="Type" required>
                    <input type="text" name="numero_materiel" placeholder="Numéro" required>
                    <input type="file" name="photo_materiel"> 
                    <button type="submit">Ajouter le matériel</button>
                </fieldset>
            </form>

            <?php if (empty($materiel)): ?>
                <p>Il n'y a aucun matériel répertorié.</p>
            <?php else: ?>
                
                <div class="table_container">
                <table id="table_materiel">
                    <thead>
                        <tr>
                            <th>ID materiel</th>
                            <th>Type</th>
                            <th>Numéro</th>
                            <th>Photographie</th>
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
                               <img src="/Public/images/<?= htmlspecialchars($mat->getPhoMat()) ?>"
                                alt="Image matériel" 
                                style="width: 150px; height: 150px; border-radius: 4px;">
                                <?php else: ?>
                                Aucune photo
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick="document.getElementById('edit-mat-<?= $mat->getIdMateriel() ?>').style.display='table-row'">✏️</button>
                                <form action="index.php?action=supprimerMaterielAdmin" method="POST" style="display:inline;">
                                    <input type="hidden" name="Id_materiel" value="<?= $mat->getIdMateriel() ?>">
                                    <button type="submit" onclick="return confirm('Confirmer la suppression ?')">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <tr id="edit-mat-<?= $mat->getIdMateriel() ?>" style="display:none; background:#f4f4f4;">
                            <td colspan="3">
                                <form action="index.php?action=modifierMaterielAdmin" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="Id_materiel" value="<?= $mat->getIdMateriel() ?>">
                                    <input type="text" name="type_materiel" value="<?= htmlspecialchars($mat->getTypMat()) ?>" required>
                                    <input type="text" name="numero_materiel" value="<?= htmlspecialchars($mat->getNumMat()) ?>" required>
                                    <span>Fichier actuel : <?= htmlspecialchars($mat->getPhoMat() ?? 'Aucun') ?></span>
                                    <input type="file" name="photo_materiel">
                                    <button type="submit">Sauvegarder</button>
                                </form>
                            </td>
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