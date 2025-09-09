-- Creating MySQL database schema for conference room booking system
CREATE DATABASE IF NOT EXISTS conference_booking;
USE conference_booking;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Rooms table
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    capacity INT NOT NULL,
    location VARCHAR(255) NOT NULL,
    amenities TEXT,
    description TEXT,
    image_url VARCHAR(500),
    price_per_hour DECIMAL(10,2) NOT NULL,
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    user_name VARCHAR(255) NOT NULL,
    room_name VARCHAR(255) NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    purpose TEXT NOT NULL,
    attendees INT NOT NULL,
    total_cost DECIMAL(10,2) NOT NULL,
    status ENUM('confirmed', 'pending', 'cancelled') DEFAULT 'confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert sample rooms
INSERT INTO rooms (name, capacity, location, amenities, description, image_url, price_per_hour, available) VALUES
('Executive Boardroom', 12, 'Floor 10, East Wing', 'Projector,Whiteboard,Video Conferencing,Coffee Machine', 'Premium boardroom with city views, perfect for executive meetings and presentations.', '/modern-executive-boardroom-with-large-table.jpg', 50.00, TRUE),
('Creative Studio', 8, 'Floor 5, West Wing', 'Whiteboard,Wireless Display,Standing Desks,Natural Light', 'Bright, creative space designed for brainstorming and collaborative work sessions.', '/bright-creative-meeting-room-with-whiteboard.jpg', 35.00, TRUE),
('Tech Hub', 6, 'Floor 3, North Wing', 'Multiple Monitors,High-Speed Internet,Power Outlets,Ergonomic Chairs', 'Technology-focused room equipped for development teams and technical discussions.', '/modern-tech-meeting-room-with-monitors.jpg', 40.00, FALSE),
('Collaboration Space', 15, 'Floor 7, Central', 'Projector,Sound System,Moveable Furniture,Catering Setup', 'Flexible space that can be configured for various meeting types and team events.', '/flexible-collaboration-meeting-space.jpg', 45.00, TRUE),
('Quiet Focus Room', 4, 'Floor 2, South Wing', 'Soundproofing,Whiteboard,Comfortable Seating,Natural Light', 'Intimate space perfect for small team meetings and focused discussions.', '/small-quiet-meeting-room-with-comfortable-seating.jpg', 25.00, TRUE),
('Presentation Theater', 30, 'Floor 1, Main Hall', 'Large Screen,Audio System,Theater Seating,Recording Equipment', 'Professional presentation space ideal for large meetings and company events.', '/presentation-theater-with-large-screen.jpg', 75.00, TRUE);

-- Create admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES 
('Admin User', 'admin@conference.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
