# Automated Examination Seat Allocation System

A web-based mini project to automate examination seat allocation for
colleges — built with **PHP, MySQL, HTML/CSS/JS**, designed to run on
**XAMPP**, exactly matching the configuration in the project proposal.

## Features

- Staff login (secure password hashing)
- Manage Departments, Students, Examination Halls, and Exam Schedule (CRUD)
- Bulk student import (paste register no. + name, one per line)
- **Automatic seat allocation algorithm**: mixes students from different
  departments so that, wherever possible, no two students from the same
  department sit directly next to each other (left/right) or directly in
  front of/behind each other — this is checked and repaired automatically.
- Visual, colour-coded seating chart per hall
- Printable **room-wise** report (seat list per hall)
- **Student-wise** lookup — search any student by register number or name to
  instantly find their hall and seat
- Dashboard with live counts and recent activity

## Requirements

- XAMPP (Apache + MySQL + PHP 8+) — https://www.apachefriends.org
- A modern browser (Chrome / Edge)

## Setup Instructions (XAMPP)

1. **Copy the project folder**
   Copy the entire `exam-seat-allocation` folder into your XAMPP
   `htdocs` directory, e.g.:
   `C:\xampp\htdocs\exam-seat-allocation`

2. **Start Apache and MySQL**
   Open the XAMPP Control Panel and click **Start** next to Apache and MySQL.

3. **Create the database**
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Click **Import**
   - Choose the file `sql/schema.sql` from this project
   - Click **Go**

   This creates the `exam_seat_db` database with all required tables and
   loads some sample departments, students, halls and a demo exam
   (feel free to delete the sample data from inside the app once you're
   comfortable with it).

4. **Check the database connection settings**
   Open `includes/db.php`. The defaults match a stock XAMPP install:
   ```php
   $DB_HOST = 'localhost';
   $DB_NAME = 'exam_seat_db';
   $DB_USER = 'root';
   $DB_PASS = '';
   ```
   Change these only if your MySQL setup is different (e.g. you set a
   root password).

5. **Open the app**
   Visit: http://localhost/exam-seat-allocation/

6. **Log in**
   - Username: `admin`
   - Password: `admin123`

   You can change this later by editing the `staff` table directly in
   phpMyAdmin (generate a new hash with PHP's `password_hash()`).

## How to Use

1. **Departments** — add your college departments (e.g. CSE, ECE, MECH).
2. **Students** — add students one at a time, or use **Bulk Import** to
   paste a whole class list at once (`register_no, name` per line).
3. **Halls** — add your examination halls with their row × column layout
   (this determines seating capacity).
4. **Exams** — schedule exams. Exams on the **same date and session
   (Forenoon/Afternoon)** are treated as one seating session — their
   students get mixed together across your chosen halls.
5. **Allocate Seats** — pick an exam session, tick the halls to use, and
   click **Generate Seating Arrangement**. The system will:
   - Interleave students from different departments
   - Check every seat against its left and top neighbour
   - Swap students around to avoid same-department neighbours wherever
     the numbers allow it
   - Save the result and take you to the seating chart
6. **View / Reports** — browse the colour-coded seating chart per hall,
   print a room-wise seat list, or look up a specific student's seat.

## Project Structure

```
exam-seat-allocation/
├── index.php              Login page
├── login.php / logout.php Auth handlers
├── dashboard.php          Overview / stats
├── departments.php        Department CRUD
├── students.php           Student CRUD + bulk import
├── halls.php              Hall CRUD
├── exams.php              Exam schedule CRUD
├── allocate.php           Seat allocation trigger + algorithm hookup
├── view_allocation.php    Visual seating chart
├── reports/
│   ├── room_wise.php      Printable room-wise report
│   └── student_wise.php   Student seat lookup
├── includes/
│   ├── db.php             Database connection
│   ├── auth.php           Session/auth helpers
│   ├── functions.php      Allocation algorithm + helpers
│   ├── header.php          Shared navbar/layout
│   └── footer.php
├── assets/
│   ├── css/style.css
│   └── js/script.js
└── sql/schema.sql         Full database schema + sample data
```

## Notes for your project report

- **Frontend**: HTML5, CSS3 (custom), Bootstrap 5, vanilla JavaScript
- **Backend**: PHP 8 (PDO for all database access — protects against SQL
  injection via prepared statements)
- **Database**: MySQL, normalized across `departments`, `students`,
  `halls`, `exams`, and `exam_allocations` tables
- **Algorithm**: round-robin department interleaving followed by a
  neighbour-conflict repair pass (an approach related to task-scheduling
  / rearrange-string algorithms), run in `includes/functions.php`
  (`build_interleaved_sequence()` and `allocate_seats()`)
