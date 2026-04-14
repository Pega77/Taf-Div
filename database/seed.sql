USE student_mvp;

INSERT INTO users (full_name, username, phone, password_hash, role) VALUES
('מנהל מערכת', 'admin', '0500000000', '$2y$12$doRqsOZrlOlIB8yi./pmBO1g07bxzOykJesBvk/rNNtfbH4x.jgpK', 'admin'),
('רכזת תוכנית', 'coordinator', '0500000001', '$2y$12$doRqsOZrlOlIB8yi./pmBO1g07bxzOykJesBvk/rNNtfbH4x.jgpK', 'coordinator'),
('מדריך ראשון', 'instructor1', '0500000002', '$2y$12$doRqsOZrlOlIB8yi./pmBO1g07bxzOykJesBvk/rNNtfbH4x.jgpK', 'instructor'),
('מדריכה שנייה', 'instructor2', '0500000003', '$2y$12$doRqsOZrlOlIB8yi./pmBO1g07bxzOykJesBvk/rNNtfbH4x.jgpK', 'instructor');

INSERT INTO programs (name, status) VALUES
('תוכנית מנהיגות', 'active'),
('תוכנית קהילה', 'active');

INSERT INTO groups (program_id, name, instructor_user_id, status) VALUES
(1, 'קבוצת בוגרים', 3, 'active'),
(1, 'קבוצת צעירים', 4, 'active'),
(2, 'קבוצת הורים', 3, 'active');

INSERT INTO students (national_id, full_name, gender, birth_date, status) VALUES
('123456789', 'נועה לוי', 'female', '2010-02-14', 'active'),
('223456789', 'יונתן כהן', 'male', '2009-11-20', 'active'),
('323456789', 'דניאל ישראלי', 'male', '2011-05-03', 'active'),
('423456789', 'רוני מזרחי', 'female', '2010-09-08', 'frozen');

INSERT INTO group_student (group_id, student_id, is_active, joined_at) VALUES
(1, 1, 1, '2026-01-01'),
(1, 2, 1, '2026-01-01'),
(2, 3, 1, '2026-01-01'),
(3, 4, 1, '2026-01-01');

INSERT INTO metadata_fields (program_id, field_key, field_label, field_type, is_required, options_json, sort_order, is_active) VALUES
(1, 'school_name', 'בית ספר', 'text', 0, NULL, 1, 1),
(1, 'grade_level', 'שכבה', 'select', 0, JSON_ARRAY('ז', 'ח', 'ט', 'י', 'יא', 'יב'), 2, 1),
(2, 'city', 'יישוב', 'text', 0, NULL, 1, 1);

INSERT INTO metadata_values (student_id, metadata_field_id, value_text, value_json) VALUES
(1, 1, 'מקיף א', NULL),
(1, 2, NULL, JSON_QUOTE('י')),
(3, 3, 'אשדוד', NULL);

INSERT INTO activity_types (name, is_active, sort_order) VALUES
('מפגש קבוצתי', 1, 1),
('מפגש פרטני', 1, 2),
('סדנה', 1, 3),
('אירוע', 1, 4);

INSERT INTO activities (program_id, group_id, activity_type_id, activity_date, notes, created_by_user_id) VALUES
(1, 1, 1, CURDATE(), 'מפגש פתיחה', 3),
(1, 2, 3, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'סדנת היכרות', 4);

INSERT INTO activity_students (activity_id, student_id, participation_status) VALUES
(1, 1, 'present'),
(1, 2, 'present'),
(2, 3, 'present');
