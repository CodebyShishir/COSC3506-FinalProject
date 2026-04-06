-- SplitEase Database Schema
-- To use this: imported into phpMyAdmin via XAMPP or run from MySQL console.

CREATE DATABASE IF NOT EXISTS `splitease`;
USE `splitease`;

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Groups Table
CREATE TABLE IF NOT EXISTS `groups` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Group Members (Junction Table)
CREATE TABLE IF NOT EXISTS `group_members` (
  `group_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`group_id`, `user_id`),
  FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Expenses Table
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `group_id` INT NOT NULL,
  `payer_id` INT NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`payer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Expense Splits Table (Who owes what for a specific expense)
CREATE TABLE IF NOT EXISTS `expense_splits` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `expense_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `amount_owed` DECIMAL(10, 2) NOT NULL,
  `is_settled` TINYINT(1) DEFAULT 0,
  FOREIGN KEY (`expense_id`) REFERENCES `expenses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Sample Seed Data
-- Note: password_hash here is a placeholder. Real passwords will be bcrypt hashed via PHP.
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`) VALUES 
(1, 'John Doe', 'john@test.com', '$2y$10$wT2eY38XUo0K/8r3u.v.rOsFzWbq4F9r1u1U9tB/5yIrtgKk9zDxe'), -- Pass: 'password'
(2, 'Jane Smith', 'jane@test.com', '$2y$10$wT2eY38XUo0K/8r3u.v.rOsFzWbq4F9r1u1U9tB/5yIrtgKk9zDxe'),
(3, 'Mark Johnson', 'mark@test.com', '$2y$10$wT2eY38XUo0K/8r3u.v.rOsFzWbq4F9r1u1U9tB/5yIrtgKk9zDxe');

INSERT INTO `groups` (`id`, `name`, `created_by`) VALUES 
(1, 'Trip to Montreal', 1),
(2, 'Apartment Utilities', 2);

INSERT INTO `group_members` (`group_id`, `user_id`) VALUES 
(1, 1), (1, 2), (1, 3), -- John, Jane, Mark go to Montreal
(2, 1), (2, 2);         -- John and Jane share an apartment

-- John paid $150 for Montreal Gas. Split 3 ways ($50 each)
INSERT INTO `expenses` (`id`, `group_id`, `payer_id`, `amount`, `description`) VALUES 
(1, 1, 1, 150.00, 'Gas for road trip');

-- Jane and Mark owe $50 to John for the road trip (John paid his own share, so we only track others' debts)
INSERT INTO `expense_splits` (`expense_id`, `user_id`, `amount_owed`, `is_settled`) VALUES 
(1, 2, 50.00, 0), -- Jane owes $50
(1, 3, 50.00, 0); -- Mark owes $50
