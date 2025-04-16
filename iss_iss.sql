-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 16, 2025 at 04:38 PM
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
-- Database: `cis355`
--

-- --------------------------------------------------------

--
-- Table structure for table `iss_iss`
--

CREATE TABLE `iss_iss` (
  `id` int(11) NOT NULL,
  `short_description` varchar(255) NOT NULL,
  `long_description` text NOT NULL,
  `open_date` date NOT NULL,
  `close_date` date NOT NULL,
  `priority` varchar(255) NOT NULL,
  `org` varchar(255) NOT NULL,
  `project` varchar(255) NOT NULL,
  `per_id` int(11) NOT NULL,
  `pdf_attachment` varchar(255) DEFAULT NULL,
  `creator_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `iss_iss`
--

INSERT INTO `iss_iss` (`id`, `short_description`, `long_description`, `open_date`, `close_date`, `priority`, `org`, `project`, `per_id`, `pdf_attachment`, `creator_id`) VALUES
(1, 'cs451 solidity', 'The course, cs451 needs to be updated to include blockchains concepts and ethirum', '2025-02-19', '0000-00-00', 'C', '', '', 0, NULL, 0),
(2, 'cis335 Final', 'The final project for the class that is due April 18th', '2025-03-13', '2025-12-31', 'Low', 'SVSU', 'issues project', 1, './uploads/2311c7c983f736d890f9f8f4743133ee.pdf', 0),
(3, 'cis335 Final test', 'Testing to see if this works for sally to be the one to only access.', '2025-04-12', '2025-07-09', 'Medium', 'SVSU', 'issues project James', 2, NULL, 3),
(4, 'cis335 Final test test', 'Need to see if only James sees, updates and deletes this issue.', '2025-04-12', '2025-08-29', 'High', 'SVSU', 'issues project Sally', 3, NULL, 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `iss_iss`
--
ALTER TABLE `iss_iss`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `iss_iss`
--
ALTER TABLE `iss_iss`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
