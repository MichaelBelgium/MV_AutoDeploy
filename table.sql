CREATE TABLE IF NOT EXISTS `Update_Data` (
  `uID` int(11) NOT NULL AUTO_INCREMENT,
  `Hash` varchar(256) NOT NULL,
  `Message` varchar(64) NOT NULL,
  `Type` tinyint(4) NOT NULL,
  `Date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Handled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`uID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;