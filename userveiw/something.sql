CREATE TABLE users (
  userid INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  phoneno VARCHAR(15) NOT NULL UNIQUE,
  email VARCHAR(255),
  hashedpassword VARCHAR(255) NOT NULL,
  monthlybudget DECIMAL(10,2),
  datejoined DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE saccos (
  saccoid INT AUTO_INCREMENT PRIMARY KEY,
  sacconame VARCHAR(50) NOT NULL,
  mpesashortcode VARCHAR(20) NOT NULL,
  mpesapasskey VARCHAR(255) NOT NULL,
  qr_identifier VARCHAR(100) NOT NULL UNIQUE,
  status TINYINT DEFAULT 1,
  createdat DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE drivers (
  driverid INT AUTO_INCREMENT PRIMARY KEY,
  dname VARCHAR(100) NOT NULL,
  phoneno VARCHAR(15) NOT NULL
);
CREATE TABLE tripsessions (
  sessionid INT AUTO_INCREMENT PRIMARY KEY,
  userid INT NOT NULL,
  saccoid INT NOT NULL,
  busid INT,
  fromstageid INT,
  tostageid INT,
  fareamount DECIMAL(10,2) NOT NULL,
  seatid INT,
  status ENUM('pending','paid','expired','cancelled') DEFAULT 'pending',
  expiresat DATETIME,
  createdat DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (userid) REFERENCES users(userid)
);
CREATE TABLE payments (
  paymentid INT AUTO_INCREMENT PRIMARY KEY,
  sessionid INT NOT NULL,
  mpesareceipt VARCHAR(50),
  amount DECIMAL(10,2) NOT NULL,
  phoneno VARCHAR(15) NOT NULL,
  status ENUM('success','failed','pending') DEFAULT 'pending',
  createdat DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sessionid) REFERENCES tripsessions(sessionid)
);
CREATE TABLE trips (
  tripid INT AUTO_INCREMENT PRIMARY KEY,
  sessionid INT NOT NULL,
  routeid INT NOT NULL,
  busid INT NOT NULL,
  starttime DATETIME,
  status ENUM('active','completed','cancelled') DEFAULT 'active',
  FOREIGN KEY (sessionid) REFERENCES tripsessions(sessionid)
);
CREATE TABLE seats (
  seatid INT AUTO_INCREMENT PRIMARY KEY,
  busid INT NOT NULL,
  seatnumber VARCHAR(5) NOT NULL,
  status ENUM('available','occupied') DEFAULT 'available'
);
