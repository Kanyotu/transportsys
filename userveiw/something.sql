-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 10, 2026 at 02:53 PM
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
(1, 1, 'KBC 123A', 14, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `driverid` int(11) NOT NULL,
  `dname` varchar(100) NOT NULL,
  `phoneno` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`driverid`, `dname`, `phoneno`) VALUES
(1, 'John Doe', '0787654321');

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
(3, 1, 2, 3, 50.00);

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
(1, 'Nairobi CBD - Westlands', 1, '2026-02-26 16:18:22');

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
(1, 'Matatu Express', '123456', 'passkey123', 'SAC001', 1, '2026-02-26 16:18:22');

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
(1, 1, 'S1', 'occupied'),
(2, 1, 'S2', 'available'),
(3, 1, 'S3', 'available'),
(4, 1, 'S4', 'occupied'),
(5, 1, 'S5', 'available');

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
(3, 1, 'Westlands Mall', 3);

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
(2, 1, 1, 1, '2026-02-27 08:00:00', 'active', 'long');

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
  `disability_type` varchar(50) DEFAULT 'None',
  `luggage_type` varchar(50) DEFAULT 'None',
  `expiresat` datetime DEFAULT NULL,
  `createdat` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tripsessions`
--

INSERT INTO `tripsessions` (`sessionid`, `userid`, `tripid`, `saccoid`, `busid`, `fromstageid`, `tostageid`, `fareamount`, `seatid`, `status`, `disability_type`, `luggage_type`, `expiresat`, `createdat`) VALUES
(9, 3, 2, 1, 1, 1, 3, 200.00, 3, 'pending', 'None', 'Large', '2026-02-26 17:45:29', '2026-02-26 17:35:29'),
(10, 3, 2, 1, 1, 1, 3, 100.00, NULL, 'cancelled', 'None', 'None', '2026-03-01 15:39:50', '2026-03-01 15:29:50'),
(11, 3, 2, 1, 1, 2, 3, 50.00, 1, 'pending', 'Visually Impaired', 'None', '2026-03-10 15:26:25', '2026-03-10 15:16:25');

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
(8, 'kanyotu', '0700000000', '', '$2y$10$ZWea.MldKA05PlPkpIWz.eNXtqiHbNbNYsxTZxLp4JmZfc3Abi.u.', NULL, '2026-03-10 16:18:06', 'admin', NULL);

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
  ADD PRIMARY KEY (`driverid`);

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
  MODIFY `busid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `driverid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fares`
--
ALTER TABLE `fares`
  MODIFY `fareid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `paymentid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `routeid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `saccos`
--
ALTER TABLE `saccos`
  MODIFY `saccoid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `seats`
--
ALTER TABLE `seats`
  MODIFY `seatid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `spendingsummary`
--
ALTER TABLE `spendingsummary`
  MODIFY `summaryid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stages`
--
ALTER TABLE `stages`
  MODIFY `stageid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `trips`
--
ALTER TABLE `trips`
  MODIFY `tripid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tripsessions`
--
ALTER TABLE `tripsessions`
  MODIFY `sessionid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`saccoid`) REFERENCES `saccos` (`saccoid`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
