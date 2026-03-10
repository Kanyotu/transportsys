-- Sample Data for SACCO Manager
-- Passwords are 'password123' (hashed)

-- Add a new SACCO for demonstration
INSERT INTO `saccos` (`sacconame`, `mpesashortcode`, `mpesapasskey`, `qr_identifier`, `status`) 
VALUES ('Star Transporters', '654321', 'passkey_star', 'STAR001', 1);

-- Get the last inserted SACCO ID
SET @star_sacco_id = LAST_INSERT_ID();

-- Add a SACCO Manager user
INSERT INTO `users` (`username`, `phoneno`, `email`, `hashedpassword`, `type`, `saccoid`) 
VALUES ('Manager Mike', '0722000111', 'mike@startransport.com', '$2y$10$Laz.vtOv5V9.N.emcN95jO.7Zi8ZI971qNfYYNMQAovb.6YOqPhhy', 'sacco', @star_sacco_id);

-- Add some drivers
INSERT INTO `drivers` (`dname`, `phoneno`) VALUES 
('Peter Karanja', '0711222333'),
('Samuel Waweru', '0722333444');

-- Add some vehicles for this SACCO
INSERT INTO `buses` (`saccoid`, `platenumber`, `capacity`, `status`, `driverid`) VALUES 
(@star_sacco_id, 'KDD 444X', 14, 1, (SELECT driverid FROM drivers WHERE dname = 'Peter Karanja')),
(@star_sacco_id, 'KEE 555Y', 32, 1, (SELECT driverid FROM drivers WHERE dname = 'Samuel Waweru'));

-- Add some routes for this SACCO
INSERT INTO `routes` (`routename`, `saccoid`) VALUES 
('Nairobi - Murang\'a', @star_sacco_id),
('Murang\'a - Nairobi', @star_sacco_id);

-- Add sample trips
INSERT INTO `trips` (`routeid`, `busid`, `driverid`, `starttime`, `status`, `trip_type`) VALUES 
((SELECT routeid FROM routes WHERE routename = 'Nairobi - Murang\'a' LIMIT 1), (SELECT busid FROM buses WHERE platenumber = 'KDD 444X'), (SELECT driverid FROM drivers WHERE dname = 'Peter Karanja'), NOW() + INTERVAL 2 HOUR, 'active', 'short');
