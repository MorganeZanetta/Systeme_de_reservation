<?php
/**
 * @var array $roles
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

    <div id="interface_role_port_admin">
    <main id="gestion_role_port">
        <section id="gestion_roles">
            <form id="formulaire_role_admin" action="index.php?action=creerRoleAdmin" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">    
                <fieldset class="fieldset_role_port_admin">
                    <legend>Formulaire de création des rôles</legend>
                    <input type="text" name="libelle_role" placeholder="Libellé du rôle" required>
                    <button type="submit">Ajouter le rôle</button>
                </fieldset>
            </form>

            <?php if (empty($roles)): ?>
                <p>Il n'y a aucun rôle répertorié.</p>
            <?php else: ?>
                <div class="table_container_petit">
                <table id="table_role">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Libellé</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $role): ?>
                        <tr>
                            <td class="Id_role"><?= htmlspecialchars($role->getIdRol()) ?></td>
                            <td class="libelle_role"><?= htmlspecialchars($role->getLib()) ?></td>
                            <td>
                                <button onclick="document.getElementById('edit-role-<?= $role->getIdRol() ?>').style.display='table-row'">✏️</button>
                                <form action="index.php?action=supprimerRolesAdmin" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">    
                                    <input type="hidden" name="Id_role" value="<?= $role->getIdRol() ?>">
                                    <button type="submit" onclick="return confirm('Confirmer la suppression ?')">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <tr id="edit-role-<?= $role->getIdRol() ?>" class="ligne-edition">
                            <form action="index.php?action=modifierRolesAdmin" method="POST">
                                <td><?= htmlspecialchars($role->getIdRol()) ?></td>
                                <td>
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="Id_role" value="<?= $role->getIdRol() ?>">
                                    <input type="text" name="libelle_role" value="<?= htmlspecialchars($role->getLib()) ?>" required>
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

        <hr>

        <section id="gestion_ports">
            <form id="formulaire_port_admin" action="index.php?action=creerPortAdmin" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <fieldset class="fieldset_role_port_admin">
                    <legend>Formulaire de création des ports</legend>
                    <input type="text" name="identite_port" placeholder="Libellé du port" required>
                    <button type="submit">Ajouter le port</button>
                </fieldset>
            </form>

            <?php if (empty($ports)): ?>
                <p>Il n'y a aucun port répertorié.</p>
            <?php else: ?>
                <div class="table_container_petit">
                <table id="table_port">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Libellé</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ports as $port): ?>
                        <tr>
                            <td><?= htmlspecialchars($port->getIdPort()) ?></td>
                            <td><?= htmlspecialchars($port->getLibPort()) ?></td>
                            <td>
                                <button onclick="document.getElementById('edit-port-<?= $port->getIdPort() ?>').style.display='table-row'">✏️</button>
                                <form action="index.php?action=supprimerPortAdmin" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <input type="hidden" name="Id_port" value="<?= $port->getIdPort() ?>">
                                    <button type="submit" onclick="return confirm('Confirmer la suppression ?')">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <tr id="edit-port-<?= $port->getIdPort() ?>" class="ligne-edition">
                            <form action="index.php?action=modifierPortAdmin" method="POST">
                                <td><?= htmlspecialchars($port->getIdPort()) ?></td>
                                <td>
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">    
                                    <input type="hidden" name="Id_port" value="<?= $port->getIdPort() ?>">
                                    <input type="text" name="identite_port" value="<?= htmlspecialchars($port->getLibPort()) ?>" required>
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