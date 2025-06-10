# Student Assessment System

A comprehensive system for managing student assessments and faculty ratings.

## Features

### Admin Features
- Create and manage surveys
- Student management
- Semester management
- Generate assessment results
  - Rating summary
  - Questionnaire management

### Student Features
- Student registration
- COR (Certificate of Registration) submission
- Faculty rating system

## Installation

1. Clone this repository to your XAMPP htdocs folder
2. Import the database file `database/student_assessment.sql`
3. Configure your database credentials in `config/database.php`
4. Access the system through your web browser: `http://localhost/Student-Assessment-System`

## Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP/Apache server

## Security Features
- Password hashing
- SQL injection prevention
- XSS protection
- CSRF protection
- Session management 