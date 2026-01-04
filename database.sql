-- ============================================
-- BugTracker Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS bugtracker
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE bugtracker;

-- ============================================
-- Table: users
-- ============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- ============================================
-- Table: categories
-- ============================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Table: tickets
-- ============================================
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT NOT NULL,
    status ENUM('Open', 'In Progress', 'Closed') DEFAULT 'Open',
    priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    created_by INT NOT NULL,
    assigned_to INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_created_by (created_by),
    INDEX idx_category (category_id)
) ENGINE=InnoDB;

-- ============================================
-- Insert default data
-- ============================================

-- Insert default admin user
-- Password: 123456 (hashed with password_hash())
INSERT INTO users (name, email, password) VALUES 
('Admin User', 'admin@bugtracker.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert categories
INSERT INTO categories (name) VALUES 
('Front-end'),
('Back-end'),
('Infrastructure');

-- Insert 10 sample tickets
INSERT INTO tickets (title, description, category_id, status, priority, created_by, assigned_to, resolved_at) VALUES 
('Button not responding on mobile', 'The submit button does not work on iOS devices', 1, 'Open', 'High', 1, NULL, NULL),
('API endpoint returns 500 error', 'The /api/users endpoint crashes when no parameters are provided', 2, 'In Progress', 'Critical', 1, 1, NULL),
('Database connection timeout', 'Server loses connection to MySQL after 1 hour of inactivity', 3, 'Open', 'High', 1, NULL, NULL),
('Login form validation missing', 'Email field accepts invalid email formats', 1, 'Closed', 'Medium', 1, 1, '2024-12-20 10:30:00'),
('Slow query on reports page', 'The reports page takes 15+ seconds to load', 2, 'In Progress', 'High', 1, 1, NULL),
('SSL certificate expired', 'Production SSL certificate needs renewal', 3, 'Closed', 'Critical', 1, 1, '2024-12-18 14:20:00'),
('Navigation menu overlaps content', 'On tablet view, the menu covers the main content area', 1, 'Open', 'Low', 1, NULL, NULL),
('Password reset email not sending', 'Users report not receiving password reset emails', 2, 'Open', 'High', 1, NULL, NULL),
('Server disk space at 95%', 'Need to clean up old log files or expand storage', 3, 'In Progress', 'Critical', 1, 1, NULL),
('Dark mode toggle not persisting', 'User preference for dark mode resets on page refresh', 1, 'Closed', 'Low', 1, 1, '2024-12-22 09:15:00');

-- ============================================
-- Useful queries for the application
-- ============================================

-- Get all tickets with user and category information
-- SELECT t.*, u.name as creator_name, a.name as assigned_name, c.name as category_name
-- FROM tickets t
-- JOIN users u ON t.created_by = u.id
-- LEFT JOIN users a ON t.assigned_to = a.id
-- JOIN categories c ON t.category_id = c.id
-- ORDER BY t.created_at DESC;

-- Get ticket statistics
-- SELECT 
--     COUNT(*) as total_tickets,
--     SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open_tickets,
--     SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_tickets,
--     SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_tickets
-- FROM tickets;