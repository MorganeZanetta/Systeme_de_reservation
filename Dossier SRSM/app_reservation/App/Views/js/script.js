// Attend que le contenu HTML (DOM) soit entièrement chargé pour éviter toute erreur de sélection d'éléments et exécuter le script
document.addEventListener("DOMContentLoaded", function () {
  // --- SECTION 1 : Initialisation des variables et sélecteurs DOM ---
  // Sélection des éléments de saisie et d'affichage dans le DOM
  const calendrierContainer = document.querySelector("#mon_calendrier_fixe"); // Le conteneur du calendrier
  const selectSalles = document.getElementById("choix_salle_reservation"); // Liste déroulante des salles
  const selectMateriels = document.getElementById("choix_materiel_reservation"); // Liste déroulante des matériels
  const selectCreneaux = document.getElementById("creneau_reservation"); // Sélection du créneau (Matin/AM/Journée)
  // Champs cachés/visibles pour synchroniser les données entre le calendrier et le formulaire
  // Champs de formulaire pour stocker les dates (format humain et format ISO)
  const inputDebut = document.getElementById("date_debut_reservation");
  const inputFin = document.getElementById("date_fin_reservation");
  const inputDebutIso = document.getElementById("date_debut_iso");
  const inputFinIso = document.getElementById("date_fin_iso");
  // Récupère l'ID d'une réservation existante (mode édition) ou 0 par défaut
  const idRes = document.getElementById("id_reservation_en_cours")?.value || 0;
  let calendarInstance; // Variable pour stocker l'instance Flatpickr

  // Convertit une date "jj/mm/aaaa" en "aaaa-mm-jj" pour faciliter les comparaisons logiques
  function convertToIso(dateStr) {
    const [d, m, y] = dateStr.split("/");
    return `${y}-${m}-${d}`;
  }
  // --- SECTION 2 : Logique de rafraîchissement ---
  // Fonction principale qui met à jour le calendrier selon les choix de l'utilisateur
  function rafraichirCalendrier() {
    if (!calendrierContainer) return; // Arrête la fonction si le calendrier n'existe pas dans la page
    // Construction des paramètres pour la requête API
    // Récupère les valeurs sélectionnées dans les listes déroulantes
    const salles = Array.from(selectSalles.selectedOptions).map((o) => o.value);
    const materiels = Array.from(selectMateriels.selectedOptions).map(
      (o) => o.value,
    );
    // Prépare les paramètres pour l'appel API via URLSearchParams
    const params = new URLSearchParams();
    salles.forEach((id) => params.append("salles[]", id));
    materiels.forEach((id) => params.append("materiels[]", id));
    // Appel API pour récupérer les dates déjà occupées (indisponibilités)
    fetch(
      `index.php?action=obtenirIndisponibilites&ignoreId=${idRes}&${params.toString()}`,
    )
      .then((response) => response.json()) // Transforme la réponse en JSON
      .then((data) => {
        if (calendarInstance) calendarInstance.destroy(); // Nettoyage : Détruit l'instance précédente pour éviter les bugs
        // Filtrage de sécurité : on exclut la réservation en cours (si édition) pour ne pas qu'elle se bloque elle-même
        // On retire la réservation en cours du calcul des conflits.
        // Cela rend cette réservation "invisible" pour les règles de sécurité ci-dessous.
        const autresReservations = data.filter(
          (res) => parseInt(res.id_reservation) !== idRes,
        );
        // Préparation des dates à "griser" dans Flatpickr (format de plage {from, to})
        const datesReservees = autresReservations
          .filter((res) => {
            const selected = selectCreneaux.value;
            const creneau = res.creneau_reservation;
            // Logique d'intersection : définit si une réservation existante entre en conflit avec le créneau sélectionné
            if (selected === "Journée complète")
              // Bloque tout
              return (
                creneau.includes("Matin") ||
                creneau.includes("Après-midi") ||
                creneau.includes("Journée complète")
              );
            if (selected === "Matin (09H00 / 12H00)")
              return (
                creneau.includes("Matin") ||
                creneau.includes("Journée complète")
              );
            if (selected === "Après-midi (13H00 / 17H00)")
              return (
                creneau.includes("Après-midi") ||
                creneau.includes("Journée complète")
              );
            return false;
          })
          .map((res) => ({
            // Reformate les dates pour Flatpickr (jj/mm/aaaa)
            from: res.date_debut_reservation.split("-").reverse().join("/"),
            to: res.date_fin_reservation.split("-").reverse().join("/"),
          }));
        // Sécurité : Vérifie si la date déjà choisie est devenue invalide après le changement de salle/créneau
        if (inputDebutIso.value) {
          const debutIso = inputDebutIso.value;
          const finIso = inputFinIso.value || debutIso;
          const estInvalide = datesReservees.some((plage) => {
            const fromIso = convertToIso(plage.from);
            const toIso = convertToIso(plage.to);
            return debutIso <= toIso && finIso >= fromIso;
          });
          if (estInvalide) {
            // Réinitialise les champs si conflit
            inputDebut.value =
              inputFin.value =
              inputDebutIso.value =
              inputFinIso.value =
                "";
          }
        }
        // --- SECTION 3 : Configuration Flatpickr ---
        // Initialisation de Flatpickr
        calendarInstance = flatpickr(calendrierContainer, {
          inline: true,
          mode: "range", // Mode sélection de plage de dates
          dateFormat: "d/m/Y",
          locale: "fr",
          minDate: "today", // Empêche le choix de dates passées
          static: true,
          allowInvalidPreselection: true, // Autorise la sélection temporaire
          disable: [
            (date) => date.getDay() === 0 || date.getDay() === 6, // Bloque Week-ends
            ...datesReservees, // Bloque les dates récupérées de l'API
          ],

          // Gère la sélection utilisateur et la vérification des conflits en temps réel
          onChange: function (selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
              const [debut, fin] = selectedDates;
              let estValide = true;
              let d = new Date(debut);
              // Boucle de validation : Parcourt chaque jour pour vérifier l'absence de conflit dans la plage choisie
              while (d <= fin) {
                const dIso = instance.formatDate(d, "Y-m-d");
                if (
                  d.getDay() === 0 ||
                  d.getDay() === 6 ||
                  datesReservees.some(
                    (plage) =>
                      dIso >= convertToIso(plage.from) &&
                      dIso <= convertToIso(plage.to),
                  )
                ) {
                  estValide = false; // Conflit trouvé
                  break;
                }
                d.setDate(d.getDate() + 1);
              }
              // Réinitialisation si conflit détecté
              if (!estValide) {
                alert(
                  "Période invalide : votre sélection inclut des jours indisponibles.",
                );
                instance.clear();
                inputDebut.value =
                  inputFin.value =
                  inputDebutIso.value =
                  inputFinIso.value =
                    "";
              } else {
                // Mise à jour des champs (affichés et cachés)
                inputDebut.value = instance.formatDate(debut, "d/m/Y");
                inputDebutIso.value = instance.formatDate(debut, "Y-m-d");
                inputFin.value = instance.formatDate(fin, "d/m/Y");
                inputFinIso.value = instance.formatDate(fin, "Y-m-d");
              }
            } else if (selectedDates.length === 1) {
              inputDebut.value = instance.formatDate(selectedDates[0], "d/m/Y");
              inputDebutIso.value = instance.formatDate(
                selectedDates[0],
                "d/m/Y",
              );
            }
            verifierEtapes(); // Appel de validation externe
          },
          // --- SECTION 4 : Customisation visuelle (onDayCreate) ---
          onDayCreate: function (dObj, dStr, fp, dayElem) {
            // 1. Gestion des weekends
            const jourSemaine = dayElem.dateObj.getDay();
            if (jourSemaine === 0 || jourSemaine === 6) {
              dayElem.classList.add("is-weekend");
            }

            // 2. Nettoyage DOM : supprime les anciens badges/classes pour éviter les duplicatas
            dayElem.classList.remove("res-mixte", "res-salle", "res-materiel");
            dayElem
              .querySelectorAll(".creneau-badge")
              .forEach((b) => b.remove());

            // 3. Préparation des données
            const dateFormatted = fp.formatDate(dayElem.dateObj, "Y-m-d");

            // Utilisation de Set pour une recherche plus rapide (O(1))
            const idsSallesSelect = new Set(
              Array.from(selectSalles.selectedOptions).map((o) => o.value),
            );
            const idsMaterielsSelect = new Set(
              Array.from(selectMateriels.selectedOptions).map((o) => o.value),
            );

            // 4. Recherche des réservations
            // Filtre les données pour le jour actuel
            const resDuJour = data.filter(
              (res) =>
                dateFormatted >= res.date_debut_reservation &&
                dateFormatted <= res.date_fin_reservation,
            );

            if (resDuJour.length === 0) return; // Rien à afficher

            // 5. Logique des conflits
            // Détection de conflits basés sur les IDs sélectionnés via Set (performance optimale)
            const conflitSalle = resDuJour.some((res) =>
              idsSallesSelect.has(res.id_salle),
            );
            const conflitMateriel = resDuJour.some((res) =>
              idsMaterielsSelect.has(res.id_materiel),
            );
            // Calcul de priorité CSS pour le type d'affichage (conflit simple vs mixte)
            const nbConflits =
              (conflitSalle ? 1 : 0) + (conflitMateriel ? 1 : 0);
            const totalGlobal = nbConflits + resDuJour.length;

            // Application des classes CSS
            if (totalGlobal >= 2) {
              dayElem.classList.add("res-mixte");
            } else if (conflitSalle || resDuJour[0].nb_salles > 0) {
              dayElem.classList.add("res-salle");
            } else if (conflitMateriel || resDuJour[0].nb_materiels > 0) {
              dayElem.classList.add("res-materiel");
            }

            // 6. Logique des badges
            // Génération des badges J/M/AM dans la cellule du jour
            const aMatin = resDuJour.some((r) =>
              r.creneau_reservation.includes("Matin"),
            );
            const aApresMidi = resDuJour.some((r) =>
              r.creneau_reservation.includes("Après-midi"),
            );
            const aJournee = resDuJour.some((r) =>
              r.creneau_reservation.includes("Journée complète"),
            );

            const badgesAAfficher = [];
            if (aJournee || (aMatin && aApresMidi)) {
              badgesAAfficher.push("J");
            } else {
              if (aMatin) badgesAAfficher.push("M");
              if (aApresMidi) badgesAAfficher.push("AM");
            }

            // Ajout des badges au DOM
            badgesAAfficher.forEach((texte) => {
              const badge = document.createElement("span");
              badge.textContent = texte;
              badge.className = "creneau-badge";
              dayElem.appendChild(badge);
            });
          },
        });
      })
      .catch((err) => console.error("Erreur calendrier :", err));
  }

  // Fonction de validation des étapes
  function verifierEtapes() {
    // Logique spécifique de validation (à voir)
  }
  // Ajoute des écouteurs d'événements pour mettre à jour le calendrier si les filtres changent
  [selectSalles, selectMateriels, selectCreneaux].forEach((el) =>
    el.addEventListener("change", rafraichirCalendrier),
  );

  rafraichirCalendrier(); // Appel initial
});

