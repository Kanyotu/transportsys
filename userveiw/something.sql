-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 11, 2026 at 11:16 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `something`
--

-- --------------------------------------------------------

--
-- Table structure for table `buses`
--

CREATE TABLE `buses` (
  `busid` int(11) NOT NULL,
  `saccoid` int(11) NOT NULL,
  `platenumber` varchar(20) NOT NULL,
  `capacity` int(11) DEFAULT 14,
  `status` tinyint(4) DEFAULT 1,
  `driverid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buses`
--

INSERT INTO `buses` (`busid`, `saccoid`, `platenumber`, `capacity`, `status`, `driverid`) VALUES
(1, 1, 'KBC 123A', 14, 1, 1),
(2, 2, 'KDD 444X', 14, 1, 2),
(3, 2, 'KEE 555Y', 32, 1, 3),
(4, 2, 'KDF 256H', 34, 1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `driverid` int(11) NOT NULL,
  `dname` varchar(100) NOT NULL,
  `phoneno` varchar(15) NOT NULL,
  `hashedpassword` varchar(255) DEFAULT NULL,
  `saccoid` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`driverid`, `dname`, `phoneno`, `hashedpassword`, `saccoid`, `email`) VALUES
(1, 'John Doe', '0787654321', '$2y$10$hiikOTtHVIzlEuF1RGTmuu./qMYioSel2gKqitKCbde1iY5tDmhZG', 2, NULL),
(2, 'Peter Karanja', '0711222333', NULL, 2, NULL),
(3, 'Samuel Waweru', '0722333444', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `fares`
--

CREATE TABLE `fares` (
  `fareid` int(11) NOT NULL,
  `routeid` int(11) NOT NULL,
  `fromstageid` int(11) NOT NULL,
  `tostageid` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fares`
--

INSERT INTO `fares` (`fareid`, `routeid`, `fromstageid`, `tostageid`, `amount`) VALUES
(1, 1, 1, 2, 50.00),
(2, 1, 1, 3, 100.00),
(3, 1, 2, 3, 50.00),
(6, 3, 10, 11, 150.00),
(7, 3, 10, 9, 100.00),
(8, 3, 10, 12, 50.00),
(9, 3, 9, 11, 80.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `paymentid` int(11) NOT NULL,
  `sessionid` int(11) NOT NULL,
  `mpesareceipt` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `phoneno` varchar(15) NOT NULL,
  `status` enum('success','failed','pending') DEFAULT 'pending',
  `createdat` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `routeid` int(11) NOT NULL,
  `routename` varchar(100) NOT NULL,
  `saccoid` int(11) DEFAULT NULL,
  `createdat` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `routes`
--

INSERT INTO `routes` (`routeid`, `routename`, `saccoid`, `createdat`) VALUES
(1, 'Nairobi CBD - Westlands', 1, '2026-02-26 16:18:22'),
(2, 'Nairobi - Murang\'a', 2, '2026-03-10 16:59:34'),
(3, 'Murang\'a - Nairobi', 2, '2026-03-10 16:59:34');

-- --------------------------------------------------------

--
-- Table structure for table `saccos`
--

CREATE TABLE `saccos` (
  `saccoid` int(11) NOT NULL,
  `sacconame` varchar(50) NOT NULL,
  `mpesashortcode` varchar(20) NOT NULL,
  `mpesapasskey` varchar(255) NOT NULL,
  `qr_identifier` varchar(100) NOT NULL,
  `status` tinyint(4) DEFAULT 1,
  `createdat` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saccos`
--

INSERT INTO `saccos` (`saccoid`, `sacconame`, `mpesashortcode`, `mpesapasskey`, `qr_identifier`, `status`, `createdat`) VALUES
(1, 'Matatu Express', '123456', 'passkey123', 'SAC001', 1, '2026-02-26 16:18:22'),
(2, 'Star Transporters', '654321', 'passkey_star', 'STAR001', 1, '2026-03-10 16:59:34');

-- --------------------------------------------------------

--
-- Table structure for table `seats`
--

CREATE TABLE `seats` (
  `seatid` int(11) NOT NULL,
  `busid` int(11) NOT NULL,
  `seatnumber` varchar(5) NOT NULL,
  `status` enum('available','occupied') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seats`
--

INSERT INTO `seats` (`seatid`, `busid`, `seatnumber`, `status`) VALUES
(1, 1, 'S1', 'available'),
(2, 1, 'S2', 'available'),
(3, 1, 'S3', 'available'),
(4, 1, 'S4', 'available'),
(5, 1, 'S5', 'available'),
(6, 4, 'S1', 'available'),
(7, 4, 'S2', 'available'),
(8, 4, 'S3', 'available'),
(9, 4, 'S4', 'available'),
(10, 4, 'S5', 'available'),
(11, 4, 'S6', 'available'),
(12, 4, 'S7', 'available'),
(13, 4, 'S8', 'available'),
(14, 4, 'S9', 'available'),
(15, 4, 'S10', 'available'),
(16, 4, 'S11', 'available'),
(17, 4, 'S12', 'available'),
(18, 4, 'S13', 'available'),
(19, 4, 'S14', 'available'),
(20, 4, 'S15', 'available'),
(21, 4, 'S16', 'available'),
(22, 4, 'S17', 'available'),
(23, 4, 'S18', 'available'),
(24, 4, 'S19', 'available'),
(25, 4, 'S20', 'available'),
(26, 4, 'S21', 'available'),
(27, 4, 'S22', 'available'),
(28, 4, 'S23', 'available'),
(29, 4, 'S24', 'available'),
(30, 4, 'S25', 'available'),
(31, 4, 'S26', 'available'),
(32, 4, 'S27', 'available'),
(33, 4, 'S28', 'available'),
(34, 4, 'S29', 'available'),
(35, 4, 'S30', 'available'),
(36, 4, 'S31', 'available'),
(37, 4, 'S32', 'available'),
(38, 4, 'S33', 'available'),
(39, 4, 'S34', 'available');

-- --------------------------------------------------------

--
-- Table structure for table `spendingsummary`
--

CREATE TABLE `spendingsummary` (
  `summaryid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `date` date NOT NULL,
  `totalamount` decimal(10,2) NOT NULL,
  `month` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stages`
--

CREATE TABLE `stages` (
  `stageid` int(11) NOT NULL,
  `routeid` int(11) NOT NULL,
  `stagename` varchar(100) NOT NULL,
  `stageorder` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stages`
--

INSERT INTO `stages` (`stageid`, `routeid`, `stagename`, `stageorder`) VALUES
(1, 1, 'Nairobi CBD', 1),
(2, 1, 'Museum Hill', 2),
(3, 1, 'Westlands Mall', 3),
(4, 2, 'Maragua', 1),
(5, 2, 'Mukuyu', 2),
(7, 2, 'Kenol', 3),
(8, 2, 'Sabasaba', 4),
(9, 3, 'Maragua', 1),
(10, 3, 'Mukuyu', 2),
(11, 3, 'Kenol', 3),
(12, 3, 'Sabasaba', 4);

-- --------------------------------------------------------

--
-- Table structure for table `trips`
--

CREATE TABLE `trips` (
  `tripid` int(11) NOT NULL,
  `routeid` int(11) NOT NULL,
  `busid` int(11) NOT NULL,
  `driverid` int(11) DEFAULT NULL,
  `starttime` datetime DEFAULT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `trip_type` enum('short','long') NOT NULL DEFAULT 'short'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trips`
--

INSERT INTO `trips` (`tripid`, `routeid`, `busid`, `driverid`, `starttime`, `status`, `trip_type`) VALUES
(1, 1, 1, 1, '2026-02-26 16:18:22', 'active', 'short'),
(2, 1, 1, 1, '2026-02-27 08:00:00', 'active', 'long'),
(3, 2, 2, 2, '2026-03-10 18:59:34', 'active', 'short'),
(4, 3, 4, 2, '2025-10-03 21:30:00', 'active', 'long'),
(5, 3, 3, 3, '2027-02-04 15:26:00', 'active', 'long');

-- --------------------------------------------------------

--
-- Table structure for table `tripsessions`
--

CREATE TABLE `tripsessions` (
  `sessionid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `tripid` int(11) NOT NULL,
  `saccoid` int(11) NOT NULL,
  `busid` int(11) DEFAULT NULL,
  `fromstageid` int(11) DEFAULT NULL,
  `tostageid` int(11) DEFAULT NULL,
  `fareamount` decimal(10,2) NOT NULL,
  `seatid` int(11) DEFAULT NULL,
  `status` enum('pending','paid','expired','cancelled') DEFAULT 'pending',
  `boarding_status` enum('pending','boarded') DEFAULT 'pending',
  `disability_type` varchar(50) DEFAULT 'None',
  `luggage_type` varchar(50) DEFAULT 'None',
  `expiresat` datetime DEFAULT NULL,
  `createdat` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tripsessions`
--

INSERT INTO `tripsessions` (`sessionid`, `userid`, `tripid`, `saccoid`, `busid`, `fromstageid`, `tostageid`, `fareamount`, `seatid`, `status`, `boarding_status`, `disability_type`, `luggage_type`, `expiresat`, `createdat`) VALUES
(9, 3, 2, 1, 1, 1, 3, 200.00, 3, 'cancelled', 'pending', 'None', 'Large', '2026-02-26 17:45:29', '2026-02-26 17:35:29'),
(10, 3, 2, 1, 1, 1, 3, 100.00, NULL, 'cancelled', 'pending', 'None', 'None', '2026-03-01 15:39:50', '2026-03-01 15:29:50'),
(11, 3, 2, 1, 1, 2, 3, 50.00, 1, 'cancelled', 'pending', 'Visually Impaired', 'None', '2026-03-10 15:26:25', '2026-03-10 15:16:25'),
(12, 3, 5, 2, 3, 9, 11, 180.00, 13, 'cancelled', 'pending', 'Visually Impaired', 'Large', '2026-03-10 18:15:39', '2026-03-10 18:05:39');

-- --------------------------------------------------------

--
-- Table structure for table `trip_issues`
--

CREATE TABLE `trip_issues` (
  `issueid` int(11) NOT NULL,
  `tripid` int(11) NOT NULL,
  `driverid` int(11) NOT NULL,
  `description` text NOT NULL,
  `status` enum('reported','resolved') DEFAULT 'reported',
  `createdat` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userid` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `phoneno` varchar(15) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `hashedpassword` varchar(255) NOT NULL,
  `monthlybudget` decimal(10,2) DEFAULT NULL,
  `datejoined` datetime DEFAULT current_timestamp(),
  `type` enum('user','sacco','admin') NOT NULL DEFAULT 'user',
  `saccoid` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userid`, `username`, `phoneno`, `email`, `hashedpassword`, `monthlybudget`, `datejoined`, `type`, `saccoid`) VALUES
(3, 'joseph', '0707074553', '', '$2y$10$WdTT/Rzj6O.VNLQGFSnd6OjxQEDXgVq6mM9cFB1hMq53E68ew2B7K', 10000.00, '2025-12-25 00:00:00', 'user', NULL),
(4, 'joseph', '0707074552', '', '$2y$10$Laz.vtOv5V9.N.emcN95jO.7Zi8ZI971qNfYYNMQAovb.6YOqPhhy', NULL, '2025-12-26 00:00:00', 'user', NULL),
(5, 'Tracy', '0712345678', '', '$2y$10$R0rwcIjghnF8dTYMSDPsle0DGMIBXgWz3zEyWXMR4Asohtzh8fu16', NULL, '2026-01-09 00:00:00', 'user', NULL),
(6, 'john', '0723456789', '', '$2y$10$uhwYT2Lh3x4eCZca3xH2hOWmDTXgVAXonj6nA2I6c86iB9H8wkeAO', NULL, '2026-01-15 00:00:00', 'user', NULL),
(7, 'Steve', '0768216484', 'kariuki@gmail.com', '$2y$10$4gq1dN3hxcM0ymX/1dyLD.o/yJenPOmo9q1q/tBMj1iOczqj0C9wO', 123333.00, '2026-01-26 00:00:00', 'user', NULL),
(8, 'kanyotu', '0700000000', '', '$2y$10$ZWea.MldKA05PlPkpIWz.eNXtqiHbNbNYsxTZxLp4JmZfc3Abi.u.', NULL, '2026-03-10 16:18:06', 'admin', NULL),
(9, 'Manager Mike', '0722000111', 'mike@startransport.com', '$2y$10$Laz.vtOv5V9.N.emcN95jO.7Zi8ZI971qNfYYNMQAovb.6YOqPhhy', NULL, '2026-03-10 16:59:34', 'sacco', 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buses`
--
ALTER TABLE `buses`
  ADD PRIMARY KEY (`busid`),
  ADD UNIQUE KEY `platenumber` (`platenumber`),
  ADD KEY `saccoid` (`saccoid`),
  ADD KEY `driverid` (`driverid`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`driverid`),
  ADD KEY `saccoid` (`saccoid`);

--
-- Indexes for table `fares`
--
ALTER TABLE `fares`
  ADD PRIMARY KEY (`fareid`),
  ADD KEY `routeid` (`routeid`),
  ADD KEY `fromstageid` (`fromstageid`),
  ADD KEY `tostageid` (`tostageid`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`paymentid`),
  ADD KEY `sessionid` (`sessionid`);

--
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`routeid`),
  ADD KEY `saccoid` (`saccoid`);

--
-- Indexes for table `saccos`
--
ALTER TABLE `saccos`
  ADD PRIMARY KEY (`saccoid`),
  ADD UNIQUE KEY `qr_identifier` (`qr_identifier`);

--
-- Indexes for table `seats`
--
ALTER TABLE `seats`
  ADD PRIMARY KEY (`seatid`),
  ADD KEY `busid` (`busid`);

--
-- Indexes for table `spendingsummary`
--
ALTER TABLE `spendingsummary`
  ADD PRIMARY KEY (`summaryid`),
  ADD KEY `userid` (`userid`);

--
-- Indexes for table `stages`
--
ALTER TABLE `stages`
  ADD PRIMARY KEY (`stageid`),
  ADD KEY `routeid` (`routeid`);

--
-- Indexes for table `trips`
--
ALTER TABLE `trips`
  ADD PRIMARY KEY (`tripid`),
  ADD KEY `routeid` (`routeid`),
  ADD KEY `busid` (`busid`),
  ADD KEY `driverid` (`driverid`);

--
-- Indexes for table `tripsessions`
--
ALTER TABLE `tripsessions`
  ADD PRIMARY KEY (`sessionid`),
  ADD KEY `userid` (`userid`),
  ADD KEY `tripid` (`tripid`),
  ADD KEY `fromstageid` (`fromstageid`),
  ADD KEY `tostageid` (`tostageid`);

--
-- Indexes for table `trip_issues`
--
ALTER TABLE `trip_issues`
  ADD PRIMARY KEY (`issueid`),
  ADD KEY `tripid` (`tripid`),
  ADD KEY `driverid` (`driverid`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userid`),
  ADD UNIQUE KEY `phoneno` (`phoneno`),
  ADD KEY `saccoid` (`saccoid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buses`
--
ALTER TABLE `buses`
  MODIFY `busid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `driverid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `fares`
--
ALTER TABLE `fares`
  MODIFY `fareid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `paymentid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `routeid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `saccos`
--
ALTER TABLE `saccos`
  MODIFY `saccoid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `seats`
--
ALTER TABLE `seats`
  MODIFY `seatid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `spendingsummary`
--
ALTER TABLE `spendingsummary`
  MODIFY `summaryid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stages`
--
ALTER TABLE `stages`
  MODIFY `stageid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `trips`
--
ALTER TABLE `trips`
  MODIFY `tripid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tripsessions`
--
ALTER TABLE `tripsessions`
  MODIFY `sessionid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `trip_issues`
--
ALTER TABLE `trip_issues`
  MODIFY `issueid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `buses`
--
ALTER TABLE `buses`
  ADD CONSTRAINT `buses_ibfk_1` FOREIGN KEY (`saccoid`) REFERENCES `saccos` (`saccoid`),
  ADD CONSTRAINT `buses_ibfk_2` FOREIGN KEY (`driverid`) REFERENCES `drivers` (`driverid`);

--
-- Constraints for table `drivers`
--
ALTER TABLE `drivers`
  ADD CONSTRAINT `drivers_ibfk_1` FOREIGN KEY (`saccoid`) REFERENCES `saccos` (`saccoid`);

--
-- Constraints for table `fares`
--
ALTER TABLE `fares`
  ADD CONSTRAINT `fares_ibfk_1` FOREIGN KEY (`routeid`) REFERENCES `routes` (`routeid`),
  ADD CONSTRAINT `fares_ibfk_2` FOREIGN KEY (`fromstageid`) REFERENCES `stages` (`stageid`),
  ADD CONSTRAINT `fares_ibfk_3` FOREIGN KEY (`tostageid`) REFERENCES `stages` (`stageid`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`sessionid`) REFERENCES `tripsessions` (`sessionid`);

--
-- Constraints for table `routes`
--
ALTER TABLE `routes`
  ADD CONSTRAINT `routes_ibfk_1` FOREIGN KEY (`saccoid`) REFERENCES `saccos` (`saccoid`);

--
-- Constraints for table `seats`
--
ALTER TABLE `seats`
  ADD CONSTRAINT `seats_ibfk_1` FOREIGN KEY (`busid`) REFERENCES `buses` (`busid`);

--
-- Constraints for table `spendingsummary`
--
ALTER TABLE `spendingsummary`
  ADD CONSTRAINT `spendingsummary_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`);

--
-- Constraints for table `stages`
--
ALTER TABLE `stages`
  ADD CONSTRAINT `stages_ibfk_1` FOREIGN KEY (`routeid`) REFERENCES `routes` (`routeid`);

--
-- Constraints for table `trips`
--
ALTER TABLE `trips`
  ADD CONSTRAINT `trips_ibfk_1` FOREIGN KEY (`routeid`) REFERENCES `routes` (`routeid`),
  ADD CONSTRAINT `trips_ibfk_2` FOREIGN KEY (`busid`) REFERENCES `buses` (`busid`),
  ADD CONSTRAINT `trips_ibfk_3` FOREIGN KEY (`driverid`) REFERENCES `drivers` (`driverid`);

--
-- Constraints for table `tripsessions`
--
ALTER TABLE `tripsessions`
  ADD CONSTRAINT `tripsessions_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`userid`),
  ADD CONSTRAINT `tripsessions_ibfk_2` FOREIGN KEY (`tripid`) REFERENCES `trips` (`tripid`),
  ADD CONSTRAINT `tripsessions_ibfk_3` FOREIGN KEY (`fromstageid`) REFERENCES `stages` (`stageid`),
  ADD CONSTRAINT `tripsessions_ibfk_4` FOREIGN KEY (`tostageid`) REFERENCES `stages` (`stageid`);

--
-- Constraints for table `trip_issues`
--
ALTER TABLE `trip_issues`
  ADD CONSTRAINT `trip_issues_ibfk_1` FOREIGN KEY (`tripid`) REFERENCES `trips` (`tripid`),
  ADD CONSTRAINT `trip_issues_ibfk_2` FOREIGN KEY (`driverid`) REFERENCES `drivers` (`driverid`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`saccoid`) REFERENCES `saccos` (`saccoid`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
