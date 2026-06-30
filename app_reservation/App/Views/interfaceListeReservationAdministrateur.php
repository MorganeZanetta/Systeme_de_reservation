<?php
/**
 * @var array $salle_liste
 * @var array $materiel_liste
 * @var array $reservations
 * @var string $csrf_token
 */

// --- PROTECTION : On initialise les variables si elles sont vides ou nulles ---
$reservations = $reservations ?? [];
$idUtilisateur = $_SESSION['Id_utilisateur'] ?? null;
$roleUtilisateur = $_SESSION['Id_role'] ?? null;

$afficherColonnesActions = false;
foreach ($reservations as $row) {
    $u = $row->getUtilisateur();
    if ($roleUtilisateur == 1 || ($u && $u->getIdUtilisateur() == $idUtilisateur)) {
        $afficherColonnesActions = true;
        break;
    }
}

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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

    <div id="interface_reservation_admin">
        <main id="gestion_reservation_admin">
            <div class="table_container">
                <table>
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Utilisateur</th>
                            <th>Port</th>
                            <th>Motif</th>
                            <th>Salle(s)</th>
                            <th>Matériel</th>
                            <th>Période</th>
                            <th>Créneau</th>
                            <?php if ($afficherColonnesActions): ?><th>Actions</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservations)): ?>
                            <tr><td colspan="9" style="text-align:center;">Aucune réservation trouvée.</td></tr>
                        <?php else: ?>
                            <?php foreach ($reservations as $row): 
                                $user = $row->getUtilisateur();
                                $estAutorise = ($roleUtilisateur == 1 || ($user && $user->getIdUtilisateur() == $idUtilisateur));
                                $nomComplet = ($user ? $user->getPrenomUtilisateur() . ' ' . $user->getNomUtilisateur() : '');
                                $periode = htmlspecialchars($row->getDebut()) . ' au ' . htmlspecialchars($row->getFin());
                            ?>
                            <tr>
                                <td><?= $row->getIdRes() ?></td>
                                <td><?= htmlspecialchars($nomComplet) ?></td>
                                <td><?= htmlspecialchars(($user && $user->getPort()) ? $user->getPort()->getLibPort() : '') ?></td>
                                <td><?= htmlspecialchars($row->getMotif()) ?></td>
                                <td><?= htmlspecialchars(implode(" - ", array_map(fn($s) => $s->getNomSalle(), $row->getSalles()))) ?></td>
                                <td><?= htmlspecialchars(implode(" - ", array_map(fn($m) => $m->getTypMat(), $row->getMateriels()))) ?></td>
                                <td><?= $periode ?></td>
                                <td><?= htmlspecialchars($row->getCre()) ?></td>
                                
                                <?php if ($afficherColonnesActions && $estAutorise): ?>
                                <td>
                                    <a id="lien_action_admin" href="index.php?action=formulaireModificationAdmin&id=<?= $row->getIdRes() ?>" class="btn-edit">✏️</a>
                                    <form action="index.php?action=supprimerReservationAdmin" method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                        <input type="hidden" name="action" value="supprimer">
                                        <input type="hidden" name="id" value="<?= $row->getIdRes() ?>">
                                        <button type="submit" name="bouton_suppression_admin" onclick="return confirm('Confirmer la suppression ?')">🗑️</button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
    <script src="/App/Views/js/script.js"></script>
</body>
</html>