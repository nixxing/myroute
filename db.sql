-- phpMyAdmin SQL Dump
-- version 4.0.2
-- http://www.phpmyadmin.net
--
-- Host: db4free.net:3306
-- Generation Time: Jun 03, 2013 at 07:13 PM
-- Server version: 5.6.11-log
-- PHP Version: 5.3.10-1ubuntu3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `dbconnect`
--
CREATE DATABASE IF NOT EXISTS `dbconnect` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `dbconnect`;

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `route_name` varchar(60) NOT NULL,
  `city` varchar(60) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `route_name` (`route_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=13 ;


-- --------------------------------------------------------

--
-- Table structure for table `route_info`
--

CREATE TABLE `route_info` (
  `route_id` int(11) NOT NULL,
  `description` varchar(500) NOT NULL,
  `longitude` double NOT NULL,
  `checkpoint` varchar(60) NOT NULL,
  `latitude` double NOT NULL,
  UNIQUE KEY `checkpoint` (`checkpoint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;