-- ============================================================
-- sql/seed.sql - SAMPLE data for testing only (all names are fictional).
-- Replace with approved real courses/units/lecturers before the final demo.
-- Run AFTER schema.sql, ONCE (running twice would add duplicates of lecturers).
-- ============================================================
USE student_evaluation;

INSERT INTO lecturers (full_name, department) VALUES
  ('Dr. Amina Otieno',   'Computing'),
  ('Prof. Brian Mwangi', 'Computing'),
  ('Ms. Grace Wanjiru',  'Business'),
  ('Mr. Daniel Kiptoo',  'Networks'),
  ('Dr. Fatuma Hassan',  'Security');

INSERT INTO courses (code, title) VALUES
  ('BSC-CS',  'BSc Computer Science'),
  ('BSC-CNS', 'BSc Cybersecurity and Networking Systems'),
  ('BBIT',    'Bachelor of Business Information Technology');

INSERT INTO units (course_id, lecturer_id, code, title)
SELECT c.id, l.id, v.ucode, v.utitle
FROM (
  SELECT 'BSC-CS'  AS ccode, 'Dr. Amina Otieno'   AS lname, 'CS101' AS ucode, 'Introduction to Programming' AS utitle UNION ALL
  SELECT 'BSC-CS',           'Prof. Brian Mwangi',          'CS204',          'Data Structures and Algorithms' UNION ALL
  SELECT 'BSC-CNS',          'Mr. Daniel Kiptoo',           'CNS210',         'Computer Networks'              UNION ALL
  SELECT 'BSC-CNS',          'Dr. Fatuma Hassan',           'CNS320',         'Network Security'               UNION ALL
  SELECT 'BSC-CNS',          'Dr. Fatuma Hassan',           'CNS330',         'Ethical Hacking'                UNION ALL
  SELECT 'BBIT',             'Ms. Grace Wanjiru',           'BIT150',         'Business Information Systems'   UNION ALL
  SELECT 'BBIT',             'Prof. Brian Mwangi',          'BIT240',         'Database Systems'               UNION ALL
  SELECT 'BSC-CS',           'Dr. Amina Otieno',            'CS310',          'Web Application Development'
) v
JOIN courses   c ON c.code = v.ccode
JOIN lecturers l ON l.full_name = v.lname;
