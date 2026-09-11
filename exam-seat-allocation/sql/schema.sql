-- =====================================================================
-- Automated Examination Seat Allocation System
-- Database Schema  (MySQL / phpMyAdmin / XAMPP)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS exam_seat_db;
USE exam_seat_db;

-- ---------------------------------------------------------------------
-- Staff / Admin login
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default login  ->  username: admin   password: admin123
INSERT INTO staff (username, password_hash, full_name) VALUES
('admin', '$2y$10$QOKcwSrEl5Yp3kPfz8y8q.wPYacuHO087l8sFmSSMLUcLk3AhepyS', 'Examination Administrator')
ON DUPLICATE KEY UPDATE username = username;

-- ---------------------------------------------------------------------
-- Departments
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dept_code VARCHAR(10) NOT NULL UNIQUE,
    dept_name VARCHAR(100) NOT NULL
);

-- ---------------------------------------------------------------------
-- Students
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    register_no VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    department_id INT NOT NULL,
    year_of_study INT NOT NULL DEFAULT 1,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- Examination Halls
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS halls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hall_name VARCHAR(50) NOT NULL UNIQUE,
    rows_count INT NOT NULL DEFAULT 6,
    cols_count INT NOT NULL DEFAULT 6,
    capacity INT GENERATED ALWAYS AS (rows_count * cols_count) STORED
);

-- ---------------------------------------------------------------------
-- Exams  (one row = one subject/department exam on a date+session)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) NOT NULL,
    subject_name VARCHAR(150) NOT NULL,
    department_id INT NOT NULL,
    exam_date DATE NOT NULL,
    session_slot ENUM('FN','AN') NOT NULL DEFAULT 'FN',
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- Seat Allocation results
-- session_key = exam_date + '_' + session_slot  (groups exams run together)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS exam_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_key VARCHAR(30) NOT NULL,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    hall_id INT NOT NULL,
    seat_row INT NOT NULL,
    seat_col INT NOT NULL,
    seat_label VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (hall_id) REFERENCES halls(id) ON DELETE CASCADE
);

-- =====================================================================
-- Sample data (optional - safe to delete if you want to start empty)
-- =====================================================================

INSERT INTO departments (dept_code, dept_name) VALUES
('CSE', 'Computer Science and Engineering'),
('ECE', 'Electronics and Communication Engineering'),
('MECH', 'Mechanical Engineering'),
('CIVIL', 'Civil Engineering')
ON DUPLICATE KEY UPDATE dept_code = dept_code;

INSERT INTO halls (hall_name, rows_count, cols_count) VALUES
('Hall A', 6, 5),
('Hall B', 6, 5),
('Hall C', 5, 5)
ON DUPLICATE KEY UPDATE hall_name = hall_name;

INSERT INTO students (register_no, name, department_id, year_of_study) VALUES
('CSE001','Aarav Kumar',1,2), ('CSE002','Divya Sharma',1,2), ('CSE003','Rohan Mehta',1,2),
('CSE004','Sneha Iyer',1,2), ('CSE005','Karthik Raj',1,2), ('CSE006','Ananya Nair',1,2),
('CSE007','Vikram Singh',1,2), ('CSE008','Priya Menon',1,2),
('ECE001','Arjun Das',2,2), ('ECE002','Meera Pillai',2,2), ('ECE003','Suresh Babu',2,2),
('ECE004','Lakshmi Rao',2,2), ('ECE005','Naveen Kumar',2,2), ('ECE006','Kavya Reddy',2,2),
('ECE007','Ravi Teja',2,2), ('ECE008','Pooja Varma',2,2),
('MECH001','Aditya Joshi',3,2), ('MECH002','Nisha Gupta',3,2), ('MECH003','Manoj Verma',3,2),
('MECH004','Swathi Pillai',3,2), ('MECH005','Harish Chandra',3,2), ('MECH006','Divya Prakash',3,2),
('CIVIL001','Ramesh Babu',4,2), ('CIVIL002','Anjali Nair',4,2), ('CIVIL003','Vishal Kumar',4,2),
('CIVIL004','Deepa Krishnan',4,2), ('CIVIL005','Sanjay Rao',4,2)
ON DUPLICATE KEY UPDATE register_no = register_no;

INSERT INTO exams (subject_code, subject_name, department_id, exam_date, session_slot) VALUES
('CS301', 'Database Management Systems', 1, CURDATE() + INTERVAL 7 DAY, 'FN'),
('EC302', 'Digital Signal Processing', 2, CURDATE() + INTERVAL 7 DAY, 'FN'),
('ME303', 'Thermodynamics', 3, CURDATE() + INTERVAL 7 DAY, 'FN'),
('CE304', 'Structural Analysis', 4, CURDATE() + INTERVAL 7 DAY, 'FN')
ON DUPLICATE KEY UPDATE subject_code = subject_code;
