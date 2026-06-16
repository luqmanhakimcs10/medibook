-- =============================================
-- MediBook - Online Clinic Appointment System
-- Database Schema
-- =============================================

CREATE DATABASE IF NOT EXISTS medibook_db;
USE medibook_db;

-- Users Table (patients, doctors, admin)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('patient', 'doctor', 'admin') DEFAULT 'patient',
    phone VARCHAR(20),
    gender ENUM('Male', 'Female', 'Other'),
    date_of_birth DATE,
    address TEXT,
    profile_image VARCHAR(255) DEFAULT 'default.png',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Doctors Extended Info
CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    specialization VARCHAR(100),
    qualification VARCHAR(200),
    experience_years INT DEFAULT 0,
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    bio TEXT,
    available_days VARCHAR(100) DEFAULT 'Mon,Tue,Wed,Thu,Fri',
    slot_duration INT DEFAULT 30,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Time Slots
CREATE TABLE time_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    slot_time VARCHAR(20) NOT NULL,
    day_type ENUM('weekday','weekend','all') DEFAULT 'weekday',
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- Appointments
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    time_slot VARCHAR(20) NOT NULL,
    status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
    symptoms TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Prescriptions
CREATE TABLE prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    doctor_id INT NOT NULL,
    patient_id INT NOT NULL,
    diagnosis TEXT,
    medicines TEXT,
    instructions TEXT,
    follow_up_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id),
    FOREIGN KEY (patient_id) REFERENCES users(id)
);

-- Notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200),
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- SAMPLE DATA
-- =============================================

-- Admin user (password: admin123)
INSERT INTO users (name, email, password, role, phone) VALUES
('Admin User', 'admin@medibook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '03001234567');

-- Sample Doctors (password: doctor123)
INSERT INTO users (name, email, password, role, phone, gender) VALUES
('Dr. Ayesha Siddiqui', 'ayesha@medibook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '03011111111', 'Female'),
('Dr. Usman Khalid', 'usman@medibook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '03022222222', 'Male'),
('Dr. Sara Ahmed', 'sara@medibook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '03033333333', 'Female');

-- Doctor profiles
INSERT INTO doctors (user_id, specialization, qualification, experience_years, consultation_fee, bio, available_days) VALUES
(2, 'Cardiologist', 'MBBS, FCPS Cardiology', 12, 2000.00, 'Specialist in heart diseases with 12+ years of experience.', 'Mon,Tue,Wed,Thu,Fri'),
(3, 'General Physician', 'MBBS, MCPS', 8, 1000.00, 'Experienced GP providing comprehensive primary care.', 'Mon,Tue,Wed,Thu,Fri,Sat'),
(4, 'Dermatologist', 'MBBS, FCPS Dermatology', 6, 1500.00, 'Expert in skin, hair and nail conditions.', 'Tue,Wed,Thu,Fri,Sat');

-- Time slots for each doctor
INSERT INTO time_slots (doctor_id, slot_time, is_active) VALUES
(1, '09:00 AM', 1),(1, '09:30 AM', 1),(1, '10:00 AM', 1),(1, '10:30 AM', 1),
(1, '11:00 AM', 1),(1, '11:30 AM', 1),(1, '02:00 PM', 1),(1, '02:30 PM', 1),
(1, '03:00 PM', 1),(1, '03:30 PM', 1),(1, '04:00 PM', 1),
(2, '09:00 AM', 1),(2, '09:30 AM', 1),(2, '10:00 AM', 1),(2, '10:30 AM', 1),
(2, '11:00 AM', 1),(2, '02:00 PM', 1),(2, '02:30 PM', 1),(2, '03:00 PM', 1),
(3, '10:00 AM', 1),(3, '10:30 AM', 1),(3, '11:00 AM', 1),(3, '11:30 AM', 1),
(3, '02:00 PM', 1),(3, '03:00 PM', 1),(3, '03:30 PM', 1),(3, '04:00 PM', 1);

-- Sample patient (password: patient123)
INSERT INTO users (name, email, password, role, phone, gender) VALUES
('Ali Hassan', 'ali@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '03044444444', 'Male');