-- Create Database
CREATE DATABASE IF NOT EXISTS hall_allocation;
USE hall_allocation;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Halls Table
CREATE TABLE IF NOT EXISTS halls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    capacity INT NOT NULL,
    price_per_day DECIMAL(10, 2) NOT NULL,
    description TEXT,
    facilities TEXT, -- Store as comma separated or JSON string
    main_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Slots Table
CREATE TABLE IF NOT EXISTS slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL, -- e.g., 'Morning Slot'
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('active', 'disabled') DEFAULT 'active'
);

-- Bookings Table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id VARCHAR(50) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    hall_id INT NOT NULL,
    event_name VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL,
    slot_id INT NOT NULL,
    is_full_day BOOLEAN DEFAULT FALSE,
    guest_count INT NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (hall_id) REFERENCES halls(id),
    FOREIGN KEY (slot_id) REFERENCES slots(id)
);

-- Insert Default Slots
INSERT INTO slots (name, start_time, end_time) VALUES 
('Morning Slot', '06:00:00', '14:00:00'),
('Evening Slot', '15:00:00', '23:00:00');

-- Insert Sample Hall (Optional, but good for testing)
INSERT INTO halls (name, location, capacity, price_per_day, description, facilities) VALUES 
('Grand Royal Ballroom', 'T. Nagar, Chennai', 500, 150000.00, 'A premium ballroom for grand weddings.', 'AC, Dining Hall, Parking, Generator, Decoration');
