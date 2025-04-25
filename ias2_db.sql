-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 24, 2025 at 09:42 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ias2_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(6) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `firebase_uid` varchar(255) NOT NULL,
  `security_question1` varchar(255) NOT NULL,
  `security_answer1` varchar(255) NOT NULL,
  `security_question2` varchar(255) NOT NULL,
  `security_answer2` varchar(255) NOT NULL,
  `failed_attempts` int(11) DEFAULT 0,
  `verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `created_at`, `firebase_uid`, `security_question1`, `security_answer1`, `security_question2`, `security_answer2`, `failed_attempts`, `verified`, `verification_token`) VALUES
(20, 'cjsprite81@gmail.com', '$2y$10$rfS4a/kztCnEbxf3n8vN1ONKKCGhkpHgMc.ISpMZyW49naVaaoFhe', '2025-04-24 19:32:30', '458H1TWhCxUjk5Ny0DQ5A9uRLVr2', 'What was your first pet\'s name?', '$2y$10$OWk/JAHYyxV/da4cPGwGwesiZVajLWUEWHWl0O2v/IT19DbF6s3NS', 'What was your first car?', '$2y$10$Q3Ac/0/U7f9/3oZ0XD0tR.Wy42Rp.QRWCbPJLpX1QivRfA5tzaQt2', 0, 1, 'a6878f6678aafcccc6605c657ef946bca6363a50e55ef421d0b6b5c0dee7dd28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`email`),
  ADD UNIQUE KEY `firebase_uid` (`firebase_uid`),
  ADD UNIQUE KEY `username_2` (`email`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
