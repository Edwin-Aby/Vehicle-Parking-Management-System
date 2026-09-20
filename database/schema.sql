-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 20, 2026 at 09:22 AM
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
-- Database: `parking_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `booking_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `vehicle_number` varchar(20) NOT NULL,
  `slot_number` varchar(10) NOT NULL,
  `location_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `booking_status` varchar(20) NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `owner`
--

CREATE TABLE `owner` (
  `owner_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `owner`
--

INSERT INTO `owner` (`owner_id`, `first_name`, `last_name`, `phone`, `email`) VALUES
(1, 'Rahul', 'Kumar', '9876543210', 'rahul@example.com'),
(2, 'Anu', 'Thomas', '9876543211', 'anu@example.com'),
(3, 'Rahul', 'Kumar', '9876543210', 'rahul@test.com');

-- --------------------------------------------------------

--
-- Table structure for table `parking_location`
--

CREATE TABLE `parking_location` (
  `location_id` int(11) NOT NULL,
  `location_name` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parking_location`
--

INSERT INTO `parking_location` (`location_id`, `location_name`, `address`, `latitude`, `longitude`) VALUES
(1, 'RSET', 'Rajagiri School of Engineering & Technology, Kakkanad, Kochi', 10.0375000, 76.3270000),
(2, 'Lulu Mall', 'Lulu Mall, Edappally, Kochi', 10.0261000, 76.3088000),
(3, 'Forum Kochi', 'Forum Kochi, Maradu, Kochi', 9.9597000, 76.3215000);

-- --------------------------------------------------------

--
-- Table structure for table `parking_rate`
--

CREATE TABLE `parking_rate` (
  `rate_id` int(11) NOT NULL,
  `vehicle_type` varchar(20) NOT NULL,
  `rate_per_hour` decimal(10,2) NOT NULL
) ;

--
-- Dumping data for table `parking_rate`
--

INSERT INTO `parking_rate` (`rate_id`, `vehicle_type`, `rate_per_hour`) VALUES
(1, 'Car', 40.00),
(2, 'Bike', 20.00);

-- --------------------------------------------------------

--
-- Table structure for table `parking_record`
--

CREATE TABLE `parking_record` (
  `record_id` int(11) NOT NULL,
  `vehicle_number` varchar(20) NOT NULL,
  `slot_number` varchar(10) NOT NULL,
  `rate_id` int(11) NOT NULL,
  `entry_time` datetime NOT NULL,
  `exit_time` datetime DEFAULT NULL,
  `booking_id` int(11) DEFAULT NULL
) ;

--
-- Dumping data for table `parking_record`
--

INSERT INTO `parking_record` (`record_id`, `vehicle_number`, `slot_number`, `rate_id`, `entry_time`, `exit_time`, `booking_id`) VALUES
(1, 'KL07AB1234', 'C01', 1, '2026-09-16 18:36:24', '2026-09-16 21:11:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `parking_slot`
--

CREATE TABLE `parking_slot` (
  `slot_number` varchar(10) NOT NULL,
  `slot_type` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Available',
  `location_id` int(11) NOT NULL
) ;

--
-- Dumping data for table `parking_slot`
--

INSERT INTO `parking_slot` (`slot_number`, `slot_type`, `status`, `location_id`) VALUES
('B01', 'Bike', 'Available', 1),
('B02', 'Bike', 'Available', 1),
('B03', 'Bike', 'Available', 1),
('C01', 'Car', 'Available', 1),
('C02', 'Car', 'Available', 1),
('C03', 'Car', 'Available', 1),
('F-B01', 'Bike', 'Available', 3),
('F-B02', 'Bike', 'Available', 3),
('F-B03', 'Bike', 'Available', 3),
('F-C01', 'Car', 'Available', 3),
('F-C02', 'Car', 'Available', 3),
('F-C03', 'Car', 'Available', 3),
('L-B01', 'Bike', 'Available', 2),
('L-B02', 'Bike', 'Available', 2),
('L-B03', 'Bike', 'Available', 2),
('L-C01', 'Car', 'Available', 2),
('L-C02', 'Car', 'Available', 2),
('L-C03', 'Car', 'Available', 2);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `payment_method` varchar(20) NOT NULL,
  `payment_date` datetime DEFAULT NULL,
  `payment_status` varchar(20) NOT NULL DEFAULT 'Pending'
) ;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `record_id`, `payment_method`, `payment_date`, `payment_status`) VALUES
(1, 1, 'UPI', '2026-09-16 18:41:52', 'Paid');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle`
--

CREATE TABLE `vehicle` (
  `vehicle_number` varchar(20) NOT NULL,
  `vehicle_type` varchar(20) NOT NULL,
  `owner_id` int(11) NOT NULL
) ;

--
-- Dumping data for table `vehicle`
--

INSERT INTO `vehicle` (`vehicle_number`, `vehicle_type`, `owner_id`) VALUES
('KL07AB1234', 'Car', 1),
('KL07CD5678', 'Bike', 2),
('KL07XY1234', 'Car', 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `fk_booking_owner` (`owner_id`),
  ADD KEY `fk_booking_vehicle` (`vehicle_number`),
  ADD KEY `fk_booking_slot` (`slot_number`),
  ADD KEY `fk_booking_location` (`location_id`);

--
-- Indexes for table `owner`
--
ALTER TABLE `owner`
  ADD PRIMARY KEY (`owner_id`);

--
-- Indexes for table `parking_location`
--
ALTER TABLE `parking_location`
  ADD PRIMARY KEY (`location_id`);

--
-- Indexes for table `parking_rate`
--
ALTER TABLE `parking_rate`
  ADD PRIMARY KEY (`rate_id`),
  ADD UNIQUE KEY `uq_rate_vehicle_type` (`vehicle_type`);

--
-- Indexes for table `parking_record`
--
ALTER TABLE `parking_record`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `fk_record_vehicle` (`vehicle_number`),
  ADD KEY `fk_record_slot` (`slot_number`),
  ADD KEY `fk_record_rate` (`rate_id`),
  ADD KEY `fk_parking_record_booking` (`booking_id`);

--
-- Indexes for table `parking_slot`
--
ALTER TABLE `parking_slot`
  ADD PRIMARY KEY (`slot_number`),
  ADD KEY `fk_parking_slot_location` (`location_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `uq_payment_record` (`record_id`);

--
-- Indexes for table `vehicle`
--
ALTER TABLE `vehicle`
  ADD PRIMARY KEY (`vehicle_number`),
  ADD KEY `fk_vehicle_owner` (`owner_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `owner`
--
ALTER TABLE `owner`
  MODIFY `owner_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `parking_location`
--
ALTER TABLE `parking_location`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `parking_rate`
--
ALTER TABLE `parking_rate`
  MODIFY `rate_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parking_record`
--
ALTER TABLE `parking_record`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `fk_booking_location` FOREIGN KEY (`location_id`) REFERENCES `parking_location` (`location_id`),
  ADD CONSTRAINT `fk_booking_owner` FOREIGN KEY (`owner_id`) REFERENCES `owner` (`owner_id`),
  ADD CONSTRAINT `fk_booking_slot` FOREIGN KEY (`slot_number`) REFERENCES `parking_slot` (`slot_number`),
  ADD CONSTRAINT `fk_booking_vehicle` FOREIGN KEY (`vehicle_number`) REFERENCES `vehicle` (`vehicle_number`);

--
-- Constraints for table `parking_record`
--
ALTER TABLE `parking_record`
  ADD CONSTRAINT `fk_parking_record_booking` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`),
  ADD CONSTRAINT `fk_record_rate` FOREIGN KEY (`rate_id`) REFERENCES `parking_rate` (`rate_id`),
  ADD CONSTRAINT `fk_record_slot` FOREIGN KEY (`slot_number`) REFERENCES `parking_slot` (`slot_number`),
  ADD CONSTRAINT `fk_record_vehicle` FOREIGN KEY (`vehicle_number`) REFERENCES `vehicle` (`vehicle_number`);

--
-- Constraints for table `parking_slot`
--
ALTER TABLE `parking_slot`
  ADD CONSTRAINT `fk_parking_slot_location` FOREIGN KEY (`location_id`) REFERENCES `parking_location` (`location_id`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `fk_payment_record` FOREIGN KEY (`record_id`) REFERENCES `parking_record` (`record_id`);

--
-- Constraints for table `vehicle`
--
ALTER TABLE `vehicle`
  ADD CONSTRAINT `fk_vehicle_owner` FOREIGN KEY (`owner_id`) REFERENCES `owner` (`owner_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
