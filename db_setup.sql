CREATE TABLE IF NOT EXISTS `message` (
  `message_ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `story_ID` int(10) unsigned NOT NULL,
  `message` text NOT NULL,
  `source` enum('player','writer') NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_ID`),
  KEY `FK_message_session_ID_idx` (`story_ID`)
);

CREATE TABLE IF NOT EXISTS `player` (
  `player_ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  PRIMARY KEY (`player_ID`),
  UNIQUE KEY `name_UNIQUE` (`name`)
);

CREATE TABLE IF NOT EXISTS `story` (
  `story_ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `writer_ID` int(10) unsigned NOT NULL,
  `player_ID` int(10) unsigned NOT NULL,
  `start_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `end_timestamp` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`story_ID`),
  KEY `FK_session_player_id_idx` (`player_ID`),
  KEY `FK_session_writer_id_idx` (`writer_ID`)
);

CREATE TABLE IF NOT EXISTS `writer` (
  `writer_ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  PRIMARY KEY (`writer_ID`),
  UNIQUE KEY `name_UNIQUE` (`name`)
);

ALTER TABLE `message`
  ADD CONSTRAINT `FK_message_story_ID` FOREIGN KEY (`story_ID`) REFERENCES `story` (`story_ID`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `story`
  ADD CONSTRAINT `FK_session_player_id` FOREIGN KEY (`player_ID`) REFERENCES `player` (`player_ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `FK_session_writer_id` FOREIGN KEY (`writer_ID`) REFERENCES `writer` (`writer_ID`) ON DELETE NO ACTION ON UPDATE NO ACTION;
