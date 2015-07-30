--
-- Schema & User
--

CREATE DATABASE rest CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE USER 'rest_admin' IDENTIFIED BY 'changeme';
GRANT ALL ON rest.* TO 'rest_admin'@'%' IDENTIFIED BY 'changeme';
GRANT ALL ON rest.* TO 'rest_admin'@'localhost' IDENTIFIED BY 'changeme';
FLUSH PRIVILEGES;

------------- SFDC Tables --------------

-----
-- Default rec_status = 0 = NOT Processed
-----

CREATE TABLE  `rest`.`sfdc_vone_update` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rec_status` smallint(5) NOT NULL DEFAULT '0',
  `caseid` varchar(32) DEFAULT NULL,
  `ownerid` varchar(32) DEFAULT NULL,
  `accountid` varchar(32) DEFAULT NULL,
  `lastmodifiedbyid` varchar(32) DEFAULT NULL,
  `type` varchar(64) DEFAULT NULL,
  `status` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
);
 