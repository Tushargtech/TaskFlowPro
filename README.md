# TaskFlow Pro - Mini ERP & Task Management System

## Tech Stack
* **Language:** PHP 8.x (Plain/Procedural & OOP)
* **Database:** MySQL
* **Frontend:** HTML5, CSS3, Bootstrap 5
* **Server:** Apache (LAMPP Stack)

## Features Implemented
* **Auth:** Role-Based Access Control (Admin/User).
* **Modules:** Employee, Project, and Task Management.
* **Security:** Password hashing (Bcrypt) and PDO Prepared Statements.
* **API:** REST-style endpoints for all CRUD operations.

## Database Design (Day 2 Decision)
I chose a relational structure with 7 tables to handle complex task assignments. 
Key decisions:
* Used `ON DELETE CASCADE` for tasks to ensure data integrity.
* Implemented `user_role_mapping` for future-proof permission scaling.