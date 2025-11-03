-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 26, 2025 at 09:45 PM
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
-- Database: `clrms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `borrowers`
--

CREATE TABLE `borrowers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('Office','Department','Faculty','Office/Department') NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrowers`
--

INSERT INTO `borrowers` (`id`, `name`, `type`, `contact_person`, `email`, `phone`, `location`, `status`, `created_at`, `updated_at`) VALUES
(1, 'IS Office', 'Office', 'Lyka Joy Empleo', 'lykajoy.empleo@chmsc.edu.ph', '09850858443', 'LSA Building 3rd Floor', 'Active', '2025-08-14 11:06:45', '2025-08-14 11:31:51');

-- --------------------------------------------------------

--
-- Table structure for table `borrow_items`
--

CREATE TABLE `borrow_items` (
  `id` int(11) NOT NULL,
  `borrow_slip_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `borrow_requests`
--

CREATE TABLE `borrow_requests` (
  `id` int(11) NOT NULL,
  `control_number` int(11) NOT NULL,
  `date_requested` datetime NOT NULL,
  `borrow_start` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
  `borrow_end` datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
  `borrower_name` varchar(100) NOT NULL COMMENT 'Name entered on the slip, not necessarily the user account name',
  `borrower_email` varchar(255) DEFAULT NULL,
  `course_year` varchar(50) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `signature_image` varchar(255) DEFAULT NULL,
  `datetime_needed` datetime DEFAULT NULL,
  `released_by` varchar(100) DEFAULT NULL,
  `id_picture` varchar(255) DEFAULT NULL,
  `tracking_code` varchar(20) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `purpose` varchar(255) NOT NULL,
  `location_of_use` varchar(255) NOT NULL,
  `return_date` datetime DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Returned') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrow_requests`
--

INSERT INTO `borrow_requests` (`id`, `control_number`, `date_requested`, `borrow_start`, `borrow_end`, `borrower_name`, `borrower_email`, `course_year`, `subject`, `signature_image`, `datetime_needed`, `released_by`, `id_picture`, `tracking_code`, `quantity`, `description`, `user_id`, `purpose`, `location_of_use`, `return_date`, `status`, `remarks`, `updated_at`) VALUES
(16, 14, '2025-08-11 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'Lyka', NULL, NULL, NULL, NULL, NULL, 'Rheign', 'uploads/borrower_ids/idpic_689981af0ddc6.jpg', NULL, NULL, NULL, NULL, 'Secret', 'Computer Lab 1', '2025-08-11 14:17:00', 'Returned', NULL, NULL),
(17, 15, '2025-08-12 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'Rheign', NULL, NULL, NULL, NULL, NULL, 'Joleah', 'uploads/borrower_ids/idpic_689988bda0c71.jpg', 'Q9728B9B', NULL, NULL, NULL, 'Try', 'Computer Lab 1', '2025-08-12 14:40:00', 'Returned', NULL, NULL),
(18, 16, '2025-08-11 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'Lyka Joy C. Empleo', 'ljeempleo.chmsu@gmail.com', NULL, NULL, NULL, NULL, 'Rheign', 'uploads/borrower_ids/idpic_68998b58c09ab.jpg', 'VGVLCQS3', NULL, NULL, NULL, 'try', 'Computer Lab 1', '2025-08-11 14:39:00', 'Returned', NULL, NULL),
(19, 17, '2025-08-11 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'Lyka', 'empleolykajoy@gmail.com', NULL, NULL, NULL, NULL, 'Rheign', 'uploads/borrower_ids/idpic_6899907e48dea.jpg', 'QQBV6ADQ', NULL, NULL, NULL, 'try', 'Computer Lab 5', '2025-08-11 15:02:00', 'Returned', NULL, NULL),
(20, 18, '2025-08-11 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'Lyka', 'empleolykajoy@gmail.com', NULL, NULL, NULL, NULL, 'Rheign', 'uploads/borrower_ids/idpic_6899953fce198.jpg', '3TNDST4J', NULL, NULL, NULL, 'try', 'Library', '2025-08-11 15:02:00', 'Returned', NULL, NULL),
(21, 19, '2025-08-11 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'Lyka', 'empleolykajoy@gmail.com', NULL, NULL, NULL, NULL, 'Rheign', 'uploads/borrower_ids/idpic_68999716e405f.jpg', 'CMNP4ZGA', NULL, NULL, NULL, 'try ulit', 'Computer Lab 2', '2025-08-11 21:07:00', 'Returned', NULL, NULL),
(22, 20, '2025-08-14 13:22:00', '2025-08-14 19:20:00', '2025-08-14 19:30:00', 'IS Office', 'lykajoy.empleo@chmsc.edu', NULL, NULL, NULL, NULL, 'Rheign Palmares', 'uploads/borrower_ids/idpic_689dc71cdb2cb.jpg', 'BNVX5M94', NULL, NULL, 10001, 'try', 'AVR', '2025-08-14 19:32:00', 'Returned', NULL, NULL),
(23, 21, '2025-08-14 13:30:00', '2025-08-14 19:31:00', '2025-08-14 19:41:00', 'IS Office', 'lykajoy.empleo@chmsc.edu', NULL, NULL, NULL, NULL, 'Rheign Palmares', 'uploads/borrower_ids/idpic_689dc8e795703.jpg', 'ESRK53FA', NULL, NULL, 10001, 'try', 'AVR', '2025-08-14 19:32:00', 'Returned', NULL, NULL),
(24, 22, '2025-08-14 13:32:00', '2025-08-14 19:31:00', '2025-08-14 19:40:00', 'IS Office', 'lykajoy.empleo@chmsc.edu.ph', NULL, NULL, NULL, NULL, 'Rheign Palmares', 'uploads/borrower_ids/idpic_689dc96a7a6eb.jpg', 'VPF4BSL6', NULL, NULL, 10001, 'try', 'AVR', '2025-08-14 19:38:00', 'Returned', NULL, NULL),
(25, 23, '2025-08-14 13:36:00', '2025-08-14 19:37:00', '2025-08-14 19:38:00', 'John Michael Kole', 'johnmichaelkole123@gmail.com', NULL, NULL, NULL, NULL, 'Rheign Palmares', 'uploads/borrower_ids/idpic_689dca92cd993.jpg', 'GDYCYZGH', NULL, NULL, 10001, 'try', 'Library', '2025-08-14 19:38:00', 'Returned', NULL, NULL),
(26, 24, '2025-08-14 13:59:00', '2025-08-14 19:59:00', '2025-08-14 20:00:00', 'IS Office', 'lykajoy.empleo@chmsc.edu.ph', NULL, NULL, NULL, NULL, 'Rheign Palmares', 'uploads/borrower_ids/idpic_689dcfaa14860.jpg', '43TRT22C', NULL, NULL, 10001, 'try', 'AVR', '2025-08-14 20:21:00', 'Returned', NULL, NULL),
(27, 25, '2025-08-14 14:19:00', '2025-08-14 20:19:00', '2025-08-14 20:21:00', 'IS Office', 'lykajoy.empleo@chmsc.edu.ph', NULL, NULL, NULL, NULL, 'Rheign Palmares', 'uploads/borrower_ids/idpic_689dd48e87204.jpg', 'VH4ZHNJE', NULL, NULL, 10001, 'try', 'AVR', '2025-08-14 20:21:00', 'Returned', NULL, NULL),
(28, 26, '2025-08-14 14:35:00', '2025-08-14 20:35:00', '2025-08-14 20:40:00', 'IS Office', 'lykajoy.empleo@chmsc.edu.ph', NULL, NULL, NULL, NULL, 'Rheign Palmares', 'uploads/borrower_ids/idpic_689dd8357c821.jpg', 'RTH7PCHQ', NULL, NULL, 10001, 'try', 'AVR', '2025-08-14 23:18:00', 'Returned', NULL, NULL),
(29, 27, '2025-08-14 17:18:00', '2025-08-14 23:18:00', '2025-08-14 23:19:00', 'IS Office', 'lykajoy.empleo@chmsc.edu.ph', NULL, NULL, NULL, NULL, 'Rheign Palmares', 'uploads/borrower_ids/idpic_689dfe684e634.jpg', 'CVW4WV52', NULL, NULL, 10001, 'try', 'AVR', NULL, 'Rejected', '', NULL),
(30, 28, '2025-08-14 17:20:00', '2025-08-14 23:21:00', '2025-08-14 23:30:00', 'IS Office', 'lykajoy.empleo@chmsc.edu.ph', NULL, NULL, NULL, NULL, 'Rheign', 'uploads/borrower_ids/idpic_689dff1cb54ba.jpg', 'WUH5DWQQ', NULL, NULL, 10001, 'try', 'Computer Lab 1', '2025-08-15 00:25:00', 'Returned', NULL, NULL),
(31, 29, '2025-08-14 18:15:00', '2025-08-15 08:00:00', '2025-08-16 08:00:00', 'IS Office', 'lykajoy.empleo@chmsc.edu.ph', NULL, NULL, NULL, NULL, 'Rheign', 'uploads/borrower_ids/idpic_689e0bfa04f2d.jpg', 'Y6SBHTFR', NULL, NULL, 10001, 'try', 'AVR', '2025-08-15 01:39:00', 'Returned', NULL, NULL),
(32, 30, '2025-08-14 18:26:00', '2025-08-15 00:26:00', '2025-08-16 00:26:00', 'IS Office', 'lykajoy.empleo@chmsc.edu.ph', NULL, NULL, NULL, NULL, 'Rheign', 'uploads/borrower_ids/idpic_689e0e4ac613b.jpg', 'DC3TXWVX', NULL, NULL, 10001, 'try', 'AVR', NULL, 'Rejected', '', NULL),
(33, 31, '2025-08-14 18:35:00', '2025-08-15 00:35:00', '2025-08-16 00:35:00', 'IS Office', 'lykajoy.empleo@chmsc.edu.ph', NULL, NULL, NULL, NULL, 'Rheign', 'uploads/borrower_ids/idpic_689e107f5ef71.jpg', 'FES88XB8', NULL, NULL, 10001, 'try', 'Computer Lab 4', NULL, 'Rejected', '', NULL),
(34, 32, '2025-08-14 18:49:00', '2025-08-15 00:49:00', '2025-08-16 00:49:00', 'IS Office', 'lykajoy.empleo@chmsc.edu.ph', NULL, NULL, NULL, NULL, 'Rheign', 'uploads/borrower_ids/idpic_689e13b7ee621.jpg', 'YRLUK47E', NULL, NULL, 10001, 'try', 'AVR', NULL, 'Rejected', '', NULL),
(35, 33, '2025-08-28 14:06:00', '2025-08-28 20:06:00', '2025-08-28 20:08:00', 'IS Office', 'lykajoy.empleo@chmsc.edu.ph', NULL, NULL, NULL, NULL, 'Rheign', 'uploads/borrower_ids/idpic_68b0467b8a628.jpg', '2KWYBMPB', NULL, NULL, 10001, 'try', 'Computer Lab 1', '2025-08-29 15:16:00', 'Returned', NULL, NULL),
(36, 34, '2025-08-29 11:22:00', '2025-08-29 17:22:00', '2025-08-30 17:22:00', 'IS Office', 'lykajoy.empleo@chmsc.edu.ph', NULL, NULL, NULL, NULL, 'Rheign', 'uploads/borrower_ids/idpic_68b1716a22ed7.jpg', 'EE2LTNQB', NULL, NULL, 10001, 'try', 'Computer Lab 2', '2025-08-29 19:06:00', 'Returned', NULL, NULL),
(37, 35, '2025-09-02 11:13:00', '2025-09-02 17:14:00', '2025-09-03 17:14:00', 'IS Office', 'lykajoy.empleo@chmsc.edu.ph', NULL, NULL, NULL, NULL, 'Rheign', 'uploads/borrower_ids/idpic_68b6b590ab6e5.jpg', 'UTZB6DNK', NULL, NULL, 10001, 'try', 'AVR', '2025-09-02 17:34:00', 'Returned', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `borrow_request_items`
--

CREATE TABLE `borrow_request_items` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrow_request_items`
--

INSERT INTO `borrow_request_items` (`id`, `request_id`, `equipment_id`, `quantity`, `description`) VALUES
(13, 16, 8, 1, NULL),
(14, 17, 6, 1, NULL),
(15, 18, 8, 1, NULL),
(16, 19, 8, 1, NULL),
(17, 20, 6, 1, NULL),
(18, 21, 8, 1, NULL),
(19, 22, 9, 1, NULL),
(20, 23, 8, 1, NULL),
(21, 24, 9, 1, NULL),
(22, 25, 6, 1, NULL),
(23, 26, 9, 1, NULL),
(24, 27, 9, 1, NULL),
(25, 28, 6, 1, NULL),
(26, 29, 9, 1, NULL),
(27, 30, 8, 1, NULL),
(28, 31, 9, 1, NULL),
(29, 32, 6, 1, NULL),
(30, 33, 6, 1, NULL),
(31, 34, 8, 1, NULL),
(32, 35, 6, 1, NULL),
(33, 36, 8, 1, NULL),
(34, 37, 9, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `borrow_slips`
--

CREATE TABLE `borrow_slips` (
  `id` int(11) NOT NULL,
  `control_number` int(11) NOT NULL,
  `date_requested` date NOT NULL,
  `borrower_name` varchar(255) NOT NULL,
  `borrower_type` varchar(50) NOT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year` varchar(20) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `datetime_needed` datetime NOT NULL,
  `teacher_signature` text DEFAULT NULL,
  `released_by` varchar(255) NOT NULL,
  `date_returned` date DEFAULT NULL,
  `received_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `calendar_events`
--

CREATE TABLE `calendar_events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` enum('meeting','maintenance','training','other') NOT NULL,
  `event_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `calendar_events`
--

INSERT INTO `calendar_events` (`id`, `title`, `type`, `event_date`, `start_time`, `end_time`, `location`, `description`, `created_by`, `created_at`) VALUES
(1, '1st Day of School', 'other', '2025-08-20', '09:00:00', '10:00:00', NULL, 'Welcome back to school!', 10001, '2025-08-08 14:06:38'),
(2, 'Try', 'meeting', '2025-08-20', '09:00:00', '10:00:00', NULL, 'Capstone Meeting', 10001, '2025-08-08 14:36:47'),
(3, 'Faculty Meeting', 'meeting', '2025-08-09', '09:00:00', '10:30:00', 'Dining Hall', 'All faculties of CHMSU meeting', 10001, '2025-08-08 14:55:06');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `name`) VALUES
(1, 'Bachelor of Science in Information Systems');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`) VALUES
(1, 'College of Computer Studies');

-- --------------------------------------------------------

--
-- Table structure for table `departments_master`
--

CREATE TABLE `departments_master` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `college` varchar(100) DEFAULT NULL,
  `head` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments_master`
--

INSERT INTO `departments_master` (`id`, `name`, `code`, `college`, `head`, `email`, `phone`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Computer Science', 'CS', 'College of Information Technology', 'Dr. John Smith', NULL, NULL, 1, '2025-10-22 00:29:35', '2025-10-22 00:29:35'),
(2, 'Information Technology', 'IT', 'College of Information Technology', 'Dr. Jane Doe', NULL, NULL, 1, '2025-10-22 00:29:35', '2025-10-22 00:29:35'),
(3, 'Computer Engineering', 'CE', 'College of Engineering', 'Dr. Bob Johnson', NULL, NULL, 1, '2025-10-22 00:29:35', '2025-10-22 00:29:35'),
(4, 'Information Systems', 'IS', 'College of Information Technology', 'Dr. Alice Brown', NULL, NULL, 1, '2025-10-22 00:29:35', '2025-10-22 00:29:35');

-- --------------------------------------------------------

--
-- Table structure for table `email_verification_codes`
--

CREATE TABLE `email_verification_codes` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `verification_code` varchar(6) NOT NULL,
  `created_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `user_data` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verification_codes`
--

INSERT INTO `email_verification_codes` (`id`, `email`, `verification_code`, `created_at`, `expires_at`, `is_verified`, `user_data`) VALUES
(1, 'christinayang472@gmail.com', '717301', '2025-10-27 02:57:52', '2025-10-27 03:07:52', 1, '{\"name\":\"Christina Yang\",\"username\":\"yang\",\"password\":\"Zurich12!\",\"role\":\"ICT Staff\",\"email\":\"christinayang472@gmail.com\",\"mobile_number\":\"09876543212\"}');

-- --------------------------------------------------------

--
-- Table structure for table `email_verification_tokens`
--

CREATE TABLE `email_verification_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verification_tokens`
--

INSERT INTO `email_verification_tokens` (`id`, `user_id`, `token`, `expires_at`, `used`, `created_at`) VALUES
(1, 10003, 'd8a42529296a30e0b2b1bafb5734f144d8eb3ff9dbd360f9baeb28d50e24d6ad', '2025-10-23 09:49:12', 0, '2025-10-22 01:49:12'),
(2, 10004, 'ce36fccbe834892f508f1b90b6650a2cb027f69f7b354da7397ce08a95f985c5', '2025-10-23 09:57:06', 0, '2025-10-22 01:57:06');

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `serial_number` varchar(100) NOT NULL,
  `model` varchar(100) DEFAULT NULL,
  `status` enum('Available','Borrowed','Maintenance','Repair','Retired','Under Repair','Disposed','Transferred') DEFAULT 'Available',
  `location` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT 'Equipment',
  `remarks` text DEFAULT NULL,
  `date_transferred` date DEFAULT NULL,
  `installation_date` date DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `maintenance_interval_months` int(11) DEFAULT 6,
  `last_updated_by` int(11) DEFAULT NULL,
  `last_updated_at` datetime DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`id`, `name`, `serial_number`, `model`, `status`, `location`, `category`, `remarks`, `date_transferred`, `installation_date`, `last_maintenance_date`, `next_maintenance_date`, `maintenance_interval_months`, `last_updated_by`, `last_updated_at`, `is_archived`) VALUES
(6, 'Aspire C24-1650 All-in-One Desktop Computer', 'IS01_PC01', 'Aspire', 'Available', 'Computer Lab 1', 'Equipment', 'DEMO PC', NULL, '2025-08-14', '2025-10-22', '2026-02-14', 6, 10001, '2025-08-15 05:26:38', 0),
(8, 'Aspire C24-1650 All-in-One Desktop Computer', 'IS01_PC02', 'Aspire', 'Available', 'Computer Lab 2', 'Equipment', '', NULL, '2025-08-14', NULL, '2026-02-14', 6, 10001, '2025-08-14 14:58:43', 0),
(9, 'Television', 'IS01_TV04', 'Skyworth 58\"', 'Available', 'Computer Lab 1', 'Equipment', '', NULL, '2025-08-14', NULL, '2026-02-14', 6, NULL, NULL, 0),
(10, 'Steel Shelf', 'IS01_SS08', 'N/A', 'Available', 'Computer Lab 1', 'Equipment', '4 Layer Steel Shelf Open Type', NULL, '2025-08-14', NULL, '2026-02-14', 6, NULL, NULL, 0),
(11, 'Electric Fan', 'IS01_EF11', '3D', 'Available', 'Computer Lab 1', 'Equipment', 'Stand Fan', NULL, '2025-08-14', NULL, '2026-02-14', 6, NULL, NULL, 0),
(13, 'Computer Chairs', 'IS01_C10', 'N/A', 'Available', 'Computer Lab 1', 'Furniture', 'Mesh Black Chair', NULL, '2025-08-14', NULL, '2026-02-14', 6, 10001, '2025-10-22 08:05:42', 0),
(15, 'Computer Chairs', 'IS01_C11', 'N/A', 'Available', 'Computer Lab 1', 'Equipment', 'Mesh Black Chair', NULL, '2025-08-15', NULL, '2026-02-14', 6, 10001, '2025-08-15 05:26:45', 0),
(16, 'HDMI', 'IS01_HDMI1', 'N/A', 'Transferred', 'Computer Lab 2', 'Equipment', '', NULL, '2025-10-22', NULL, '2026-04-22', 6, 10001, '2025-10-26 09:04:15', 0),
(32, 'Paper', 'IS01_BP1', 'HARD COPY', 'Available', 'Computer Lab 1', 'Consumables', '', NULL, '2025-10-22', NULL, '2026-04-22', 6, NULL, NULL, 0),
(34, 'Chair', 'IS01_C12', 'Office Chair OC-100', 'Available', 'Computer Lab 4', 'Furniture', '', NULL, '2025-10-26', NULL, '2026-04-26', 6, NULL, NULL, 0),
(35, 'Paper', 'IS01_BP2', '', 'Available', 'Computer Lab 5', 'Consumables', '', NULL, '2025-10-26', NULL, '2026-04-26', 6, NULL, NULL, 0),
(36, 'Computer', 'IS05_PC01', 'Aspire C24-1650', 'Available', 'Computer Lab 5', 'Equipment', '', NULL, '2025-10-26', NULL, '2026-04-26', 6, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `equipment_categories`
--

CREATE TABLE `equipment_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `equipment_categories`
--

INSERT INTO `equipment_categories` (`id`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Equipment', 'Computer equipment and hardware', 1, '2025-10-22 00:29:35', '2025-10-22 00:29:35'),
(2, 'Consumables', 'Consumable items and supplies', 1, '2025-10-22 00:29:35', '2025-10-22 00:29:35'),
(3, 'Furniture', 'Furniture and fixtures', 1, '2025-10-22 00:29:35', '2025-10-22 00:29:35'),
(4, 'Others', 'Other miscellaneous items', 1, '2025-10-22 00:29:35', '2025-10-22 00:29:35');

-- --------------------------------------------------------

--
-- Table structure for table `equipment_items_master`
--

CREATE TABLE `equipment_items_master` (
  `id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment_items_master`
--

INSERT INTO `equipment_items_master` (`id`, `category`, `item_name`, `is_active`, `created_at`) VALUES
(1, 'Consumables', 'Paper', 1, '2025-10-22 01:04:54'),
(2, 'Consumables', 'Ink Cartridge', 1, '2025-10-22 01:04:54'),
(3, 'Consumables', 'Toner', 1, '2025-10-22 01:04:54'),
(4, 'Consumables', 'Cables', 1, '2025-10-22 01:04:54'),
(5, 'Consumables', 'USB Drive', 1, '2025-10-22 01:04:54'),
(6, 'Equipment', 'Computer', 1, '2025-10-22 01:04:54'),
(7, 'Equipment', 'Monitor', 1, '2025-10-22 01:04:54'),
(8, 'Equipment', 'Keyboard', 1, '2025-10-22 01:04:54'),
(9, 'Equipment', 'Mouse', 1, '2025-10-22 01:04:54'),
(10, 'Equipment', 'Projector', 1, '2025-10-22 01:04:54'),
(11, 'Furniture', 'Chair', 1, '2025-10-22 01:04:54'),
(12, 'Furniture', 'Table', 1, '2025-10-22 01:04:54'),
(13, 'Furniture', 'Cabinet', 1, '2025-10-22 01:04:54'),
(14, 'Furniture', 'Shelf', 1, '2025-10-22 01:04:54'),
(15, 'Furniture', 'Desk', 1, '2025-10-22 01:04:54'),
(18, 'Equipment', 'HDMI', 1, '2025-10-25 23:36:20');

-- --------------------------------------------------------

--
-- Table structure for table `equipment_logs`
--

CREATE TABLE `equipment_logs` (
  `id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `transferred_to` varchar(255) DEFAULT NULL,
  `from_location` varchar(255) DEFAULT NULL,
  `authorized_by` varchar(255) DEFAULT NULL,
  `transfer_date` date DEFAULT NULL,
  `previous_values` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment_logs`
--

INSERT INTO `equipment_logs` (`id`, `equipment_id`, `action`, `deleted_by`, `transferred_to`, `from_location`, `authorized_by`, `transfer_date`, `previous_values`, `timestamp`, `remarks`) VALUES
(1, 8, 'Transferred', NULL, 'Computer Lab 2', NULL, 'Lyka Empleo', '2025-05-17', NULL, '2025-05-17 14:39:12', NULL),
(2, 8, 'Transferred', NULL, 'Computer Lab 2', NULL, 'Lyka Empleo', '2025-05-17', NULL, '2025-05-17 14:39:37', NULL),
(3, 8, 'Transferred', NULL, 'Computer Lab 2', NULL, 'Lyka Empleo', '2025-05-17', NULL, '2025-05-17 15:10:34', ''),
(4, 8, 'Transferred', NULL, 'Computer Lab 2', NULL, 'Lyka Empleo', '2025-05-17', NULL, '2025-05-17 15:36:40', ''),
(5, 8, 'Transferred', NULL, 'Computer Lab 3', NULL, 'John', '2025-05-17', NULL, '2025-05-17 17:18:22', ''),
(6, 8, 'Transferred', NULL, 'Computer Lab 2', NULL, 'Lyka Empleo', '2025-05-17', NULL, '2025-05-17 17:21:13', ''),
(7, 8, 'Updated', NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-17 17:25:13', NULL),
(8, 8, 'Transferred', NULL, 'Computer Lab 3', '', 'John', '2025-05-17', '{\"location\":\"\"}', '2025-05-17 17:31:36', ''),
(9, 8, 'Transferred', NULL, 'Computer Lab 2', 'Computer Lab 3', 'John', '2025-05-17', '{\"location\":\"Computer Lab 3\"}', '2025-05-17 17:32:18', ''),
(10, 8, 'Updated', NULL, NULL, 'Computer Lab 2', NULL, NULL, '{\"model\":\"Lenovo\",\"status\":\"\",\"location\":\"Computer Lab 2\"}', '2025-05-17 18:06:56', NULL),
(11, 6, 'Updated', NULL, NULL, 'Computer Lab 1', NULL, NULL, '{\"model\":\"Aspire\",\"location\":\"Computer Lab 1\"}', '2025-05-17 18:10:29', NULL),
(12, 8, 'Transferred', NULL, 'Computer Lab 2', '', 'John', '2025-05-17', '{\"location\":\"\"}', '2025-05-17 18:15:24', ''),
(13, 8, 'Transferred', NULL, 'Computer Lab 2', 'Computer Lab 2', 'John', '2025-05-17', '[]', '2025-05-17 18:16:01', ''),
(14, 6, 'Transferred', NULL, 'Computer Lab 2', '', 'John', '2025-05-17', '{\"location\":\"\"}', '2025-05-17 18:18:53', ''),
(15, 6, 'Transferred', NULL, 'Computer Lab 1', 'Computer Lab 2', 'Lyka Empleo', '2025-05-17', '{\"location\":\"Computer Lab 2\"}', '2025-05-17 18:19:06', ''),
(16, 8, 'Updated', NULL, NULL, 'Computer Lab 2', NULL, NULL, '{\"status\":\"\"}', '2025-05-17 18:19:16', NULL),
(17, 6, 'Updated', NULL, NULL, 'Computer Lab 1', NULL, NULL, '{\"status\":\"\"}', '2025-05-17 18:20:23', NULL),
(18, 8, 'Updated', NULL, NULL, 'Computer Lab 2', NULL, NULL, '{\"remarks\":\"Cool\"}', '2025-05-17 18:20:32', NULL),
(19, 6, 'Updated', NULL, NULL, 'Computer Lab 1', NULL, NULL, '{\"model\":\"Dell\"}', '2025-05-17 18:20:49', NULL),
(20, 8, 'Archived', NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-17 19:32:53', NULL),
(21, 8, 'Archived', NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-17 19:32:53', NULL),
(22, 8, 'Restored', NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-17 19:33:00', NULL),
(23, 8, 'Updated', 10001, NULL, 'Computer Lab 2', NULL, NULL, '{\"installation_date\":null}', '2025-08-13 20:16:38', NULL),
(24, 8, 'Updated', 10001, NULL, 'Computer Lab 2', NULL, NULL, '{\"installation_date\":\"2025-08-13\"}', '2025-08-14 14:58:43', NULL),
(25, 15, 'Updated', 10001, NULL, 'Computer Lab 1', NULL, NULL, '{\"installation_date\":\"2025-08-14\"}', '2025-08-15 04:59:07', NULL),
(26, 6, 'Updated', 10001, NULL, 'Computer Lab 1', NULL, NULL, '{\"status\":\"Available\"}', '2025-08-15 05:25:36', NULL),
(27, 15, 'Updated', 10001, NULL, 'Computer Lab 1', NULL, NULL, '{\"status\":\"Available\"}', '2025-08-15 05:25:56', NULL),
(28, 6, 'Updated', 10001, NULL, 'Computer Lab 1', NULL, NULL, '{\"status\":\"\"}', '2025-08-15 05:26:38', NULL),
(29, 15, 'Updated', 10001, NULL, 'Computer Lab 1', NULL, NULL, '{\"status\":\"\"}', '2025-08-15 05:26:45', NULL),
(31, 13, 'Updated', 10001, NULL, 'Computer Lab 1', NULL, NULL, '{\"category\":\"Equipment\"}', '2025-10-22 08:05:42', NULL),
(53, 16, 'Updated', 10001, NULL, 'Computer Lab 1', NULL, NULL, '{\"status\":\"Available\"}', '2025-10-26 07:36:54', NULL),
(54, 16, 'Transferred', 10001, 'Computer Lab 1', 'Computer Lab 1', 'Lyka Joy Empleo', '2025-10-26', '[]', '2025-10-26 08:50:41', ''),
(55, 16, 'Transferred', 10001, 'Computer Lab 2', 'Computer Lab 1', 'Lyka Joy Empleo', '2025-10-26', '{\"location\":\"Computer Lab 1\"}', '2025-10-26 08:51:01', ''),
(56, 16, 'Transferred', 10001, 'Computer Lab 2', 'Computer Lab 1', 'Lyka Joy Empleo', '2025-10-26', '{\"location\":\"Computer Lab 1\"}', '2025-10-26 09:04:15', ''),
(57, 16, 'Updated', 10001, NULL, 'Computer Lab 1', NULL, NULL, '{\"status\":\"Available\",\"location\":\"Computer Lab 1\"}', '2025-10-26 09:04:15', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `equipment_models`
--

CREATE TABLE `equipment_models` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipment_models_master`
--

CREATE TABLE `equipment_models_master` (
  `id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `model_name` varchar(100) NOT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment_models_master`
--

INSERT INTO `equipment_models_master` (`id`, `category`, `model_name`, `manufacturer`, `is_active`, `created_at`) VALUES
(1, 'Equipment', 'Aspire C24-1650', 'Acer', 1, '2025-10-26 00:37:04'),
(2, 'Equipment', 'ThinkCentre M70a', 'Lenovo', 1, '2025-10-26 00:37:04'),
(3, 'Equipment', 'Optiplex 7090', 'Dell', 1, '2025-10-26 00:37:04'),
(4, 'Equipment', 'ProDesk 400 G7', 'HP', 1, '2025-10-26 00:37:04'),
(5, 'Equipment', 'P2422H', 'Dell', 1, '2025-10-26 00:37:04'),
(6, 'Equipment', 'VG249Q', 'ASUS', 1, '2025-10-26 00:37:04'),
(7, 'Equipment', 'EX2780Q', 'BenQ', 1, '2025-10-26 00:37:04'),
(8, 'Equipment', 'K120', 'Logitech', 1, '2025-10-26 00:37:04'),
(9, 'Equipment', 'MK270', 'Logitech', 1, '2025-10-26 00:37:04'),
(10, 'Equipment', 'G102', 'Logitech', 1, '2025-10-26 00:37:04'),
(11, 'Equipment', 'M185', 'Logitech', 1, '2025-10-26 00:37:04'),
(12, 'Furniture', 'Office Chair OC-100', 'Generic', 1, '2025-10-26 00:37:04'),
(13, 'Furniture', 'Computer Desk CD-200', 'Generic', 1, '2025-10-26 00:37:04'),
(14, 'Furniture', 'Steel Shelf SS-300', 'Generic', 1, '2025-10-26 00:37:04');

-- --------------------------------------------------------

--
-- Table structure for table `equipment_status`
--

CREATE TABLE `equipment_status` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `equipment_status`
--

INSERT INTO `equipment_status` (`id`, `name`, `description`, `color`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Available', 'Equipment is available for use', 'success', 1, '2025-10-22 00:29:35', '2025-10-22 00:29:35'),
(2, 'Borrowed', 'Equipment is currently borrowed', 'warning', 1, '2025-10-22 00:29:35', '2025-10-22 00:29:35'),
(3, 'Maintenance', 'Equipment is under maintenance', 'info', 1, '2025-10-22 00:29:35', '2025-10-22 00:29:35'),
(4, 'Repair', 'Equipment is being repaired', 'danger', 1, '2025-10-22 00:29:35', '2025-10-22 00:29:35'),
(5, 'Retired', 'Equipment is no longer in service', 'secondary', 1, '2025-10-22 00:29:35', '2025-10-22 00:29:35'),
(6, 'Transferred', NULL, 'primary', 1, '2025-10-26 00:53:08', '2025-10-26 00:53:08'),
(7, 'Disposed', NULL, 'secondary', 1, '2025-10-26 00:53:08', '2025-10-26 00:53:08');

-- --------------------------------------------------------

--
-- Table structure for table `ict_support_requests`
--

CREATE TABLE `ict_support_requests` (
  `id` int(11) NOT NULL,
  `requester_name` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `request_date` date NOT NULL,
  `request_time` time NOT NULL,
  `nature_of_request` text NOT NULL,
  `action_taken` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `status` enum('Pending','In Progress','Resolved','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ict_support_requests`
--

INSERT INTO `ict_support_requests` (`id`, `requester_name`, `department`, `request_date`, `request_time`, `nature_of_request`, `action_taken`, `photo`, `status`, `created_at`, `updated_at`) VALUES
(1, 'John Doe', 'Computer Science', '2025-10-22', '08:27:45', 'Test ICT support request', 'Request submitted via form', NULL, 'Pending', '2025-10-22 00:27:45', '2025-10-22 00:27:45'),
(2, 'John Doe', 'Computer Science', '2025-10-22', '08:27:48', 'Test ICT support request', 'Request submitted via form', NULL, 'Pending', '2025-10-22 00:27:48', '2025-10-22 00:27:48');

-- --------------------------------------------------------

--
-- Table structure for table `labs`
--

CREATE TABLE `labs` (
  `id` int(11) NOT NULL,
  `lab_name` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `capacity` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `labs`
--

INSERT INTO `labs` (`id`, `lab_name`, `location`, `capacity`) VALUES
(1, 'Computer Lab 1', 'LSA Building - Room 311', 32),
(2, 'Computer Lab 2', 'LSA Building - Room 312', 25),
(3, 'Computer Lab 3', 'LSA Building - Room 313', 25),
(4, 'Computer Lab 4', 'LSA Building - Room 402', 30),
(5, 'Computer Lab 5', 'LSA Building - Room 403', 28),
(6, 'Computer Lab 6', 'LSA Building 3rd Floor', 32),
(7, 'Computer Lab 7', 'LSA Building 3rd Floor', 23);

-- --------------------------------------------------------

--
-- Table structure for table `lab_locations_backup`
--

CREATE TABLE `lab_locations_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `building` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `floor` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_locations_backup`
--

INSERT INTO `lab_locations_backup` (`id`, `name`, `building`, `floor`, `capacity`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Computer Lab 1', 'Main Building', '2nd Floor', 30, 1, '2025-10-22 00:29:35', '2025-10-22 00:29:35'),
(2, 'Computer Lab 2', 'Main Building', '2nd Floor', 25, 1, '2025-10-22 00:29:35', '2025-10-22 00:29:35'),
(3, 'Computer Lab 3', 'Main Building', '3rd Floor', 35, 1, '2025-10-22 00:29:35', '2025-10-22 00:29:35'),
(6, 'Computer Lab 4', 'Main Building', '3rd Floor', 30, 1, '2025-10-26 01:38:39', '2025-10-26 01:38:39'),
(7, 'Computer Lab 5', 'Main Building', '4th Floor', 30, 1, '2025-10-26 01:38:39', '2025-10-26 01:38:39'),
(8, 'Computer Lab 6', 'LSA Building', '3rd Floor', 32, 1, '2025-10-26 02:08:01', '2025-10-26 02:08:01');

-- --------------------------------------------------------

--
-- Table structure for table `lab_reservations`
--

CREATE TABLE `lab_reservations` (
  `id` int(11) NOT NULL,
  `control_number` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `lab_id` int(11) NOT NULL,
  `date_reserved` datetime NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `borrower_type` enum('Office','Department','Faculty','Office/Department') DEFAULT NULL,
  `borrower_id` int(11) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `needed_tools` varchar(255) DEFAULT NULL,
  `equipment` varchar(255) DEFAULT NULL,
  `software` varchar(255) DEFAULT NULL,
  `requested_by` varchar(255) DEFAULT NULL,
  `noted_by` varchar(255) DEFAULT NULL,
  `approved_by` varchar(255) DEFAULT NULL,
  `borrower_email` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `tracking_code` varchar(20) DEFAULT NULL,
  `reservation_start` datetime DEFAULT NULL,
  `reservation_end` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `approved_letter` varchar(255) DEFAULT NULL COMMENT 'File path to approved letter from dean/president explaining the reason for lab reservation',
  `id_photo` varchar(255) DEFAULT NULL COMMENT 'File path to ID photo of the person in-charge of the event/reason'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_reservations`
--

INSERT INTO `lab_reservations` (`id`, `control_number`, `user_id`, `lab_id`, `date_reserved`, `time_start`, `time_end`, `purpose`, `borrower_type`, `borrower_id`, `status`, `needed_tools`, `equipment`, `software`, `requested_by`, `noted_by`, `approved_by`, `borrower_email`, `contact_person`, `tracking_code`, `reservation_start`, `reservation_end`, `remarks`, `approved_letter`, `id_photo`) VALUES
(29, 20, 10002, 1, '2025-08-12 00:00:00', '08:30:00', '09:30:00', 'Meeting', NULL, NULL, 'Approved', 'N/A', NULL, NULL, 'Joleah', 'Lyka', 'Rheign', NULL, NULL, '3XUF5ZF8', '2025-08-12 08:30:00', '2025-08-12 09:30:00', NULL, NULL, NULL),
(30, 21, 10002, 2, '2025-08-11 00:00:00', '08:35:00', '09:35:00', 'try', NULL, NULL, 'Approved', 'n/a', NULL, NULL, 'Joleah', 'Lyka', 'Rheign', NULL, NULL, '5XW7CWUX', '2025-08-11 08:35:00', '2025-08-11 09:35:00', NULL, NULL, NULL),
(31, 22, 10002, 2, '2025-08-11 00:00:00', '21:03:00', '21:04:00', 'gsgae', NULL, NULL, 'Approved', 'gsdge', NULL, NULL, 'sdgsg', 'gsrg', 'bfhsr', NULL, NULL, '28WD6NH7', '2025-08-11 21:03:00', '2025-08-11 21:04:00', NULL, NULL, NULL),
(32, 23, 10001, 1, '2025-08-28 00:00:00', '15:10:00', '15:12:00', 'try', NULL, NULL, 'Rejected', 'dd', NULL, NULL, 'Lyka', 'Rheign', 'Joleah', NULL, NULL, '2TBCLU8G', '2025-08-28 15:10:00', '2025-08-28 15:12:00', 'finally!!', NULL, NULL),
(33, 24, 10001, 1, '2025-08-28 00:00:00', '20:05:00', '20:07:00', 'try', NULL, NULL, 'Approved', 'afcac', NULL, NULL, 'Lyka', 'Rheign', 'Joleah', NULL, NULL, '2BPMAHR6', '2025-08-28 20:05:00', '2025-08-28 20:07:00', NULL, NULL, NULL),
(34, 25, 10001, 1, '2025-08-28 00:00:00', '20:53:00', '20:53:00', 'try', 'Office/Department', 1, 'Approved', 'dcas', NULL, NULL, 'Lyka Joy Empleo', 'Rheign', 'Joleah', 'lykajoy.empleo@chmsc.edu.ph', 'Lyka Joy Empleo', '2GEJGJWD', '2025-08-28 20:53:00', '2025-08-29 20:53:00', NULL, NULL, NULL),
(35, 26, 10001, 2, '2025-08-29 13:52:23', '13:51:00', '13:51:00', 'try', 'Office/Department', 1, 'Approved', 'cacda', NULL, NULL, 'Lyka Joy Empleo', 'Rheign', 'Joleah', 'lykajoy.empleo@chmsc.edu.ph', 'Lyka Joy Empleo', 'PG928TAQ', '2025-08-29 13:51:00', '2025-08-30 13:51:00', NULL, NULL, NULL),
(36, 27, 10001, 2, '2025-08-29 13:53:26', '13:53:00', '13:53:00', 'try', 'Office/Department', 1, 'Rejected', 'acss', NULL, NULL, 'Lyka Joy Empleo', 'Rheign', 'Joleah', 'lykajoy.empleo@chmsc.edu.ph', 'Lyka Joy Empleo', 'MB8TG6EV', '2025-08-29 13:53:00', '2025-08-30 13:53:00', '', NULL, NULL),
(37, 28, 10001, 2, '2025-08-29 14:05:50', '14:05:00', '14:05:00', 'try', 'Office/Department', 1, 'Approved', 'acsx', NULL, NULL, 'Lyka Joy Empleo', 'Rheign', 'Joleah', 'lykajoy.empleo@chmsc.edu.ph', 'Lyka Joy Empleo', 'DRYYR7DX', '2025-08-29 14:05:00', '2025-08-30 14:05:00', NULL, NULL, NULL),
(38, 29, 10001, 1, '2025-08-29 21:38:56', '21:38:00', '22:38:00', 'try', 'Office/Department', 1, 'Rejected', 'scc', NULL, NULL, 'Lyka Joy Empleo', '', '', 'lykajoy.empleo@chmsc.edu.ph', 'Lyka Joy Empleo', 'KB37Q2TS', '2025-08-29 21:38:00', '2025-08-29 22:38:00', '', 'uploads/lab_reservations/approved_letter_KB37Q2TS_1756474736.pdf', 'uploads/lab_reservations/id_photo_KB37Q2TS_1756474736.jpg'),
(39, 30, 10001, 3, '2025-08-30 00:15:03', '21:41:00', '22:41:00', 'try', 'Office/Department', 1, 'Rejected', 'oasx', NULL, NULL, 'Lyka Joy Empleo', 'Rheign', 'Joleah', 'lykajoy.empleo@chmsc.edu.ph', 'Lyka Joy Empleo', 'WMZAYDPL', '2025-08-29 21:41:00', '2025-08-29 22:41:00', '', NULL, NULL),
(40, 31, 10001, 1, '2025-08-30 00:16:19', '00:15:00', '00:15:00', 'try', 'Office/Department', 1, 'Approved', 'kcam', NULL, NULL, 'Lyka Joy Empleo', 'Rheign', 'Joleah', 'lykajoy.empleo@chmsc.edu.ph', 'Lyka Joy Empleo', '5MKXT8JR', '2025-08-30 00:15:00', '2025-08-31 00:15:00', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `maintenance_dashboard`
-- (See below for the actual view)
--
CREATE TABLE `maintenance_dashboard` (
`id` int(11)
,`equipment_name` varchar(100)
,`serial_number` varchar(100)
,`location` varchar(100)
,`installation_date` date
,`last_maintenance_date` date
,`next_maintenance_date` date
,`maintenance_interval_months` int(11)
,`maintenance_status` varchar(11)
,`days_until_maintenance` int(7)
);

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_records`
--

CREATE TABLE `maintenance_records` (
  `id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `type` enum('Maintenance','Repair') DEFAULT 'Maintenance',
  `issue_description` text NOT NULL,
  `maintenance_date` date NOT NULL,
  `repair_status` enum('Pending','In Progress','Completed') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `due_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_records`
--

INSERT INTO `maintenance_records` (`id`, `equipment_id`, `type`, `issue_description`, `maintenance_date`, `repair_status`, `notes`, `photo`, `due_date`) VALUES
(5, 6, 'Maintenance', '', '2025-10-22', 'Pending', '', NULL, '2025-10-22');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_reminders`
--

CREATE TABLE `maintenance_reminders` (
  `id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `reminder_date` date NOT NULL,
  `reminder_type` enum('30_days_before','7_days_before','due_date','overdue') NOT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `sent_to` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Sent','Failed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_reminders`
--

INSERT INTO `maintenance_reminders` (`id`, `equipment_id`, `reminder_date`, `reminder_type`, `sent_at`, `sent_to`, `status`, `created_at`) VALUES
(1, 9, '2026-01-15', '30_days_before', NULL, NULL, 'Pending', '2025-08-14 06:40:47'),
(2, 9, '2026-02-07', '7_days_before', NULL, NULL, 'Pending', '2025-08-14 06:40:47'),
(3, 9, '2026-02-14', 'due_date', NULL, NULL, 'Pending', '2025-08-14 06:40:47'),
(4, 10, '2026-01-15', '30_days_before', NULL, NULL, 'Pending', '2025-08-14 20:38:24'),
(5, 10, '2026-02-07', '7_days_before', NULL, NULL, 'Pending', '2025-08-14 20:38:24'),
(6, 10, '2026-02-14', 'due_date', NULL, NULL, 'Pending', '2025-08-14 20:38:24'),
(7, 11, '2026-01-15', '30_days_before', NULL, NULL, 'Pending', '2025-08-14 20:49:35'),
(8, 11, '2026-02-07', '7_days_before', NULL, NULL, 'Pending', '2025-08-14 20:49:35'),
(9, 11, '2026-02-14', 'due_date', NULL, NULL, 'Pending', '2025-08-14 20:49:35'),
(13, 13, '2026-01-15', '30_days_before', NULL, NULL, 'Pending', '2025-08-14 20:51:29'),
(14, 13, '2026-02-07', '7_days_before', NULL, NULL, 'Pending', '2025-08-14 20:51:29'),
(15, 13, '2026-02-14', 'due_date', NULL, NULL, 'Pending', '2025-08-14 20:51:29'),
(19, 15, '2026-01-15', '30_days_before', NULL, NULL, 'Pending', '2025-08-14 20:58:41'),
(20, 15, '2026-02-07', '7_days_before', NULL, NULL, 'Pending', '2025-08-14 20:58:41'),
(21, 15, '2026-02-14', 'due_date', NULL, NULL, 'Pending', '2025-08-14 20:58:41'),
(22, 16, '2026-03-23', '30_days_before', NULL, NULL, 'Pending', '2025-10-21 23:52:43'),
(23, 16, '2026-04-15', '7_days_before', NULL, NULL, 'Pending', '2025-10-21 23:52:43'),
(24, 16, '2026-04-22', 'due_date', NULL, NULL, 'Pending', '2025-10-21 23:52:43'),
(73, 32, '2026-03-23', '30_days_before', NULL, NULL, 'Pending', '2025-10-22 02:08:27'),
(74, 32, '2026-04-15', '7_days_before', NULL, NULL, 'Pending', '2025-10-22 02:08:27'),
(75, 32, '2026-04-22', 'due_date', NULL, NULL, 'Pending', '2025-10-22 02:08:27'),
(79, 34, '2026-03-27', '30_days_before', NULL, NULL, 'Pending', '2025-10-26 01:52:45'),
(80, 34, '2026-04-19', '7_days_before', NULL, NULL, 'Pending', '2025-10-26 01:52:45'),
(81, 34, '2026-04-26', 'due_date', NULL, NULL, 'Pending', '2025-10-26 01:52:45'),
(82, 35, '2026-03-27', '30_days_before', NULL, NULL, 'Pending', '2025-10-26 01:56:02'),
(83, 35, '2026-04-19', '7_days_before', NULL, NULL, 'Pending', '2025-10-26 01:56:02'),
(84, 35, '2026-04-26', 'due_date', NULL, NULL, 'Pending', '2025-10-26 01:56:02'),
(85, 36, '2026-03-27', '30_days_before', NULL, NULL, 'Pending', '2025-10-26 02:02:10'),
(86, 36, '2026-04-19', '7_days_before', NULL, NULL, 'Pending', '2025-10-26 02:02:10'),
(87, 36, '2026-04-26', 'due_date', NULL, NULL, 'Pending', '2025-10-26 02:02:10');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_schedule`
--

CREATE TABLE `maintenance_schedule` (
  `id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `scheduled_date` date NOT NULL,
  `maintenance_type` enum('Scheduled','Overdue') DEFAULT 'Scheduled',
  `status` enum('Pending','In Progress','Completed','Skipped') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_schedule`
--

INSERT INTO `maintenance_schedule` (`id`, `equipment_id`, `scheduled_date`, `maintenance_type`, `status`, `created_at`, `updated_at`) VALUES
(1, 9, '2026-02-14', 'Scheduled', 'Pending', '2025-08-14 06:40:47', '2025-08-14 06:40:47'),
(2, 10, '2026-02-14', 'Scheduled', 'Pending', '2025-08-14 20:38:24', '2025-08-14 20:38:24'),
(3, 11, '2026-02-14', 'Scheduled', 'Pending', '2025-08-14 20:49:35', '2025-08-14 20:49:35'),
(5, 13, '2026-02-14', 'Scheduled', 'Pending', '2025-08-14 20:51:29', '2025-08-14 20:51:29'),
(7, 15, '2026-02-14', 'Scheduled', 'Pending', '2025-08-14 20:58:41', '2025-08-14 20:58:41'),
(8, 16, '2026-04-22', 'Scheduled', 'Pending', '2025-10-21 23:52:43', '2025-10-21 23:52:43'),
(25, 32, '2026-04-22', 'Scheduled', 'Pending', '2025-10-22 02:08:27', '2025-10-22 02:08:27'),
(27, 34, '2026-04-26', 'Scheduled', 'Pending', '2025-10-26 01:52:45', '2025-10-26 01:52:45'),
(28, 35, '2026-04-26', 'Scheduled', 'Pending', '2025-10-26 01:56:02', '2025-10-26 01:56:02'),
(29, 36, '2026-04-26', 'Scheduled', 'Pending', '2025-10-26 02:02:10', '2025-10-26 02:02:10');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_types`
--

CREATE TABLE `maintenance_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `interval_months` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maintenance_types`
--

INSERT INTO `maintenance_types` (`id`, `name`, `description`, `interval_months`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Regular Checkup', 'Routine maintenance and inspection', 6, 1, '2025-10-22 00:29:36', '2025-10-22 00:29:36'),
(2, 'Deep Cleaning', 'Thorough cleaning and dust removal', 3, 1, '2025-10-22 00:29:36', '2025-10-22 00:29:36'),
(3, 'Hardware Update', 'Hardware component updates', 12, 1, '2025-10-22 00:29:36', '2025-10-22 00:29:36'),
(4, 'Software Update', 'Software and driver updates', 2, 1, '2025-10-22 00:29:36', '2025-10-22 00:29:36'),
(5, 'Calibration', 'Equipment calibration and testing', 6, 1, '2025-10-22 00:29:36', '2025-10-22 00:29:36');

-- --------------------------------------------------------

--
-- Stand-in structure for view `pending_reminders`
-- (See below for the actual view)
--
CREATE TABLE `pending_reminders` (
`id` int(11)
,`equipment_id` int(11)
,`equipment_name` varchar(100)
,`location` varchar(100)
,`reminder_date` date
,`reminder_type` enum('30_days_before','7_days_before','due_date','overdue')
,`status` enum('Pending','Sent','Failed')
,`reminder_message` varchar(26)
);

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `name`) VALUES
(2, 'A'),
(1, 'C');

-- --------------------------------------------------------

--
-- Table structure for table `software`
--

CREATE TABLE `software` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `lab_id` int(11) DEFAULT NULL,
  `pc_number` varchar(50) DEFAULT NULL,
  `installation_date` date DEFAULT NULL,
  `license_expiry_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Active','Resolved') DEFAULT 'Active',
  `resolution_notes` text DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Lab Admin','Student Assistant','Borrower','ICT Staff') NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `email` varchar(100) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `verification_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `role`, `profile_picture`, `status`, `email`, `mobile_number`, `email_verified`, `verification_token`, `verification_expires`) VALUES
(10000, 'New Admin', 'newadmin', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'Lab Admin', NULL, 'Active', NULL, NULL, 0, NULL, NULL),
(10001, 'Lyka Joy C. Empleo', 'lyka', 'e3260c1266ca634735f8c5f7e9ba1cef26edb6debd4a20547bc73bf78ed64214', 'Lab Admin', '6891afc601aba.jpg', 'Active', 'ljeempleo.chmsu@gmail.com', '09850858443', 0, NULL, NULL),
(10002, 'Rheign E. Palmares', 'rheign', '87c658ddfcf56591605aa6e0dbc15c462f165211a94f1acb6dfa400c1a627d34', 'ICT Staff', NULL, 'Active', 'repalmares.chmsu@gmail.com', '09515884040', 0, NULL, NULL),
(10003, 'Joleah Dinese Agnes', 'Dinese', 'f6a8104845844173f3dd569203a25216365049b00c1c9452e9462cb61a1dfa5a', 'Student Assistant', NULL, 'Active', 'jvagnes.chmsu@gmail.com', '09876453274', 0, NULL, NULL),
(10004, 'Jo Wilson', 'Jos', 'b989b666596678721a19bf0e2d6c0bdf27b6b313df3524344754b2908af4815c', 'ICT Staff', NULL, 'Active', NULL, NULL, 0, NULL, NULL),
(10006, 'Ellie Zhyle', 'Ellie', '328f65aa178b2501d3d0ea6ca022f30b239281fd0ee5500b41a9f1290e606bcd', 'Student Assistant', NULL, 'Active', 'elliezhyle@gmail.com', '09874858347', 0, NULL, NULL),
(10007, 'Olivia Benson', 'Oli', '8ef7119a1fd1f2c42d75132521aae79cf7ce87f8feb809311264d378158a195a', 'Student Assistant', NULL, 'Active', 'oliviabmg1116@gmail.com', '09876574627', 0, NULL, NULL),
(10008, 'Christina Yang', 'yang', 'f76343f8f82c4c05201ac7bc783c2345f5e1c0d6751c4001333ad865d5775dba', 'ICT Staff', NULL, 'Active', 'christinayang472@gmail.com', '09876543212', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `name`, `description`, `permissions`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'System administrator with full access', '[\"all\"]', 1, '2025-10-22 00:29:36', '2025-10-22 00:29:36'),
(2, 'Lab Admin', 'Laboratory administrator', '[\"equipment\", \"maintenance\", \"borrowers\"]', 1, '2025-10-22 00:29:36', '2025-10-22 00:29:36'),
(3, 'ICT Staff', 'ICT support staff', '[\"equipment\", \"maintenance\", \"ict_support\"]', 1, '2025-10-22 00:29:36', '2025-10-22 00:29:36'),
(4, 'Chairperson', 'Department chairperson', '[\"reports\", \"borrowers\"]', 1, '2025-10-22 00:29:36', '2025-10-22 00:29:36'),
(5, 'Student Assistant', 'Student assistant with limited access', '[\"equipment_view\"]', 1, '2025-10-22 00:29:36', '2025-10-22 00:29:36');

-- --------------------------------------------------------

--
-- Table structure for table `years`
--

CREATE TABLE `years` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `years`
--

INSERT INTO `years` (`id`, `name`) VALUES
(1, '3rd');

-- --------------------------------------------------------

--
-- Structure for view `maintenance_dashboard`
--
DROP TABLE IF EXISTS `maintenance_dashboard`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `maintenance_dashboard`  AS SELECT `e`.`id` AS `id`, `e`.`name` AS `equipment_name`, `e`.`serial_number` AS `serial_number`, `e`.`location` AS `location`, `e`.`installation_date` AS `installation_date`, `e`.`last_maintenance_date` AS `last_maintenance_date`, `e`.`next_maintenance_date` AS `next_maintenance_date`, `e`.`maintenance_interval_months` AS `maintenance_interval_months`, CASE WHEN `e`.`next_maintenance_date` < curdate() THEN 'Overdue' WHEN `e`.`next_maintenance_date` <= curdate() + interval 7 day THEN 'Due Soon' WHEN `e`.`next_maintenance_date` <= curdate() + interval 30 day THEN 'Upcoming' ELSE 'On Schedule' END AS `maintenance_status`, to_days(`e`.`next_maintenance_date`) - to_days(curdate()) AS `days_until_maintenance` FROM `equipment` AS `e` WHERE `e`.`is_archived` = 0 AND `e`.`next_maintenance_date` is not null ORDER BY `e`.`next_maintenance_date` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `pending_reminders`
--
DROP TABLE IF EXISTS `pending_reminders`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `pending_reminders`  AS SELECT `mr`.`id` AS `id`, `mr`.`equipment_id` AS `equipment_id`, `e`.`name` AS `equipment_name`, `e`.`location` AS `location`, `mr`.`reminder_date` AS `reminder_date`, `mr`.`reminder_type` AS `reminder_type`, `mr`.`status` AS `status`, CASE WHEN `mr`.`reminder_type` = '30_days_before' THEN '30 days before maintenance' WHEN `mr`.`reminder_type` = '7_days_before' THEN '7 days before maintenance' WHEN `mr`.`reminder_type` = 'due_date' THEN 'Maintenance due today' WHEN `mr`.`reminder_type` = 'overdue' THEN 'Maintenance overdue' END AS `reminder_message` FROM (`maintenance_reminders` `mr` join `equipment` `e` on(`mr`.`equipment_id` = `e`.`id`)) WHERE `mr`.`status` = 'Pending' AND `mr`.`reminder_date` <= curdate() ORDER BY `mr`.`reminder_date` ASC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `borrowers`
--
ALTER TABLE `borrowers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `borrow_items`
--
ALTER TABLE `borrow_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `borrow_slip_id` (`borrow_slip_id`);

--
-- Indexes for table `borrow_requests`
--
ALTER TABLE `borrow_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `control_number` (`control_number`),
  ADD UNIQUE KEY `control_number_2` (`control_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `borrow_request_items`
--
ALTER TABLE `borrow_request_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indexes for table `borrow_slips`
--
ALTER TABLE `borrow_slips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `control_number` (`control_number`);

--
-- Indexes for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `departments_master`
--
ALTER TABLE `departments_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_name` (`name`),
  ADD UNIQUE KEY `unique_code` (`code`);

--
-- Indexes for table `email_verification_codes`
--
ALTER TABLE `email_verification_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_code` (`verification_code`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_equipment_installation_date` (`installation_date`),
  ADD KEY `idx_equipment_next_maintenance` (`next_maintenance_date`);

--
-- Indexes for table `equipment_categories`
--
ALTER TABLE `equipment_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_name` (`name`);

--
-- Indexes for table `equipment_items_master`
--
ALTER TABLE `equipment_items_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_item_per_category` (`category`,`item_name`);

--
-- Indexes for table `equipment_logs`
--
ALTER TABLE `equipment_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipment_id` (`equipment_id`),
  ADD KEY `deleted_by` (`deleted_by`);

--
-- Indexes for table `equipment_models`
--
ALTER TABLE `equipment_models`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_name_brand` (`name`,`brand`),
  ADD KEY `fk_category` (`category_id`);

--
-- Indexes for table `equipment_models_master`
--
ALTER TABLE `equipment_models_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_model` (`category`,`model_name`);

--
-- Indexes for table `equipment_status`
--
ALTER TABLE `equipment_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_name` (`name`);

--
-- Indexes for table `ict_support_requests`
--
ALTER TABLE `ict_support_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `labs`
--
ALTER TABLE `labs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_reservations`
--
ALTER TABLE `lab_reservations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `control_number` (`control_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `lab_id` (`lab_id`),
  ADD KEY `fk_lab_reservations_borrower` (`borrower_id`);

--
-- Indexes for table `maintenance_records`
--
ALTER TABLE `maintenance_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipment_id` (`equipment_id`);

--
-- Indexes for table `maintenance_reminders`
--
ALTER TABLE `maintenance_reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipment_id` (`equipment_id`),
  ADD KEY `idx_maintenance_reminders_date` (`reminder_date`),
  ADD KEY `idx_maintenance_reminders_status` (`status`);

--
-- Indexes for table `maintenance_schedule`
--
ALTER TABLE `maintenance_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipment_id` (`equipment_id`),
  ADD KEY `idx_maintenance_schedule_date` (`scheduled_date`),
  ADD KEY `idx_maintenance_schedule_status` (`status`);

--
-- Indexes for table `maintenance_types`
--
ALTER TABLE `maintenance_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_name` (`name`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `software`
--
ALTER TABLE `software`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_software_lab` (`lab_id`),
  ADD KEY `idx_software_pc` (`pc_number`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_name` (`name`);

--
-- Indexes for table `years`
--
ALTER TABLE `years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `borrowers`
--
ALTER TABLE `borrowers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `borrow_items`
--
ALTER TABLE `borrow_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `borrow_requests`
--
ALTER TABLE `borrow_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `borrow_request_items`
--
ALTER TABLE `borrow_request_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `borrow_slips`
--
ALTER TABLE `borrow_slips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `departments_master`
--
ALTER TABLE `departments_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `email_verification_codes`
--
ALTER TABLE `email_verification_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `equipment_categories`
--
ALTER TABLE `equipment_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `equipment_items_master`
--
ALTER TABLE `equipment_items_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `equipment_logs`
--
ALTER TABLE `equipment_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `equipment_models`
--
ALTER TABLE `equipment_models`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `equipment_models_master`
--
ALTER TABLE `equipment_models_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `equipment_status`
--
ALTER TABLE `equipment_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `ict_support_requests`
--
ALTER TABLE `ict_support_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `labs`
--
ALTER TABLE `labs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `lab_reservations`
--
ALTER TABLE `lab_reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `maintenance_records`
--
ALTER TABLE `maintenance_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `maintenance_reminders`
--
ALTER TABLE `maintenance_reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `maintenance_schedule`
--
ALTER TABLE `maintenance_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `maintenance_types`
--
ALTER TABLE `maintenance_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `software`
--
ALTER TABLE `software`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10009;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `years`
--
ALTER TABLE `years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrow_items`
--
ALTER TABLE `borrow_items`
  ADD CONSTRAINT `borrow_items_ibfk_1` FOREIGN KEY (`borrow_slip_id`) REFERENCES `borrow_slips` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `borrow_requests`
--
ALTER TABLE `borrow_requests`
  ADD CONSTRAINT `borrow_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `borrow_request_items`
--
ALTER TABLE `borrow_request_items`
  ADD CONSTRAINT `borrow_request_items_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `borrow_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `borrow_request_items_ibfk_2` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD CONSTRAINT `fk_calendar_events_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_verification_tokens`
--
ALTER TABLE `email_verification_tokens`
  ADD CONSTRAINT `email_verification_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `equipment_logs`
--
ALTER TABLE `equipment_logs`
  ADD CONSTRAINT `equipment_logs_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `equipment_logs_ibfk_2` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `equipment_models`
--
ALTER TABLE `equipment_models`
  ADD CONSTRAINT `fk_equipment_models_category` FOREIGN KEY (`category_id`) REFERENCES `equipment_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lab_reservations`
--
ALTER TABLE `lab_reservations`
  ADD CONSTRAINT `fk_lab_reservations_borrower` FOREIGN KEY (`borrower_id`) REFERENCES `borrowers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lab_reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lab_reservations_ibfk_2` FOREIGN KEY (`lab_id`) REFERENCES `labs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_records`
--
ALTER TABLE `maintenance_records`
  ADD CONSTRAINT `maintenance_records_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_reminders`
--
ALTER TABLE `maintenance_reminders`
  ADD CONSTRAINT `maintenance_reminders_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_schedule`
--
ALTER TABLE `maintenance_schedule`
  ADD CONSTRAINT `maintenance_schedule_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `software`
--
ALTER TABLE `software`
  ADD CONSTRAINT `fk_software_lab` FOREIGN KEY (`lab_id`) REFERENCES `labs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
