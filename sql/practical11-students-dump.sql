-- Practical 11 students table dump
-- Import into an empty database for inspection.
SET NAMES utf8mb4;

CREATE TABLE `students` (
  `student_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mobile` varchar(10) DEFAULT NULL,
  `course` varchar(30) NOT NULL,
  `year_of_study` tinyint(3) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','deleted') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `uq_students_email` (`email`),
  CONSTRAINT `chk_student_year` CHECK (`year_of_study` between 1 and 4)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('1','Darshil Rupani','darshil@example.com',NULL,'btech-ce','3','2026-10-07 10:23:44','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('2','Aarav Shah','aarav@example.com',NULL,'btech-it','2','2026-10-07 10:23:44','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('3','Meera Patel','meera@example.com',NULL,'bca','1','2026-10-07 10:23:44','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('4','Riya Desai','riya@example.com',NULL,'btech-ce','2','2026-10-07 10:23:44','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('5','Dev Joshi','dev@example.com',NULL,'mca','1','2026-10-07 10:23:44','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('6','Aditi Shah','aditi.lab11@example.com','9876543201','bca','1','2026-10-14 00:48:54','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('7','Aryan Patel','aryan.lab11@example.com','9876543202','btech-ce','2','2026-10-14 00:48:54','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('8','Bhavya Mehta','bhavya.lab11@example.com','9876543203','btech-it','3','2026-10-14 00:48:54','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('9','Chirag Desai','chirag.lab11@example.com','9876543204','mca','1','2026-10-14 00:48:54','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('10','Diya Joshi','diya.lab11@example.com','9876543205','bca','2','2026-10-14 00:48:54','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('11','Esha Trivedi','esha.lab11@example.com','9876543206','btech-ce','4','2026-10-14 00:48:54','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('12','Harsh Shah','harsh.lab11@example.com','9876543207','btech-it','1','2026-10-14 00:48:54','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('13','Isha Patel','isha.lab11@example.com','9876543208','mca','2','2026-10-14 00:48:54','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('14','Jay Mehta','jay.lab11@example.com','9876543209','bca','3','2026-10-14 00:48:54','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('15','Kavya Desai','kavya.lab11@example.com','9876543210','btech-ce','1','2026-10-14 00:48:54','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('16','Krish Joshi','krish.lab11@example.com','9876543211','btech-it','2','2026-10-14 00:48:54','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('17','Manav Trivedi','manav.lab11@example.com','9876543212','mca','1','2026-10-14 00:48:54','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('18','Neha Shah','neha.lab11@example.com','9876543213','bca','4','2026-10-14 00:48:54','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('19','Om Patel','om.lab11@example.com','9876543214','btech-ce','3','2026-10-14 00:48:54','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('20','Pari Mehta','pari.lab11@example.com','9876543215','btech-it','4','2026-10-14 00:48:54','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('21','Rohan Desai','rohan.lab11@example.com','9876543216','mca','2','2026-10-14 00:48:54','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('22','Sana Joshi','sana.lab11@example.com','9876543217','bca','1','2026-10-14 00:48:54','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('23','Tanvi Trivedi','tanvi.lab11@example.com','9876543218','btech-ce','2','2026-10-14 00:48:54','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('24','Vihaan Shah','vihaan.lab11@example.com','9876543219','btech-it','3','2026-10-14 00:48:54','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('25','Yash Patel','yash.lab11@example.com','9876543220','other','4','2026-10-14 00:48:54','active');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('26','CRUD Updated Student','crud.1791919215.lab11@example.com','9876543210','btech-it','3','2026-10-14 00:50:15','deleted');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('28','Updated Student','screenshot.lab11@example.com','9876543221','btech-it','3','2026-10-14 00:51:50','deleted');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('29','CRUD Updated Student','crud.1791919434.lab11@example.com','9876543210','btech-it','3','2026-10-14 00:53:54','deleted');
INSERT INTO students (`student_id`,`fullname`,`email`,`mobile`,`course`,`year_of_study`,`created_at`,`status`) VALUES ('31','CRUD Updated Student','crud.1791919517.lab11@example.com','9876543210','btech-it','3','2026-10-14 00:55:17','deleted');
