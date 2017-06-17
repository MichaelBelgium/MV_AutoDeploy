CREATE TABLE IF NOT EXISTS `Update_Data` (
  `uID` int(11) NOT NULL AUTO_INCREMENT,
  `Hash` varchar(256) NOT NULL,
  `Message` varchar(64) NOT NULL,
  `Branch` varchar(32) NOT NULL,
  `Date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Handled` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`uID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;
