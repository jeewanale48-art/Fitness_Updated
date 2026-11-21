FITTRACK - FINAL UPDATE PACKAGE (USER DASHBOARD + GYM MEMBERSHIP)

IMPORTANT: This update ZIP does not replace your existing database and does not
require importing the complete database SQL file over your current database.

1. BACKUP
   - Back up your current Fitness_new folder.
   - Export your current FitTrack2 database in phpMyAdmin.

2. PROJECT FILES
   - Extract the ZIP into C:\xampp\htdocs\Fitness_new (replace project files).
   - Keep the database you already use.
   - Check config/database.php for database name and credentials.

3. INSTALL THE NEW GYM MEMBERSHIP TABLES (ONE TIME ONLY)
   - Open phpMyAdmin and select your FitTrack2 database.
   - Select the SQL tab and run the file:
       database/gym_membership_migration.sql
   - This migration creates gym_membership_plans and gym_memberships only.
     It does not drop existing tables.

4. ADMIN
   - Sign in as admin.
   - Open Admin dashboard > Gym fees & membership payments, or:
       http://localhost/Fitness_new/admin/gym-memberships.php
   - Add packages (name, month duration, fee and description).
   - Review membership requests and mark payments paid or reject.

5. MEMBER
   - Members can browse packages at:
       http://localhost/Fitness_new/membership.php
   - Members request a package and pay at gym reception.
   - Admin confirmation sets membership active and starts the membership period.

6. USER NAVIGATION
   - Member pages use the shared green left-side navigation.
   - Fitness Hub groups workouts, workout plans, exercises, nutrition, water,
     goals and body measurements.
   - Gym Membership is separate from the existing Premium Subscription page.
   - Personal Coach remains an existing booking page; premium subscription
     logic/content remains based on the existing application, not replaced.

7. TEST
   - Log in as a member and open Dashboard, Fitness Hub, Membership, Premium,
     Personal Coach, Notifications and Profile.
   - Log in as admin and create a membership plan; request it as a member;
     then confirm payment as admin.
   - PHP syntax was checked on the included PHP source files.

NOTE
   - This is a local/offline gym-reception payment workflow. No online payment
     gateway has been connected.
   - The membership migration must be run once. Do not import the full database
     SQL file on top of your existing database.
   - Existing premium request/coach features are retained rather than rewritten.
