-- ============================================================
-- Banana University - Student Evaluation System
-- sql/schema.sql  (safe to re-run: uses IF NOT EXISTS, never DROPs)
-- Works on MariaDB 10.4+ (XAMPP) and MariaDB 10.6+ (Ubuntu on Azure)
-- ============================================================

CREATE DATABASE IF NOT EXISTS student_evaluation
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_evaluation;

-- Students and administrators. Passwords are stored ONLY as password_hash().
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(100)  NOT NULL,
  email         VARCHAR(190)  NOT NULL UNIQUE,
  admission_no  VARCHAR(20)   NOT NULL UNIQUE,
  password_hash VARCHAR(255)  NOT NULL,
  role          ENUM('student','admin') NOT NULL DEFAULT 'student',
  avatar_path   VARCHAR(255)  NULL,
  created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lecturers (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name  VARCHAR(100) NOT NULL,
  department VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS courses (
  id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code  VARCHAR(20)  NOT NULL UNIQUE,
  title VARCHAR(150) NOT NULL
) ENGINE=InnoDB;

-- A unit is taught by one lecturer and belongs to one course.
CREATE TABLE IF NOT EXISTS units (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id   INT UNSIGNED NOT NULL,
  lecturer_id INT UNSIGNED NOT NULL,
  code        VARCHAR(20)  NOT NULL UNIQUE,
  title       VARCHAR(150) NOT NULL,
  FOREIGN KEY (course_id)   REFERENCES courses(id)   ON DELETE CASCADE,
  FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ANONYMOUS feedback: deliberately has NO user_id column.
-- Only the DATE is stored (not the time), so an evaluation cannot be matched to
-- a student by comparing timestamps with submission_records.
CREATE TABLE IF NOT EXISTS evaluations (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  unit_id          INT UNSIGNED NOT NULL,
  teaching_quality TINYINT UNSIGNED NOT NULL,
  clarity          TINYINT UNSIGNED NOT NULL,
  engagement       TINYINT UNSIGNED NOT NULL,
  punctuality      TINYINT UNSIGNED NOT NULL,
  materials        TINYINT UNSIGNED NOT NULL,
  comment          TEXT NULL,
  submitted_on     DATE NOT NULL,
  FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
  CHECK (teaching_quality BETWEEN 1 AND 5),
  CHECK (clarity          BETWEEN 1 AND 5),
  CHECK (engagement       BETWEEN 1 AND 5),
  CHECK (punctuality      BETWEEN 1 AND 5),
  CHECK (materials        BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- Records WHO has already evaluated WHICH unit (to stop duplicates) but links to
-- no evaluation row. The UNIQUE key is what really prevents double submissions.
CREATE TABLE IF NOT EXISTS submission_records (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NOT NULL,
  unit_id      INT UNSIGNED NOT NULL,
  submitted_on DATE NOT NULL,
  UNIQUE KEY uq_user_unit (user_id, unit_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Login attempts, used to slow down password guessing (brute force).
CREATE TABLE IF NOT EXISTS auth_attempts (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email        VARCHAR(190) NOT NULL,
  ip_address   VARCHAR(45)  NOT NULL,
  success      TINYINT(1)   NOT NULL DEFAULT 0,
  attempted_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email_time (email, attempted_at),
  INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB;
