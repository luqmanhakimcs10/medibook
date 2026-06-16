# Medibook – Online Appointment System
## 💻 How to Run the Project Locally

Follow these steps to run the project on your system:

1. Install XAMPP (or any PHP server)
2. Start Apache and MySQL

3. Clone the repository:
   git clone https://github.com/luqmanhakimcs10/medibook.git

4. Move the project to:
   C:\xampp\htdocs\

5. Import the database:
   - Open phpMyAdmin
   - Create a database (e.g., medibook)
   - Import the provided medibook.sql file

6. Configure database:
   - Open config.php (or db.php)
   - Set:
     host = localhost
     user = root
     password = ""
     database = medibook

7. Run in browser:
   http://localhost/medibook
