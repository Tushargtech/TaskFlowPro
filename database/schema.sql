
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

INSERT INTO user_roles (role_id, role_title, role_status) VALUES 
(1, 'Admin', 'Active'),
(2, 'User', 'Active');


INSERT INTO users (user_login, user_email, user_password, user_role_id) VALUES 
('admin_user', 'admin@taskflow.com', '$2y$10$bPBuJWImOODXwS.U5AHZCOh7jGbR2bhRNem3I9zuk9ZcP6OMbnY6O', 1);


CREATE TABLE user_access_rights (
    right_id INT PRIMARY KEY AUTO_INCREMENT,
    right_title VARCHAR(50) NOT NULL,
    right_status ENUM('Active', 'Inactive') DEFAULT 'Active'
);


CREATE TABLE user_role_mapping (
    access_map_id INT PRIMARY KEY AUTO_INCREMENT,
    access_role_id INT,
    access_right_id INT,
    access_status ENUM('Yes', 'No') DEFAULT 'No',
    FOREIGN KEY (access_role_id) REFERENCES user_roles(role_id),
    FOREIGN KEY (access_right_id) REFERENCES user_access_rights(right_id)
);


CREATE TABLE projects (
    project_id INT PRIMARY KEY AUTO_INCREMENT,
    project_title VARCHAR(100) NOT NULL,
    project_description TEXT,
    project_status ENUM('Active', 'Inactive') DEFAULT 'Active',
    project_created_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    project_created_by INT,
    project_modified_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    project_modified_by INT
);


CREATE TABLE tasks (
    task_id INT PRIMARY KEY AUTO_INCREMENT,
    task_title VARCHAR(100) NOT NULL,
    task_description TEXT,
    task_project_id INT,
    task_due_date DATE,
    task_status ENUM('Due', 'Completed', 'Inactive') DEFAULT 'Due',
    task_assigned_to INT,
    task_created_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    task_created_by INT,
    task_modified_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    task_modified_by INT,
    FOREIGN KEY (task_project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (task_assigned_to) REFERENCES users(user_id)
);


INSERT INTO user_access_rights (right_id, right_title) VALUES 
(1, 'Create_User'), (2, 'Edit_User'), (3, 'List_User'),
(4, 'Create_Project'), (5, 'List_Project'), (6, 'Edit_Project'),
(7, 'Create_Task'), (8, 'List_Task'), (9, 'Edit_Task');