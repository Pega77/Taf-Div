CREATE DATABASE IF NOT EXISTS student_mvp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_mvp;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'coordinator', 'instructor') NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE programs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    program_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    instructor_user_id BIGINT UNSIGNED NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_groups_program (program_id),
    INDEX idx_groups_instructor (instructor_user_id),
    CONSTRAINT fk_groups_program FOREIGN KEY (program_id) REFERENCES programs(id),
    CONSTRAINT fk_groups_instructor FOREIGN KEY (instructor_user_id) REFERENCES users(id)
);

CREATE TABLE students (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    national_id VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    gender ENUM('male', 'female', 'other') NULL,
    birth_date DATE NULL,
    status ENUM('active', 'frozen') NOT NULL DEFAULT 'active',
    freeze_reason VARCHAR(255) NULL,
    frozen_at DATETIME NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_students_status (status),
    INDEX idx_students_name (full_name)
);

CREATE TABLE group_student (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    joined_at DATE NULL,
    left_at DATE NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_group_student_group (group_id),
    INDEX idx_group_student_student (student_id),
    CONSTRAINT fk_group_student_group FOREIGN KEY (group_id) REFERENCES groups(id),
    CONSTRAINT fk_group_student_student FOREIGN KEY (student_id) REFERENCES students(id),
    UNIQUE KEY uq_group_student_active (student_id, is_active)
);

CREATE TABLE metadata_fields (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    program_id BIGINT UNSIGNED NOT NULL,
    field_key VARCHAR(100) NOT NULL,
    field_label VARCHAR(150) NOT NULL,
    field_type ENUM('text', 'number', 'date', 'boolean', 'select') NOT NULL,
    is_required TINYINT(1) NOT NULL DEFAULT 0,
    options_json JSON NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_metadata_fields_program FOREIGN KEY (program_id) REFERENCES programs(id),
    UNIQUE KEY uq_program_field_key (program_id, field_key)
);

CREATE TABLE metadata_values (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    metadata_field_id BIGINT UNSIGNED NOT NULL,
    value_text TEXT NULL,
    value_number DECIMAL(12,2) NULL,
    value_date DATE NULL,
    value_boolean TINYINT(1) NULL,
    value_json JSON NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_metadata_values_student FOREIGN KEY (student_id) REFERENCES students(id),
    CONSTRAINT fk_metadata_values_field FOREIGN KEY (metadata_field_id) REFERENCES metadata_fields(id),
    UNIQUE KEY uq_student_field (student_id, metadata_field_id)
);

CREATE TABLE activity_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE activities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    program_id BIGINT UNSIGNED NOT NULL,
    group_id BIGINT UNSIGNED NOT NULL,
    activity_type_id BIGINT UNSIGNED NOT NULL,
    activity_date DATE NOT NULL,
    notes VARCHAR(500) NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activities_group_date (group_id, activity_date),
    INDEX idx_activities_creator (created_by_user_id),
    CONSTRAINT fk_activities_program FOREIGN KEY (program_id) REFERENCES programs(id),
    CONSTRAINT fk_activities_group FOREIGN KEY (group_id) REFERENCES groups(id),
    CONSTRAINT fk_activities_type FOREIGN KEY (activity_type_id) REFERENCES activity_types(id),
    CONSTRAINT fk_activities_user FOREIGN KEY (created_by_user_id) REFERENCES users(id)
);

CREATE TABLE activity_students (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activity_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    participation_status ENUM('present', 'absent') NOT NULL DEFAULT 'present',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activity_students_activity (activity_id),
    INDEX idx_activity_students_student (student_id),
    CONSTRAINT fk_activity_students_activity FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
    CONSTRAINT fk_activity_students_student FOREIGN KEY (student_id) REFERENCES students(id),
    UNIQUE KEY uq_activity_student (activity_id, student_id)
);

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(150) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
    details_json JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_user_date (user_id, created_at),
    INDEX idx_audit_entity (entity_type, entity_id),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id)
);
