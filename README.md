# TaskFlow Pro - Mini ERP & Task Management System

## Tech Stack
* **Language:** PHP 8.x (Plain/Procedural & OOP)
* **Database:** MySQL
* **Frontend:** HTML5, CSS3, Bootstrap 5
* **Server:** Apache (LAMPP Stack)

## Features Implemented
* **Authentication:** Email-based login with BCrypt, PDO prepared statements, and login activity tracking (user_login_records).
* **Authorization:** Role-Based Access Control (Admin/User) with middleware protection on all dashboard modules.
* **Employee Management:** Bootstrap table with create, edit, and soft-delete workflows backed by dedicated process scripts.
* **Project & Task Management:** Dashboard analytics, project cards, and per-user task views ready for future CRUD enhancements.
* **UI Framework:** Shared header/footer components, responsive Bootstrap 5 layout, and role-aware navigation.

## Database Highlights
* Normalized MySQL schema with foreign keys and cascading rules (projects → tasks).
* Seed data includes admin role, base user, and RBAC scaffolding for future permissions.
* Added user_login_records table for audit trails and compliance reporting.

## Recent Updates
* Implemented user soft delete (status toggle) and edit modals that persist changes via PDO.
* Added user_create and user_update process scripts with validation and exception logging.
* Logging each successful login to user_login_records to track activity.