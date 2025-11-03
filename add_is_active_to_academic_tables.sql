-- Optional SQL to add is_active column to academic tables
-- Run this ONLY if you want to be able to activate/deactivate courses, years, sections, and departments

-- Add is_active column to courses table
ALTER TABLE `courses` 
ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `name`;

-- Add is_active column to years table
ALTER TABLE `years` 
ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `name`;

-- Add is_active column to sections table
ALTER TABLE `sections` 
ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `name`;

-- Add is_active column to departments table
ALTER TABLE `departments` 
ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `name`;

-- Set all existing records as active
UPDATE `courses` SET `is_active` = 1;
UPDATE `years` SET `is_active` = 1;
UPDATE `sections` SET `is_active` = 1;
UPDATE `departments` SET `is_active` = 1;

