USE student_mvp;

-- Extensions needed by the mobile attendance/calendar MVP on top of the existing Taf-Div schema.

CREATE TABLE IF NOT EXISTS coordinator_groups (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  coordinator_user_id BIGINT UNSIGNED NOT NULL,
  group_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_coordinator_groups_user FOREIGN KEY (coordinator_user_id) REFERENCES users(id),
  CONSTRAINT fk_coordinator_groups_group FOREIGN KEY (group_id) REFERENCES groups(id),
  UNIQUE KEY uq_coordinator_group (coordinator_user_id, group_id)
);

CREATE TABLE IF NOT EXISTS activity_type_program (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  activity_type_id BIGINT UNSIGNED NOT NULL,
  program_id BIGINT UNSIGNED NOT NULL,
  CONSTRAINT fk_activity_type_program_type FOREIGN KEY (activity_type_id) REFERENCES activity_types(id),
  CONSTRAINT fk_activity_type_program_program FOREIGN KEY (program_id) REFERENCES programs(id),
  UNIQUE KEY uq_activity_type_program (activity_type_id, program_id)
);

ALTER TABLE activities
  ADD COLUMN start_time TIME NULL AFTER activity_date,
  ADD COLUMN end_time TIME NULL AFTER start_time,
  ADD COLUMN personal_student_id BIGINT UNSIGNED NULL AFTER end_time;

ALTER TABLE activities
  ADD INDEX idx_activities_personal_student (personal_student_id);

-- If the FK already exists, MySQL may fail on this line. In that case it is safe to skip it.
ALTER TABLE activities
  ADD CONSTRAINT fk_activities_personal_student FOREIGN KEY (personal_student_id) REFERENCES students(id);

-- Demo mappings for the supplied seed.sql.
INSERT IGNORE INTO coordinator_groups (coordinator_user_id, group_id)
SELECT 2, id FROM groups;

-- For MVP, map all existing activity types to all programs unless specific program mappings are inserted later.
INSERT IGNORE INTO activity_type_program (activity_type_id, program_id)
SELECT at.id, p.id FROM activity_types at CROSS JOIN programs p;
