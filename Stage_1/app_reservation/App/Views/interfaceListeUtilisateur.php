<?php
/**
 * @var array $salle_liste
 * @var array $materiel_liste
 * @var array $reservations
 * @var string $csrf_token
 */

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

$affichageActuel = $_POST['type_affichage'] ?? 'personnel';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Système de réservation des salles et du matériel</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../../Public/css/style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="en_tete">
            <img id="logo" src="../../Public/images/logo-epcic.png" alt="logo_epcic">
            <h2 id="titre_utilisateur">Système de réservation des salles et du matériel</h2>
            <a id="deconnexion" href="index.php?action=deconnexion">Déconnexion</a>
        </div>
    </header>

    <div id="onglet">
        <a class="lien_onglet" href="http://127.0.0.1:3000/Public/index.php?action=interfaceUtilisateur">Formulaire de réservation</a>
        <a class="lien_onglet" href="http://127.0.0.1:3000/Public/index.php?action=interfaceListeUtilisateur">Liste des réservations</a>
    </div>

    <div class="interface_utilisateur">
        <section id="liste_reservation">
            <div id="filtre_bouton">
                <form action="index.php?action=interfaceListeUtilisateur" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">    
                    <button class="bouton_utilisateur <?= ($affichageActuel === 'personnel') ? 'bouton-actif' : '' ?>" type="submit" name="type_affichage" value="personnel">Personnelles</button>
                    <button class="bouton_utilisateur <?= ($affichageActuel === 'global') ? 'bouton-actif' : '' ?>" type="submit" name="type_affichage" value="global">Globales</button>
                </form>
            </div> 
            
            <div id="reservation">
                <h3>Liste des réservations</h3>
                <?php if (empty($reservations)): ?>
                    <p>Il n'y a aucune réservation répertoriée.</p>
                <?php else: ?>
                    <div class="tableau_reservations">
                      <main id="gestion_reservation">
                        <div class="table_container">
                        <table>
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Utilisateur</th>
                                    <th>Port</th>
                                    <th>Role</th>
                                    <th>Motif</th>
                                    <th>Salle(s)</th>
                                    <th>Matériel</th>
                                    <th>Période</th>
                                    <th>Créneau</th>
                                    <?php if ($afficherColonnesActions): ?>
                                        <th>Modifier</th>
                                        <th>Supprimer</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reservations as $row): 
                                    $resId = $row->getIdRes();
                                    $user = $row->getUtilisateur();
                                    $estAutorise = ($roleUtilisateur == 1 || ($user && $user->getIdUtilisateur() == $idUtilisateur));
                                    $nomComplet = ($user ? $user->getPrenomUtilisateur() . ' ' . $user->getNomUtilisateur() : '');
                                    $periode = htmlspecialchars($row->getDebut()) . ' au ' . htmlspecialchars($row->getFin());
                                ?>
                                    <tr data-id="<?= $resId ?>">
                                        <td><?= $resId ?></td>
                                        <td><?= htmlspecialchars($nomComplet) ?></td>
                                        <td><?= htmlspecialchars(($user && $user->getPort()) ? $user->getPort()->getLibPort() : '') ?></td>
                                        <td><?= htmlspecialchars(($user && $user->getRole()) ? $user->getRole()->getLib() : 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row->getMotif()) ?></td>
                                        <td><?= htmlspecialchars(implode(" - ", array_map(fn($s) => $s->getNomSalle(), $row->getSalles()))) ?></td>
                                        <td><?= htmlspecialchars(implode(" - ", array_map(fn($m) => $m->getTypMat(), $row->getMateriels()))) ?></td>
                                        <td><?= $periode ?></td>
                                        <td><?= htmlspecialchars($row->getCre()) ?></td>
                                        
                                        <?php if ($afficherColonnesActions): ?>                                 
                                            <td>
                                                <?php if ($estAutorise): ?>
                                                    <a id="lien_action" href="index.php?action=formulaireModifier&id=<?= $resId ?>">✏️</a>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($estAutorise): ?>
                                                    <form action="index.php?action=supprimerReservation" method="POST">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">    
                                                        <input type="hidden" name="action" value="supprimer">
                                                        <input type="hidden" name="id" value="<?= $resId ?>">
                                                        <button id="bouton_action" type="submit" onclick="return confirm('Confirmer la suppression ?')">🗑️</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>

                                    <?php if ($estAutorise): ?>
                                    <tr id="edit-<?= $resId ?>" style="display:none; background:#f4f4f4;">
                                        <td colspan="<?= $afficherColonnesActions ? 11 : 9 ?>">
                                            <form class="formulaire_hidden" action="index.php?action=traiterActionReservation" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                <input type="hidden" name="action" value="modifier">
                                                <input type="hidden" name="id" value="<?= $resId ?>">
                                                
                                                <input type="text" name="motif_reservation" id="motif_hidden-<?= $resId ?>" value="<?= htmlspecialchars($row->getMotif()) ?>" required>
                                                
                                                <div class="formulaire_input">
                                                    <label for="choix_salle-<?= $resId ?>">Sélectionnez les salles :</label>
                                                    <select name="salles[]" id="choix_salle-<?= $resId ?>" multiple required>
                                                        <?php foreach ($salle_liste as $item): ?>
                                                            <option value="<?= htmlspecialchars($item->getIdSalle()) ?>"><?= htmlspecialchars($item->getNomSalle()) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <input type="date" name="date_debut_reservation" id="date_debut_hidden-<?= $resId ?>" value="<?= htmlspecialchars($row->getDebut()) ?>" required>
                                                <input type="date" name="date_fin_reservation" id="date_fin_hidden-<?= $resId ?>" value="<?= htmlspecialchars($row->getFin()) ?>" required>
                                                
                                                <button type="submit">Enregistrer</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                 </main>
                <?php endif; ?> 
                </div>
            </div>
        </section>
    </div>
</body>
</html>