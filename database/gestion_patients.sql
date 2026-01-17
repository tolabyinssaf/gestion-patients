-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
<<<<<<< HEAD
-- Généré le : mar. 23 déc. 2025 à 00:55
=======
-- Généré le : mer. 24 déc. 2025 à 21:01
>>>>>>> dd9db1a122bc7dbde914fe7d134d652a88119cfe
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion_patients`
--

DELIMITER $$
--
-- Procédures
--
<<<<<<< HEAD
CREATE DEFINER=`root`@`localhost` PROCEDURE `ajouter_suivi` (IN `p_id_patient` INT, IN `p_date_suivi` DATE, IN `p_commentaire` TEXT, IN `p_status` VARCHAR(20))   BEGIN
    DECLARE date_actuelle DATE;
    SET date_actuelle = CURDATE();
    
  
    IF p_date_suivi < date_actuelle THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La date du suivi ne peut pas être antérieure à aujourd''hui';
    ELSE
        INSERT INTO suivis (id_patient, date_suivi, commentaire, status)
        VALUES (p_id_patient, p_date_suivi, p_commentaire, p_status);
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
=======
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_ajouter_traitement` (IN `p_id_patient` INT, IN `p_description` TEXT, IN `p_date_traitement` DATE, IN `p_medicament` VARCHAR(50), IN `p_suivi` TEXT, OUT `p_id_traitement` INT, OUT `p_message` VARCHAR(255))   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_id_traitement = 0;
        SET p_message = 'Erreur lors de l’ajout du traitement';
        ROLLBACK;
    END;

    START TRANSACTION;

    INSERT INTO traitements (
        id_patient,
        description,
        date_traitement,
        medicament,
        suivi
    ) VALUES (
        p_id_patient,
        p_description,
        p_date_traitement,
        p_medicament,
        p_suivi
    );

    SET p_id_traitement = LAST_INSERT_ID();
    SET p_message = 'Traitement ajouté avec succès';

    COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_statistiques` ()   BEGIN
    -- Statistiques globales
    SELECT 
        (SELECT COUNT(*) FROM patients) as total_patients,
        (SELECT COUNT(*) FROM traitements) as total_traitements,
        (SELECT COUNT(DISTINCT id_patient) FROM traitements) as patients_avec_traitement,
        (SELECT COUNT(*) FROM traitements WHERE medicament IS NOT NULL AND medicament != '') as traitements_avec_medicament;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_modifier_traitement` (IN `p_id_traitement` INT, IN `p_id_patient` INT, IN `p_description` TEXT, IN `p_date_traitement` DATE, IN `p_medicament` VARCHAR(255), IN `p_suivi` TEXT, OUT `p_success` BOOLEAN, OUT `p_message` VARCHAR(255))   BEGIN DECLARE v_traitement_exists INT; 
DECLARE v_patient_exists INT;
-- Vérifier si le traitement existe 
SELECT COUNT(*) INTO v_traitement_exists FROM traitements WHERE id_traitement = p_id_traitement; -- Vérifier si le patient existe 
SELECT COUNT(*) INTO v_patient_exists FROM patients WHERE id_patient = p_id_patient;
IF v_traitement_exists = 0 THEN
SET p_success = FALSE;
SET p_message = 'Erreur: Traitement non trouvé'; 
ELSEIF v_patient_exists = 0 THEN
SET p_success = FALSE;
SET p_message = 'Erreur: Patient non trouvé';
ELSE
-- Mise à jour du traitement 
UPDATE traitements SET id_patient = p_id_patient, description = p_description, date_traitement = p_date_traitement, medicament = p_medicament, suivi = p_suivi, date_modification = NOW() WHERE id_traitement = p_id_traitement;
SET p_success = TRUE; 
SET p_message = 'Traitement modifié avec succès'; 
END IF; 
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_supprimer_traitement` (IN `p_id_traitement` INT)   BEGIN
    DECLARE anciennes_valeurs JSON;
    
    -- Récupérer les anciennes valeurs
    SELECT JSON_OBJECT(
        'patient_id', id_patient,
        'description', description,
        'date', date_traitement
    ) INTO anciennes_valeurs
    FROM traitements
    WHERE id_traitement = p_id_traitement;
    
    -- Supprimer le traitement
    DELETE FROM traitements WHERE id_traitement = p_id_traitement;
    
    -- Journaliser l'action
    INSERT INTO historique_traitements (id_traitement, action_type, anciennes_valeurs, utilisateur)
    VALUES (p_id_traitement, 'DELETE', anciennes_valeurs, CURRENT_USER());
    
    SELECT 'Traitement supprimé avec succès' as message;
END$$

--
-- Fonctions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_calculer_age` (`date_naissance` DATE) RETURNS INT(11) DETERMINISTIC BEGIN
    RETURN TIMESTAMPDIFF(YEAR, date_naissance, CURDATE());
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `fn_generer_code_traitement` () RETURNS VARCHAR(20) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC BEGIN
    DECLARE annee CHAR(4);
    DECLARE sequence INT;
    DECLARE code VARCHAR(20);
    
    SET annee = YEAR(CURDATE());
    
    SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(code_traitement, '-', -1) AS UNSIGNED)), 0) + 1
    INTO sequence
    FROM traitements
    WHERE code_traitement LIKE CONCAT('TRT-', annee, '-%');
    
    SET code = CONCAT('TRT-', annee, '-', LPAD(sequence, 5, '0'));
    
    RETURN code;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `fn_nombre_traitements_patient` (`patient_id` INT) RETURNS INT(11) DETERMINISTIC BEGIN
    DECLARE total INT;
    SELECT COUNT(*) INTO total FROM traitements WHERE id_patient = patient_id;
    RETURN COALESCE(total, 0);
>>>>>>> dd9db1a122bc7dbde914fe7d134d652a88119cfe
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `admissions`
--

CREATE TABLE `admissions` (
  `id_admission` int(11) NOT NULL,
  `id_patient` int(11) DEFAULT NULL,
  `date_admission` date DEFAULT NULL,
  `service` varchar(50) DEFAULT NULL,
  `motif` text DEFAULT NULL,
  `status` enum('En cours','Terminé') DEFAULT 'En cours'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `admissions`
--

INSERT INTO `admissions` (`id_admission`, `id_patient`, `date_admission`, `service`, `motif`, `status`) VALUES
(1, 1, '2025-12-18', 'A', 'Check-up', 'En cours');

-- --------------------------------------------------------

--
<<<<<<< HEAD
=======
-- Structure de la table `historique_traitements`
--

CREATE TABLE `historique_traitements` (
  `id_historique` int(11) NOT NULL,
  `id_traitement` int(11) NOT NULL,
  `action_type` enum('CREATE','UPDATE','DELETE') NOT NULL,
  `anciennes_valeurs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`anciennes_valeurs`)),
  `nouvelles_valeurs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`nouvelles_valeurs`)),
  `utilisateur` varchar(100) DEFAULT NULL,
  `date_action` datetime DEFAULT current_timestamp(),
  `date_modification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
>>>>>>> dd9db1a122bc7dbde914fe7d134d652a88119cfe
-- Structure de la table `patients`
--

CREATE TABLE `patients` (
  `id_patient` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `sexe` enum('M','F') DEFAULT NULL,
  `adresse` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
<<<<<<< HEAD
  `date_inscription` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_medecin` int(11) NOT NULL
=======
  `mot_de_passe` varchar(255) DEFAULT NULL,
  `date_inscription` timestamp NOT NULL DEFAULT current_timestamp()
>>>>>>> dd9db1a122bc7dbde914fe7d134d652a88119cfe
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `patients`
--

<<<<<<< HEAD
INSERT INTO `patients` (`id_patient`, `nom`, `prenom`, `date_naissance`, `sexe`, `adresse`, `telephone`, `email`, `date_inscription`, `id_medecin`) VALUES
(1, 'Tolaby', 'Inssaf', '2000-01-01', 'F', 'Rabat', '0612345678', 'tolabyinssaf@test.com', '2025-12-18 22:24:28', 0),
(2, 'Belkheiri', 'Houda', '2000-01-01', 'F', 'Rabat', '0612345678', 'houdabelkheiri@test.com', '2025-12-18 22:24:28', 0),
(3, 'awssaf', 'Awssaf', '2000-01-01', 'F', 'Rabat', '0612345678', 'awssaf@test.com', '2025-12-18 22:24:28', 0),
(11, 'rabie', 'tolaby', '2007-01-31', '', 'kenitra', '0671344507', 'rabie@test.com', '2025-12-21 23:00:00', 1),
(12, 'rim', 'rim', '2005-10-22', 'F', 'tetouane', '0671344502', 'rim@test.com', '2025-12-21 23:00:00', 1);

--
-- Déclencheurs `patients`
--
DELIMITER $$
CREATE TRIGGER `before_insert_patient` BEFORE INSERT ON `patients` FOR EACH ROW BEGIN
   
    IF EXISTS (SELECT 1 FROM patients WHERE email = NEW.email) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Email déjà utilisé';
    END IF;

  
END
$$
DELIMITER ;
=======
INSERT INTO `patients` (`id_patient`, `nom`, `prenom`, `date_naissance`, `sexe`, `adresse`, `telephone`, `email`, `mot_de_passe`, `date_inscription`) VALUES
(1, 'Tolaby', 'Inssaf', '2000-01-01', 'F', 'Rabat', '0612345678', 'tolabyinssaf@test.com', '12345', '2025-12-18 22:24:28'),
(2, 'Belkheiri', 'Houda', '2000-01-01', 'F', 'Rabat', '0612345678', 'houdabelkheiri@test.com', '123456', '2025-12-18 22:24:28'),
(3, 'awssaf', 'Awssaf', '2000-01-01', 'F', 'Rabat', '0612345678', 'awssaf@test.com', '1234567', '2025-12-18 22:24:28');
>>>>>>> dd9db1a122bc7dbde914fe7d134d652a88119cfe

-- --------------------------------------------------------

--
-- Structure de la table `suivis`
--

CREATE TABLE `suivis` (
  `id_suivi` int(11) NOT NULL,
  `id_patient` int(11) DEFAULT NULL,
  `date_suivi` date DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `status` enum('En cours','Terminé') DEFAULT 'En cours'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `traitements`
--

CREATE TABLE `traitements` (
  `id_traitement` int(11) NOT NULL,
<<<<<<< HEAD
  `id_patient` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `date_traitement` date DEFAULT NULL,
  `medicament` varchar(50) DEFAULT NULL,
  `suivi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

=======
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
(1, 1, 'TRT-2025-00001', 'Consultation générale avec examen physique complet', '2024-01-10', 'Paracétamol 500mg', 'Prise 3 fois par jour pendant 5 jours', '2025-12-24 18:21:20', '2025-12-24 18:21:20'),
(2, 2, 'TRT-2025-00002', 'Suivi post-opératoire avec changement de pansement', '2024-01-12', 'Ibuprofène 400mg', 'Repos recommandé pendant 7 jours', '2025-12-24 18:21:20', '2025-12-24 18:21:20'),
(3, 1, 'TRT-2025-00003', 'Contrôle tension artérielle', '2024-01-15', NULL, 'Tension stabilisée à 130/80', '2025-12-24 18:21:20', '2025-12-24 18:21:20'),
(4, 3, 'TRT-2025-00004', 'hello world are you ready', '2025-12-24', '', '', '2025-12-24 18:24:04', '2025-12-24 20:58:52'),
(5, 2, 'TRT-2025-00005', 'hello everyone thisis a description', '2025-12-24', '', '', '2025-12-24 20:55:21', '2025-12-24 20:55:21');

--
-- Déclencheurs `traitements`
--
DELIMITER $$
CREATE TRIGGER `tr_before_insert_traitement` BEFORE INSERT ON `traitements` FOR EACH ROW BEGIN
    IF NEW.date_traitement > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La date du traitement ne peut pas être dans le futur';
    END IF;
    
    -- Générer un code si vide
    IF NEW.code_traitement IS NULL THEN
        SET NEW.code_traitement = fn_generer_code_traitement();
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_before_update_traitement` BEFORE UPDATE ON `traitements` FOR EACH ROW BEGIN
    IF NEW.date_traitement > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La date du traitement ne peut pas être dans le futur';
    END IF;
END
$$
DELIMITER ;

>>>>>>> dd9db1a122bc7dbde914fe7d134d652a88119cfe
-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id_user` int(11) NOT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `prenom` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `mot_de_passe` varchar(255) DEFAULT NULL,
  `role` enum('patient','admin','medecin') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

<<<<<<< HEAD
--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id_user`, `nom`, `prenom`, `email`, `mot_de_passe`, `role`) VALUES
(1, 'tolaby', 'inssaf', 'inssaf@gmail.com', '123456', 'medecin');
=======
-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_patients_traitement_recent`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_patients_traitement_recent` (
`id_patient` int(11)
,`nom` varchar(50)
,`prenom` varchar(50)
,`date_naissance` date
,`sexe` enum('M','F')
,`adresse` varchar(100)
,`telephone` varchar(20)
,`email` varchar(50)
,`mot_de_passe` varchar(255)
,`date_inscription` timestamp
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
-- Doublure de structure pour la vue `v_traitements_complets`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_traitements_complets` (
`id_traitement` int(11)
,`description` text
,`date_traitement` date
,`medicament` varchar(255)
,`suivi` text
,`date_creation` datetime
,`date_modification` datetime
,`id_patient` int(11)
,`nom` varchar(50)
,`prenom` varchar(50)
,`date_naissance` date
,`telephone` varchar(20)
,`email` varchar(50)
,`age` int(11)
,`total_traitements` int(11)
);

-- --------------------------------------------------------

--
-- Structure de la vue `v_patients_traitement_recent`
--
DROP TABLE IF EXISTS `v_patients_traitement_recent`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_patients_traitement_recent`  AS SELECT `p`.`id_patient` AS `id_patient`, `p`.`nom` AS `nom`, `p`.`prenom` AS `prenom`, `p`.`date_naissance` AS `date_naissance`, `p`.`sexe` AS `sexe`, `p`.`adresse` AS `adresse`, `p`.`telephone` AS `telephone`, `p`.`email` AS `email`, `p`.`mot_de_passe` AS `mot_de_passe`, `p`.`date_inscription` AS `date_inscription`, max(`t`.`date_traitement`) AS `dernier_traitement`, count(`t`.`id_traitement`) AS `nombre_traitements`, group_concat(distinct `t`.`medicament` separator ', ') AS `medicaments_prescrits` FROM (`patients` `p` left join `traitements` `t` on(`p`.`id_patient` = `t`.`id_patient`)) GROUP BY `p`.`id_patient` HAVING `dernier_traitement` >= curdate() - interval 90 day ORDER BY max(`t`.`date_traitement`) DESC ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_statistiques_mensuelles`
--
DROP TABLE IF EXISTS `v_statistiques_mensuelles`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_statistiques_mensuelles`  AS SELECT date_format(`traitements`.`date_traitement`,'%Y-%m') AS `mois`, count(0) AS `nombre_traitements`, count(distinct `traitements`.`id_patient`) AS `nombre_patients`, sum(case when `traitements`.`medicament` is not null and `traitements`.`medicament` <> '' then 1 else 0 end) AS `traitements_avec_medicament`, avg(octet_length(`traitements`.`description`)) AS `longueur_moyenne_description` FROM `traitements` GROUP BY date_format(`traitements`.`date_traitement`,'%Y-%m') ORDER BY date_format(`traitements`.`date_traitement`,'%Y-%m') DESC ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_traitements_complets`
--
DROP TABLE IF EXISTS `v_traitements_complets`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_traitements_complets`  AS SELECT `t`.`id_traitement` AS `id_traitement`, `t`.`description` AS `description`, `t`.`date_traitement` AS `date_traitement`, `t`.`medicament` AS `medicament`, `t`.`suivi` AS `suivi`, `t`.`date_creation` AS `date_creation`, `t`.`date_modification` AS `date_modification`, `p`.`id_patient` AS `id_patient`, `p`.`nom` AS `nom`, `p`.`prenom` AS `prenom`, `p`.`date_naissance` AS `date_naissance`, `p`.`telephone` AS `telephone`, `p`.`email` AS `email`, `fn_calculer_age`(`p`.`date_naissance`) AS `age`, `fn_nombre_traitements_patient`(`p`.`id_patient`) AS `total_traitements` FROM (`traitements` `t` join `patients` `p` on(`t`.`id_patient` = `p`.`id_patient`)) ORDER BY `t`.`date_traitement` DESC ;
>>>>>>> dd9db1a122bc7dbde914fe7d134d652a88119cfe

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admissions`
--
ALTER TABLE `admissions`
  ADD PRIMARY KEY (`id_admission`),
  ADD KEY `id_patient` (`id_patient`);

--
<<<<<<< HEAD
=======
-- Index pour la table `historique_traitements`
--
ALTER TABLE `historique_traitements`
  ADD PRIMARY KEY (`id_historique`),
  ADD KEY `idx_id_traitement` (`id_traitement`),
  ADD KEY `idx_date_action` (`date_action`);

--
>>>>>>> dd9db1a122bc7dbde914fe7d134d652a88119cfe
-- Index pour la table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id_patient`);

--
-- Index pour la table `suivis`
--
ALTER TABLE `suivis`
  ADD PRIMARY KEY (`id_suivi`),
  ADD KEY `id_patient` (`id_patient`);

--
-- Index pour la table `traitements`
--
ALTER TABLE `traitements`
  ADD PRIMARY KEY (`id_traitement`),
<<<<<<< HEAD
  ADD KEY `id_patient` (`id_patient`);
=======
  ADD UNIQUE KEY `code_traitement` (`code_traitement`),
  ADD KEY `idx_date_traitement` (`date_traitement`),
  ADD KEY `idx_patient` (`id_patient`);
>>>>>>> dd9db1a122bc7dbde914fe7d134d652a88119cfe

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
  MODIFY `id_admission` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
<<<<<<< HEAD
-- AUTO_INCREMENT pour la table `patients`
--
ALTER TABLE `patients`
  MODIFY `id_patient` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
=======
-- AUTO_INCREMENT pour la table `historique_traitements`
--
ALTER TABLE `historique_traitements`
  MODIFY `id_historique` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `patients`
--
ALTER TABLE `patients`
  MODIFY `id_patient` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
>>>>>>> dd9db1a122bc7dbde914fe7d134d652a88119cfe

--
-- AUTO_INCREMENT pour la table `suivis`
--
ALTER TABLE `suivis`
<<<<<<< HEAD
  MODIFY `id_suivi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
=======
  MODIFY `id_suivi` int(11) NOT NULL AUTO_INCREMENT;
>>>>>>> dd9db1a122bc7dbde914fe7d134d652a88119cfe

--
-- AUTO_INCREMENT pour la table `traitements`
--
ALTER TABLE `traitements`
<<<<<<< HEAD
  MODIFY `id_traitement` int(11) NOT NULL AUTO_INCREMENT;
=======
  MODIFY `id_traitement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
>>>>>>> dd9db1a122bc7dbde914fe7d134d652a88119cfe

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
<<<<<<< HEAD
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
=======
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT;
>>>>>>> dd9db1a122bc7dbde914fe7d134d652a88119cfe

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `admissions`
--
ALTER TABLE `admissions`
  ADD CONSTRAINT `admissions_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patients` (`id_patient`);

--
-- Contraintes pour la table `suivis`
--
ALTER TABLE `suivis`
  ADD CONSTRAINT `suivis_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patients` (`id_patient`);

--
-- Contraintes pour la table `traitements`
--
ALTER TABLE `traitements`
<<<<<<< HEAD
  ADD CONSTRAINT `traitements_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patients` (`id_patient`);
=======
  ADD CONSTRAINT `traitements_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patients` (`id_patient`) ON DELETE CASCADE;
>>>>>>> dd9db1a122bc7dbde914fe7d134d652a88119cfe
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
