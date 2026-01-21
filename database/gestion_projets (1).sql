-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 21 jan. 2026 à 02:28
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion_projets`
--

DELIMITER $$
--
-- Procédures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `ajouter_patient` (IN `p_CIN` VARCHAR(20), IN `p_nom` VARCHAR(50), IN `p_prenom` VARCHAR(50), IN `p_date_naissance` DATE, IN `p_sexe` CHAR(1), IN `p_adresse` VARCHAR(255), IN `p_telephone` VARCHAR(20), IN `p_email` VARCHAR(100), IN `p_date_inscription` DATE, IN `p_id_medecin` INT)   BEGIN
    
    IF EXISTS (SELECT 1 FROM patients WHERE CIN = p_CIN) THEN
       
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Le patient existe déjà';
    ELSE
    
        INSERT INTO patients
        (CIN, nom, prenom, date_naissance, sexe, adresse, telephone, email, date_inscription, id_medecin)
        VALUES
        (p_CIN, p_nom, p_prenom, p_date_naissance, p_sexe, p_adresse, p_telephone, p_email, p_date_inscription, p_id_medecin);
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `ajouter_patient_secure` (IN `p_cin` VARCHAR(20), IN `p_nom` VARCHAR(100), IN `p_prenom` VARCHAR(100), IN `p_date_naissance` DATE, IN `p_sexe` CHAR(1), IN `p_adresse` TEXT, IN `p_telephone` VARCHAR(20), IN `p_email` VARCHAR(100), IN `p_groupe_sanguin` VARCHAR(5), IN `p_statut` VARCHAR(50), IN `p_allergies` TEXT, IN `p_date_inscription` DATE, IN `p_user_id` INT, OUT `msg` VARCHAR(100))   BEGIN
    -- Variables
    DECLARE done INT DEFAULT 0;
    DECLARE v_cin VARCHAR(20);

    -- Curseur : cherche le CIN
    DECLARE cur_cin CURSOR FOR
        SELECT cin FROM patients WHERE cin = p_cin;

    -- Handler obligatoire pour le curseur
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    -- Ouvrir le curseur
    OPEN cur_cin;

    -- Lire une ligne
    FETCH cur_cin INTO v_cin;

    -- Fermer le curseur
    CLOSE cur_cin;

    -- Si un CIN a été trouvé
    IF done = 0 THEN
        SET msg = 'CIN déjà exist, veuillez entrer un autre';
    ELSE
        INSERT INTO patients (
            cin, nom, prenom, date_naissance, sexe,
            adresse, telephone, email, groupe_sanguin,
            statut, allergies, date_inscription, created_by
        ) VALUES (
            p_cin, p_nom, p_prenom, p_date_naissance, p_sexe,
            p_adresse, p_telephone, p_email, p_groupe_sanguin,
            p_statut, p_allergies, p_date_inscription, p_user_id
        );

        SET msg = 'OK';
    END IF;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `ajouter_suivi` (IN `p_id_patient` INT, IN `p_id_medecin` INT, IN `p_date_suivi` DATE, IN `p_commentaire` TEXT, IN `p_status` VARCHAR(50))   BEGIN
    -- Vérification que la date n'est pas dans le passé
    IF p_date_suivi < CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La date du suivi ne peut pas être antérieure à aujourd''hui';
    ELSE
        -- Insertion du suivi
        INSERT INTO suivis (id_patient, id_medecin, date_suivi, commentaire, status)
        VALUES (p_id_patient, p_id_medecin, p_date_suivi, p_commentaire, p_status);
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `login_user` (IN `p_email` VARCHAR(100), IN `p_password` VARCHAR(255))   BEGIN
    DECLARE v_id INT;
    DECLARE v_password VARCHAR(255);
    DECLARE v_role VARCHAR(50);

    
    SELECT id_user, mot_de_passe, role
    INTO v_id, v_password, v_role
    FROM utilisateurs
    WHERE email = p_email;

   
    IF v_id IS NULL OR v_password != p_password THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Email ou mot de passe incorrect';
    END IF;

 
    SELECT v_id AS id, v_role AS role;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AddChambre` (IN `p_numero` VARCHAR(50), IN `p_service` VARCHAR(100), IN `p_bloc` VARCHAR(50), IN `p_etage` INT, IN `p_capacite` INT, IN `p_type_lit` VARCHAR(100), IN `p_oxigene` VARCHAR(10))   BEGIN
    INSERT INTO chambres (numero_chambre, service, bloc, etage, capacite, type_lit, oxigene)
    VALUES (p_numero, p_service, p_bloc, p_etage, p_capacite, p_type_lit, p_oxigene);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_add_admission_safe` (IN `p_id_patient` INT, IN `p_date_admission` DATE, IN `p_service` VARCHAR(100), IN `p_motif` TEXT, IN `p_type_admission` VARCHAR(50), IN `p_id_chambre` INT, IN `p_id_medecin` INT)   BEGIN
    DECLARE nb_en_cours INT DEFAULT 0;
    DECLARE patient_exist INT DEFAULT 0;
    DECLARE medecin_exist INT DEFAULT 0;

    -- 1. Vérifier si le patient existe
    SELECT COUNT(*) INTO patient_exist FROM patients WHERE id_patient = p_id_patient;
    IF patient_exist = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Patient introuvable';
    END IF;

    -- 2. Vérifier si une admission est déjà en cours
    SELECT COUNT(*) INTO nb_en_cours FROM admissions 
    WHERE id_patient = p_id_patient AND statut = 'En cours';
    IF nb_en_cours > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Admission déjà en cours';
    END IF;

    -- 3. Vérifier le service et le motif
    IF p_service IS NULL OR p_service = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Le service est obligatoire';
    END IF;

    IF p_motif IS NULL OR p_motif = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Le motif est obligatoire';
    END IF;

    -- 4. Vérifier le médecin (role 'medecin')
    IF p_id_medecin IS NOT NULL AND p_id_medecin > 0 THEN
        SELECT COUNT(*) INTO medecin_exist FROM utilisateurs 
        WHERE id_user = p_id_medecin AND role='medecin';
        IF medecin_exist = 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Médecin invalide';
        END IF;
    END IF;

    -- 5. INSERTION 
    -- Note : On laisse le TRIGGER s'occuper des logs, mais la table DOIT exister
    INSERT INTO admissions(
        id_patient, 
        date_admission, 
        service, 
        motif, 
        type_admission,
        id_chambre, 
        id_medecin, 
        statut
    )
    VALUES(
        p_id_patient, 
        p_date_admission, 
        p_service, 
        p_motif, 
        IFNULL(p_type_admission,'Normal'),
        p_id_chambre, 
        p_id_medecin, 
        'En cours'
    );

    -- IMPORTANT: On retire le SELECT LAST_INSERT_ID() car il cause des erreurs 
    -- de synchronisation avec le closeCursor() en PHP.
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AjouterAdmission` (IN `p_id_patient` INT, IN `p_date_admission` DATETIME, IN `p_service` VARCHAR(100), IN `p_motif` TEXT, IN `p_type_admission` VARCHAR(50), IN `p_id_chambre` INT, IN `p_id_medecin` INT)   BEGIN
    DECLARE nb_en_cours INT DEFAULT 0;
    DECLARE patient_exist INT DEFAULT 0;
    DECLARE medecin_exist INT DEFAULT 0;
    DECLARE v_etat_chambre VARCHAR(20);

    -- Patient existe ?
    SELECT COUNT(*) INTO patient_exist 
    FROM patients WHERE id_patient = p_id_patient;

    IF patient_exist = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Patient introuvable';
    END IF;

    -- Admission déjà existante
    SELECT COUNT(*) INTO nb_en_cours
    FROM admissions
    WHERE id_patient = p_id_patient AND statut = 'En cours';

    IF nb_en_cours > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Admission déjà existante, veuillez entrer une autre';
    END IF;

    -- Champs obligatoires
    IF p_service IS NULL OR p_service = '' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Le service est obligatoire';
    END IF;

    IF p_motif IS NULL OR p_motif = '' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Le motif est obligatoire';
    END IF;

    -- Vérification chambre
    IF p_id_chambre IS NOT NULL THEN
        SELECT etat INTO v_etat_chambre 
        FROM chambres WHERE id_chambre = p_id_chambre;

        IF v_etat_chambre = 'complet' THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Chambre complète';
        ELSEIF v_etat_chambre = 'maintenance' THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Chambre en maintenance';
        END IF;
    END IF;

    -- Vérification médecin
    IF p_id_medecin IS NOT NULL THEN
        SELECT COUNT(*) INTO medecin_exist
        FROM utilisateurs
        WHERE id_user = p_id_medecin AND role='medecin';

        IF medecin_exist = 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Médecin invalide';
        END IF;
    END IF;

    -- INSERT
    INSERT INTO admissions(
        id_patient,
        date_admission,
        service,
        motif,
        type_admission,
        id_chambre,
        id_medecin,
        statut
    ) VALUES (
        p_id_patient,
        p_date_admission,
        p_service,
        p_motif,
        IFNULL(p_type_admission,'Normal'),
        p_id_chambre,
        p_id_medecin,
        'En cours'
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AjouterUtilisateur` (IN `p_nom` VARCHAR(50), IN `p_prenom` VARCHAR(50), IN `p_email` VARCHAR(100), IN `p_password` VARCHAR(255), IN `p_role` VARCHAR(20), IN `p_specialite` VARCHAR(100), IN `p_tel` VARCHAR(20), IN `p_cin` VARCHAR(20))   BEGIN
  
    IF EXISTS (SELECT 1 FROM utilisateurs WHERE cin = p_cin) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ce CIN existe déjà, veuillez en entrer un autre.';
    
    
    ELSEIF EXISTS (SELECT 1 FROM utilisateurs WHERE email = p_email) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cet email est déjà utilisé, veuillez en entrer un autre.';

 
    ELSE
       
        SET @num_ordre = (SELECT COUNT(*) + 1 FROM utilisateurs);
        SET @matricule = CONCAT(YEAR(NOW()), LPAD(@num_ordre, 3, '0'));

        INSERT INTO utilisateurs (
            cin, 
            matricule, 
            nom, 
            prenom, 
            role, 
            specialite, 
            email, 
            telephone, 
            mot_de_passe,
            created_at
        ) 
        VALUES (
            UPPER(p_cin), 
            @matricule, 
            UPPER(p_nom), 
            p_prenom, 
            p_role, 
            p_specialite, 
            p_email, 
            p_tel, 
            p_password,
            NOW()
        );
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_ajouter_traitement` (IN `p_id_patient` INT, IN `p_description` TEXT, IN `p_date_traitement` DATE, IN `p_medicament` VARCHAR(255), IN `p_suivi` TEXT)   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    IF NOT EXISTS (SELECT 1 FROM patients WHERE id_patient = p_id_patient) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Patient non trouvé';
    END IF;

    IF p_date_traitement > CURDATE() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Date invalide';
    END IF;

    INSERT INTO traitements (id_patient, description, date_traitement, medicament, suivi)
    VALUES (p_id_patient, p_description, p_date_traitement, p_medicament, p_suivi);

    COMMIT;

    SELECT LAST_INSERT_ID() AS id_traitement,
           'Traitement ajouté avec succès' AS message;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_archive_admission` (IN `p_id_admission` INT, IN `p_user_id` INT, IN `p_reason` VARCHAR(255))   BEGIN
    IF EXISTS (SELECT 1 FROM admissions WHERE id_admission = p_id_admission) THEN
        
        INSERT INTO admissions_archive (
            id_admission,
            id_patient,
            id_medecin,
            id_chambre,
            service,
            motif,
            statut,
            type_admission,
            date_admission,
            date_sortie,
            archived_by,
            archive_reason,
            archived_at
        )
        SELECT 
            id_admission,
            id_patient,
            id_medecin,
            id_chambre,
            service,
            motif,
            statut,
            type_admission,
            date_admission,
            date_sortie,
            p_user_id,
            p_reason,
            NOW()
        FROM admissions
        WHERE id_admission = p_id_admission;

        DELETE FROM admissions WHERE id_admission = p_id_admission;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_generer_rapport_traitements` (IN `p_date_debut` DATE, IN `p_date_fin` DATE, IN `p_id_patient` INT)   BEGIN
    -- Rapport détaillé des traitements
    SELECT 
        t.id_traitement,
        t.date_traitement,
        p.nom,
        p.prenom,
        LEFT(t.description, 100) as description_courte,
        t.medicament,
        CASE 
            WHEN t.suivi IS NULL OR t.suivi = '' THEN 'Non'
            ELSE 'Oui'
        END as avec_suivi,
        DATEDIFF(p_date_fin, t.date_traitement) as jours_ecoules
    FROM traitements t
    JOIN patients p ON t.id_patient = p.id_patient
    WHERE t.date_traitement BETWEEN p_date_debut AND p_date_fin
    AND (p_id_patient IS NULL OR t.id_patient = p_id_patient)
    ORDER BY t.date_traitement DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_modifier_traitement` (IN `p_id_traitement` INT, IN `p_id_patient` INT, IN `p_description` TEXT, IN `p_date_traitement` DATE, IN `p_medicament` VARCHAR(255), IN `p_suivi` TEXT)   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    IF NOT EXISTS (SELECT 1 FROM traitements WHERE id_traitement = p_id_traitement) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Traitement non trouvé';
    END IF;

    UPDATE traitements
    SET id_patient = p_id_patient,
        description = p_description,
        date_traitement = p_date_traitement,
        medicament = p_medicament,
        suivi = p_suivi
    WHERE id_traitement = p_id_traitement;

    COMMIT;

    SELECT 1 AS success, 'Traitement modifié avec succès' AS message;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_supprimer_traitement` (IN `p_id_traitement` INT, IN `p_raison_suppression` VARCHAR(255))   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    IF NOT EXISTS (SELECT 1 FROM traitements WHERE id_traitement = p_id_traitement) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Traitement non trouvé';
    END IF;

    INSERT INTO historique_suppressions (id_traitement, raison_suppression, donnees_traitement)
    SELECT 
        id_traitement,
        p_raison_suppression,
        JSON_OBJECT(
            'id_patient', id_patient,
            'description', description,
            'date_traitement', date_traitement,
            'medicament', medicament,
            'suivi', suivi,
            'code_traitement', code_traitement,
            'date_creation', date_creation
        )
    FROM traitements
    WHERE id_traitement = p_id_traitement;

    DELETE FROM traitements WHERE id_traitement = p_id_traitement;

    COMMIT;

    SELECT 1 AS success, 'Traitement supprimé et archivé avec succès' AS message;
END$$

--
-- Fonctions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `check_chambre_disponible` (`p_id_chambre` INT) RETURNS TINYINT(1) DETERMINISTIC BEGIN
    DECLARE nb INT;

    -- Compter le nombre d'admissions en cours dans la chambre spécifiée
    SELECT COUNT(*) INTO nb
    FROM admissions
    WHERE id_chambre = p_id_chambre AND statut = 'En cours';

    -- Vérifier si la chambre a encore de la place
    RETURN IF(
        nb < (SELECT capacite FROM chambres WHERE id_chambre = p_id_chambre),
        1,  
        0  
    );
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `fn_calculer_age` (`p_date_naissance` DATE) RETURNS INT(11) DETERMINISTIC BEGIN
    RETURN TIMESTAMPDIFF(YEAR, p_date_naissance, CURDATE());
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `fn_generer_numero_traitement` () RETURNS VARCHAR(20) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC BEGIN
    DECLARE v_max INT DEFAULT 0;
    DECLARE v_code VARCHAR(20);

    SELECT COALESCE(MAX(id_traitement), 0)
    INTO v_max
    FROM traitements;

    SET v_code = CONCAT(
        'TRT-',
        YEAR(CURDATE()),
        '-',
        LPAD(v_max + 1, 5, '0')
    );

    RETURN v_code;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `fn_nombre_traitements_patient` (`p_id_patient` INT) RETURNS INT(11) DETERMINISTIC BEGIN
    DECLARE v_nombre INT;
    
    SELECT COUNT(*) INTO v_nombre
    FROM traitements
    WHERE id_patient = p_id_patient;
    
    RETURN IFNULL(v_nombre, 0);
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `fn_patient_a_traitements` (`p_id_patient` INT) RETURNS TINYINT(1) DETERMINISTIC BEGIN
    DECLARE v_nombre INT;
    
    SELECT COUNT(*) INTO v_nombre
    FROM traitements
    WHERE id_patient = p_id_patient
    AND date_traitement >= CURDATE() - INTERVAL 30 DAY;
    
    RETURN v_nombre > 0;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `admissions`
--

CREATE TABLE `admissions` (
  `id_admission` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `id_medecin` int(11) DEFAULT NULL,
  `id_chambre` int(11) DEFAULT NULL,
  `date_admission` datetime NOT NULL,
  `date_sortie` datetime DEFAULT NULL,
  `service` varchar(100) NOT NULL,
  `motif` text NOT NULL,
  `type_admission` varchar(50) DEFAULT 'Normal',
  `statut` varchar(50) DEFAULT 'En cours',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `admissions`
--

INSERT INTO `admissions` (`id_admission`, `id_patient`, `id_medecin`, `id_chambre`, `date_admission`, `date_sortie`, `service`, `motif`, `type_admission`, `statut`, `created_at`, `updated_at`) VALUES
(4, 44, 9, 8, '2026-01-18 00:00:00', '2026-01-18 02:53:30', 'Cardiologie', 'rrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrrr', 'Urgent', 'En cours', '2026-01-20 18:00:10', '2026-01-20 18:00:10'),
(7, 16, 1, 9, '2026-01-18 00:00:00', '2026-01-21 01:36:57', 'Pédiatrie', 'lllllllllllllllllllllllllllllllllllllll', 'Urgent', 'Terminée', '2026-01-20 18:00:10', '2026-01-21 01:59:51'),
(8, 48, 1, 1, '2026-01-21 00:00:00', NULL, 'pediatre', 'controle programee', 'Urgent', 'En cours', '2026-01-21 01:25:55', '2026-01-21 01:28:16');

--
-- Déclencheurs `admissions`
--
DELIMITER $$
CREATE TRIGGER `after_admission_delete` AFTER DELETE ON `admissions` FOR EACH ROW BEGIN
    DECLARE total INT;
    DECLARE cap INT;

    SELECT COUNT(*) INTO total 
    FROM admissions 
    WHERE id_chambre = OLD.id_chambre AND statut='En cours';

    SELECT capacite INTO cap 
    FROM chambres WHERE id_chambre = OLD.id_chambre;

    IF total < cap THEN
        UPDATE chambres SET etat='libre' WHERE id_chambre = OLD.id_chambre;
    ELSE
        UPDATE chambres SET etat='complet' WHERE id_chambre = OLD.id_chambre;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_admission_insert` AFTER INSERT ON `admissions` FOR EACH ROW BEGIN
    DECLARE total INT;
    DECLARE cap INT;

    INSERT INTO admission_logs(id_admission, action, description)
    VALUES (NEW.id_admission,'insertion','Nouvelle admission');

    SELECT COUNT(*) INTO total 
    FROM admissions 
    WHERE id_chambre = NEW.id_chambre AND statut='En cours';

    SELECT capacite INTO cap 
    FROM chambres WHERE id_chambre = NEW.id_chambre;

    IF total >= cap THEN
        UPDATE chambres SET etat='complet' WHERE id_chambre = NEW.id_chambre;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_admission_update` AFTER UPDATE ON `admissions` FOR EACH ROW BEGIN
    DECLARE total INT;
    DECLARE cap INT;

    SELECT COUNT(*) INTO total 
    FROM admissions 
    WHERE id_chambre = NEW.id_chambre AND statut='En cours';

    SELECT capacite INTO cap 
    FROM chambres WHERE id_chambre = NEW.id_chambre;

    IF total < cap THEN
        UPDATE chambres SET etat='libre' WHERE id_chambre = NEW.id_chambre;
    ELSE
        UPDATE chambres SET etat='complet' WHERE id_chambre = NEW.id_chambre;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `admissions_archive`
--

CREATE TABLE `admissions_archive` (
  `id_admission` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `id_medecin` int(11) DEFAULT NULL,
  `id_chambre` int(11) DEFAULT NULL,
  `service` varchar(100) NOT NULL,
  `motif` text NOT NULL,
  `statut` enum('En cours','Terminé') DEFAULT 'En cours',
  `type_admission` enum('Normal','Urgent') DEFAULT 'Normal',
  `date_admission` datetime NOT NULL DEFAULT current_timestamp(),
  `date_sortie` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_at` datetime NOT NULL DEFAULT current_timestamp(),
  `archived_by` int(11) DEFAULT NULL,
  `archive_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `admission_logs`
--

CREATE TABLE `admission_logs` (
  `id_log` int(11) NOT NULL,
  `id_admission` int(11) DEFAULT NULL,
  `action` enum('ajout','modification','suppression') DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `admission_logs`
--

INSERT INTO `admission_logs` (`id_log`, `id_admission`, `action`, `description`, `created_at`) VALUES
(0, 8, '', 'Nouvelle admission', '2026-01-21 01:25:55');

-- --------------------------------------------------------

--
-- Structure de la table `antecedents`
--

CREATE TABLE `antecedents` (
  `id_ante` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `categorie` enum('Médical','Chirurgical') NOT NULL,
  `nom_pathologie` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `date_evenement` varchar(255) NOT NULL,
  `date_enregistrement` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `antecedents`
--

INSERT INTO `antecedents` (`id_ante`, `id_patient`, `categorie`, `nom_pathologie`, `description`, `date_evenement`, `date_enregistrement`) VALUES
(9, 43, 'Chirurgical', 'pancrias ', '', '', '2026-01-12 23:17:09'),
(10, 43, 'Médical', 'Antécédents Néoplasiques', '', '', '2026-01-12 23:35:03'),
(15, 34, 'Médical', 'Diabète', '', '', '2026-01-16 10:18:44'),
(16, 34, 'Médical', 'Hypertension (HTA)', '', '', '2026-01-16 10:18:44'),
(17, 34, 'Chirurgical', 'pancrias 2025', '', '', '2026-01-16 10:18:44'),
(20, 16, 'Médical', 'Diabète', '2015', '', '2026-01-21 00:41:08'),
(21, 16, 'Médical', 'Antécédents Néoplasiques', '', '', '2026-01-21 00:41:08'),
(22, 16, 'Chirurgical', 'pancrias ', '', '2023', '2026-01-21 00:41:08');

-- --------------------------------------------------------

--
-- Structure de la table `archives_utilisateurs`
--

CREATE TABLE `archives_utilisateurs` (
  `id_archive` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `specialite` varchar(255) NOT NULL,
  `telephone` varchar(255) NOT NULL,
  `cin` varchar(255) NOT NULL,
  `matricule` varchar(255) NOT NULL,
  `date_suppression` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `archives_utilisateurs`
--

INSERT INTO `archives_utilisateurs` (`id_archive`, `id_user`, `nom`, `prenom`, `email`, `role`, `specialite`, `telephone`, `cin`, `matricule`, `date_suppression`) VALUES
(1, 2, 'laarioui', 'awssaf', 'awssaf@gmail.com', 'medecin', 'Généraliste', '611546090', '', '', '2026-01-16 22:07:26'),
(2, 2, 'laarioui', 'awssaf', 'awssaf@gmail.com', 'medecin', 'Généraliste', '611546090', '', '', '2026-01-16 22:07:26'),
(3, 8, 'BIRAM', 'nada', 'biram@gmail.com', 'medecin', 'geniraliste', '671399566', 'TT123', '2026005', '2026-01-16 22:47:21'),
(4, 8, 'BIRAM', 'nada', 'biram@gmail.com', 'medecin', 'geniraliste', '671399566', 'TT123', '2026005', '2026-01-16 22:47:21'),
(5, 11, 'BIRAM', 'nada', 'tolabyinssaf45@gmail.com', 'medecin', 'geniraliste', '671399566', 'TT123UU', '2026007', '2026-01-17 11:34:39'),
(6, 11, 'BIRAM', 'nada', 'tolabyinssaf45@gmail.com', 'medecin', 'geniraliste', '671399566', 'TT123UU', '2026007', '2026-01-17 11:34:39'),
(7, 13, 'BIRAM', 'nada', 'tolabyinssaf12k3@gmail.com', 'medecin', 'geniraliste', '671399566', 'TT123JJ', '2026008', '2026-01-18 02:50:41'),
(8, 13, 'BIRAM', 'nada', 'tolabyinssaf12k3@gmail.com', 'medecin', 'geniraliste', '671399566', 'TT123JJ', '2026008', '2026-01-18 02:50:41');

-- --------------------------------------------------------

--
-- Structure de la table `chambres`
--

CREATE TABLE `chambres` (
  `id_chambre` int(11) NOT NULL,
  `numero_chambre` varchar(20) NOT NULL,
  `bloc` varchar(50) DEFAULT 'A',
  `etage` varchar(50) DEFAULT 'RDC',
  `service` varchar(100) DEFAULT NULL,
  `capacite` int(11) DEFAULT 1,
  `etat` enum('libre','complet','maintenance') DEFAULT 'libre',
  `type_lit` enum('Manuel','Electrique') DEFAULT 'Electrique',
  `oxigene` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `chambres`
--

INSERT INTO `chambres` (`id_chambre`, `numero_chambre`, `bloc`, `etage`, `service`, `capacite`, `etat`, `type_lit`, `oxigene`, `created_at`) VALUES
(1, '101', 'Bloc A', '1er Etage', 'Cardiologie', 1, 'complet', 'Electrique', 1, '2026-01-20 17:00:10'),
(2, '102', 'Bloc A', '1er Etage', 'Cardiologie', 2, 'complet', 'Electrique', 1, '2026-01-20 17:00:10'),
(3, '201', 'Bloc B', '2ème Etage', 'Pédiatrie', 1, 'libre', 'Electrique', 1, '2026-01-20 17:00:10'),
(4, '202', 'Bloc B', '2ème Etage', 'Pédiatrie', 1, 'maintenance', 'Electrique', 1, '2026-01-20 17:00:10'),
(5, '001', 'Bloc C', 'RDC', 'Urgences', 4, 'libre', 'Electrique', 1, '2026-01-20 17:00:10'),
(6, '002', 'Bloc C', 'RDC', 'Urgences', 2, 'complet', 'Electrique', 1, '2026-01-20 17:00:10'),
(7, '207', 'Bloc A', '1', 'Cardiologie', 2, 'libre', '', 1, '2026-01-21 01:17:38'),
(8, '301', 'Bloc B', '1', 'Urgences', 3, 'libre', '', 1, '2026-01-21 01:18:32');

-- --------------------------------------------------------

--
-- Structure de la table `factures`
--

CREATE TABLE `factures` (
  `id_facture` int(11) NOT NULL,
  `id_admission` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `date_facture` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `numero_facture` varchar(255) NOT NULL,
  `nb_jours` int(11) NOT NULL DEFAULT 1,
  `prix_unitaire_jour` decimal(10,0) NOT NULL,
  `frais_actes_medicaux` decimal(10,0) NOT NULL,
  `montant_total` decimal(10,2) NOT NULL,
  `mode_paiement` enum('Espèces','Carte','Chèque') NOT NULL,
  `type_couverture` enum('CNSS','CNOPS','MUTUELLE_PRIVEE','AUCUN') NOT NULL,
  `statut_paiement` enum('Payé','Annulé') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `factures`
--

INSERT INTO `factures` (`id_facture`, `id_admission`, `id_patient`, `id_utilisateur`, `date_facture`, `numero_facture`, `nb_jours`, `prix_unitaire_jour`, `frais_actes_medicaux`, `montant_total`, `mode_paiement`, `type_couverture`, `statut_paiement`) VALUES
(3, 4, 44, 4, '2026-01-18 01:53:30', 'FAC-20260118-954', 1, 600, 150, 750.00, 'Espèces', '', 'Payé'),
(4, 6, 16, 4, '2026-01-18 02:07:51', 'FAC-20260118-708', 1, 600, 150, 750.00, 'Espèces', '', 'Payé'),
(5, 7, 0, 0, '2026-01-21 00:59:51', 'FAC-2026-0007', 3, 0, 0, 1950.00, '', '', 'Payé');

-- --------------------------------------------------------

--
-- Structure de la table `historique_statuts`
--

CREATE TABLE `historique_statuts` (
  `id_historique` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `ancien_statut` varchar(255) NOT NULL,
  `nouveau_statut` varchar(255) NOT NULL,
  `date_changement` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `historique_statuts`
--

INSERT INTO `historique_statuts` (`id_historique`, `id_patient`, `ancien_statut`, `nouveau_statut`, `date_changement`) VALUES
(1, 16, 'En observation', 'Stable', '2026-01-04 21:44:28'),
(2, 16, 'Stable', 'Critique', '2026-01-04 21:51:41'),
(3, 16, 'Critique', 'Stable', '2026-01-04 22:19:30'),
(4, 16, 'Stable', 'Critique', '2026-01-04 23:46:30'),
(5, 16, 'Critique', 'Urgent', '2026-01-21 00:38:53');

-- --------------------------------------------------------

--
-- Structure de la table `historique_suppressions`
--

CREATE TABLE `historique_suppressions` (
  `id_suppression` int(11) NOT NULL,
  `id_traitement` int(11) NOT NULL,
  `date_suppression` datetime DEFAULT current_timestamp(),
  `raison_suppression` varchar(255) DEFAULT NULL,
  `donnees_traitement` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `historique_suppressions`
--

INSERT INTO `historique_suppressions` (`id_suppression`, `id_traitement`, `date_suppression`, `raison_suppression`, `donnees_traitement`) VALUES
(1, 1, '2026-01-21 01:52:41', 'Suppression manuelle', '{\"id_patient\": 36, \"description\": \"eeeeeeeeeeeeeeeeeeeeeeell\", \"date_traitement\": \"2026-01-20\", \"medicament\": \"doliprane\", \"suivi\": \"eeeeeeeeeeeeeeeeeeeeeeeee\", \"code_traitement\": \"TRT-2026-00001\", \"date_creation\": \"2026-01-20 17:59:20\"}');

-- --------------------------------------------------------

--
-- Structure de la table `historique_traitements`
--

CREATE TABLE `historique_traitements` (
  `id_historique` int(11) NOT NULL,
  `id_traitement` int(11) NOT NULL,
  `champ_modifie` varchar(100) DEFAULT NULL,
  `ancienne_valeur` text DEFAULT NULL,
  `nouvelle_valeur` text DEFAULT NULL,
  `date_modification` datetime DEFAULT current_timestamp(),
  `utilisateur` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `historique_traitements`
--

INSERT INTO `historique_traitements` (`id_historique`, `id_traitement`, `champ_modifie`, `ancienne_valeur`, `nouvelle_valeur`, `date_modification`, `utilisateur`) VALUES
(1, 2, 'description', 'hhhhhhhhhhhhhhhhhhhh', 'ooooooooooooooooo', '2026-01-20 18:10:09', 'root@localhost'),
(2, 2, 'medicament', 'Oméprazole 20 mg', 'Oméprazole 50 mg', '2026-01-20 18:10:09', 'root@localhost'),
(3, 2, 'suivi', 'tttttttttttttttttttttttttttt', 'ppppppppppppppppppp', '2026-01-20 18:10:09', 'root@localhost'),
(4, 2, 'suivi', 'ppppppppppppppppppp', 'pppppeeeeeeeeeeeeeeeee', '2026-01-20 18:20:02', 'root@localhost'),
(5, 2, 'suivi', 'pppppeeeeeeeeeeeeeeeee', 'hhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhh', '2026-01-20 18:24:22', 'root@localhost'),
(6, 2, 'suivi', 'hhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhh', 'haheyecx xbxvxvx', '2026-01-20 20:31:36', 'root@localhost'),
(7, 2, 'description', 'ooooooooooooooooo', 'pppppppppppppp', '2026-01-20 20:33:23', 'root@localhost'),
(8, 2, 'description', 'pppppppppppppp', 'mmmmmmmmmmmmm', '2026-01-20 20:34:06', 'root@localhost'),
(9, 2, 'description', 'mmmmmmmmmmmmm', 'aaaaaaaaaaaaaaaaaaaaa', '2026-01-20 20:38:13', 'root@localhost'),
(10, 2, 'date_traitement', '2026-01-20', '0000-00-00', '2026-01-20 20:38:13', 'root@localhost'),
(11, 2, 'date_traitement', '0000-00-00', '2026-01-20', '2026-01-20 20:41:30', 'root@localhost'),
(12, 1, 'description', 'eeeeeeeeeeeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeeeell', '2026-01-20 20:47:15', 'root@localhost');

-- --------------------------------------------------------

--
-- Structure de la table `patients`
--

CREATE TABLE `patients` (
  `id_patient` int(11) NOT NULL,
  `CIN` varchar(10) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `sexe` varchar(255) DEFAULT NULL,
  `adresse` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `groupe_sanguin` varchar(255) NOT NULL,
  `statut` varchar(255) NOT NULL DEFAULT 'stable',
  `allergies` text NOT NULL,
  `date_inscription` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_medecin` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `patients`
--

INSERT INTO `patients` (`id_patient`, `CIN`, `nom`, `prenom`, `date_naissance`, `sexe`, `adresse`, `telephone`, `email`, `groupe_sanguin`, `statut`, `allergies`, `date_inscription`, `id_medecin`) VALUES
(1, '', 'Tolaby', 'Inssaf', '2000-01-01', 'F', 'Rabat', '0612345678', 'tolabyinssaf@test.com', '', 'stable', '', '2025-12-18 22:24:28', 0),
(2, '', 'Belkheiri', 'Houda', '2000-01-01', 'F', 'Rabat', '0612345678', 'houdabelkheiri@test.com', '', 'stable', '', '2025-12-18 22:24:28', 0),
(3, '', 'awssaf', 'Awssaf', '2000-01-01', 'F', 'Rabat', '0612345678', 'awssaf@test.com', '', 'stable', '', '2025-12-18 22:24:28', 0),
(16, 'TT123', 'INSSAF', 'Tolaby', '2005-06-09', 'F', 'tanger', '0671344502', 'inssaf@test.com', 'AB+', 'Urgent', '', '2025-12-22 23:00:00', 1),
(30, 'jj1231', 'inssaf', 'tolaby', '2003-05-12', 'H', 'tanger', '0655448892', 'hh@gmail.com', '', 'stable', '', '2025-12-27 23:00:00', 2),
(31, 'cc456', 'inssaf', 'tolaby', '2003-05-12', 'F', 'tanger', '0655448892', 'hhg@gmail.com', '', 'stable', '', '2025-12-27 23:00:00', 2),
(34, 'GG123', 'inssaf', 'tolaby', '2004-02-10', 'F', 'rabat', '0612547877', 'inssaf@gmail.com', '', 'Stable', '', '2025-12-29 23:00:00', 1),
(36, 'EE1562', 'BIRAM', 'Nada', '2005-02-12', 'F', NULL, '0612445065', NULL, '', 'stable', '', '2026-01-06 00:20:14', 0),
(37, 'KK14578', 'TOLABY', 'Rabie', '2005-12-30', 'M', NULL, '0655113399', NULL, '', 'stable', '', '2026-01-06 00:30:54', 0),
(40, 'II1234577', 'TOLABY', 'Achraf', '2002-07-13', 'M', NULL, '0655113399', NULL, '', 'stable', '', '2026-01-06 00:39:20', 0),
(41, 'RR1234587', 'LAARIOUI', 'Adam', '2007-05-12', 'M', NULL, '0614526380', NULL, '', 'stable', '', '2026-01-06 00:48:26', 0),
(42, 'RR12345875', 'LAARIOUI', 'Adam', '2007-05-12', 'M', NULL, '0614526380', NULL, '', 'stable', '', '2026-01-06 00:50:09', 0),
(43, 'RR12345888', 'LAARIOUI', 'Aya', '2000-05-12', 'F', '', '0614526374', 'laariouiaya@gmail.com', 'AB+', 'Stable', '', '2026-01-06 01:00:08', 1),
(44, 'ZE12345', 'LAARIOUI', 'Aya', '2000-05-12', 'F', NULL, '0614526374', NULL, '', 'stable', '', '2026-01-06 01:00:57', 0),
(45, 'LL47859', 'HHHHH', 'Eeee', '0006-02-25', 'M', 'hhhhh', '0625148899', 'hhhhhh@gmail.com', '', 'stable', '', '2026-01-11 12:47:06', 0),
(46, 'YY258963', 'MMMMMMMMM', 'Pppppppp', '2004-08-07', 'F', 'mmm', '0645789620', 'hhhhhhiii@gmail.com', '', 'stable', '', '2026-01-11 13:09:05', 0),
(47, 'GG12312', 'BRIGUIi', 'Hakima', '2005-05-12', 'F', 'tetouane', '0671307458', NULL, '', 'stable', '', '2026-01-17 19:10:01', 0),
(48, 'ZZ12312', 'BELKHEIRI', 'Malak', '2008-05-14', 'F', 'kenitra', '0671307477', NULL, '', 'stable', '', '2026-01-21 00:23:52', 0);

--
-- Déclencheurs `patients`
--
DELIMITER $$
CREATE TRIGGER `before_delete` BEFORE DELETE ON `patients` FOR EACH ROW BEGIN
    INSERT INTO patients_archive 
    (id_patient, nom, prenom, date_naissance, sexe, adresse, telephone, email, date_inscription, id_medecin)
    VALUES 
    (OLD.id_patient, OLD.nom, OLD.prenom, OLD.date_naissance, OLD.sexe, OLD.adresse, OLD.telephone, OLD.email, OLD.date_inscription, OLD.id_medecin);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_insert_patient` BEFORE INSERT ON `patients` FOR EACH ROW BEGIN
   
    IF EXISTS (SELECT 1 FROM patients WHERE email = NEW.email) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Email déjà utilisé';
    END IF;

  
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_update` BEFORE UPDATE ON `patients` FOR EACH ROW BEGIN
   
    IF EXISTS (
        SELECT 1 
        FROM patients
        WHERE email = NEW.email
          AND id_medecin = NEW.id_medecin
          AND id_patient <> OLD.id_patient
    ) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Email déjà utilisé';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `log_statut_patient` AFTER UPDATE ON `patients` FOR EACH ROW BEGIN
   
    IF OLD.statut <> NEW.statut OR (OLD.statut IS NULL AND NEW.statut IS NOT NULL) THEN
        INSERT INTO historique_statuts (
            id_patient, 
            ancien_statut, 
            nouveau_statut
        )
        VALUES (
            NEW.id_patient, 
            OLD.statut, 
            NEW.statut
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `patients_archive`
--

CREATE TABLE `patients_archive` (
  `id_patient` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `date_naissance` date NOT NULL,
  `sexe` enum('H','F','','','') NOT NULL,
  `adresse` varchar(255) NOT NULL,
  `telephone` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `date_inscription` date NOT NULL,
  `id_medecin` int(11) NOT NULL,
  `date_supprimee` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `patients_archive`
--

INSERT INTO `patients_archive` (`id_patient`, `nom`, `prenom`, `date_naissance`, `sexe`, `adresse`, `telephone`, `email`, `date_inscription`, `id_medecin`, `date_supprimee`) VALUES
(26, 'tolaby', 'rim', '2007-12-30', 'F', 'kenitra', '0671307499', 'rim@gmail.com', '2025-12-23', 1, '2025-12-28 21:32:46'),
(28, 'tolaby', 'rim', '2007-12-30', 'H', 'kenitra', '0671307499', 'rim477@gmail.com', '2025-12-23', 1, '2025-12-28 21:50:16'),
(29, 'inssaf', 'tolaby', '2003-05-12', 'F', 'tanger', '0655448892', 'hakima@gmail.com', '2025-12-28', 1, '2025-12-28 22:14:16'),
(32, 'inssaf', 'tolaby', '2004-02-10', 'F', 'rabat', '0612547896', 'rim@gmail.com', '2025-12-28', 1, '2025-12-30 19:17:38'),
(33, 'inssaf', 'tolaby', '2003-05-12', 'H', 'tanger', '0655448892', 'hhg55@gmail.com', '2025-12-28', 1, '2025-12-30 19:17:34'),
(35, 'tolaby', 'rabie', '0007-01-30', 'H', 'kenitra', '0622447788', 'rabie@test.com', '2026-01-05', 1, '2026-01-21 00:48:25');

-- --------------------------------------------------------

--
-- Structure de la table `planning_soins`
--

CREATE TABLE `planning_soins` (
  `id_planning` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `id_admission` int(11) NOT NULL,
  `date_prevue` date NOT NULL,
  `heure_prevue` time NOT NULL,
  `soin_a_faire` varchar(255) NOT NULL,
  `description_detaillee` text NOT NULL,
  `statut` enum('en attente','fait','annulé') NOT NULL DEFAULT 'en attente',
  `priorite` enum('basse','normale','haute','urgente') NOT NULL DEFAULT 'normale',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `prestations`
--

CREATE TABLE `prestations` (
  `id_prestation` int(11) NOT NULL,
  `nom_prestation` varchar(255) NOT NULL,
  `categorie` varchar(255) NOT NULL,
  `prix_unitaire` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `prestations`
--

INSERT INTO `prestations` (`id_prestation`, `nom_prestation`, `categorie`, `prix_unitaire`) VALUES
(2, 'Consultation Spécialiséehdhdhhd', 'Consultation', 250),
(3, 'Consultation Spécialisée', 'Chirurgie', 650),
(4, 'Scanner Cérébral (TDM)', 'Radiologie', 1200),
(5, 'IRM Lombaire', 'Radiologie', 2500),
(6, 'Injection de Fer ', 'Soins', 150),
(9, 'Test PCR COVID-19', 'Laboratoire', 400);

-- --------------------------------------------------------

--
-- Structure de la table `soins_patients`
--

CREATE TABLE `soins_patients` (
  `id_soin_patient` int(11) NOT NULL,
  `id_admission` int(11) NOT NULL,
  `id_prestation` int(11) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `date_soin` datetime NOT NULL DEFAULT current_timestamp(),
  `id_infirmier` int(11) NOT NULL,
  `statut_facturation` enum('en_attente','paye') NOT NULL,
  `temperature` decimal(10,0) NOT NULL,
  `tension` varchar(255) NOT NULL,
  `frequence_cardiaque` int(11) NOT NULL,
  `type_acte` int(11) NOT NULL,
  `medicament` varchar(255) NOT NULL,
  `observations` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `soins_patients`
--

INSERT INTO `soins_patients` (`id_soin_patient`, `id_admission`, `id_prestation`, `quantite`, `date_soin`, `id_infirmier`, `statut_facturation`, `temperature`, `tension`, `frequence_cardiaque`, `type_acte`, `medicament`, `observations`) VALUES
(1, 6, 1, 1, '2026-01-12 20:15:56', 6, '', 37, '12/8', 76, 0, 'fer', ''),
(2, 6, 1, 1, '2026-01-12 22:21:42', 6, '', 78, '12/8', 55, 0, 'doliprane', ''),
(3, 5, 1, 1, '2026-01-13 01:46:37', 6, '', 36, '12/8', 55, 0, 'doliprane', ''),
(4, 4, 1, 1, '2026-01-13 02:18:11', 6, '', 77, '12/8', 78, 0, 'fer', ''),
(5, 3, 1, 1, '2026-01-13 02:21:39', 6, '', 44, '12/8', 78, 0, 'fer', ''),
(6, 6, 1, 1, '2026-01-16 11:13:23', 6, '', 12, '12/8', 75, 0, 'fer', ''),
(7, 8, 6, 1, '2026-01-21 01:56:25', 6, 'en_attente', 37, '12/8', 75, 0, 'fer', '');

--
-- Déclencheurs `soins_patients`
--
DELIMITER $$
CREATE TRIGGER `after_soin_update_total` AFTER INSERT ON `soins_patients` FOR EACH ROW BEGIN
    DECLARE v_total_soins DECIMAL(10,2);
    DECLARE v_jours INT;
    DECLARE v_prix_jour DECIMAL(10,2);

    -- 1. Calculer la somme de TOUS les soins actuels pour cette admission
    SELECT SUM(p.prix_unitaire) INTO v_total_soins
    FROM soins_patients s
    JOIN prestations p ON s.id_prestation = p.id_prestation
    WHERE s.id_admission = NEW.id_admission;

    -- 2. Récupérer les infos de base de la facture
    SELECT nb_jours, prix_unitaire_jour INTO v_jours, v_prix_jour
    FROM factures
    WHERE id_admission = NEW.id_admission;

    -- 3. Mettre à jour la facture avec le montant 
    UPDATE factures 
    SET frais_actes_medicaux = IFNULL(v_total_soins, 0),
        montant_total = (v_jours * v_prix_jour) + IFNULL(v_total_soins, 0)
    WHERE id_admission = NEW.id_admission;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `statistiques_systeme`
--

CREATE TABLE `statistiques_systeme` (
  `id` int(11) NOT NULL DEFAULT 1,
  `nombre_traitements_total` int(11) DEFAULT 0,
  `nombre_patients_total` int(11) DEFAULT 0,
  `nombre_suppressions` int(11) DEFAULT 0,
  `derniere_maj` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `derniere_suppression` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `suivis`
--

CREATE TABLE `suivis` (
  `id_suivi` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `id_medecin` int(11) NOT NULL,
  `date_suivi` date NOT NULL,
  `commentaire` text NOT NULL,
  `status` varchar(50) DEFAULT 'En cours',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `suivis`
--

INSERT INTO `suivis` (`id_suivi`, `id_patient`, `id_medecin`, `date_suivi`, `commentaire`, `status`, `created_at`) VALUES
(1, 16, 1, '2026-02-17', 'suivis de routine', 'Terminé', '2026-01-20 16:04:36'),
(3, 43, 1, '2026-01-22', 'controle', 'Terminé', '2026-01-21 00:44:58');

-- --------------------------------------------------------

--
-- Structure de la table `traitements`
--

CREATE TABLE `traitements` (
  `id_traitement` int(11) NOT NULL,
  `id_patient` int(11) NOT NULL,
  `code_traitement` varchar(20) DEFAULT NULL,
  `description` text NOT NULL,
  `date_traitement` date NOT NULL,
  `medicament` varchar(255) DEFAULT NULL,
  `suivi` text DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_modification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `traitements`
--

INSERT INTO `traitements` (`id_traitement`, `id_patient`, `code_traitement`, `description`, `date_traitement`, `medicament`, `suivi`, `date_creation`, `date_modification`) VALUES
(2, 36, 'TRT-2026-00002', 'aaaaaaaaaaaaaaaaaaaaa', '2026-01-20', 'Oméprazole 50 mg', 'haheyecx xbxvxvx', '2026-01-20 18:02:00', '2026-01-20 20:41:30'),
(3, 36, 'TRT-2026-00003', 'doleurs', '2026-01-21', 'Oméprazole 50 mg', 'apres les repas ', '2026-01-21 01:42:43', '2026-01-21 01:42:43');

--
-- Déclencheurs `traitements`
--
DELIMITER $$
CREATE TRIGGER `tr_after_update_traitement` AFTER UPDATE ON `traitements` FOR EACH ROW BEGIN
    IF NOT (OLD.description <=> NEW.description) THEN
        INSERT INTO historique_traitements
        VALUES (NULL, NEW.id_traitement, 'description',
                OLD.description, NEW.description, NOW(), USER());
    END IF;

    IF NOT (OLD.medicament <=> NEW.medicament) THEN
        INSERT INTO historique_traitements
        VALUES (NULL, NEW.id_traitement, 'medicament',
                OLD.medicament, NEW.medicament, NOW(), USER());
    END IF;

    IF NOT (OLD.suivi <=> NEW.suivi) THEN
        INSERT INTO historique_traitements
        VALUES (NULL, NEW.id_traitement, 'suivi',
                OLD.suivi, NEW.suivi, NOW(), USER());
    END IF;

    IF OLD.date_traitement <> NEW.date_traitement THEN
        INSERT INTO historique_traitements
        VALUES (NULL, NEW.id_traitement, 'date_traitement',
                OLD.date_traitement, NEW.date_traitement, NOW(), USER());
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_before_insert_traitement` BEFORE INSERT ON `traitements` FOR EACH ROW BEGIN
    IF NEW.date_traitement > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La date du traitement ne peut pas être dans le futur';
    END IF;

    IF NEW.code_traitement IS NULL OR NEW.code_traitement = '' THEN
        SET NEW.code_traitement = fn_generer_numero_traitement();
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id_user` int(11) NOT NULL,
  `cin` varchar(255) NOT NULL,
  `matricule` varchar(255) NOT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `prenom` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `mot_de_passe` varchar(255) DEFAULT NULL,
  `telephone` int(11) NOT NULL,
  `adresse` text NOT NULL,
  `photo` varchar(255) NOT NULL DEFAULT '''default_avatar.png''',
  `role` enum('infirmier','admin','medecin','secretaire') DEFAULT NULL,
  `specialite` varchar(255) NOT NULL DEFAULT 'Généraliste',
  `statut_compte` enum('actif','suspendu','conge') NOT NULL,
  `date_embauche` date NOT NULL DEFAULT current_timestamp(),
  `derniere_connexion` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id_user`, `cin`, `matricule`, `nom`, `prenom`, `email`, `mot_de_passe`, `telephone`, `adresse`, `photo`, `role`, `specialite`, `statut_compte`, `date_embauche`, `derniere_connexion`, `created_at`) VALUES
(1, '', '', 'tolaby', 'inssaf', 'inssaf@gmail.com', '123456', 655447720, '', 'medecin_1_1768925774.jpg', 'medecin', 'Généraliste', 'actif', '2026-01-16', NULL, '2026-01-16 00:15:09'),
(4, '', '', 'houda', 'houda', 'houda@gmail.com', '123123', 622887799, '', 'secretaire_4_1768955600.jpg', 'secretaire', 'Généraliste', 'actif', '2026-01-16', NULL, '2026-01-16 00:15:09'),
(6, '', '', 'laarioui', 'awssaf', 'awssy@gmail.com', '123456', 622887799, '', 'infirmier_6_1768957095.jpg', 'infirmier', '', 'actif', '2026-01-16', NULL, '2026-01-16 00:15:09'),
(7, '', '', 'tolaby', 'inssaf jjjj', 'inss@gmail.com', '1212', 611546099, '', '\'default_avatar.png\'', 'admin', '', 'actif', '2026-01-16', NULL, '2026-01-16 00:15:09'),
(9, 'TT123', '2026005', 'BIRAM', 'nada', 'tolabyinssaf123@gmail.com', '$2y$10$Cz3G9WJE7ZA6ZLLtuDG4YOdA5eT08nJkRaFt4gIsQTcUj7EgFizZu', 671399566, '', '\'default_avatar.png\'', 'medecin', 'geniraliste', 'actif', '2026-01-16', NULL, '2026-01-16 21:50:50'),
(10, 'KK45678', '2026006', 'BIRAM', 'nada', 'tolabyinssaf4@gmail.com', '$2y$10$FT5jH0qdauFK8620neMj4e4DCeaY0sMNa7E6PIuqeF/ER482TGRzu', 671399566, '', '\'default_avatar.png\'', 'infirmier', '', 'actif', '2026-01-16', NULL, '2026-01-16 21:52:31'),
(12, 'JJJJ456K', '2026008', 'INSSAF', 'tolaby', 'tolabyinssaf78@gmail.com', '$2y$10$MWyGgJQIddVWi65ufYG5X.XRXMCQE.LEM1ZkyGKYvp5NVmXC/riDG', 671307458, '', '\'default_avatar.png\'', 'secretaire', '', 'actif', '2026-01-16', NULL, '2026-01-16 22:10:31');

--
-- Déclencheurs `utilisateurs`
--
DELIMITER $$
CREATE TRIGGER `delete_user` BEFORE DELETE ON `utilisateurs` FOR EACH ROW BEGIN
    INSERT INTO archives_utilisateurs (
        id_user, 
        nom, 
        prenom, 
        email, 
        role, 
        specialite, 
        telephone, 
        cin, 
        matricule
    )
    VALUES (
        OLD.id_user, 
        OLD.nom, 
        OLD.prenom, 
        OLD.email, 
        OLD.role, 
        OLD.specialite, 
        OLD.telephone, 
        OLD.cin, 
        OLD.matricule
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vw_admission_kpi`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `vw_admission_kpi` (
`total` bigint(21)
,`en_cours` decimal(22,0)
,`termine` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_admissions_age`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_admissions_age` (
`0-14` decimal(22,0)
,`15-30` decimal(22,0)
,`31-50` decimal(22,0)
,`+50` decimal(22,0)
,`year` int(4)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_admissions_sexe`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_admissions_sexe` (
`sexe` varchar(255)
,`total` bigint(21)
,`year` int(4)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_admissions_type`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_admissions_type` (
`year` int(4)
,`type_admission` varchar(50)
,`total` bigint(21)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_avg_duration`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_avg_duration` (
`year` int(4)
,`moyenne_jours` decimal(10,4)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_patients_traitement_recent`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_patients_traitement_recent` (
`id_patient` int(11)
,`CIN` varchar(10)
,`nom` varchar(50)
,`prenom` varchar(50)
,`date_naissance` date
,`sexe` varchar(255)
,`adresse` varchar(100)
,`telephone` varchar(20)
,`email` varchar(50)
,`groupe_sanguin` varchar(255)
,`statut` varchar(255)
,`allergies` text
,`date_inscription` timestamp
,`id_medecin` int(11)
,`dernier_traitement` date
,`nombre_traitements` bigint(21)
,`medicaments_prescrits` mediumtext
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_statistiques_mensuelles`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_statistiques_mensuelles` (
`mois` varchar(7)
,`nombre_traitements` bigint(21)
,`nombre_patients` bigint(21)
,`traitements_avec_medicament` decimal(22,0)
,`longueur_moyenne_description` decimal(13,4)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_status_counts`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_status_counts` (
`statut` varchar(50)
,`service` varchar(100)
,`total` bigint(21)
,`year` int(4)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_top_services`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_top_services` (
`service` varchar(100)
,`total` bigint(21)
,`year` int(4)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_traitements_complets`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_traitements_complets` (
`id_traitement` int(11)
,`id_patient` int(11)
,`code_traitement` varchar(20)
,`description` text
,`date_traitement` date
,`medicament` varchar(255)
,`suivi` text
,`date_creation` datetime
,`date_modification` datetime
,`nom` varchar(50)
,`prenom` varchar(50)
,`date_naissance` date
,`telephone` varchar(20)
,`email` varchar(50)
,`cin` varchar(10)
,`groupe_sanguin` varchar(255)
,`age` bigint(21)
,`total_traitements_patient` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure de la vue `vw_admission_kpi`
--
DROP TABLE IF EXISTS `vw_admission_kpi`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_admission_kpi`  AS SELECT count(0) AS `total`, sum(case when `admissions`.`statut` = 'En cours' then 1 else 0 end) AS `en_cours`, sum(case when `admissions`.`statut` = 'Terminé' then 1 else 0 end) AS `termine` FROM `admissions` ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_admissions_age`
--
DROP TABLE IF EXISTS `v_admissions_age`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_admissions_age`  AS SELECT sum(case when timestampdiff(YEAR,`p`.`date_naissance`,`a`.`date_admission`) between 0 and 14 then 1 else 0 end) AS `0-14`, sum(case when timestampdiff(YEAR,`p`.`date_naissance`,`a`.`date_admission`) between 15 and 30 then 1 else 0 end) AS `15-30`, sum(case when timestampdiff(YEAR,`p`.`date_naissance`,`a`.`date_admission`) between 31 and 50 then 1 else 0 end) AS `31-50`, sum(case when timestampdiff(YEAR,`p`.`date_naissance`,`a`.`date_admission`) > 50 then 1 else 0 end) AS `+50`, year(`a`.`date_admission`) AS `year` FROM (`admissions` `a` join `patients` `p` on(`a`.`id_patient` = `p`.`id_patient`)) GROUP BY year(`a`.`date_admission`) ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_admissions_sexe`
--
DROP TABLE IF EXISTS `v_admissions_sexe`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_admissions_sexe`  AS SELECT `p`.`sexe` AS `sexe`, count(0) AS `total`, year(`a`.`date_admission`) AS `year` FROM (`admissions` `a` join `patients` `p` on(`a`.`id_patient` = `p`.`id_patient`)) GROUP BY `p`.`sexe`, year(`a`.`date_admission`) ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_admissions_type`
--
DROP TABLE IF EXISTS `v_admissions_type`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_admissions_type`  AS SELECT year(`admissions`.`date_admission`) AS `year`, `admissions`.`type_admission` AS `type_admission`, count(0) AS `total` FROM `admissions` GROUP BY year(`admissions`.`date_admission`), `admissions`.`type_admission` ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_avg_duration`
--
DROP TABLE IF EXISTS `v_avg_duration`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_avg_duration`  AS SELECT year(`a`.`date_admission`) AS `year`, avg(to_days(`a`.`date_sortie`) - to_days(`a`.`date_admission`)) AS `moyenne_jours` FROM `admissions` AS `a` WHERE `a`.`date_sortie` is not null GROUP BY year(`a`.`date_admission`) ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_patients_traitement_recent`
--
DROP TABLE IF EXISTS `v_patients_traitement_recent`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_patients_traitement_recent`  AS SELECT `p`.`id_patient` AS `id_patient`, `p`.`CIN` AS `CIN`, `p`.`nom` AS `nom`, `p`.`prenom` AS `prenom`, `p`.`date_naissance` AS `date_naissance`, `p`.`sexe` AS `sexe`, `p`.`adresse` AS `adresse`, `p`.`telephone` AS `telephone`, `p`.`email` AS `email`, `p`.`groupe_sanguin` AS `groupe_sanguin`, `p`.`statut` AS `statut`, `p`.`allergies` AS `allergies`, `p`.`date_inscription` AS `date_inscription`, `p`.`id_medecin` AS `id_medecin`, max(`t`.`date_traitement`) AS `dernier_traitement`, count(`t`.`id_traitement`) AS `nombre_traitements`, group_concat(distinct `t`.`medicament` separator ', ') AS `medicaments_prescrits` FROM (`patients` `p` left join `traitements` `t` on(`p`.`id_patient` = `t`.`id_patient`)) GROUP BY `p`.`id_patient` HAVING `dernier_traitement` >= curdate() - interval 90 day ORDER BY max(`t`.`date_traitement`) DESC ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_statistiques_mensuelles`
--
DROP TABLE IF EXISTS `v_statistiques_mensuelles`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_statistiques_mensuelles`  AS SELECT date_format(`traitements`.`date_traitement`,'%Y-%m') AS `mois`, count(0) AS `nombre_traitements`, count(distinct `traitements`.`id_patient`) AS `nombre_patients`, sum(case when `traitements`.`medicament` is not null and `traitements`.`medicament` <> '' then 1 else 0 end) AS `traitements_avec_medicament`, avg(octet_length(`traitements`.`description`)) AS `longueur_moyenne_description` FROM `traitements` GROUP BY date_format(`traitements`.`date_traitement`,'%Y-%m') ORDER BY date_format(`traitements`.`date_traitement`,'%Y-%m') DESC ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_status_counts`
--
DROP TABLE IF EXISTS `v_status_counts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_status_counts`  AS SELECT `a`.`statut` AS `statut`, `a`.`service` AS `service`, count(0) AS `total`, year(`a`.`date_admission`) AS `year` FROM `admissions` AS `a` GROUP BY `a`.`statut`, `a`.`service`, year(`a`.`date_admission`) ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_top_services`
--
DROP TABLE IF EXISTS `v_top_services`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_top_services`  AS SELECT `a`.`service` AS `service`, count(0) AS `total`, year(`a`.`date_admission`) AS `year` FROM `admissions` AS `a` GROUP BY `a`.`service`, year(`a`.`date_admission`) ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_traitements_complets`
--
DROP TABLE IF EXISTS `v_traitements_complets`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_traitements_complets`  AS SELECT `t`.`id_traitement` AS `id_traitement`, `t`.`id_patient` AS `id_patient`, `t`.`code_traitement` AS `code_traitement`, `t`.`description` AS `description`, `t`.`date_traitement` AS `date_traitement`, `t`.`medicament` AS `medicament`, `t`.`suivi` AS `suivi`, `t`.`date_creation` AS `date_creation`, `t`.`date_modification` AS `date_modification`, `p`.`nom` AS `nom`, `p`.`prenom` AS `prenom`, `p`.`date_naissance` AS `date_naissance`, `p`.`telephone` AS `telephone`, `p`.`email` AS `email`, `p`.`CIN` AS `cin`, `p`.`groupe_sanguin` AS `groupe_sanguin`, timestampdiff(YEAR,`p`.`date_naissance`,curdate()) AS `age`, (select count(0) from `traitements` `t2` where `t2`.`id_patient` = `t`.`id_patient`) AS `total_traitements_patient` FROM (`traitements` `t` join `patients` `p` on(`p`.`id_patient` = `t`.`id_patient`)) ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admissions`
--
ALTER TABLE `admissions`
  ADD PRIMARY KEY (`id_admission`);

--
-- Index pour la table `admissions_archive`
--
ALTER TABLE `admissions_archive`
  ADD PRIMARY KEY (`id_admission`,`archived_at`),
  ADD KEY `id_patient` (`id_patient`),
  ADD KEY `id_medecin` (`id_medecin`),
  ADD KEY `id_chambre` (`id_chambre`);

--
-- Index pour la table `antecedents`
--
ALTER TABLE `antecedents`
  ADD PRIMARY KEY (`id_ante`);

--
-- Index pour la table `archives_utilisateurs`
--
ALTER TABLE `archives_utilisateurs`
  ADD PRIMARY KEY (`id_archive`);

--
-- Index pour la table `chambres`
--
ALTER TABLE `chambres`
  ADD PRIMARY KEY (`id_chambre`),
  ADD UNIQUE KEY `numero_chambre` (`numero_chambre`);

--
-- Index pour la table `factures`
--
ALTER TABLE `factures`
  ADD PRIMARY KEY (`id_facture`);

--
-- Index pour la table `historique_statuts`
--
ALTER TABLE `historique_statuts`
  ADD PRIMARY KEY (`id_historique`);

--
-- Index pour la table `historique_suppressions`
--
ALTER TABLE `historique_suppressions`
  ADD PRIMARY KEY (`id_suppression`);

--
-- Index pour la table `historique_traitements`
--
ALTER TABLE `historique_traitements`
  ADD PRIMARY KEY (`id_historique`);

--
-- Index pour la table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id_patient`);

--
-- Index pour la table `patients_archive`
--
ALTER TABLE `patients_archive`
  ADD PRIMARY KEY (`id_patient`);

--
-- Index pour la table `planning_soins`
--
ALTER TABLE `planning_soins`
  ADD PRIMARY KEY (`id_planning`);

--
-- Index pour la table `prestations`
--
ALTER TABLE `prestations`
  ADD PRIMARY KEY (`id_prestation`);

--
-- Index pour la table `soins_patients`
--
ALTER TABLE `soins_patients`
  ADD PRIMARY KEY (`id_soin_patient`);

--
-- Index pour la table `statistiques_systeme`
--
ALTER TABLE `statistiques_systeme`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `suivis`
--
ALTER TABLE `suivis`
  ADD PRIMARY KEY (`id_suivi`),
  ADD KEY `id_patient` (`id_patient`),
  ADD KEY `id_medecin` (`id_medecin`);

--
-- Index pour la table `traitements`
--
ALTER TABLE `traitements`
  ADD PRIMARY KEY (`id_traitement`),
  ADD UNIQUE KEY `code_traitement` (`code_traitement`),
  ADD KEY `idx_patient` (`id_patient`),
  ADD KEY `idx_date_traitement` (`date_traitement`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admissions`
--
ALTER TABLE `admissions`
  MODIFY `id_admission` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `antecedents`
--
ALTER TABLE `antecedents`
  MODIFY `id_ante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT pour la table `archives_utilisateurs`
--
ALTER TABLE `archives_utilisateurs`
  MODIFY `id_archive` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `chambres`
--
ALTER TABLE `chambres`
  MODIFY `id_chambre` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `factures`
--
ALTER TABLE `factures`
  MODIFY `id_facture` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `historique_statuts`
--
ALTER TABLE `historique_statuts`
  MODIFY `id_historique` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `historique_suppressions`
--
ALTER TABLE `historique_suppressions`
  MODIFY `id_suppression` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `historique_traitements`
--
ALTER TABLE `historique_traitements`
  MODIFY `id_historique` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `patients`
--
ALTER TABLE `patients`
  MODIFY `id_patient` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT pour la table `patients_archive`
--
ALTER TABLE `patients_archive`
  MODIFY `id_patient` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT pour la table `planning_soins`
--
ALTER TABLE `planning_soins`
  MODIFY `id_planning` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `prestations`
--
ALTER TABLE `prestations`
  MODIFY `id_prestation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `soins_patients`
--
ALTER TABLE `soins_patients`
  MODIFY `id_soin_patient` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `suivis`
--
ALTER TABLE `suivis`
  MODIFY `id_suivi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `traitements`
--
ALTER TABLE `traitements`
  MODIFY `id_traitement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `suivis`
--
ALTER TABLE `suivis`
  ADD CONSTRAINT `suivis_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patients` (`id_patient`),
  ADD CONSTRAINT `suivis_ibfk_2` FOREIGN KEY (`id_medecin`) REFERENCES `utilisateurs` (`id_user`);

--
-- Contraintes pour la table `traitements`
--
ALTER TABLE `traitements`
  ADD CONSTRAINT `traitements_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patients` (`id_patient`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
