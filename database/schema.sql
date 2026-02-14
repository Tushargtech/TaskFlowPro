-- 1. User Roles Table
CREATE TABLE user_roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_title VARCHAR(50) NOT NULL,
    role_status ENUM('Active', 'Inactive') DEFAULT 'Active'
);

CREATE TABLE users (
    user_id INT AUTO_INCREMENT,
    user_login VARCHAR(50) NOT NULL UNIQUE,
    user_email VARCHAR(100) NOT NULL UNIQUE,
    user_phone VARCHAR(15),
    user_password VARCHAR(255) NOT NULL,
    user_first_name VARCHAR(50),
    user_last_name VARCHAR(50),
    user_role_id INT,
    user_status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    user_created_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    user_created_by INT,
    user_modified_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    user_modified_by INT,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_user_role
        FOREIGN KEY (user_role_id)
        REFERENCES user_roles(role_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);
-- 3. Seed Master Data (Crucial for the "Out of Scope" requirement)
INSERT INTO user_roles (role_id, role_title, role_status) VALUES 
(1, 'Admin', 'Active'),
(2, 'User', 'Active');

-- Create a Default Admin (Password: admin123)
-- Note: In a real app, we use password_hash, but for DB setup, use a known hash.
INSERT INTO users (user_login, user_email, user_password, user_role_id) VALUES 
('admin_user', 'admin@taskflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);