-- ============================================================
-- VTA Student Information System — Database Schema
-- Run in phpMyAdmin or MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS vtasis_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vtasis_db;

CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('admin','faculty','student','support') NOT NULL DEFAULT 'student',
    full_name  VARCHAR(150) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    is_active  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS departments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    code        VARCHAR(20),
    description TEXT,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS faculty (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT          NOT NULL,
    faculty_code   VARCHAR(50)  NOT NULL UNIQUE,
    full_name      VARCHAR(150) NOT NULL,
    email          VARCHAR(150) NOT NULL,
    phone          VARCHAR(20),
    specialization VARCHAR(200),
    department_id  INT,
    address        TEXT,
    is_active      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)       REFERENCES users(id)       ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    INDEX idx_faculty_code (faculty_code)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT          NOT NULL,
    student_id_no   VARCHAR(50)  NOT NULL UNIQUE,
    full_name       VARCHAR(150) NOT NULL,
    email           VARCHAR(150) NOT NULL,
    phone           VARCHAR(20),
    nic             VARCHAR(20),
    address         TEXT,
    dob             DATE,
    gender          ENUM('male','female','other'),
    department_id   INT,
    enrolled_date   DATE,
    guardian_name   VARCHAR(150),
    guardian_phone  VARCHAR(20),
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)       REFERENCES users(id)       ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    INDEX idx_student_id_no (student_id_no)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS courses (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    course_code    VARCHAR(50)  NOT NULL UNIQUE,
    name           VARCHAR(200) NOT NULL,
    description    TEXT,
    department_id  INT,
    credits        INT,
    duration_weeks INT,
    max_students   INT,
    status         ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    INDEX idx_course_code (course_code),
    INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS course_assignments (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    course_id     INT         NOT NULL,
    faculty_id    INT         NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester      TINYINT     NOT NULL,
    created_at    TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_assignment (course_id, faculty_id, academic_year, semester),
    FOREIGN KEY (course_id)  REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS enrollments (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    student_id    INT         NOT NULL,
    course_id     INT         NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester      TINYINT     NOT NULL,
    status        ENUM('enrolled','dropped','completed') NOT NULL DEFAULT 'enrolled',
    enrolled_at   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_enrollment (student_id, course_id, academic_year, semester),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id)  REFERENCES courses(id)  ON DELETE CASCADE,
    INDEX idx_student (student_id),
    INDEX idx_course  (course_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attendance (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT         NOT NULL,
    course_id  INT         NOT NULL,
    date       DATE        NOT NULL,
    status     ENUM('present','absent','late','excused') NOT NULL DEFAULT 'present',
    marked_by  INT,
    created_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_attendance (student_id, course_id, date),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id)  REFERENCES courses(id)  ON DELETE CASCADE,
    FOREIGN KEY (marked_by)  REFERENCES users(id)    ON DELETE SET NULL,
    INDEX idx_date (date),
    INDEX idx_student_course (student_id, course_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS grades (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id    INT            NOT NULL UNIQUE,
    student_id       INT            NOT NULL,
    course_id        INT            NOT NULL,
    midterm_score    DECIMAL(5,2),
    final_score      DECIMAL(5,2),
    assignment_score DECIMAL(5,2),
    total_score      DECIMAL(5,2),
    grade_letter     CHAR(2),
    academic_year    VARCHAR(20),
    semester         TINYINT,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id)    REFERENCES students(id)    ON DELETE CASCADE,
    FOREIGN KEY (course_id)     REFERENCES courses(id)     ON DELETE CASCADE,
    INDEX idx_student_course (student_id, course_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS financial_aid (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    student_id   INT            NOT NULL,
    type         VARCHAR(100)   NOT NULL,
    amount       DECIMAL(10,2)  NOT NULL,
    description  TEXT,
    awarded_date DATE,
    status       ENUM('active','expired','pending') NOT NULL DEFAULT 'active',
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;
