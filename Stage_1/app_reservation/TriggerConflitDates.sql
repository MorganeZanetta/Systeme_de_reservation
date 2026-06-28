DELIMITER //

CREATE TRIGGER verifier_conflit_reservation_salle
BEFORE INSERT ON reservation_salle
FOR EACH ROW
BEGIN
    DECLARE conflit_count INT;

    -- On vérifie s'il existe déjà une réservation sur cette salle, à cette date et ce créneau
    SELECT COUNT(*) INTO conflit_count
    FROM reservation r
    JOIN reservation_salle rs ON r.Id_reservation = rs.Id_reservation
    WHERE rs.Id_salle = NEW.Id_salle
    AND r.date_debut_reservation = (SELECT date_debut_reservation FROM reservation WHERE Id_reservation = NEW.Id_reservation)
    AND r.date_fin_reservation = (SELECT date_fin_reservation FROM reservation WHERE Id_reservation = NEW.Id_reservation)
    AND r.creneau_reservation = (SELECT creneau_reservation FROM reservation WHERE Id_reservation = NEW.Id_reservation);

    IF conflit_count > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erreur : Salle déjà réservée sur ce créneau.';
    END IF;
END;
//
DELIMITER ;

DELIMITER //

CREATE TRIGGER verifier_conflit_reservation_materiel
BEFORE INSERT ON reservation_materiel
FOR EACH ROW
BEGIN
    DECLARE conflit_count INT;

    -- On vérifie s'il existe déjà une réservation sur cette salle, à cette date et ce créneau
    SELECT COUNT(*) INTO conflit_count
    FROM reservation r
    JOIN reservation_salle rs ON r.Id_reservation = rs.Id_reservation
    WHERE rs.Id_materiel = NEW.Id_materiel
    AND r.date_debut_reservation = (SELECT date_debut_reservation FROM reservation WHERE Id_reservation = NEW.Id_reservation)
    AND r.date_fin_reservation = (SELECT date_fin_reservation FROM reservation WHERE Id_reservation = NEW.Id_reservation)
    AND r.creneau_reservation = (SELECT creneau_reservation FROM reservation WHERE Id_reservation = NEW.Id_reservation);

    IF conflit_count > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erreur : Equipement déjà réservée sur ce créneau.';
    END IF;
END;
//
DELIMITER ;