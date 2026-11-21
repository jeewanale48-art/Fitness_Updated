# FitTrack repair package notes

This update repairs several PHP queries and form writes to align with the `FitTrack_2_Full_Database.sql` schema:
- workout sessions use `session_date` and store the session title at the beginning of notes (the schema does not have a title column)
- fitness goals use the schema's `starting_value`, `deadline`, and goal-type enum values
- body measurements use `recorded_at` and only the supported measurement fields (weight, body fat, waist)
- water records use `log_date`
- dashboard queries use matching schema field names
- user dashboard sidebar navigation is reduced and a Fitness Hub landing page groups workouts, plans, exercises, nutrition, water, goals and body progress
- duplicate Exercises link in the shared user navigation was removed
- green dashboard/hub styling was added

## Database reminder
The project config uses the `FitTrack2` database. Import/maintain the database using `FitTrack_2_Full_Database.sql` and do not mix it with the older `database/schema.sql` without reviewing the differences. Back up the database before importing or running any migrations.

## Not yet implemented in this repair pass
The redesigned flexible gym-membership package pricing/payment workflow and the expanded premium coach subscription workflow are not yet fully implemented. This repair pass addresses the reported SQL errors and starts the user-side dashboard/navigation consolidation; those business workflows need a separate implementation pass against the existing admin pages and database.
