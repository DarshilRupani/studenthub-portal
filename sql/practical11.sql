-- Practical 11 migration: run in the existing studenthub_db.
ALTER TABLE students ADD COLUMN IF NOT EXISTS status ENUM('active','deleted') NOT NULL DEFAULT 'active';
-- Twenty fictional records. Repeat imports leave existing student records unchanged.
INSERT INTO students (fullname,email,mobile,course,year_of_study) VALUES
('Aditi Shah','aditi.lab11@example.com','9876543201','bca',1),
('Aryan Patel','aryan.lab11@example.com','9876543202','btech-ce',2),
('Bhavya Mehta','bhavya.lab11@example.com','9876543203','btech-it',3),
('Chirag Desai','chirag.lab11@example.com','9876543204','mca',1),
('Diya Joshi','diya.lab11@example.com','9876543205','bca',2),
('Esha Trivedi','esha.lab11@example.com','9876543206','btech-ce',4),
('Harsh Shah','harsh.lab11@example.com','9876543207','btech-it',1),
('Isha Patel','isha.lab11@example.com','9876543208','mca',2),
('Jay Mehta','jay.lab11@example.com','9876543209','bca',3),
('Kavya Desai','kavya.lab11@example.com','9876543210','btech-ce',1),
('Krish Joshi','krish.lab11@example.com','9876543211','btech-it',2),
('Manav Trivedi','manav.lab11@example.com','9876543212','mca',1),
('Neha Shah','neha.lab11@example.com','9876543213','bca',4),
('Om Patel','om.lab11@example.com','9876543214','btech-ce',3),
('Pari Mehta','pari.lab11@example.com','9876543215','btech-it',4),
('Rohan Desai','rohan.lab11@example.com','9876543216','mca',2),
('Sana Joshi','sana.lab11@example.com','9876543217','bca',1),
('Tanvi Trivedi','tanvi.lab11@example.com','9876543218','btech-ce',2),
('Vihaan Shah','vihaan.lab11@example.com','9876543219','btech-it',3),
('Yash Patel','yash.lab11@example.com','9876543220','other',4)
ON DUPLICATE KEY UPDATE student_id=student_id;
