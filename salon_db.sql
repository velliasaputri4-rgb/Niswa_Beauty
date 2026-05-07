-- =============================================
-- DATABASE: salon_db
-- Jalankan script ini di phpMyAdmin
-- =============================================

CREATE DATABASE IF NOT EXISTS salon_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE salon_db;

CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    service VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data (opsional)
INSERT INTO bookings (name, phone, email, service, date, time) VALUES
('Sari Dewi', '081234567890', 'sari@email.com', 'Hair Treatment', '2025-06-01', '10:00:00'),
('Budi Santoso', '082345678901', 'budi@email.com', 'Facial', '2025-06-02', '13:00:00'),
('Rina Putri', '083456789012', 'rina@email.com', 'Manicure & Pedicure', '2025-06-03', '15:00:00');
