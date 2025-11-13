-- SQL script to add Reaper and Crawler columns to OGameX database
-- Run this script on your MySQL database (via phpMyAdmin, command line, etc.)

-- Step 1: Make sure you're using the correct database
-- USE your_database_name;  -- Uncomment and change to your database name

-- Step 2: Add columns to planets table
ALTER TABLE planets ADD COLUMN IF NOT EXISTS reaper INT NOT NULL DEFAULT 0 AFTER deathstar;
ALTER TABLE planets ADD COLUMN IF NOT EXISTS crawler INT NOT NULL DEFAULT 0 AFTER solar_satellite;

-- Step 3: Add columns to fleet_missions table
ALTER TABLE fleet_missions ADD COLUMN IF NOT EXISTS reaper INT NOT NULL DEFAULT 0 AFTER deathstar;
ALTER TABLE fleet_missions ADD COLUMN IF NOT EXISTS crawler INT NOT NULL DEFAULT 0 AFTER espionage_probe;

-- Step 4: Verify columns were added (optional check)
-- SELECT 'Planets table structure:' AS info;
-- SHOW COLUMNS FROM planets LIKE '%reaper%';
-- SHOW COLUMNS FROM planets LIKE '%crawler%';
--
-- SELECT 'Fleet missions table structure:' AS info;
-- SHOW COLUMNS FROM fleet_missions LIKE '%reaper%';
-- SHOW COLUMNS FROM fleet_missions LIKE '%crawler%';

-- Done! The crawler and reaper columns are now ready to use.
