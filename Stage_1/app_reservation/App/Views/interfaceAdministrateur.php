<?php
/**
 * @var array $utilisateurs
 * @var array $roles
 * @var array $ports
 * @var string $csrf_token
 */
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Administration - Système de réservation</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="/Public/css/style.css" />
    <script src="/Public/js/script.js" defer></script>
</head>
<body>
    <header>
        <div class="en_tete">
            <img id="logo" src="/Public/images/logo-epcic.png" alt="logo_epcic">
            <h2 id="titre_administrateur">Administration du système de réservation</h2>
            <a id="deconnexion" href="index.php?action=deconnexion" >Déconnexion</a>
        </div>
    </header>

    <div id="onglet">
        <a class="lien_onglet" href="index.php?action=interfaceAdministrateur">Actions sur les utilisateurs</a>
        <a class="lien_onglet" href="index.php?action=interfaceRolePortAdministrateur">Actions sur les rôles / ports</a>
        <a class="lien_onglet" href="index.php?action=interfaceSalleMaterielAdministrateur">Actions sur les salles / matériel</a>
        <a class="lien_onglet" href="index.php?action=interfaceReservationAdministrateur">Formulaire de réservation</a>
        <a class="lien_onglet" href="index.php?action=interfaceListeReservationAdministrateur">Liste des réservations</a>
        <a class="lien_onglet" href="index.php?action=interfaceLogAdministrateur">Historique des logs</a>
    </div>

    <div id="interface_admin">
        <main id="gestion_utilisateur">
            <form id="formulaire_utilisateur_admin" action="index.php?action=creerUtilisateursAdmin" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <fieldset class="fieldset_utilisateur_admin">
                    <legend>Formulaire de création des utilisateurs</legend>
                    <input type="text" name="identifiant_utilisateur" placeholder="Identifiant" required>
                    <input type="text" name="prenom_utilisateur" placeholder="Prénom" required>
                    <input type="text" name="nom_utilisateur" placeholder="Nom" required>
                    <input type="email" name="e_mail_utilisateur" placeholder="Adresse e-mail" required>
                    <input type="password" name="mot_de_passe_utilisateur" placeholder="Mot de passe" required>
                    
                    <select name="Id_role" required>
                        <option value="">Choisissez un rôle</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role->getIdRol() ?>"><?= htmlspecialchars($role->getLib()) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="Id_port" required>
                        <option value="">Choisissez un port</option>
                        <?php foreach ($ports as $port): ?>
                            <option value="<?= $port->getIdPort() ?>"><?= htmlspecialchars($port->getLibPort()) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Ajouter un utilisateur</button>
                </fieldset>
            </form>

            <?php if (empty($utilisateurs)): ?>
                <p>Il n'y a aucun utilisateur répertorié.</p>
            <?php else: ?>
                <div class="table_container_petit">
                <table id="table_utilisateur">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Identifiant</th>
                            <th>Prénom</th>
                            <th>Nom</th>
                            <th>E-mail</th>
                            <th>Port</th>
                            <th>Rôle</th>
                            <th>MDP</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utilisateurs as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row->getIdUtilisateur()) ?></td>
                            <td><?= htmlspecialchars($row->getIdentifiant()) ?></td>
                            <td><?= htmlspecialchars($row->getPrenomUtilisateur()) ?></td>
                            <td><?= htmlspecialchars($row->getNomUtilisateur()) ?></td>
                            <td><?= htmlspecialchars($row->getEmailUtilisateur()) ?></td>
                            <td><?= htmlspecialchars($row->getPort() ? $row->getPort()->getLibPort() : 'N/A') ?></td>
                            <td><?= htmlspecialchars($row->getRole() ? $row->getRole()->getLib() : 'N/A') ?></td>
                            <td><?= htmlspecialchars($row->getMdpUtilisateur() ?? '') ?></td>
                            <td>
                                <button onclick="document.getElementById('edit-user-<?= $row->getIdUtilisateur() ?>').style.display='table-row'">✏️</button>
                                <form action="index.php?action=supprimerUtilisateursAdmin" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="Id_utilisateur" value="<?= $row->getIdUtilisateur() ?>">
                                    <button type="submit" onclick="return confirm('Confirmer la suppression ?')">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <tr id="edit-user-<?= $row->getIdUtilisateur() ?>" class="ligne-edition">
                            <form action="index.php?action=modifierUtilisateursAdmin" method="POST">
                                <td><?= htmlspecialchars($row->getIdUtilisateur()) ?></td>
                                <td><input type="text" name="identifiant_utilisateur" value="<?= htmlspecialchars($row->getIdentifiant()) ?>" required></td>
                                <td><input type="text" name="prenom_utilisateur" value="<?= htmlspecialchars($row->getPrenomUtilisateur()) ?>" required></td>
                                <td><input type="text" name="nom_utilisateur" value="<?= htmlspecialchars($row->getNomUtilisateur()) ?>" required></td>
                                <td><input type="email" name="e_mail_utilisateur" value="<?= htmlspecialchars($row->getEmailUtilisateur()) ?>" required></td>
                                <td>
                                    <select name="Id_port" required>
                                        <?php foreach ($ports as $port): ?>
                                            <option value="<?= $port->getIdPort() ?>" <?= ($row->getPort() && $row->getPort()->getIdPort() == $port->getIdPort()) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($port->getLibPort()) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="Id_role" required>
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?= $role->getIdRol() ?>" <?= ($row->getRole() && $row->getRole()->getIdRol() == $role->getIdRol()) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($role->getLib()) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="password" name="mot_de_passe_utilisateur" placeholder="Nouveau mot de passe" value="<?= htmlspecialchars($row->getMdpUtilisateur()) ?>">
                                <td>
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="Id_utilisateur" value="<?= $row->getIdUtilisateur() ?>">
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
        </main>
    </div>
</body>
</html>