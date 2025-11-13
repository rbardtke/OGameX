-- SQL script to add Reaper and Crawler columns
-- Run this script on your OGameX database if migrations won't run

-- Add reaper column to planets table
ALTER TABLE planets ADD COLUMN reaper INT DEFAULT 0 AFTER deathstar;

-- Add reaper column to fleet_missions table
ALTER TABLE fleet_missions ADD COLUMN reaper INT DEFAULT 0 AFTER deathstar;

-- Add crawler column to planets table
ALTER TABLE planets ADD COLUMN crawler INT DEFAULT 0 AFTER solar_satellite;

-- Add crawler column to fleet_missions table (though crawlers can't be sent on missions)
ALTER TABLE fleet_missions ADD COLUMN crawler INT DEFAULT 0 AFTER espionage_probe;

-- Verify the columns were added
SELECT 'Columns added successfully!' AS status;
