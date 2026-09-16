-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 16, 2026 at 02:45 PM
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
-- Table structure for table `owner`
--

CREATE TABLE `owner` (
  `owner_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parking_rate`
--

CREATE TABLE `parking_rate` (
  `rate_id` int(11) NOT NULL,
  `vehicle_type` varchar(20) NOT NULL,
  `rate_per_hour` decimal(10,2) NOT NULL
) ;

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
  `exit_time` datetime DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `parking_slot`
--

CREATE TABLE `parking_slot` (
  `slot_number` varchar(10) NOT NULL,
  `slot_type` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Available'
) ;

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
-- Indexes for dumped tables
--

--
-- Indexes for table `owner`
--
ALTER TABLE `owner`
  ADD PRIMARY KEY (`owner_id`);

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
  ADD KEY `fk_record_rate` (`rate_id`);

--
-- Indexes for table `parking_slot`
--
ALTER TABLE `parking_slot`
  ADD PRIMARY KEY (`slot_number`);

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
-- AUTO_INCREMENT for table `owner`
--
ALTER TABLE `owner`
  MODIFY `owner_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `parking_record`
--
ALTER TABLE `parking_record`
  ADD CONSTRAINT `fk_record_rate` FOREIGN KEY (`rate_id`) REFERENCES `parking_rate` (`rate_id`),
  ADD CONSTRAINT `fk_record_slot` FOREIGN KEY (`slot_number`) REFERENCES `parking_slot` (`slot_number`),
  ADD CONSTRAINT `fk_record_vehicle` FOREIGN KEY (`vehicle_number`) REFERENCES `vehicle` (`vehicle_number`);

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
