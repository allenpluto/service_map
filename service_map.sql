-- phpMyAdmin SQL Dump
-- version 4.0.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 29, 2017 at 10:01 AM
-- Server version: 5.6.12-log
-- PHP Version: 5.4.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `service_map`
--
CREATE DATABASE IF NOT EXISTS `service_map` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
USE `service_map`;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_entity`
--

CREATE TABLE IF NOT EXISTS `tbl_entity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `friendly_uri` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `alternate_name` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(500) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `image_id` int(11) NOT NULL DEFAULT '0',
  `enter_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_entity_line`
--

CREATE TABLE IF NOT EXISTS `tbl_entity_line` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `friendly_uri` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `alternate_name` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(500) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `image_id` int(11) NOT NULL DEFAULT '0',
  `enter_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `color` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '[0,0,0]',
  `path` text COLLATE utf8_unicode_ci NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=11 ;

--
-- Dumping data for table `tbl_entity_line`
--

INSERT INTO `tbl_entity_line` (`id`, `friendly_uri`, `name`, `alternate_name`, `description`, `image_id`, `enter_time`, `update_time`, `color`, `path`, `content`) VALUES
(1, '', 'Content Marketing', 'Inbound Links', '', 0, '2017-11-28 04:03:13', '2017-11-28 05:57:54', '[240,88,39]', '[[0.495,0.655],[0.58,0.475],[0.58,0.43],[0.54,0.43],[0.54,0.37],[0.44,0.37],[0.44,0.325],[0.35,0.235],[0.16,0.235],[0.16,0.2]]', ''),
(2, '', 'Local SEO', '', '', 0, '2017-11-27 05:31:33', '2017-11-28 05:52:01', '[240,103,147]', '[[0.655,0.59],[0.655,0.485],[0.485,0.485],[0.485,0.475],[0.345,0.475],[0.345,0.325],[0.44,0.325]]', ''),
(3, '', 'Social Meida', 'Social Meida', '', 0, '2017-11-28 04:22:16', '2017-11-28 05:46:37', '[11,134,67]', '[[0.55,0.105],[0.55,0.42],[0.635,0.42],[0.635,0.465],[0.675,0.465],[0.685,0.475],[0.745,0.475],[0.745,0.64]]', ''),
(4, '', 'CRM', 'Email Marketing', '', 0, '2017-11-28 04:33:05', '2017-11-28 05:39:53', '[22,154,216]', '[[0.73,0.13],[0.635,0.305],[0.635,0.42],[0.895,0.42]]', ''),
(5, '', 'Social Paid', '', '', 0, '2017-11-28 05:28:12', '2017-11-28 05:35:40', '[148,157,162]', '[[0.865,0.33],[0.835,0.32],[0.835,0.17]]', ''),
(6, '', 'Paid Search', '', '', 0, '2017-11-28 04:38:00', '2017-11-28 05:35:28', '[236,30,43]', '[[0.835,0.17],[0.72,0.325],[0.58,0.325],[0.58,0.38],[0.635,0.42]]', ''),
(7, '', 'Mobile', 'Emerging Technology', '', 0, '2017-11-28 04:44:49', '2017-11-28 05:24:48', '[151,32,96]', '[[0.26,0.145],[0.26,0.18],[0.445,0.18],[0.58,0.325],[0.46,0.325],[0.46,0.4],[0.44,0.415],[0.44,0.55],[0.38,0.6]]', ''),
(8, '', 'Website', '', '', 0, '2017-11-28 04:58:09', '2017-11-28 06:15:14', '[94,46,132]', '[[0.23,0.39],[0.23,0.31],[0.25,0.3],[0.25,0.275],[0.085,0.275],[0.085,0.365],[0.28,0.365],[0.355,0.415],[0.355,0.465],[0.57,0.465],[0.57,0.44]]', ''),
(9, '', 'Analytics', '', '', 0, '2017-11-28 05:02:51', '2017-11-28 05:24:48', '[87,58,54]', '[[0.065,0.455],[0.245,0.455],[0.28,0.415],[0.355,0.415],[0.355,0.335],[0.685,0.335],[0.685,0.475],[0.515,0.475]]', ''),
(10, '', 'Technical SEO', '', '', 0, '2017-11-28 05:08:25', '2017-11-28 05:24:48', '[13,177,176]', '[[0.135,0.53],[0.225,0.53],[0.355,0.415],[0.365,0.415],[0.365,0.455],[0.515,0.455]]', '');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_entity_stop`
--

CREATE TABLE IF NOT EXISTS `tbl_entity_stop` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `friendly_uri` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `alternate_name` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` varchar(500) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `image_id` int(11) NOT NULL DEFAULT '0',
  `enter_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `line_id` int(11) NOT NULL DEFAULT '1',
  `line_position` int(11) NOT NULL DEFAULT '1',
  `point_x` decimal(6,5) NOT NULL DEFAULT '0.00000',
  `point_y` decimal(6,5) NOT NULL DEFAULT '0.00000',
  `text_x` decimal(6,5) NOT NULL DEFAULT '0.00000',
  `text_y` decimal(6,5) NOT NULL DEFAULT '0.00000',
  `stop_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=19 ;

--
-- Dumping data for table `tbl_entity_stop`
--

INSERT INTO `tbl_entity_stop` (`id`, `friendly_uri`, `name`, `alternate_name`, `description`, `image_id`, `enter_time`, `update_time`, `line_id`, `line_position`, `point_x`, `point_y`, `text_x`, `text_y`, `stop_id`) VALUES
(1, '', 'Google Authorship', '', '', 0, '2017-11-28 22:56:18', '2017-11-28 22:56:18', 1, 1, '0.50500', '0.63000', '-0.01000', '0.00000', 0),
(2, '', 'Imagery', '', '', 0, '2017-11-28 22:56:18', '2017-11-28 22:56:18', 1, 2, '0.51000', '0.62000', '0.01000', '0.00000', 0),
(3, '', 'Shareable Content', '', '', 0, '2017-11-28 22:56:18', '2017-11-28 22:56:18', 1, 3, '0.51500', '0.60500', '-0.01000', '0.00000', 0),
(4, '', 'Thought Leadership', '', '', 0, '2017-11-28 22:56:18', '2017-11-28 22:56:18', 1, 4, '0.52000', '0.59500', '0.01000', '0.00000', 0),
(5, '', 'Blogging', '', '', 0, '2017-11-28 22:56:18', '2017-11-28 22:56:18', 1, 5, '0.53000', '0.58000', '-0.01000', '0.00000', 0),
(6, '', 'Video Marketing', '', '', 0, '2017-11-28 22:56:18', '2017-11-28 22:56:18', 1, 6, '0.53500', '0.57000', '0.01000', '0.00000', 0),
(7, '', 'Infographics', '', '', 0, '2017-11-28 22:56:18', '2017-11-28 22:56:18', 1, 7, '0.54500', '0.55000', '-0.01000', '0.00000', 0),
(8, '', 'Fresh Content', '', '', 0, '2017-11-28 22:56:18', '2017-11-28 22:56:18', 1, 8, '0.55000', '0.53500', '0.01000', '0.00000', 0),
(9, '', 'Unique Content', '', '', 0, '2017-11-28 22:56:18', '2017-11-28 22:56:18', 1, 9, '0.56000', '0.51500', '-0.01000', '0.00000', 0),
(10, '', 'Keyword Research', '', '', 0, '2017-11-28 22:56:18', '2017-11-28 22:56:18', 1, 10, '0.58000', '0.53500', '0.00500', '0.00000', 0),
(11, '', 'Organic Traffic', '', '', 0, '2017-11-28 22:56:18', '2017-11-28 22:56:18', 1, 11, '0.44000', '0.33500', '-0.00500', '0.01000', 0),
(12, '', 'Outreach', '', '', 0, '2017-11-28 22:56:18', '2017-11-28 22:56:18', 1, 12, '0.42000', '0.30500', '-0.01000', '0.00000', 0),
(13, '', 'Online PR', '', '', 0, '2017-11-28 22:56:18', '2017-11-28 22:56:18', 1, 13, '0.40500', '0.29000', '-0.01000', '0.00000', 0),
(14, '', 'Domain Trust', '', '', 0, '2017-11-28 22:56:18', '2017-11-28 22:56:18', 1, 14, '0.38500', '0.27000', '-0.01000', '0.00000', 0),
(15, '', 'Recency', '', '', 0, '2017-11-28 22:56:18', '2017-11-28 22:56:18', 1, 15, '0.37000', '0.25500', '-0.01000', '0.00000', 0),
(16, '', 'Analysis', '', '', 0, '2017-11-28 22:56:18', '2017-11-28 22:56:18', 1, 16, '0.33000', '0.23500', '0.00000', '-0.00500', 0),
(17, '', 'Topicality', '', '', 0, '2017-11-28 22:56:18', '2017-11-28 22:56:18', 1, 17, '0.26500', '0.23500', '0.00000', '-0.00500', 0),
(18, '', 'Backlink Analysis', '', '', 0, '2017-11-28 22:56:18', '2017-11-28 22:56:18', 1, 18, '0.20500', '0.23500', '0.00000', '-0.00500', 0);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
