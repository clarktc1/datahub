-- phpMyAdmin SQL Dump
-- version 4.7.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 22, 2019 at 02:27 PM
-- Server version: 5.7.25-0ubuntu0.18.04.2
-- PHP Version: 7.0.33-1+ubuntu18.04.1+deb.sury.org+1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tcc`
--

-- --------------------------------------------------------

--
-- Table structure for table `fba_shipment_details`
--

CREATE TABLE `fba_shipment_details` (
  `id` bigint(255) NOT NULL,
  `shipment_id` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `date` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  `destination_fulfillment_center_id` varchar(255) DEFAULT NULL,
  `label_prep_type` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `country_code` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `from_name` varchar(255) DEFAULT NULL,
  `addressLine1` varchar(255) DEFAULT NULL,
  `state_or_province_code` varchar(255) DEFAULT NULL,
  `are_cases_required` varchar(255) DEFAULT NULL,
  `shipment_name` varchar(255) DEFAULT NULL,
  `box_contents_source` varchar(255) DEFAULT NULL,
  `shipment_status` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `fba_shipment_details`
--

INSERT INTO `fba_shipment_details` (`id`, `shipment_id`, `user_id`, `date`, `update_at`, `destination_fulfillment_center_id`, `label_prep_type`, `city`, `country_code`, `postal_code`, `from_name`, `addressLine1`, `state_or_province_code`, `are_cases_required`, `shipment_name`, `box_contents_source`, `shipment_status`) VALUES
(1, 'FBA15G2KFMZN', 9, '2019-02-21 11:30:58', '2019-02-22 09:59:02', 'DEN2', 'NO_LABEL', 'RANCHO DOMINGUEZ', 'US', '90220', 'LiveFresh-AN Deringer', '19520 WILMINGTON AVE.', 'CA', 'true', 'FBA (2/18/19 1:07 AM) - 1', 'INTERACTIVE', 'RECEIVING'),
(2, 'FBA15G2N8CTH', 9, '2019-02-21 11:31:00', '2019-02-21 14:52:19', 'IVSB', 'SELLER_LABEL', 'Mount Crawford', 'US', '22841', 'LiveFresh-TCC HQ', '498 N River Rd', 'VA', 'true', 'FBA (2/18/19 1:46 AM) - 1', 'INTERACTIVE', 'SHIPPED'),
(3, 'FBA15FYKCYBJ', 9, '2019-02-21 11:31:03', '2019-02-21 14:52:23', 'PHL4', 'SELLER_LABEL', 'Mount Crawford', 'US', '22841', '\"TCC Brands, Mount Crawford, VA\"', '498 N River Rd', 'VA', 'false', 'FBA (2/1/19 2:52 AM) - 1', 'INTERACTIVE', 'CLOSED'),
(4, 'FBA15G217N65', 9, '2019-02-21 11:31:05', '2019-02-21 14:52:27', 'CLT3', 'SELLER_LABEL', 'Mount Crawford', 'US', '22841', '\"TCC Brands, Mount Crawford, VA\"', '498 N River Rd', 'VA', 'false', 'FBA (2/14/19 1:38 PM) - 1', 'INTERACTIVE', 'RECEIVING'),
(5, 'FBA15FZ4JRZ9', 9, '2019-02-21 11:31:08', '2019-02-21 14:52:31', 'CLT2', 'SELLER_LABEL', 'Mount Crawford', 'US', '22841', '\"TCC Brands, Mount Crawford, VA\"', '498 N River Rd', 'VA', 'false', 'FBA (2/1/19 9:28 AM) - 3', 'INTERACTIVE', 'CLOSED'),
(6, 'FBA15FYGG28H', 9, '2019-02-21 11:31:10', '2019-02-21 14:52:35', 'CLT2', 'SELLER_LABEL', 'Mount Crawford', 'US', '22841', '\"TCC Brands, Mount Crawford, VA\"', '498 N River Rd', 'VA', 'false', 'FBA (1/29/19 6:39 AM) - 1', 'INTERACTIVE', 'CLOSED'),
(7, 'FBA15G2KFN09', 9, '2019-02-21 11:31:13', '2019-02-21 14:52:39', 'ONT8', 'NO_LABEL', 'RANCHO DOMINGUEZ', 'US', '90220', 'LiveFresh-AN Deringer', '19520 WILMINGTON AVE.', 'CA', 'true', 'FBA (2/18/19 1:07 AM) - 2', 'INTERACTIVE', 'RECEIVING'),
(8, 'FBA15FYKCY9W', 9, '2019-02-21 11:31:15', '2019-02-21 14:52:42', 'TPA1', 'SELLER_LABEL', 'Mount Crawford', 'US', '22841', '\"TCC Brands, Mount Crawford, VA\"', '498 N River Rd', 'VA', 'false', 'FBA (2/1/19 2:52 AM) - 6', 'INTERACTIVE', 'CLOSED'),
(9, 'FBA15G2YNG9W', 9, '2019-02-21 11:31:18', '2019-02-22 09:58:51', 'PHX5', 'SELLER_LABEL', 'Mount Crawford', 'US', '22841', '\"TCC Brands, Mount Crawford, VA\"', '498 N River Rd', 'VA', 'false', 'FBA (2/20/19 1:54 AM) - 2', 'INTERACTIVE', 'SHIPPED'),
(10, 'FBA15G38YQ0Y', 9, '2019-02-21 11:31:20', '2019-02-22 09:58:55', 'TPA1', 'SELLER_LABEL', 'Mount Crawford', 'US', '22841', '\"TCC Brands, Mount Crawford, VA\"', '498 N River Rd', 'VA', 'false', 'FBA (2/20/19 2:32 PM) - 2', 'INTERACTIVE', 'SHIPPED'),
(11, 'FBA15G37XQ74', 9, '2019-02-21 11:31:22', '2019-02-22 09:58:58', 'CLT2', 'SELLER_LABEL', 'Mount Crawford', 'US', '22841', '\"TCC Brands, Mount Crawford, VA\"', '498 N River Rd', 'VA', 'false', 'FBA (2/20/19 2:32 PM) - 3', '2D_BARCODE', 'SHIPPED'),
(12, 'FBA15G0M1NL3', 9, '2019-02-21 11:31:25', '2019-02-21 14:52:58', 'ITX2', 'SELLER_LABEL', 'RANCHO DOMINGUEZ', 'US', '90220', 'LiveFresh-AN Deringer', '19520 WILMINGTON AVE.', 'CA', 'true', 'FBA (2/13/19 6:10 AM) - 1', 'INTERACTIVE', 'RECEIVING'),
(13, 'FBA15G2V9FF8', 9, '2019-02-21 11:31:27', '2019-02-21 14:53:02', 'CLT2', 'SELLER_LABEL', 'Mount Crawford', 'US', '22841', '\"TCC Brands, Mount Crawford, VA\"', '498 N River Rd', 'VA', 'false', 'FBA (2/19/19 5:12 AM) - 1', 'INTERACTIVE', 'RECEIVING'),
(14, 'FBA15G359941', 12, '2019-02-21 11:31:32', '2019-02-21 14:53:17', 'CLT2', 'SELLER_LABEL', 'Harrisonburg', 'US', '22801', '\"NextHub - Harrisonburg, VA\"', '1322 Hillside Ave #208', 'VA', 'false', 'FBA (2/20/19 1:55 AM) - 1', 'INTERACTIVE', 'WORKING'),
(15, 'FBA15FYFYF5F', 12, '2019-02-21 11:31:34', '2019-02-21 14:53:22', 'RIC2', 'SELLER_LABEL', 'Harrisonburg', 'US', '22801', '\"NextHub - Harrisonburg, VA\"', '1322 Hillside Ave #208', 'VA', 'true', 'FBA (1/29/19 6:01 AM) - 1', 'INTERACTIVE', 'CLOSED'),
(16, 'FBA15G2YMYLS', 12, '2019-02-21 11:31:37', '2019-02-21 14:53:26', 'GSP1', 'SELLER_LABEL', 'Harrisonburg', 'US', '22801', '\"NextHub - Harrisonburg, VA\"', '1322 Hillside Ave #208', 'VA', 'false', 'FBA (2/20/19 1:55 AM) - 4', 'INTERACTIVE', 'WORKING'),
(17, 'FBA15C95MK2R', 1, '2019-02-21 11:31:40', '2019-02-21 14:53:32', 'BHX4', 'NO_LABEL', 'Clyst Honiton', 'GB', 'EX5 2UL', 'LiveFresh-ISCA', 'Unit 6 Newbery Commercial Centre', 'Devon', 'true', 'FBA (18/02/2019 10:17) - 1', 'INTERACTIVE', 'SHIPPED'),
(18, 'FBA15FY43BV0', 9, '2019-02-21 13:14:08', '2019-02-21 14:53:06', 'BOS7', 'NO_LABEL', 'RANCHO DOMINGUEZ', 'US', '90220', 'LiveFresh-AN Deringer', '19520 WILMINGTON AVE.', 'CA', 'true', 'FBA (1/27/19 11:53 PM) - 1', 'INTERACTIVE', 'CLOSED'),
(19, 'FBA15FXX6YRX', 9, '2019-02-22 09:58:44', '2019-02-22 09:58:44', 'CLT2', 'SELLER_LABEL', 'Mount Crawford', 'US', '22841', 'TCC Brands HQ', '498 N River Rd', 'VA', 'true', 'FBA (1/28/19 1:23 PM) - 2', 'INTERACTIVE', 'CLOSED'),
(20, 'FBA15FYKCYC5', 9, '2019-02-22 09:58:48', '2019-02-22 09:58:48', 'PHX6', 'SELLER_LABEL', 'Mount Crawford', 'US', '22841', '\"TCC Brands, Mount Crawford, VA\"', '498 N River Rd', 'VA', 'false', 'FBA (2/1/19 2:52 AM) - 7', 'INTERACTIVE', 'CLOSED');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `fba_shipment_details`
--
ALTER TABLE `fba_shipment_details`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `fba_shipment_details`
--
ALTER TABLE `fba_shipment_details`
  MODIFY `id` bigint(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
