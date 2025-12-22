-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 23 déc. 2025 à 00:55
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
  `date_inscription` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_medecin` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `patients`
--

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
  `id_patient` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `date_traitement` date DEFAULT NULL,
  `medicament` varchar(50) DEFAULT NULL,
  `suivi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id_user`, `nom`, `prenom`, `email`, `mot_de_passe`, `role`) VALUES
(1, 'tolaby', 'inssaf', 'inssaf@gmail.com', '123456', 'medecin');

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
  ADD KEY `id_patient` (`id_patient`);

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
-- AUTO_INCREMENT pour la table `patients`
--
ALTER TABLE `patients`
  MODIFY `id_patient` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `suivis`
--
ALTER TABLE `suivis`
  MODIFY `id_suivi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `traitements`
--
ALTER TABLE `traitements`
  MODIFY `id_traitement` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  ADD CONSTRAINT `traitements_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patients` (`id_patient`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
