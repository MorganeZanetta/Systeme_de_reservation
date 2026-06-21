<?php
/**
 * @var array $utilisateurs
 * @var array $roles
 * @var array $ports
 * @var string $csrf_token (Injecté par le BaseController)
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
        <a class="lien_onglet" href="index.php?action=interfaceListeAdministrateur">Actions sur les réservations</a>
        <a class="lien_onglet" href="index.php?action=interfaceSalleMaterielAdministrateur">Actions sur les salles et le matériel</a>
        <a class="lien_onglet" href="index.php?action=interfaceLogAdministrateur">Historique des évènements</a>
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
                <div class="table_container">
                <table id="table_utilisateur">
                    <thead>
                        <tr>
                            <th>ID utilisateur</th>
                            <th>Identifiant</th>
                            <th>Prénom</th>
                            <th>Nom</th>
                            <th>E-mail</th>
                            <th>Port</th>
                            <th>Rôle</th>
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
                            <td>
                                <button onclick="document.getElementById('edit-user-<?= $row->getIdUtilisateur() ?>').style.display='table-row'">✏️</button>
                                <form action="index.php?action=supprimerUtilisateursAdmin" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="Id_utilisateur" value="<?= $row->getIdUtilisateur() ?>">
                                    <button type="submit" onclick="return confirm('Confirmer la suppression ?')">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <tr id="edit-user-<?= $row->getIdUtilisateur() ?>" style="display:none; background:#f4f4f4;">
                            <td colspan="8">
                                <form action="index.php?action=modifierUtilisateursAdmin" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="Id_utilisateur" value="<?= $row->getIdUtilisateur() ?>">
                                    <input type="text" name="identifiant_utilisateur" value="<?= htmlspecialchars($row->getIdentifiant()) ?>" required>
                                    <input type="text" name="prenom_utilisateur" value="<?= htmlspecialchars($row->getPrenomUtilisateur()) ?>" required>
                                    <input type="text" name="nom_utilisateur" value="<?= htmlspecialchars($row->getNomUtilisateur()) ?>" required>
                                    <input type="email" name="e_mail_utilisateur" value="<?= htmlspecialchars($row->getEmailUtilisateur()) ?>" required>
                                    <input type="password" name="mot_de_passe_utilisateur" placeholder="Nouveau mot de passe">
                                    
                                    <select name="Id_role" required>
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?= $role->getIdRol() ?>" <?= ($row->getRole() && $row->getRole()->getIdRol() == $role->getIdRol()) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($role->getLib()) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <select name="Id_port" required>
                                        <?php foreach ($ports as $port): ?>
                                            <option value="<?= $port->getIdPort() ?>" <?= ($row->getPort() && $row->getPort()->getIdPort() == $port->getIdPort()) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($port->getLibPort()) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit">Sauvegarder</button>
                                </form>
                            </td>
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