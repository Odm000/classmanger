-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 01, 2025 at 04:31 PM
-- Server version: 5.7.34
-- PHP Version: 8.2.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `classflow`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT '',
  `score` decimal(6,2) DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `student_id`, `subject_id`, `title`, `score`, `created_at`) VALUES
(1, 2, 2, 'Laboratory 1', 88.00, '2025-11-29 15:40:14'),
(2, 6, 2, 'Laboratory 1', 96.00, '2025-11-29 15:49:23'),
(3, 6, 2, 'Quiz 1', 48.00, '2025-11-29 15:49:38'),
(4, 6, 2, 'Laboratory 2', 87.00, '2025-11-30 18:33:02'),
(5, 1, 1, 'Laboratory 1', 50.00, '2025-12-01 03:32:02'),
(6, 1, 1, 'Laboratory 2', 85.00, '2025-12-01 03:34:05'),
(7, 1, 1, 'Quiz 1', 15.00, '2025-12-01 03:36:23'),
(8, 1, 1, 'Laboratory 3', 43.00, '2025-12-01 03:44:39'),
(10, 1, 1, 'Quiz 2', 38.00, '2025-12-01 03:55:40');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `status` enum('present','absent') NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `subject_id`, `status`, `date`, `created_at`) VALUES
(1, 1, 1, 'present', '2025-11-29', '2025-11-29 15:37:52'),
(2, 2, 2, 'present', '2025-11-29', '2025-11-29 15:37:58'),
(3, 6, 2, 'present', '2025-11-29', '2025-11-29 15:52:17'),
(4, 4, 2, 'present', '2025-11-29', '2025-11-29 15:52:18'),
(5, 5, 2, 'present', '2025-11-29', '2025-11-29 15:52:20'),
(6, 3, 2, 'present', '2025-11-29', '2025-11-29 15:52:20'),
(7, 3, 2, 'absent', '2025-11-30', '2025-11-30 16:37:25'),
(8, 5, 2, 'present', '2025-11-30', '2025-11-30 16:37:26'),
(9, 2, 2, 'present', '2025-11-30', '2025-11-30 16:37:27'),
(10, 4, 2, 'present', '2025-11-30', '2025-11-30 16:37:27'),
(11, 6, 2, 'present', '2025-11-30', '2025-11-30 16:37:28'),
(12, 1, 1, 'present', '2025-12-01', '2025-12-01 05:14:48'),
(13, 48, 2, 'present', '2025-12-01', '2025-12-01 06:45:16'),
(14, 47, 3, 'present', '2025-12-01', '2025-12-01 06:45:21'),
(15, 2, 2, 'present', '2025-12-01', '2025-12-01 06:59:50'),
(16, 49, 3, 'absent', '2025-12-01', '2025-12-01 06:59:58'),
(17, 50, 3, 'present', '2025-12-01', '2025-12-01 07:17:28'),
(18, 51, 2, 'present', '2025-12-01', '2025-12-01 07:19:30'),
(19, 7, 1, 'present', '2025-12-01', '2025-12-01 07:19:39');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `prelim` decimal(5,2) DEFAULT '0.00',
  `midterm` decimal(5,2) DEFAULT '0.00',
  `finals` decimal(5,2) DEFAULT '0.00',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `student_id`, `subject_id`, `prelim`, `midterm`, `finals`, `updated_at`) VALUES
(1, 1, 1, 1.20, 0.00, 0.00, '2025-12-01 03:33:19'),
(2, 2, 2, 0.00, 0.00, 0.00, '2025-11-29 15:37:29'),
(3, 3, 2, 0.00, 0.00, 0.00, '2025-11-29 15:41:44'),
(4, 4, 2, 0.00, 0.00, 0.00, '2025-11-29 15:41:44'),
(5, 5, 2, 0.00, 0.00, 0.00, '2025-11-29 15:41:44'),
(6, 6, 2, 1.10, 1.10, 1.10, '2025-11-30 18:33:22'),
(7, 7, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(8, 8, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(9, 9, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(10, 10, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(11, 11, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(12, 12, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(13, 13, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(14, 14, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(15, 15, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(16, 16, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(17, 17, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(18, 18, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(19, 19, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(20, 20, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(21, 21, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(22, 22, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(23, 23, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(24, 24, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(25, 25, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(26, 26, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(27, 27, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(28, 28, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(29, 29, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(30, 30, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(31, 31, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(32, 32, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(33, 33, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(34, 34, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(35, 35, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(36, 36, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(37, 37, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(38, 38, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(39, 39, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(40, 40, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(41, 41, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(42, 42, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(43, 43, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(44, 44, 1, 0.00, 0.00, 0.00, '2025-12-01 05:29:15'),
(45, 45, 1, 0.00, 0.00, 0.00, '2025-12-01 06:22:02'),
(46, 46, 3, 0.00, 0.00, 0.00, '2025-12-01 06:23:06'),
(47, 47, 3, 0.00, 0.00, 0.00, '2025-12-01 06:42:38'),
(48, 48, 2, 0.00, 0.00, 0.00, '2025-12-01 06:44:58'),
(49, 49, 3, 0.00, 0.00, 0.00, '2025-12-01 06:59:20'),
(50, 50, 3, 0.00, 0.00, 0.00, '2025-12-01 07:17:17'),
(51, 51, 2, 0.00, 0.00, 0.00, '2025-12-01 07:19:15');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `course` varchar(100) DEFAULT '',
  `year_level` varchar(50) DEFAULT '',
  `avatar` varchar(255) DEFAULT 'assets/img/default-avatar.png',
  `archived` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `subject_id`, `lastname`, `firstname`, `course`, `year_level`, `avatar`, `archived`, `created_at`) VALUES
(1, 1, 'Doe', 'John', 'BSIT', '4th', 'uploads/1764430548_69249cc366a68.jpg', 0, '2025-11-29 15:23:40'),
(2, 2, 'Doe', 'John', 'BSIT', '4th', 'uploads/1764430663_69249cc366a68.jpg', 0, '2025-11-29 15:37:29'),
(3, 2, 'Agustin', 'Mark', 'BSIT', '4th', 'uploads/1764430978_Capture-1024x576.png', 0, '2025-11-29 15:41:44'),
(4, 2, 'Matthias', 'Bryan', 'BSIT', '4th', 'uploads/1764431000_CIDOO_V68_VIA_bf78b370-690a-4847-bac6-d8df07f730e4_1024x1024.jpg', 0, '2025-11-29 15:41:44'),
(49, 3, 'Doe ', 'John', 'BSIT', '4th', 'assets/img/default-avatar.png', 0, '2025-12-01 06:59:20'),
(6, 2, 'Morales', 'Oalden Lamper', 'BSIT', '4th', 'uploads/1764431344_1761724386_14726.jpg', 0, '2025-11-29 15:48:47'),
(7, 1, 'ABADILLA', 'ARVIN Burigsay', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(8, 1, 'ALEJANDRO', 'CLYDE FRANCIS ANGELO Asuncion', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(9, 1, 'AMOTOY', 'JESSA JANE Caldito', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(10, 1, 'ANCHETA', 'RANDOLPH Mandac', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(11, 1, 'CANDAROMA', 'ADRIAN Daguio', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(12, 1, 'CARLA', 'JOHN PATRICK Del Rosario', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(13, 1, 'CAYSIP', 'MARIA ISABEL Pedronan', '', '', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(14, 1, 'DAGUIO', 'AHRON CARL Llapitan', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(15, 1, 'DELA CRUZ', 'AARON JAMES Galvez', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(16, 1, 'DELA RAZAN', 'RON DANIEL Tolentino', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(17, 1, 'DISCION', 'CRISHA ANNE Monte', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(18, 1, 'DOMINGO', 'RUSSELL JOSH Batiforra', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(19, 1, 'DOROPAN', 'KEITH JUZRHYLLE Andres', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(20, 1, 'EUSTAQUIO', 'ASHLEY JAEL Fermin', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(21, 1, 'FREZ', 'NAYR THEODORE Agaran', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(22, 1, 'GANOTISI', 'RHUBEN', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(23, 1, 'GAOAT', 'JR.', 'LEOCADIO Pakig-angay', 'BSIT', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(24, 1, 'GAOIRAN', 'JERWIL VAL Centeno', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(25, 1, 'GUMAYAGAY', 'KINGSLEY VON GILCHRIST Galarpez', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(26, 1, 'JOVELLANOS', 'BRENNAN Macarubbo', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(27, 1, 'LEAÑO', 'GIAN ANGELO Malla', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(28, 1, 'LLAPITAN', 'ERIN ALLELI Pestaño', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(29, 1, 'NACURAY', 'B-JAY Vidad', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(30, 1, 'OANIA', 'PRINCE WILLIAM LEO Aguinaldo', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(31, 1, 'OCAMPO', 'CHRISTIAN Alonzo', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(32, 1, 'PASCUA', 'MELVIN', 'JR. Roldan', 'BSIT', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(33, 1, 'PERALTA', 'MARY ALYSSA ALOVES Guerrero', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(34, 1, 'RABANG', 'JHONRES Sawaan', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(35, 1, 'RINGPIS', 'PRINZ HARVEY Ruelos', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(36, 1, 'RIVERA', 'RICHLEE Regala', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(37, 1, 'RORALDO', 'WINSOME PAUL Marcelino', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(38, 1, 'SAMBAJON', 'JEE JAY Ganitano', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(39, 1, 'TABANIAG', 'DANNE JYCEE Martinez', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(40, 1, 'TAGABAN', 'JAMES BRYAN Bautista', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(41, 1, 'TIBURCIO', 'JOSE MIGUEL Dela Cruz', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(42, 1, 'VILLA', 'NEIL MARKSON Jacobe', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(43, 1, 'VILLANUEVA', 'EARL TROY Obando', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(44, 1, 'VILLANUEVA', 'EARL TYRO Obando', 'BSIT', '3rd year', 'assets/img/default-avatar.png', 0, '2025-12-01 05:29:15'),
(48, 2, ' CASTRO', 'Josh', 'BSIT', '4th', 'assets/img/default-avatar.png', 0, '2025-12-01 06:44:58'),
(47, 3, 'CASTRO', 'Josh', 'BSIT', '4th', 'uploads/1764576992_1764431000_CIDOO_V68_VIA_bf78b370-690a-4847-bac6-d8df07f730e4_1024x1024.jpg', 0, '2025-12-01 06:42:38'),
(51, 2, 'ABADILLA', 'ARVIN Burigsay', 'BSIT ', '3rd', 'assets/img/default-avatar.png', 0, '2025-12-01 07:19:15');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(100) DEFAULT '',
  `schedule` varchar(255) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `faculty_id`, `name`, `code`, `schedule`, `created_at`) VALUES
(1, 5, 'Web Systems and Technologies', 'WS101', 'M,T,TH,F - 5:00-7:40PM', '2025-11-29 15:23:18'),
(2, 5, 'Human Computer Interactions', 'HCI-101', 'M,W,F - 5:00-7:40PM', '2025-11-29 15:37:00'),
(3, 5, 'Networking 1', 'N-01', 'T,TH - 1:00-3:30PM', '2025-11-30 18:09:28');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(255) DEFAULT '',
  `role` enum('admin','faculty') NOT NULL DEFAULT 'faculty',
  `department` varchar(255) DEFAULT '',
  `avatar` varchar(255) DEFAULT 'assets/img/default-avatar.png',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `role`, `department`, `avatar`, `created_at`) VALUES
(1, 'admin', '$2a$12$8MLXcgg39Trnh00oJFfIVuOliHxXYvT13eWC89Nc/Mjvttzou1M1C', 'Administrator', 'admin', 'IT Department', 'assets/img/classflow-favicon.svg', '2025-11-29 15:08:04'),
(8, 'johnD', '$2y$10$7dYOTJi8Ib7l/fjfdVhg9Or.HkTxLvsS9DBYfjZPvAB9NL6YMWFoy', 'John Doe', 'faculty', 'SEAIT', 'assets/img/default-avatar.png', '2025-11-30 15:41:55'),
(5, 'oaldenm', '$2y$10$uP4eQD2dxtGeChRj6PvW.uW9cF37GPjUGSQMj9pQR1iuwkfPk9vkO', 'Oalden Morales', 'faculty', 'SEAIT', '../uploads/1764429755_1761724386_14726.jpg', '2025-11-29 15:19:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `faculty_id` (`faculty_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
