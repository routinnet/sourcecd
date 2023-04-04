-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 19, 2022 at 06:59 PM
-- Server version: 5.7.39-cll-lve
-- PHP Version: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Table structure for table `fl_accounts`
--

CREATE TABLE `fl_accounts` (
  `id` int(11) NOT NULL,
  `fid` int(11) NOT NULL,
  `text` text NOT NULL,
  `sold` int(11) NOT NULL DEFAULT '0',
  `active` int(11) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Dumping data for table `fl_accounts`
--

INSERT INTO `fl_accounts` (`id`, `fid`, `text`, `sold`, `active`) VALUES
(1, 1, 'ss', 0, 1),
(3, 6, '\n\nusername: Test\npassword: pwd', 0, 1),
(4, 6, 'username: las02fll@gmail.com \npassword: dfghER43bkl\n', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `fl_cat`
--

CREATE TABLE `fl_cat` (
  `id` int(11) NOT NULL,
  `title` varchar(50) COLLATE utf8mb4_persian_ci NOT NULL,
  `parent` int(2) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Dumping data for table `fl_cat`
--

INSERT INTO `fl_cat` (`id`, `title`, `parent`) VALUES
(1, 'اکانت تست', 0),
(2, 'اکانت اکسپرس', 0),
(9, 'نورد وی بی ان', 0),
(8, 'apple id', 0);

-- --------------------------------------------------------

--
-- Table structure for table `fl_file`
--

CREATE TABLE `fl_file` (
  `id` int(11) NOT NULL,
  `fileid` varchar(250) COLLATE utf8mb4_persian_ci NOT NULL,
  `catid` int(4) NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_persian_ci NOT NULL,
  `price` int(5) NOT NULL,
  `descr` text COLLATE utf8mb4_persian_ci NOT NULL,
  `pic` varchar(100) COLLATE utf8mb4_persian_ci NOT NULL,
  `active` int(1) NOT NULL DEFAULT '0',
  `step` int(1) NOT NULL,
  `date` varchar(50) COLLATE utf8mb4_persian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Dumping data for table `fl_file`
--

INSERT INTO `fl_file` (`id`, `fileid`, `catid`, `title`, `price`, `descr`, `pic`, `active`, `step`, `date`) VALUES
(6, '', 2, 'یکماهه', 25000, 'تضمینی', 'images/d41d8cd98f00b204e9800998ecf8427e.jpg', 1, 10, '1668869579');

-- --------------------------------------------------------

--
-- Table structure for table `fl_order`
--

CREATE TABLE `fl_order` (
  `id` int(11) NOT NULL,
  `userid` varchar(30) NOT NULL,
  `transid` varchar(150) COLLATE utf8mb4_persian_ci NOT NULL,
  `fileid` int(5) NOT NULL,
  `amount` int(5) NOT NULL,
  `status` int(1) NOT NULL,
  `date` varchar(50) COLLATE utf8mb4_persian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fl_user`
--

CREATE TABLE `fl_user` (
  `id` int(11) NOT NULL,
  `userid` varchar(30) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_persian_ci NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_persian_ci NOT NULL,
  `date` varchar(50) COLLATE utf8mb4_persian_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Indexes for dumped tables
--

--
-- Table structure for table `fl_software`
--

CREATE TABLE `fl_software` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `link` varchar(250) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Dumping data for table `fl_software`
--

INSERT INTO `fl_software` (`id`, `title`, `link`, `status`) VALUES
(1, 'softether', 'https://site.site//dl/softether-vpnclient-v4.41-9782-beta-2022.11.17-windows-x86_x64-intel.exe', 1),
(2, 'kerio', 'https://site.site//dl/kerio-.exe', 1),
(3, 'WireGuard', 'https://site.site/', 1),
(4, 'HTTP Client', 'https://sswewe.rr/', 1);

--

CREATE TABLE `fl_wallet` (
  `id` int NOT NULL,
  `amount` int NOT NULL,
  `userid` varchar(30) NOT NULL,
  `trans_id` varchar(254) NOT NULL DEFAULT '',
  `status` int NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


ALTER TABLE `fl_wallet`
  ADD PRIMARY KEY (`id`);
  --
-- AUTO_INCREMENT for table `fl_wallet`
--
ALTER TABLE `fl_wallet`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
-- Indexes for dumped tables
--
ALTER TABLE `fl_user` ADD `wallet` VARCHAR(50) NOT NULL DEFAULT '0' AFTER `date`; 
--
-- Indexes for table `fl_software`
--
ALTER TABLE `fl_software`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `fl_software`
--
ALTER TABLE `fl_software`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Indexes for table `fl_accounts`
--
ALTER TABLE `fl_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fl_cat`
--
ALTER TABLE `fl_cat`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fl_file`
--
ALTER TABLE `fl_file`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fl_order`
--
ALTER TABLE `fl_order`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fl_user`
--
ALTER TABLE `fl_user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `fl_accounts`
--
ALTER TABLE `fl_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `fl_cat`
--
ALTER TABLE `fl_cat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `fl_file`
--
ALTER TABLE `fl_file`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `fl_order`
--
ALTER TABLE `fl_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fl_user`
--
ALTER TABLE `fl_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
