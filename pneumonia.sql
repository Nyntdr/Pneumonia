-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 02, 2025 at 11:05 AM
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
-- Database: `pneumonia`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `a_id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`a_id`, `username`, `password`, `role`) VALUES
(1, 'admin', '$2y$10$OoyC3hSCHruRwF7DxREA7OFA6Rj3cx0hCks1eUYIIs51A0C3b.2zm', 'admin'),
(2, 'userAB', '$2y$10$aSg1HHRHpNx.qOLuaS.MP.GZvoREi0o8nr7vlRXacYeMyczc5b6cK', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `Result_ID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `ScanID` int(11) DEFAULT NULL,
  `Username` varchar(255) DEFAULT NULL,
  `Outcome` varchar(100) DEFAULT NULL,
  `UploadDate` datetime DEFAULT NULL,
  `Confidence` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `results`
--

INSERT INTO `results` (`Result_ID`, `UserID`, `ScanID`, `Username`, `Outcome`, `UploadDate`, `Confidence`) VALUES
(33, 4, 46, 'anita', 'Pneumonia', '2025-07-31 13:42:32', 100),
(34, 4, 47, 'anita', 'Pneumonia', '2025-07-31 13:42:40', 100),
(35, 4, 48, 'anita', 'Pneumonia', '2025-07-31 13:42:50', 100),
(36, 4, 49, 'anita', 'Pneumonia', '2025-07-31 13:43:04', 99.84),
(37, 4, 50, 'anita', 'Pneumonia', '2025-07-31 13:43:11', 98.54),
(38, 4, 51, 'anita', 'Pneumonia', '2025-07-31 13:43:19', 73.45),
(39, 4, 52, 'anita', 'Normal', '2025-07-31 13:43:26', 65.61),
(40, 4, 53, 'anita', 'Normal', '2025-07-31 13:43:45', 99.74),
(42, 3, 39, 'nayan', 'Normal', '2025-07-31 12:01:42', 85.35),
(43, 3, 40, 'nayan', 'Normal', '2025-07-31 12:56:48', 97.99),
(44, 3, 41, 'nayan', 'Pneumonia', '2025-07-31 13:38:06', 100),
(45, 3, 42, 'nayan', 'Normal', '2025-07-31 13:38:18', 65.61),
(46, 3, 43, 'nayan', 'Pneumonia', '2025-07-31 13:39:04', 99.37),
(47, 3, 44, 'nayan', 'Pneumonia', '2025-07-31 13:39:18', 99.59),
(48, 3, 45, 'nayan', 'Pneumonia', '2025-07-31 13:39:31', 100),
(50, 3, 56, 'nayan', 'Normal', '2025-07-31 14:47:40', 99.36),
(51, 3, 57, 'nayan', 'Normal', '2025-07-31 14:48:38', 88.69);

-- --------------------------------------------------------

--
-- Table structure for table `scans`
--

CREATE TABLE `scans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `outcome` varchar(50) NOT NULL,
  `Confidence` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scans`
--

INSERT INTO `scans` (`id`, `user_id`, `image_path`, `upload_date`, `outcome`, `Confidence`) VALUES
(39, 3, '../uploads/688b0a4a9822b_IM-0001-0001.jpeg', '2025-07-31 06:16:42', 'Normal', 85.35),
(40, 3, '../uploads/688b1733788fc_IM-0010-0001.jpeg', '2025-07-31 07:11:48', 'Normal', 97.99),
(41, 3, '../uploads/688b20e180705_IM-0011-0001.jpeg', '2025-07-31 07:53:06', 'Pneumonia', 100),
(42, 3, '../uploads/688b20ee338d4_IM-0011-0001-0002.jpeg', '2025-07-31 07:53:18', 'Normal', 65.61),
(43, 3, '../uploads/688b211bab7ce_NORMAL2-IM-0381-0001.jpeg', '2025-07-31 07:54:04', 'Pneumonia', 99.37),
(44, 3, '../uploads/688b2129d3a44_IM-0036-0001.jpeg', '2025-07-31 07:54:18', 'Pneumonia', 99.59),
(45, 3, '../uploads/688b21375bb79_person3_virus_16.jpeg', '2025-07-31 07:54:31', 'Pneumonia', 100),
(46, 4, '../uploads/688b21ebaf1ff_person1_virus_6.jpeg', '2025-07-31 07:57:32', 'Pneumonia', 100),
(47, 4, '../uploads/688b21f444063_person3_virus_16.jpeg', '2025-07-31 07:57:40', 'Pneumonia', 100),
(48, 4, '../uploads/688b21fe7f0a9_person25_virus_59.jpeg', '2025-07-31 07:57:50', 'Pneumonia', 100),
(49, 4, '../uploads/688b220bb38b4_IM-0003-0001.jpeg', '2025-07-31 07:58:04', 'Pneumonia', 99.84),
(50, 4, '../uploads/688b2212ece1b_IM-0005-0001.jpeg', '2025-07-31 07:58:11', 'Pneumonia', 98.54),
(51, 4, '../uploads/688b221abea16_IM-0007-0001.jpeg', '2025-07-31 07:58:19', 'Pneumonia', 73.45),
(52, 4, '../uploads/688b2222426c5_IM-0011-0001-0002.jpeg', '2025-07-31 07:58:26', 'Normal', 65.61),
(53, 4, '../uploads/688b22358c3fb_IM-0128-0001.jpeg', '2025-07-31 07:58:45', 'Normal', 99.74),
(56, 3, '../uploads/688b312fd9ed0_IM-0115-0001.jpeg', '2025-07-31 09:02:40', 'Normal', 99.36),
(57, 3, '../uploads/688b316a38263_IM-0125-0001.jpeg', '2025-07-31 09:03:38', 'Normal', 88.69),
(66, 3, '../uploads/688b39dc6318b_person1_virus_7.jpeg', '2025-07-31 09:39:40', 'Pneumonia', 100),
(67, 3, '../uploads/688b3a07c4075_IM-0137-0001.jpeg', '2025-07-31 09:40:24', 'Normal', 95.36),
(69, 3, '../uploads/688dcd9648ec7_IM-0141-0001.jpeg', '2025-08-02 08:34:31', 'Normal', 99.93),
(70, 15, '../uploads/688dd4824af7b_IM-0119-0001.jpeg', '2025-08-02 09:04:02', 'Normal', 99.82);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin', '$2y$10$OoyC3hSCHruRwF7DxREA7OFA6Rj3cx0hCks1eUYIIs51A0C3b.2zm', 'admin'),
(2, 'User124', '$2y$10$63kwNUZKcfbD.RYdnKSgDuHvmVMzR5yjR2IjrplzkZfaBrj8yNhgG', 'user'),
(3, 'nayan', '$2y$10$6V2yj9YdbRBPuAPeq75lzuIAmoGNxXQKW0Wv6gs0Srh.d2rV0UtY6', 'user'),
(4, 'anita', '$2y$10$oIcsrWL6HdXVQmP5B6RP7utI.7e92zLcnQCD/.mBOEQelbfKpZ1ve', 'user'),
(7, 'saya', '$2y$10$aVeVTvVpACM.AKRjcY3GSu3jV8FfWZSdgxr7jP5z8YfRwtuKr7nAa', 'user'),
(11, 'Nayan12', '$2y$10$m6m/n7ZE7JDIJq0wwNvQLe2G388G99Mulij3EQYKzdlFjVUnKpc6.', 'user'),
(12, 'Anita2', '$2y$10$95anNogaw/MwOZw8x8zOUeeJoDw.BhmCOhZ16NivwwEYlfjd9auGe', 'user'),
(13, 'Anita90', '$2y$10$7K1PegJjNE3pCkShIvrzveNTa/7Idgb5uMWiBFnOr.tz8bORFZfjq', 'user'),
(14, 'Nayan300', '$2y$10$/I2TXbc5MbQqXe04VaFN2.VJPbyIg0rc2vYrdBoy4zSXMJpgFqBAi', 'user'),
(15, 'Nayan30', '$2y$10$8Ks1wNqzgN6pZ9XKO.7rbOz88yGF9zd7DTlk6LMw0NucUsUg.im4y', 'user'),
(16, 'Nayan301', '$2y$10$O0oL7CeA05Ater7aB24SK.jwKPmI/.13S0r/1yARY8NXr4jkWZn5W', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`a_id`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`Result_ID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `ScanID` (`ScanID`);

--
-- Indexes for table `scans`
--
ALTER TABLE `scans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `Result_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `scans`
--
ALTER TABLE `scans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`a_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`ScanID`) REFERENCES `scans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `scans`
--
ALTER TABLE `scans`
  ADD CONSTRAINT `scans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
