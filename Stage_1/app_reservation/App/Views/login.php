<!doctype html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Système de réservation de salle et matériel</title>
    <link rel="stylesheet" href="../../Public/css/style.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
      rel="stylesheet"
    />
  </head>

  <body id="background_image">
    <div class="login_card">
      <h2 class="titre_login">Connexion au SRSM</h2>

      <?php if (!empty($error_message)): ?>
        <p style="color:red; font-size: 0.9em; margin-bottom: 15px;">
            <?= htmlspecialchars($error_message) ?>
        </p>
      <?php endif; ?>

      <form action="index.php?action=connexion" method="POST">
        <div class="input_group">
          <input type="text" name="identifiant_utilisateur" id="identifiant" required />
          <label class="label_login" for="identifiant">Identifiant</label>
        </div>
        <div class="input_group">
          <input type="password" name="mot_de_passe_utilisateur" id="password" required />
          <label class="label_login" for="password">Mot de passe</label>
        </div>
        <button class="login_bouton" type="submit" name="login_bouton">
          Se connecter
        </button>
      </form>
    </div>
  </body>
</html>