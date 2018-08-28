-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Jan 25, 2017 at 06:58 PM
-- Server version: 10.1.9-MariaDB
-- PHP Version: 5.5.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `crawler`
--

-- --------------------------------------------------------

--
-- Table structure for table `job_details`
--

CREATE TABLE `job_details` (
  `id` int(100) NOT NULL,
  `job_unique_id` varchar(255) NOT NULL,
  `job_post_id` varchar(20) NOT NULL,
  `job_portal` text NOT NULL,
  `job_industry` text NOT NULL,
  `job_url` text NOT NULL,
  `job_title` varchar(255) NOT NULL,
  `job_description` text NOT NULL,
  `career_level` varchar(255) NOT NULL,
  `employment_type` varchar(255) NOT NULL,
  `min_work_experience` varchar(255) NOT NULL,
  `min_educational_level` varchar(255) NOT NULL,
  `monthly_salary_range` varchar(255) NOT NULL,
  `job_location` varchar(500) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `company_overview` text NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `company_logo` varchar(500) NOT NULL,
  `nationality` varchar(500) NOT NULL,
  `function` varchar(500) NOT NULL,
  `role` varchar(500) NOT NULL,
  `keyword_skill` varchar(500) NOT NULL,
  `foreign_language` varchar(500) NOT NULL,
  `gender` varchar(500) NOT NULL,
  `salary_type` varchar(500) NOT NULL,
  `job_date_posted` varchar(255) DEFAULT NULL,
  `skip` tinyint(4) NOT NULL DEFAULT '0',
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `job_details`
--
ALTER TABLE `job_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_unique_id` (`job_unique_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `job_details`
--
ALTER TABLE `job_details`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
