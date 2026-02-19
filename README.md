# TaskFlow Pro - Mini ERP & Task Management System

## Tech Stack
- **Language:** PHP 8.x (OOP with strict types)
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, Bootstrap 5.3.3
- **Icons:** Bootstrap Icons 1.11.3
- **Server:** Apache (XAMPP/LAMPP Stack)
- **Authentication:** BCrypt password hashing, PDO prepared statements

## Features Implemented
1. **Authentication & Security**
   - Email/login-based authentication with BCrypt hashing
   - Session-based authorization with middleware guards
   - Login activity tracking (user_login_records table)
   - Secure logout functionality

2. **Role-Based Access Control (RBAC)**
   - Admin and User roles with permission scaffolding
   - Role-aware UI (Employees link visible to Admin only)
   - Middleware-protected dashboard endpoints

3. **Employee Management**
   - Admin-only create, read, update, and soft-delete workflows
   - Bootstrap modal forms for Add/Edit operations
   - Real-time validation and error handling
   - Login activity logging per user

4. **Project Management**
   - Admin create project via modal
   - Admin edit projects in-place with full modals
   - Project status tracking (Active/Inactive)
   - Description support for each project

5. **Task Management**
   - Admin assign tasks to employees with projects and due dates
   - Task status tracking (Pending, In Progress, Completed)
   - Task analytics dashboard (total, completed, overdue, due-soon counts)
   - Role-specific task views (admin sees all, users see assigned)
   - Task edit modals for admins with status/assignee updates
   - Mark Done action for assigned users and admins

6. **User Interface**
   - Responsive Bootstrap 5 layout
   - Shared header/footer components with search bar
   - Card layouts for projects and dashboard stats
   - Responsive table for task listings
   - Admin action buttons (Edit, Done, etc.)
   - Role-aware navigation

## Setup Instructions

### Prerequisites
- XAMPP/LAMPP stack with PHP 8.0+
- MySQL server running
- Git for version control

### Installation Steps

1. **Clone the repository:**
   ```bash
   git clone https://github.com/Tushargtech/TaskFlowPro.git
   cd TaskFlowPro
   ```

2. **Configure database connection:**
   - Edit `config/db.php`
   - Update MySQL credentials (host, user, password)
   - Ensure database name is `taskflow_db`

3. **Create database and tables:**
   - Import `database/schema.sql` into MySQL:
     ```bash
     mysql -u root -p taskflow_db < database/schema.sql
     ```

4. **Set file permissions:**
   - Ensure `uploads/` directory exists and is writable
   - Ensure `public/` directory is accessible

5. **Start Apache and MySQL:**
   ```bash
   sudo /opt/lampp/lampp start  # or xampp start on Windows
   ```

6. **Access the application:**
   - Navigate to `http://localhost/TaskFlowPro/`
   - You will be redirected to login

## Test Credentials

| Role | Login | Password |
|------|-------|----------|
| Admin | `admin_user` | `admin123` |

**Note:** Seed the database with additional employees via the Employee Management page to test task assignment workflows.

## API Endpoints

### Authentication
- `POST /src/auth/login_process.php` - Process login form
- `GET /src/auth/logout.php` - Clear session and redirect to login

### Employee Management (Admin Only)
- `POST /src/processes/user_create.php` - Create employee
- `POST /src/processes/user_update.php` - Update employee details
- `POST /src/processes/user_deactivate.php` - Soft delete employee

### Project Management (Admin Only)
- `POST /src/processes/project_create.php` - Create project
- `POST /src/processes/project_update.php` - Update project details

### Task Management (Admin Only)
- `POST /src/processes/task_create.php` - Assign task to employee
- `POST /src/processes/task_update.php` - Update task details/status
- `GET /src/processes/task_complete.php?id={taskId}` - Mark task completed

### Views (Protected by Middleware)
- `GET /views/dashboard.php` - Dashboard with stats
- `GET /views/users.php` - Employee list (Admin only)
- `GET /views/projects.php` - Project board
- `GET /views/tasks.php` - Task manager with analytics

## Known Limitations

1. **Search Bar:** Currently non-functional placeholder; backend search logic not yet implemented
2. **Task Filtering:** No project-specific task filtering; future enhancement planned
3. **Permissions:** RBAC scaffold exists but full permission matrix not enforced in all views
4. **File Uploads:** Uploads directory created but no file attachment feature implemented
5. **Email Notifications:** No email alerts for task assignments or due dates
6. **Audit Trail:** Login records captured; full audit log for all actions pending
7. **Task Deletion:** No hard delete; soft delete (status change) only
8. **Mobile Optimization:** Responsive but not fully tested on small screens

## Screenshots

### Login Page
- Email/login input field
- Password field with secure handling
- Bootstrap alert for invalid credentials

### Dashboard
- Quick stats cards (Total Projects, Active Tasks, Team Members)
- Role-aware navigation (Admin sees Employees link)
- Responsive grid layout

### Employee Management
- Table with employee list
- Add/Edit modals with validation
- Status indicators (Active/Inactive)
- Action buttons (Edit, Deactivate)

### Project Board
- Card layout for projects
- Admin Edit button per project
- Status badge (Active/Inactive)
- Create New Project modal

### Task Manager
- Analytics cards (Total, Completed, Due Soon, Overdue)
- Responsive table with task details
- Status badges with color coding
- Admin Edit and Done buttons
- Assign New Task modal
- Role-specific view (admin sees all, users see assigned)