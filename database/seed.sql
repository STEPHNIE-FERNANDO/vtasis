-- ============================================================
-- VTA SIS — Sample Data  (run AFTER vtasis_db.sql)
-- Default passwords are hashed versions of the plaintext shown
-- admin/support: password  |  faculty: faculty123  |  student: student123
-- ============================================================
USE vtasis_db;

INSERT INTO departments (name, code, description) VALUES
('Information Technology', 'IT', 'Software, networking and IT infrastructure'),
('Electrical Engineering', 'EE', 'Electrical systems and electronics'),
('Mechanical Engineering', 'ME', 'Mechanical design and manufacturing'),
('Civil Construction',     'CC', 'Building and civil engineering'),
('Business Management',    'BM', 'Business, accounting and management');

-- To create correct password hashes open phpMyAdmin SQL tab and run:
-- INSERT INTO users ... with password_hash() — or use the setup below.
-- These are placeholder hashes. Replace with real ones via PHP:
--   php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"

INSERT INTO users (username, password, role, full_name, email, is_active) VALUES
('admin',    '$2y$10$e0MYzXyjpJS7Vz2WceGbBO5IPFJ9/vBrj9t.FHerMhJ.w9IqGj6qO', 'admin',   'System Administrator',  'admin@vtasis.lk',    1),
('support1', '$2y$10$e0MYzXyjpJS7Vz2WceGbBO5IPFJ9/vBrj9t.FHerMhJ.w9IqGj6qO', 'support', 'Support Officer',       'support@vtasis.lk',  1),
('FAC001',   '$2y$10$6Gi6kEBOTHeAu3nIbKlCnuJVNwt/Wz4E0qqLdnHaGHVsq7K2Tld1q', 'faculty', 'Mr. Kamal Perera',      'kamal@vtasis.lk',    1),
('FAC002',   '$2y$10$6Gi6kEBOTHeAu3nIbKlCnuJVNwt/Wz4E0qqLdnHaGHVsq7K2Tld1q', 'faculty', 'Ms. Dilini Silva',      'dilini@vtasis.lk',   1),
('FAC003',   '$2y$10$6Gi6kEBOTHeAu3nIbKlCnuJVNwt/Wz4E0qqLdnHaGHVsq7K2Tld1q', 'faculty', 'Mr. Nimal Fernando',    'nimal@vtasis.lk',    1),
('VTA2024001','$2y$10$XFSGop5Y3eOOJi6jyD2y.OhbbDc9YC4JAtlpidUmHoGAbqLIaqXYi','student', 'Amara Kumari',          'amara@student.lk',   1),
('VTA2024002','$2y$10$XFSGop5Y3eOOJi6jyD2y.OhbbDc9YC4JAtlpidUmHoGAbqLIaqXYi','student', 'Ruwan Dissanayake',     'ruwan@student.lk',   1),
('VTA2024003','$2y$10$XFSGop5Y3eOOJi6jyD2y.OhbbDc9YC4JAtlpidUmHoGAbqLIaqXYi','student', 'Sanduni Pathirana',     'sanduni@student.lk', 1),
('VTA2024004','$2y$10$XFSGop5Y3eOOJi6jyD2y.OhbbDc9YC4JAtlpidUmHoGAbqLIaqXYi','student', 'Chamara Wickramasinghe','chamara@student.lk', 1),
('VTA2024005','$2y$10$XFSGop5Y3eOOJi6jyD2y.OhbbDc9YC4JAtlpidUmHoGAbqLIaqXYi','student', 'Priya Jayawardena',     'priya@student.lk',   1);

INSERT INTO faculty (user_id, faculty_code, full_name, email, phone, specialization, department_id, is_active) VALUES
(3, 'FAC001', 'Mr. Kamal Perera',   'kamal@vtasis.lk',  '0771234567', 'Software Engineering', 1, 1),
(4, 'FAC002', 'Ms. Dilini Silva',   'dilini@vtasis.lk', '0772345678', 'Electronics',          2, 1),
(5, 'FAC003', 'Mr. Nimal Fernando', 'nimal@vtasis.lk',  '0773456789', 'Business Studies',     5, 1);

INSERT INTO students (user_id, student_id_no, full_name, email, phone, gender, department_id, enrolled_date, is_active) VALUES
(6,  'VTA2024001', 'Amara Kumari',           'amara@student.lk',   '0774567890', 'female', 1, '2024-01-15', 1),
(7,  'VTA2024002', 'Ruwan Dissanayake',      'ruwan@student.lk',   '0775678901', 'male',   1, '2024-01-15', 1),
(8,  'VTA2024003', 'Sanduni Pathirana',      'sanduni@student.lk', '0776789012', 'female', 2, '2024-01-15', 1),
(9,  'VTA2024004', 'Chamara Wickramasinghe', 'chamara@student.lk', '0777890123', 'male',   5, '2024-01-15', 1),
(10, 'VTA2024005', 'Priya Jayawardena',      'priya@student.lk',   '0778901234', 'female', 1, '2024-02-01', 1);

INSERT INTO courses (course_code, name, description, department_id, credits, duration_weeks, max_students, status) VALUES
('IT101', 'Introduction to Programming',  'Basic programming concepts using Python', 1, 4, 16, 30, 'active'),
('IT201', 'Web Development Fundamentals', 'HTML, CSS and JavaScript basics',         1, 4, 16, 30, 'active'),
('IT301', 'Database Management Systems',  'Relational databases and SQL',            1, 3, 12, 25, 'active'),
('EE101', 'Basic Electronics',            'Electronic components and circuits',      2, 3, 14, 25, 'active'),
('BM101', 'Principles of Management',     'Management theories and practices',       5, 3, 12, 35, 'active');

INSERT INTO course_assignments (course_id, faculty_id, academic_year, semester) VALUES
(1, 1, '2024/2025', 1),
(2, 1, '2024/2025', 1),
(3, 1, '2024/2025', 2),
(4, 2, '2024/2025', 1),
(5, 3, '2024/2025', 1);

INSERT INTO enrollments (student_id, course_id, academic_year, semester, status) VALUES
(1, 1, '2024/2025', 1, 'enrolled'),
(1, 2, '2024/2025', 1, 'enrolled'),
(2, 1, '2024/2025', 1, 'enrolled'),
(2, 3, '2024/2025', 2, 'enrolled'),
(3, 4, '2024/2025', 1, 'enrolled'),
(4, 5, '2024/2025', 1, 'enrolled'),
(5, 1, '2024/2025', 1, 'enrolled'),
(5, 2, '2024/2025', 1, 'enrolled');

INSERT INTO attendance (student_id, course_id, date, status, marked_by) VALUES
(1, 1, '2024-02-05', 'present', 1),(1, 1, '2024-02-12', 'present', 1),
(1, 1, '2024-02-19', 'absent',  1),(1, 1, '2024-02-26', 'present', 1),
(2, 1, '2024-02-05', 'present', 1),(2, 1, '2024-02-12', 'absent',  1),
(2, 1, '2024-02-19', 'absent',  1),(2, 1, '2024-02-26', 'present', 1),
(5, 1, '2024-02-05', 'present', 1),(5, 1, '2024-02-12', 'present', 1),
(5, 1, '2024-02-19', 'present', 1),(5, 1, '2024-02-26', 'late',    1);

INSERT INTO grades (enrollment_id, student_id, course_id, midterm_score, final_score, assignment_score, total_score, grade_letter, academic_year, semester) VALUES
(1, 1, 1, 72.0, 78.5, 80.0, 77.15, 'A', '2024/2025', 1),
(3, 2, 1, 60.0, 65.0, 70.0, 64.50, 'B', '2024/2025', 1),
(7, 5, 1, 85.0, 88.0, 90.0, 87.90, 'A', '2024/2025', 1);
