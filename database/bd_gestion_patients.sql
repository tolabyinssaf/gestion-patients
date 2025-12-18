-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : jeu. 18 déc. 2025 à 23:26
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
-- Base de données : `gestion_patients`
--

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
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admissions`
--
ALTER TABLE `admissions`
  MODIFY `id_admission` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `admissions`
--
ALTER TABLE `admissions`
  ADD CONSTRAINT `admissions_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `patients` (`id_patient`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
