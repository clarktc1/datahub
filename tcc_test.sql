-- phpMyAdmin SQL Dump
-- version 4.7.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 10, 2019 at 10:24 AM
-- Server version: 5.7.26-0ubuntu0.18.04.1
-- PHP Version: 7.2.17-0ubuntu0.18.04.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tcc_test`
--

-- --------------------------------------------------------

--
-- Table structure for table `finance_adjustment_event_list`
--

CREATE TABLE `finance_adjustment_event_list` (
  `id` bigint(20) NOT NULL,
  `perunitamount` varchar(20) DEFAULT NULL,
  `totalamount` varchar(20) DEFAULT NULL,
  `quantity` varchar(10) DEFAULT NULL,
  `sellersku` varchar(100) DEFAULT NULL,
  `productdescription` text,
  `adjustmentamount` varchar(20) DEFAULT NULL,
  `adjustmenttype` varchar(250) DEFAULT NULL,
  `posteddate` datetime DEFAULT NULL,
  `added_by` bigint(20) DEFAULT NULL,
  `fin_country` varbinary(5) DEFAULT NULL,
  `createDate` datetime DEFAULT NULL,
  `updateDate` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `finance_data_log`
--

CREATE TABLE `finance_data_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `dateUpdate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `finance_data_log`
--

INSERT INTO `finance_data_log` (`id`, `user_id`, `date`, `dateUpdate`) VALUES
(1, 5, '2019-05-10', '2019-05-10 10:21:35'),
(2, 9, '2019-05-10', '2019-05-10 10:21:40'),
(3, 12, '2019-05-10', '2019-05-10 10:21:42');

-- --------------------------------------------------------

--
-- Table structure for table `finance_order_data`
--

CREATE TABLE `finance_order_data` (
  `id` bigint(20) NOT NULL,
  `amazon_order_id` varchar(200) DEFAULT NULL,
  `market_place` varchar(100) DEFAULT NULL,
  `seller_order_id` varchar(200) DEFAULT NULL,
  `posted_date` datetime DEFAULT NULL,
  `posted_date_gmt` datetime DEFAULT NULL,
  `posted_date_pst` datetime DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL,
  `fin_country` varbinary(5) DEFAULT NULL,
  `dev_ref` varchar(200) DEFAULT NULL,
  `dev_date` varchar(200) DEFAULT NULL,
  `finance_order_data_summary` enum('n','y') DEFAULT 'n'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `finance_order_data_summary`
--

CREATE TABLE `finance_order_data_summary` (
  `f_oid` bigint(20) NOT NULL,
  `posted_date` datetime DEFAULT NULL,
  `Date_in_GMT` datetime DEFAULT NULL,
  `Date_in_PST` datetime DEFAULT NULL,
  `amazon_order_id` varchar(200) DEFAULT NULL,
  `seller_sku` varchar(200) DEFAULT NULL,
  `quantity` varchar(100) DEFAULT NULL,
  `marketplace` varchar(100) DEFAULT NULL,
  `product_sales` varchar(100) DEFAULT '0.00',
  `shipping_credits` varchar(100) DEFAULT '0.00',
  `gift_wrap_credits` varchar(100) DEFAULT '0.00',
  `promotional_rebates` varchar(100) DEFAULT '0.00',
  `sales_tax_collected` varchar(100) DEFAULT '0.00',
  `marketplace_facilitator_tax` varchar(100) DEFAULT '0.00',
  `selling_fees` varchar(100) DEFAULT '0.00',
  `fba_fees` varchar(100) DEFAULT '0.00',
  `other_transaction_fees` varchar(100) DEFAULT '0.00',
  `added_by` bigint(20) DEFAULT NULL,
  `dev_date` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `finance_order_item_charge_list_data`
--

CREATE TABLE `finance_order_item_charge_list_data` (
  `charge_type` varchar(200) DEFAULT NULL,
  `charge_amount` varchar(100) DEFAULT '0.00',
  `currency_code` varchar(100) DEFAULT NULL,
  `amazon_order_id` varchar(200) DEFAULT NULL,
  `order_dev_ref` varchar(100) DEFAULT NULL,
  `item_dev_ref` varchar(100) DEFAULT NULL,
  `dev_date` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `finance_order_item_data`
--

CREATE TABLE `finance_order_item_data` (
  `order_item_id` varchar(200) DEFAULT NULL,
  `quantity_shipped` varchar(200) DEFAULT NULL,
  `seller_sku` varchar(200) DEFAULT NULL,
  `amazon_order_id` varchar(200) DEFAULT NULL,
  `order_dev_ref` varchar(200) DEFAULT NULL,
  `item_dev_ref` varchar(200) DEFAULT NULL,
  `added_by` bigint(20) DEFAULT NULL,
  `fin_country` varbinary(5) DEFAULT NULL,
  `dev_date` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `finance_order_item_fee_list_data`
--

CREATE TABLE `finance_order_item_fee_list_data` (
  `fee_type` varchar(200) DEFAULT NULL,
  `fee_amount` varchar(100) DEFAULT '0.00',
  `currency_code` varchar(100) DEFAULT NULL,
  `amazon_order_id` varchar(200) DEFAULT NULL,
  `order_dev_ref` varchar(100) DEFAULT NULL,
  `item_dev_ref` varchar(100) DEFAULT NULL,
  `dev_date` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `finance_order_item_promotion_list_data`
--

CREATE TABLE `finance_order_item_promotion_list_data` (
  `promotion_type` varchar(200) DEFAULT NULL,
  `promotion_amount` varchar(100) DEFAULT '0.00',
  `currency_code` varchar(100) DEFAULT NULL,
  `promotion_id` longtext,
  `amazon_order_id` varchar(200) DEFAULT NULL,
  `order_dev_ref` varchar(100) DEFAULT NULL,
  `item_dev_ref` varchar(100) DEFAULT NULL,
  `dev_date` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `finance_order_item_tax_withheld_list_data`
--

CREATE TABLE `finance_order_item_tax_withheld_list_data` (
  `charge_type` varchar(200) DEFAULT NULL,
  `charge_amount` varchar(100) DEFAULT '0.00',
  `currency_code` varchar(100) DEFAULT NULL,
  `amazon_order_id` varchar(200) DEFAULT NULL,
  `order_dev_ref` varchar(100) DEFAULT NULL,
  `item_dev_ref` varchar(100) DEFAULT NULL,
  `dev_date` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `finance_order_shipment_fee_list`
--

CREATE TABLE `finance_order_shipment_fee_list` (
  `fee_type` varchar(200) DEFAULT NULL,
  `fee_amount` varchar(100) DEFAULT '0.00',
  `currency_code` varchar(200) DEFAULT NULL,
  `amazon_order_id` varchar(200) DEFAULT NULL,
  `order_dev_ref` varchar(200) DEFAULT NULL,
  `shipment_fee_list_dev_ref` varchar(200) DEFAULT NULL,
  `dev_date` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `finance_refund_event_list`
--

CREATE TABLE `finance_refund_event_list` (
  `id` bigint(20) NOT NULL,
  `amazonorderid` varchar(250) DEFAULT NULL,
  `posteddate` datetime DEFAULT NULL,
  `marketplacename` varchar(250) DEFAULT NULL,
  `sellerorderid` varchar(200) DEFAULT NULL,
  `orderadjustmentitemid` varchar(200) DEFAULT NULL,
  `quantityshipped` varchar(200) DEFAULT NULL,
  `sellersku` varchar(200) DEFAULT NULL,
  `commission` varchar(10) DEFAULT NULL,
  `refundcommission` varchar(10) DEFAULT NULL,
  `tax` varchar(10) DEFAULT NULL,
  `marketplacefacilitatortaxprincipal` varchar(150) DEFAULT '0.00',
  `marketplacefacilitatortaxshipping` varchar(150) DEFAULT '0.00',
  `principal` varchar(10) DEFAULT NULL,
  `shippingtax` varchar(50) DEFAULT '0.0',
  `shippingcharge` varchar(50) DEFAULT '0.0',
  `promotionmetadatadefinitionvalue` varchar(50) DEFAULT '0.00',
  `promotionid` longtext,
  `added_by` bigint(20) DEFAULT NULL,
  `fin_country` varbinary(5) DEFAULT NULL,
  `createDate` datetime DEFAULT NULL,
  `updateDate` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `finance_service_fee_event_list`
--

CREATE TABLE `finance_service_fee_event_list` (
  `id` bigint(20) NOT NULL,
  `fee_amount` varchar(100) DEFAULT '0.00',
  `fee_type` varchar(150) DEFAULT NULL,
  `added_by` bigint(20) DEFAULT NULL,
  `fin_country` varbinary(5) DEFAULT NULL,
  `createDate` datetime DEFAULT NULL,
  `updateDate` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` bigint(20) NOT NULL,
  `data` text,
  `createData` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `mws_new_data_log`
--

CREATE TABLE `mws_new_data_log` (
  `id` bigint(20) NOT NULL,
  `table_name` varchar(150) DEFAULT NULL,
  `user_id` varchar(20) DEFAULT NULL,
  `data` longtext,
  `amazon_order_id` varchar(200) DEFAULT NULL,
  `api_date` date DEFAULT NULL,
  `sent_mail` enum('y','n') DEFAULT 'n' COMMENT 'y = sent mail, n = npt sent',
  `insert_date` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `finance_adjustment_event_list`
--
ALTER TABLE `finance_adjustment_event_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `finance_data_log`
--
ALTER TABLE `finance_data_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `finance_order_data`
--
ALTER TABLE `finance_order_data`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `finance_order_data_summary`
--
ALTER TABLE `finance_order_data_summary`
  ADD PRIMARY KEY (`f_oid`);

--
-- Indexes for table `finance_refund_event_list`
--
ALTER TABLE `finance_refund_event_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `finance_service_fee_event_list`
--
ALTER TABLE `finance_service_fee_event_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mws_new_data_log`
--
ALTER TABLE `mws_new_data_log`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `finance_adjustment_event_list`
--
ALTER TABLE `finance_adjustment_event_list`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `finance_data_log`
--
ALTER TABLE `finance_data_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `finance_order_data`
--
ALTER TABLE `finance_order_data`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `finance_order_data_summary`
--
ALTER TABLE `finance_order_data_summary`
  MODIFY `f_oid` bigint(20) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `finance_refund_event_list`
--
ALTER TABLE `finance_refund_event_list`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `finance_service_fee_event_list`
--
ALTER TABLE `finance_service_fee_event_list`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `mws_new_data_log`
--
ALTER TABLE `mws_new_data_log`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
