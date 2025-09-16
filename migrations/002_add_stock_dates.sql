-- Add stock update tracking to existing items table
-- Run this if you already have an existing database

USE BENTA;

-- Add the last_stock_update column if it doesn't exist
ALTER TABLE items ADD COLUMN IF NOT EXISTS last_stock_update TIMESTAMP NULL DEFAULT NULL;

-- Update existing items to set last_stock_update to their updated_at timestamp
UPDATE items SET last_stock_update = updated_at WHERE last_stock_update IS NULL AND updated_at IS NOT NULL;

-- For items that have never been updated, set to created_at
UPDATE items SET last_stock_update = created_at WHERE last_stock_update IS NULL;
