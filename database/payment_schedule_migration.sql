-- FitTrack: exercise fields + Cash/QR payments for gym memberships and coaching.
-- OPTIONAL: the application adds these columns automatically the first time any page loads.
-- Run this in phpMyAdmin (database Fittrack2) only if you prefer to apply it by hand.
-- Uses "ADD COLUMN IF NOT EXISTS" (MariaDB / XAMPP). Safe to run more than once.

ALTER TABLE `exercises`
  ADD COLUMN IF NOT EXISTS `difficulty` enum('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
  ADD COLUMN IF NOT EXISTS `equipment` varchar(120) DEFAULT NULL;

ALTER TABLE `gym_memberships`
  ADD COLUMN IF NOT EXISTS `payment_method` enum('cash','qr') NOT NULL DEFAULT 'cash',
  ADD COLUMN IF NOT EXISTS `payment_reference` varchar(120) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `payment_proof` varchar(255) DEFAULT NULL;

ALTER TABLE `coach_bookings`
  ADD COLUMN IF NOT EXISTS `payment_method` enum('cash','qr') DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `payment_status` enum('unpaid','pending','paid','rejected') NOT NULL DEFAULT 'unpaid',
  ADD COLUMN IF NOT EXISTS `payment_amount` decimal(10,2) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `payment_reference` varchar(120) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `payment_proof` varchar(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `payment_note` text DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `payment_submitted_at` datetime DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `payment_reviewed_at` datetime DEFAULT NULL;
