-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 27, 2025 at 02:02 PM
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
(8, 'F001', 'John ', 'Smith', 'Computer Science', 'active', '2025-05-27 06:11:37'),
(9, 'F002', 'Maria ', 'Garcia', 'Mathematics', 'active', '2025-05-27 06:12:01'),
(10, 'F003', 'David', 'Wilson', 'Physics', 'active', '2025-05-27 06:12:22'),
(11, 'F004', 'Sarah', 'Johnson', 'English', 'active', '2025-05-27 06:12:44'),
(14, 'F005', 'Bruce', 'Wayne', 'Computer Science', 'active', '2025-05-27 11:51:44');

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
(78, 11, 'rate 1', 'rating', 'cat 1', 1),
(79, 11, 'rate 2', 'rating', 'cat 2', 2),
(80, 11, 'rate 3', 'rating', 'cat 3', 3),
(82, 13, 'The instructor explains the subject matter clearly and effectively', 'rating', 'Teaching Effectiveness', 1),
(83, 13, 'The instructor uses appropriate teaching methods and materials', 'rating', 'Teaching Effectiveness', 2),
(84, 13, 'The instructor encourages student participation and engagement', 'rating', 'Teaching Effectiveness', 3),
(85, 13, 'The instructor starts and ends the class on time', 'rating', 'Class Management', 4),
(86, 13, 'The instructor maintains order and discipline in the classroom', 'rating', 'Class Management', 5),
(87, 13, 'The instructor provides helpful feedback on student work', 'rating', 'Student Development', 6),
(88, 13, 'The instructor shows concern for student learning and progress', 'rating', 'Student Development', 7),
(89, 13, 'The instructor demonstrates mastery of the subject matter', 'rating', 'Professional Qualities', 8),
(90, 13, 'The instructor is professional in appearance and behavior', 'rating', 'Professional Qualities', 9),
(91, 13, 'Overall, how would you rate this instructor?', 'rating', 'Overall Assessment', 10),
(92, 14, 'what haffen vella?', 'text', 'cat 1', 1),
(93, 14, 'rate me.', 'rating', 'cat 2', 2),
(94, 15, 'test ', 'rating', 'cat 1', 2),
(95, 15, 'test 2', 'text', 'cat 2', 1);

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
(30, 1, 8, 11, 4, '2025-05-27 06:37:10'),
(31, 1, 9, 11, 4, '2025-05-27 06:37:15'),
(32, 1, 11, 11, 4, '2025-05-27 06:37:21'),
(33, 1, 10, 11, 4, '2025-05-27 06:37:26'),
(34, 2, 9, 13, 5, '2025-05-27 06:47:44'),
(35, 2, 10, 13, 5, '2025-05-27 06:48:02'),
(36, 2, 11, 13, 5, '2025-05-27 06:48:17'),
(37, 2, 8, 13, 5, '2025-05-27 06:48:33'),
(38, 2, 8, 14, 5, '2025-05-27 06:51:42'),
(39, 2, 11, 14, 5, '2025-05-27 06:51:53'),
(40, 1, 8, 14, 5, '2025-05-27 11:25:32'),
(41, 1, 10, 14, 5, '2025-05-27 11:26:24'),
(42, 1, 14, 13, 5, '2025-05-27 11:55:06');

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
(182, 30, 78, 3, NULL),
(183, 30, 79, 2, NULL),
(184, 30, 80, 4, NULL),
(185, 31, 78, 4, NULL),
(186, 31, 79, 5, NULL),
(187, 31, 80, 4, NULL),
(188, 32, 78, 1, NULL),
(189, 32, 79, 5, NULL),
(190, 32, 80, 2, NULL),
(191, 33, 78, 1, NULL),
(192, 33, 79, 1, NULL),
(193, 33, 80, 1, NULL),
(194, 34, 82, 5, NULL),
(195, 34, 83, 5, NULL),
(196, 34, 84, 5, NULL),
(197, 34, 85, 5, NULL),
(198, 34, 86, 5, NULL),
(199, 34, 87, 5, NULL),
(200, 34, 88, 5, NULL),
(201, 34, 89, 5, NULL),
(202, 34, 90, 5, NULL),
(203, 34, 91, 5, NULL),
(204, 35, 82, 4, NULL),
(205, 35, 83, 4, NULL),
(206, 35, 84, 4, NULL),
(207, 35, 85, 4, NULL),
(208, 35, 86, 4, NULL),
(209, 35, 87, 4, NULL),
(210, 35, 88, 4, NULL),
(211, 35, 89, 4, NULL),
(212, 35, 90, 4, NULL),
(213, 35, 91, 4, NULL),
(214, 36, 82, 3, NULL),
(215, 36, 83, 3, NULL),
(216, 36, 84, 3, NULL),
(217, 36, 85, 3, NULL),
(218, 36, 86, 3, NULL),
(219, 36, 87, 3, NULL),
(220, 36, 88, 3, NULL),
(221, 36, 89, 3, NULL),
(222, 36, 90, 3, NULL),
(223, 36, 91, 3, NULL),
(224, 37, 82, 1, NULL),
(225, 37, 83, 1, NULL),
(226, 37, 84, 1, NULL),
(227, 37, 85, 1, NULL),
(228, 37, 86, 1, NULL),
(229, 37, 87, 1, NULL),
(230, 37, 88, 1, NULL),
(231, 37, 89, 1, NULL),
(232, 37, 90, 1, NULL),
(233, 37, 91, 1, NULL),
(234, 38, 92, NULL, 'wala ra'),
(235, 38, 93, 3, NULL),
(236, 39, 92, NULL, 'kjsdlkfjlskdj '),
(237, 39, 93, 1, NULL),
(238, 40, 92, NULL, 'i know'),
(239, 40, 93, 3, NULL),
(240, 41, 92, NULL, 'weweew'),
(241, 41, 93, 5, NULL),
(242, 42, 82, 4, NULL),
(243, 42, 83, 1, NULL),
(244, 42, 84, 1, NULL),
(245, 42, 85, 1, NULL),
(246, 42, 86, 1, NULL),
(247, 42, 87, 1, NULL),
(248, 42, 88, 1, NULL),
(249, 42, 89, 2, NULL),
(250, 42, 90, 3, NULL),
(251, 42, 91, 3, NULL);

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
(4, '1st Semester 2024-2025', '2025-05-14', '2025-05-30', 0, '2025-05-27 06:13:40'),
(5, '2nd Semester 2024-2025', '2025-05-14', '2025-07-24', 1, '2025-05-27 06:45:59'),
(6, '1st Semester 2023-2024', '2023-08-21', '2023-12-15', 0, '2025-05-27 11:43:34');

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
(3, 7, '5220666', 'Lebron', 'James', 'EDUCATION', 4, '5220666_1748345085.jpg', 'pending');

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
(11, 'Faculty Evaluation 2024', 'dsfasdf sfdaasdf ', 4, '2025-05-14', '2025-05-28', 'active', '2025-05-27 06:14:13'),
(13, 'Survey 1', 'test 1 ', 5, '2025-05-27', '2025-05-28', 'active', '2025-05-27 06:46:47'),
(14, 'survey 2', 'test 2', 5, '2025-05-27', '2025-05-31', 'active', '2025-05-27 06:49:38'),
(15, 'survey 3', 'test 3', 5, '2025-05-27', '2025-05-30', 'draft', '2025-05-27 11:30:12');

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
(7, 'lebron', '$2y$10$C3v97U0QKs9s.bDXb.nINeo0vSNxbV.L1BgWaETBZxIkDW/LJXevy', 'lebron@gmail.com', 'student', '2025-05-27 11:08:15');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `responses`
--
ALTER TABLE `responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `response_details`
--
ALTER TABLE `response_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=252;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `surveys`
--
ALTER TABLE `surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
