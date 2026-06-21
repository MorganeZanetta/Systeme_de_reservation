document.addEventListener("DOMContentLoaded", function () {
  // Sélection des éléments du DOM nécessaires à la gestion du formulaire
  const calendrierContainer = document.querySelector("#mon_calendrier_fixe");
  const selectSalles = document.getElementById("choix_salle_reservation");
  const selectMateriels = document.getElementById("choix_materiel_reservation");
  const selectCreneaux = document.getElementById("creneau_reservation");

  const inputDebut = document.getElementById("date_debut_reservation");
  const inputFin = document.getElementById("date_fin_reservation");
  const inputDebutIso = document.getElementById("date_debut_iso");
  const inputFinIso = document.getElementById("date_fin_iso");

  const idRes = document.getElementById("id_reservation_en_cours")?.value || 0;
  let calendarInstance;

  // Conversion du format date local (jj/mm/aaaa) vers format standard ISO (aaaa-mm-jj)
  function convertToIso(dateStr) {
    const [d, m, y] = dateStr.split("/");
    return `${y}-${m}-${d}`;
  }

  function rafraichirCalendrier() {
    // Collecte les choix de l'utilisateur pour envoyer les paramètres à l'API
    const salles = Array.from(selectSalles.selectedOptions).map((o) => o.value);
    const materiels = Array.from(selectMateriels.selectedOptions).map(
      (o) => o.value,
    );

    const params = new URLSearchParams();
    salles.forEach((id) => params.append("salles[]", id));
    materiels.forEach((id) => params.append("materiels[]", id));

    // Récupère les réservations existantes via l'API pour mettre à jour le calendrier
    fetch(
      `index.php?action=obtenirIndisponibilites&ignoreId=${idRes}&${params.toString()}`,
    )
      .then((response) => response.json())
      .then((data) => {
        if (calendarInstance) calendarInstance.destroy(); // Nettoie l'instance précédente

        // 1. Logique de filtrage : Détermine quelles réservations bloquent le créneau actuel
        const datesReservees = data
          .filter((res) => {
            const selected = selectCreneaux.value;
            const creneau = res.creneau_reservation;
            // Une "Journée complète" bloque tout le monde. Les créneaux partiels bloquent leur homologue.
            if (selected === "Journée complète")
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
            from: res.date_debut_reservation.split("-").reverse().join("/"),
            to: res.date_fin_reservation.split("-").reverse().join("/"),
          }));

        // 2. Sécurité : Vérifie si la date déjà choisie par l'utilisateur est devenue invalide
        // suite à un changement de salle/créneau (évite de garder une sélection interdite)
        if (inputDebutIso.value) {
          const debutIso = inputDebutIso.value;
          const finIso = inputFinIso.value || debutIso;
          const estInvalide = datesReservees.some((plage) => {
            const fromIso = convertToIso(plage.from);
            const toIso = convertToIso(plage.to);
            return debutIso <= toIso && finIso >= fromIso;
          });
          if (estInvalide) {
            inputDebut.value =
              inputFin.value =
              inputDebutIso.value =
              inputFinIso.value =
                "";
          }
        }

        // 3. Initialisation du calendrier Flatpickr
        calendarInstance = flatpickr(calendrierContainer, {
          inline: true,
          mode: "range",
          dateFormat: "d/m/Y",
          locale: "fr",
          minDate: "today",
          static: true, // Ancre le calendrier au conteneur pour éviter les soucis de positionnement
          allowInvalidPreselection: true, // Autorise de cliquer sur des dates bloquées pour sélectionner une plage
          disable: [
            (date) => date.getDay() === 0 || date.getDay() === 6, // Bloque les week-ends par défaut
            ...datesReservees,
          ],

          // 4. Gestion de la sélection (le "gendarme" de la validité)
          onChange: function (selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
              const [debut, fin] = selectedDates;
              let estValide = true;

              // Parcourt chaque jour de la plage choisie pour vérifier si elle contient un jour interdit
              let d = new Date(debut);
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
                  estValide = false;
                  break;
                }
                d.setDate(d.getDate() + 1);
              }

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
                // Mise à jour des champs texte (visible et caché)
                inputDebut.value = instance.formatDate(debut, "d/m/Y");
                inputDebutIso.value = instance.formatDate(debut, "Y-m-d");
                inputFin.value = instance.formatDate(fin, "d/m/Y");
                inputFinIso.value = instance.formatDate(fin, "Y-m-d");
              }
            } else if (selectedDates.length === 1) {
              inputDebut.value = instance.formatDate(selectedDates[0], "d/m/Y");
              inputDebutIso.value = instance.formatDate(
                selectedDates[0],
                "Y-m-d",
              );
            }
            verifierEtapes();
          },

          // 5. Personnalisation visuelle : Ajoute des badges M/AM/J sur le calendrier
          onDayCreate: function (dObj, dStr, fp, dayElem) {
            if (
              dayElem.dateObj.getDay() === 0 ||
              dayElem.dateObj.getDay() === 6
            )
              dayElem.classList.add("is-weekend");
            const dateFormatted = fp.formatDate(dayElem.dateObj, "Y-m-d");
            const reservation = data.find(
              (res) =>
                dateFormatted >= res.date_debut_reservation &&
                dateFormatted <= res.date_fin_reservation,
            );
            if (reservation) {
              dayElem.classList.add(
                parseInt(reservation.nb_salles) > 0 &&
                  parseInt(reservation.nb_materiels) > 0
                  ? "res-mixte"
                  : "res-salle",
              );
              const badge = document.createElement("span");
              badge.textContent = reservation.creneau_reservation.includes(
                "Matin",
              )
                ? "M"
                : reservation.creneau_reservation.includes("Après-midi")
                  ? "AM"
                  : "J";
              badge.className = "creneau-badge";
              dayElem.appendChild(badge);
            }
          },
        });
      })
      .catch((err) => console.error("Erreur mise à jour :", err));
  }

  // Écouteur pour rafraîchir le calendrier dès qu'un paramètre change
  [selectSalles, selectMateriels, selectCreneaux].forEach((el) =>
    el.addEventListener("change", rafraichirCalendrier),
  );

  function verifierEtapes() {
    /* Logique de validation d'étapes */
  }
  rafraichirCalendrier();
});
