-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 10, 2025 at 08:28 PM
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
-- Database: `student_assessment`
--

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `id` int(11) NOT NULL,
  `faculty_number` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `department` varchar(100) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`id`, `faculty_number`, `first_name`, `last_name`, `department`, `status`, `created_at`) VALUES
(18, 'F1', 'Jimf B.', 'Bocales', 'I.T', 'active', '2025-06-10 17:44:57'),
(19, 'F2', 'Leonardo A. Jr.', 'Albit', 'I.T', 'active', '2025-06-10 17:46:07'),
(20, 'F3', 'Johnrel M.', 'Paglinawan', 'I.T', 'active', '2025-06-10 17:49:33'),
(21, 'F4', 'Alyssa Marie M. ', 'Paglinawan', 'I.T', 'active', '2025-06-10 17:50:05'),
(22, 'F5', 'Belinda Q. ', 'Orquia', 'I.T', 'active', '2025-06-10 17:50:31'),
(23, 'F6', 'Chenie Gae C.', 'Asendiente', 'I.T', 'active', '2025-06-10 17:51:04'),
(24, 'F7', 'Jae-an S.', 'Buhia', 'I.T', 'active', '2025-06-10 17:52:53'),
(25, 'F8', 'Teodoro S.', 'Buhia', 'I.T', 'active', '2025-06-10 17:55:52'),
(26, 'F9', 'Catherine P. ', 'Loseñara', 'I.T', 'active', '2025-06-10 17:58:30'),
(27, 'F10', 'John Vincent V.', 'Otto', 'I.T', 'active', '2025-06-10 17:59:33'),
(28, 'F11', 'Angel', 'Buot', 'I.T', 'active', '2025-06-10 17:59:58'),
(29, 'F12', 'Naomi A. ', 'Bajao', 'I.T', 'active', '2025-06-10 18:02:28'),
(30, 'F13', 'Rhianne', 'Castro', 'I.T', 'active', '2025-06-10 18:02:53'),
(31, 'F14', 'John Carl', 'Estrella ', 'I.T', 'active', '2025-06-10 18:03:28'),
(32, 'F15', 'Efreim Christian A.', 'Genson', 'I.T', 'active', '2025-06-10 18:04:31'),
(33, 'F16', 'Cristelles', 'Bascar', 'I.T', 'active', '2025-06-10 18:04:54'),
(34, 'F17', 'Zhyrus', 'Mapula', 'I.T', 'active', '2025-06-10 18:11:45');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `survey_id` int(11) DEFAULT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('rating','text') DEFAULT 'rating',
  `category` varchar(100) NOT NULL,
  `order_num` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `survey_id`, `question_text`, `question_type`, `category`, `order_num`) VALUES
(116, 16, 'The instructor explains the subject matter clearly and effectively', 'rating', 'Teaching Effectiveness', 1),
(117, 16, 'The instructor uses appropriate teaching methods and materials', 'rating', 'Teaching Effectiveness', 2),
(118, 16, 'The instructor encourages student participation and engagement', 'rating', 'Teaching Effectiveness', 3),
(119, 16, 'The instructor starts and ends the class on time', 'rating', 'Class Management', 4),
(120, 16, 'The instructor maintains order and discipline in the classroom', 'rating', 'Class Management', 5),
(121, 16, 'The instructor provides helpful feedback on student work', 'rating', 'Student Development', 6),
(122, 16, 'The instructor shows concern for student learning and progress', 'rating', 'Student Development', 7),
(123, 16, 'The instructor demonstrates mastery of the subject matter', 'rating', 'Professional Qualities', 8),
(124, 16, 'The instructor is professional in appearance and behavior', 'rating', 'Professional Qualities', 9),
(125, 16, 'Overall, how would you rate this instructor?', 'rating', 'Overall Assessment', 10);

-- --------------------------------------------------------

--
-- Table structure for table `responses`
--

CREATE TABLE `responses` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `faculty_id` int(11) DEFAULT NULL,
  `survey_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `responses`
--

INSERT INTO `responses` (`id`, `student_id`, `faculty_id`, `survey_id`, `semester_id`, `created_at`) VALUES
(49, 1, 33, 16, 7, '2025-06-10 18:23:47'),
(50, 2, 33, 16, 7, '2025-06-10 18:24:37'),
(51, 1, 30, 16, 7, '2025-06-10 18:25:27');

-- --------------------------------------------------------

--
-- Table structure for table `response_details`
--

CREATE TABLE `response_details` (
  `id` int(11) NOT NULL,
  `response_id` int(11) DEFAULT NULL,
  `question_id` int(11) DEFAULT NULL,
  `rating_value` int(11) DEFAULT NULL,
  `text_answer` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `response_details`
--

INSERT INTO `response_details` (`id`, `response_id`, `question_id`, `rating_value`, `text_answer`) VALUES
(304, 49, 116, 5, NULL),
(305, 49, 117, 5, NULL),
(306, 49, 118, 5, NULL),
(307, 49, 119, 5, NULL),
(308, 49, 120, 5, NULL),
(309, 49, 121, 5, NULL),
(310, 49, 122, 5, NULL),
(311, 49, 123, 5, NULL),
(312, 49, 124, 5, NULL),
(313, 49, 125, 5, NULL),
(314, 50, 116, 4, NULL),
(315, 50, 117, 4, NULL),
(316, 50, 118, 3, NULL),
(317, 50, 119, 4, NULL),
(318, 50, 120, 4, NULL),
(319, 50, 121, 4, NULL),
(320, 50, 122, 4, NULL),
(321, 50, 123, 4, NULL),
(322, 50, 124, 4, NULL),
(323, 50, 125, 4, NULL),
(324, 51, 116, 4, NULL),
(325, 51, 117, 4, NULL),
(326, 51, 118, 4, NULL),
(327, 51, 119, 4, NULL),
(328, 51, 120, 3, NULL),
(329, 51, 121, 5, NULL),
(330, 51, 122, 5, NULL),
(331, 51, 123, 5, NULL),
(332, 51, 124, 4, NULL),
(333, 51, 125, 4, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`id`, `name`, `start_date`, `end_date`, `is_active`, `created_at`) VALUES
(7, '1st Semester 2024-2025', '2024-08-26', '2025-12-19', 1, '2025-06-10 15:13:20');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `student_number` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `course` varchar(100) NOT NULL,
  `year_level` int(11) NOT NULL,
  `cor_file` varchar(255) DEFAULT NULL,
  `cor_status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `student_number`, `first_name`, `last_name`, `course`, `year_level`, `cor_file`, `cor_status`) VALUES
(1, 2, '5220111', 'Gemry Delle', 'Taparan', 'BSIT', 3, '5220111_1748320815.pdf', 'approved'),
(2, 6, '5220123', 'Kawhi', 'Leonard', 'BSIT', 3, '5220123_1748328265.jpg', 'approved'),
(3, 7, '5220666', 'Lebron', 'James', 'EDUCATION', 4, '5220666_1748345085.jpg', 'pending'),
(4, 8, '5220555', 'Jen', 'Lanorias', 'HM', 3, '5220555_1748394345.png', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `surveys`
--

CREATE TABLE `surveys` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('draft','active','closed') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `surveys`
--

INSERT INTO `surveys` (`id`, `title`, `description`, `semester_id`, `start_date`, `end_date`, `status`, `created_at`) VALUES
(16, 'Survey 1', 'test 1', 7, '2025-06-10', '2025-06-14', 'active', '2025-06-10 15:15:39');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','student') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `created_at`) VALUES
(2, 'gemry', '$2y$10$Ok040rQll0n5gvRbLhrMYelb5b6bzx6I9SIH.HV94N9pQ72jkmN5e', 'gemrydelle@gmail.com', 'student', '2025-05-27 04:17:49'),
(5, 'admin', '$2y$10$xuUdC/rHq7iJWbJaceklg.gMt5PYtPRULQD3KQ6qpj/11V1Is7N3K', 'admin@example.com', 'admin', '2025-05-27 04:32:16'),
(6, 'kawhi', '$2y$10$Vo.LgemVjWEdcskgFmcQseMkT205DlxdihM87dtAi43naQsXUZ3GO', 'kawhi@gmail.com', 'student', '2025-05-27 06:43:30'),
(7, 'lebron', '$2y$10$C3v97U0QKs9s.bDXb.nINeo0vSNxbV.L1BgWaETBZxIkDW/LJXevy', 'lebron@gmail.com', 'student', '2025-05-27 11:08:15'),
(8, 'jen', '$2y$10$C1hkW1RLZwjvRA.1fklwJeS5A6AUYZcQJAyhRECbK9EBzTUMahDRy', 'jen@gmail.com', 'student', '2025-05-28 01:03:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `faculty_number` (`faculty_number`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `survey_id` (`survey_id`);

--
-- Indexes for table `responses`
--
ALTER TABLE `responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `faculty_id` (`faculty_id`),
  ADD KEY `survey_id` (`survey_id`),
  ADD KEY `semester_id` (`semester_id`);

--
-- Indexes for table `response_details`
--
ALTER TABLE `response_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `response_id` (`response_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `surveys`
--
ALTER TABLE `surveys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `semester_id` (`semester_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `responses`
--
ALTER TABLE `responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `response_details`
--
ALTER TABLE `response_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=334;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `surveys`
--
ALTER TABLE `surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `responses`
--
ALTER TABLE `responses`
  ADD CONSTRAINT `responses_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `responses_ibfk_2` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`id`),
  ADD CONSTRAINT `responses_ibfk_3` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`),
  ADD CONSTRAINT `responses_ibfk_4` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`);

--
-- Constraints for table `response_details`
--
ALTER TABLE `response_details`
  ADD CONSTRAINT `response_details_ibfk_1` FOREIGN KEY (`response_id`) REFERENCES `responses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `response_details_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `surveys`
--
ALTER TABLE `surveys`
  ADD CONSTRAINT `surveys_ibfk_1` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
