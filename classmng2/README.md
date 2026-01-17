# ClassFlow — Student Management System

A minimal but functional Student Management System (PHP + MySQL + Bootstrap + JS).

Features:
- Admin login to create faculty accounts, and backup CSVs of data
- Faculty dashboard: manage subjects, students, attendance, activities, grades
- Student tiles UI with modal showing profile, attendance, activities, grades
- Archive/unarchive students, print student records, multi-student add
- Responsive UI, animated modals/alerts

Setup
1. Create a MySQL database (e.g. `classflow`) and run `create_db.sql`.
2. Edit `config.php` to set DB connection (DB_HOST, DB_NAME, DB_USER, DB_PASS).
3. Place files on a PHP-enabled server and open `index.php`.
4. Default admin credentials:
   - Username: `admin`
   - Password: `password123`

Notes
- For demo purposes, images are stored in `uploads/` and default avatar is provided.
- Backup creates CSV files for students, attendance, activities and grades.