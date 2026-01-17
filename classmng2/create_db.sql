-- Create ClassFlow database schema
CREATE DATABASE IF NOT EXISTS classflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE classflow;

-- users: admin and faculty
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  fullname VARCHAR(255) DEFAULT '',
  role ENUM('admin','faculty') NOT NULL DEFAULT 'faculty',
  department VARCHAR(255) DEFAULT '',
  avatar VARCHAR(255) DEFAULT 'assets/img/default-avatar.png',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- subjects (each tied to a faculty)
CREATE TABLE IF NOT EXISTS subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  faculty_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  code VARCHAR(100) DEFAULT '',
  schedule VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE CASCADE
);

-- students (per subject)
CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subject_id INT NOT NULL,
  lastname VARCHAR(255) NOT NULL,
  firstname VARCHAR(255) NOT NULL,
  course VARCHAR(100) DEFAULT '',
  year_level VARCHAR(50) DEFAULT '',
  avatar VARCHAR(255) DEFAULT 'assets/img/default-avatar.png',
  archived TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- attendance records
CREATE TABLE IF NOT EXISTS attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  subject_id INT NOT NULL,
  status ENUM('present','absent') NOT NULL,
  date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- activities
CREATE TABLE IF NOT EXISTS activities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  subject_id INT NOT NULL,
  title VARCHAR(255) DEFAULT '',
  score DECIMAL(6,2) DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- grades per student per subject (prelim, midterm, final)
CREATE TABLE IF NOT EXISTS grades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  subject_id INT NOT NULL,
  prelim DECIMAL(5,2) DEFAULT 0.00,
  midterm DECIMAL(5,2) DEFAULT 0.00,
  finals DECIMAL(5,2) DEFAULT 0.00,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- Seed admin user (username: admin, password: password123)
INSERT INTO users (username, password, fullname, role, department)
VALUES ('admin', '{PASSWORD_PLACEHOLDER}', 'Administrator', 'admin', 'IT Department')
ON DUPLICATE KEY UPDATE username = username;