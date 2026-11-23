# FitTrack - gym + fitness management (PHP 8.1+, MySQL, XAMPP)

## Setup
1. Copy the project to `C:\xampp\htdocs\Fitness` and start Apache + MySQL.
2. In phpMyAdmin create a database named `Fittrack2` and import `fittrack2.sql` (the only schema file; it also holds sample data).
3. Check `config/database.php` (host, database name, user, password).
4. Open `http://localhost/Fitness/`. Admin sign-in: `/admin/login.php`.
   To promote an account: `UPDATE users SET role='admin' WHERE email='you@example.com';`
5. Gmail notifications (optional): run `composer install`, then edit `config/mail.php`
   (Gmail address, Google **App Password**, `admin_email`, `enabled => true`). Check `email_logs` if mail does not arrive.
6. Payment details: `config/payment.php`. **Put your real payment QR image at `assets/images/payment-qr.png`**
   (jpg/jpeg/webp also work). Until you do, a clearly-labelled SAMPLE placeholder (`payment-qr.svg`, not a real QR) is shown.
7. Existing database? Nothing to import: missing columns (exercise difficulty/equipment, gym + coaching payment fields)
   are added automatically on first page load. `database/payment_schedule_migration.sql` does the same by hand.

## How a member moves through the system
Register -> Sign in -> **only Gym Membership is open** -> choose a package and pay by **Cash** (reception) or **QR** (upload screenshot) -> admin confirms ->
**all sections unlock** (until the membership end date; then they lock again until renewed).

| Member section | What it does | Needs | DB tables |
|---|---|---|---|
| Gym Membership (`membership.php`) | Pick a package, pay by Cash or QR (QR needs a payment screenshot), see status/history | login only | gym_membership_plans, gym_memberships |
| Dashboard (`dashboard.php`) | Workout totals, weight, active goal, quick links | active gym membership | workout_sessions, body_measurements, fitness_goals |
| Fitness Hub (`fitness_hub.php`) | Menu for the tools below | gym membership | - |
| - Workout Log (`workouts.php`) | Log sessions | gym membership | workout_sessions |
| - Workout Plans (`workout_plans.php`) | Your own named routines (pick one when logging) | gym membership | workout_plans |
| - Exercise Library (`exercises.php`) | Search exercises added by admin | gym membership | exercises |
| - Nutrition (`nutrition.php`) | Food/calorie log | gym membership | food_logs |
| - Water Tracker (`water.php`) | Daily water log | gym membership | water_logs |
| - Fitness Goals (`goals.php`) | Create goals, mark achieved/cancelled | gym membership | fitness_goals |
| - Body Progress (`body_measurements.php`) | Weight / body fat / waist history | gym membership | body_measurements |
| Free Plans (`plans.php`) | **Free** diet + workout plans for your level, trainer suggestions, plans assigned to you | gym membership | diet_plans, workout_templates, user_plan_assignments, admin_suggestions |
| Premium Subscription (`subscription.php`) | Request Premium with payment proof | gym membership | subscription_requests, users |
| - My Plans (`premium-plans.php`) | **Premium-only** diet + workout plans | active Premium | diet_plans, workout_templates, user_plan_assignments |
| - Demo Videos (`premium-videos.php`) | YouTube demo videos added by admin | active Premium | premium_videos |
| Personal Coach (`coaching.php`) | See each coach's weekly availability + open dates/times, request a session. **Payment (Cash/QR) appears only after the admin approves the session** | gym membership | coaches, coach_availability, coach_bookings |
| Notifications (`notifications.php`) | In-app messages (approvals, assignments) | gym membership | notifications |
| Profile (`profile.php`) | Personal details, fitness level | gym membership | users |

## Admin sections
| Admin page | Controls |
|---|---|
| Overview (`dashboard.php`) | Counts + shortcuts |
| Users / User detail | Member list; set fitness level, send suggestion, assign a diet/workout plan |
| Subscriptions (`requests.php`) | Approve/reject Premium requests (payment proof) |
| Diet Plans / Workout Plans | Create free or premium plans per fitness level; hide or delete |
| Videos (`videos.php`) | Add YouTube links for Premium members |
| Gym Fees (`gym-memberships.php`) | Create packages; confirm or reject gym fee requests (shows Cash/QR, reference and payment proof) |
| Exercises (`exercises.php`) | Exercise library shown to members |
| Coaches | Add coaches and set availability for any of the 7 days (add form + per-coach editor) |
| Bookings | Approve coaching bookings, then confirm/reject the member's Cash or QR payment |
| Notifications | New-request alerts for the admin |

## Emails (Gmail/SMTP)
- Admin is emailed on: new gym membership request, new Premium request.
- Member is emailed on: gym membership approved/rejected, Premium approved/rejected, plan assigned, suggestion sent, coaching booking update.

## Code map
- `includes/auth.php` - login/role checks, `requireGymMembership()`, `hasActivePremium()`
- `includes/header.php` / `footer.php` - shared layout, sidebar locks, flash messages
- `includes/init.php` - also holds `fittrackBrand()`: the single FitTrack logo (`assets/images/fittrack-logo.svg`) used on every page
- `includes/schema.php` - auto-adds columns an older database import is missing
- `includes/payments.php` - Cash/QR payment portal + proof upload (gym membership and coaching)
- `includes/coach_schedule.php` - 7-day coach availability editor, display and open-slot calculation
- `includes/mailer.php` - PHPMailer wrapper, `fittrackNotify()` (in-app + email), `fittrackEmailAdmins()`
- `includes/videos.php` - YouTube link parser, auto-creates `premium_videos`
