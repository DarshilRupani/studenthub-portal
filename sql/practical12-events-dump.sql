-- Practical 12 events table dump
-- Import into an empty database for inspection.
SET NAMES utf8mb4;

CREATE TABLE `events` (
  `event_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` datetime NOT NULL,
  `venue` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` enum('technical','academic','cultural','sports','other') NOT NULL DEFAULT 'technical',
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `poster_filename` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`event_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO events (`event_id`,`title`,`description`,`event_date`,`venue`,`created_at`,`category`,`status`,`poster_filename`) VALUES ('1','Web Development Workshop','Learn HTML, CSS, JavaScript and PHP basics.','2026-10-15 10:00:00','Computer Lab 1','2026-10-07 10:23:44','technical','open',NULL);
INSERT INTO events (`event_id`,`title`,`description`,`event_date`,`venue`,`created_at`,`category`,`status`,`poster_filename`) VALUES ('2','College Hackathon','Build a useful application with your team.','2026-10-17 09:00:00','Innovation Lab','2026-10-07 10:23:44','technical','open',NULL);
INSERT INTO events (`event_id`,`title`,`description`,`event_date`,`venue`,`created_at`,`category`,`status`,`poster_filename`) VALUES ('3','Project Exhibition','Present student projects and demonstrate their features.','2026-10-20 11:00:00','Seminar Hall','2026-10-07 10:23:44','technical','open',NULL);
INSERT INTO events (`event_id`,`title`,`description`,`event_date`,`venue`,`created_at`,`category`,`status`,`poster_filename`) VALUES ('4','Campus Sports Day','Sample event for the management practical.','2026-11-05 09:00:00','College Ground','2026-10-14 01:16:53','sports','open',NULL);
INSERT INTO events (`event_id`,`title`,`description`,`event_date`,`venue`,`created_at`,`category`,`status`,`poster_filename`) VALUES ('5','Cultural Evening','Sample closed event for category and status filtering.','2026-11-07 17:00:00','College Auditorium','2026-10-14 01:16:53','cultural','closed',NULL);
INSERT INTO events (`event_id`,`title`,`description`,`event_date`,`venue`,`created_at`,`category`,`status`,`poster_filename`) VALUES ('8','Web Development Workshop Demo','Learn HTML, CSS and PHP. Sample event for Practical 12.','2026-11-14 10:00:00','Computer Lab','2026-10-14 01:23:33','academic','closed','56a9c7cf19bb3ca45b03a3b908b81703.png');
