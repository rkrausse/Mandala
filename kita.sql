SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";
CREATE DATABASE IF NOT EXISTS `kita` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `kita`;

DROP TABLE IF EXISTS `auth`;
CREATE TABLE `auth` (
  `role` varchar(20) NOT NULL,
  `password` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `auth` (`role`, `password`) VALUES
('adult', 'bar'),
('kid', 'foo');

DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
  `gid` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `comment` varchar(1000) NOT NULL,
  `image` varchar(500) NOT NULL,
  `active` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `groups` (`gid`, `name`, `comment`, `image`, `active`) VALUES
(1, 'Group1', 'Group1Comment', '', 1),
(2, 'Group2', 'Group2Comment', '', 1),
(3, 'Group3', 'Group3Comment', '', 1);

DROP TABLE IF EXISTS `group_assignments`;
CREATE TABLE `group_assignments` (
  `kid` int(11) NOT NULL,
  `aid` int(11) NOT NULL,
  `gid` int(11) NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `group_assignments` (`kid`, `aid`, `gid`, `start`, `end`) VALUES
(1, 1, 1, '2017-12-05 00:00:00', NULL),
(2, 2, 2, '2017-12-04 00:00:00', NULL),
(3, 3, 2, '2017-12-04 06:00:00', '2017-12-04 18:00:00');

DROP TABLE IF EXISTS `kids`;
CREATE TABLE `kids` (
  `kid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `comment` varchar(1000) NOT NULL,
  `image` varchar(500) NOT NULL,
  `active` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `kids` (`kid`, `name`, `comment`, `image`, `active`) VALUES
(1, 'Kid1', 'Kid1Comment', '', 1),
(2, 'Kid2', 'Kid2Comment', '', 1),
(3, 'Kid3', 'Kid3Comment', '', 1),
(4, 'Kid4', 'Kid4Comment', '', 1);


ALTER TABLE `auth`
  ADD PRIMARY KEY (`role`);

ALTER TABLE `groups`
  ADD PRIMARY KEY (`gid`);

ALTER TABLE `group_assignments`
  ADD PRIMARY KEY (`aid`),
  ADD KEY `gid` (`gid`),
  ADD KEY `kid` (`kid`) USING BTREE;

ALTER TABLE `kids`
  ADD PRIMARY KEY (`kid`);


ALTER TABLE `groups`
  MODIFY `gid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `group_assignments`
  MODIFY `aid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `kids`
  MODIFY `kid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `group_assignments`
  ADD CONSTRAINT `FKEY_GROUPS` FOREIGN KEY (`gid`) REFERENCES `groups` (`gid`),
  ADD CONSTRAINT `FKEY_KIDS` FOREIGN KEY (`kid`) REFERENCES `kids` (`kid`);
COMMIT;
