-- Optional text for the ingredient Add/Edit form.
ALTER TABLE ingredients
    ADD COLUMN IF NOT EXISTS common_in VARCHAR(255) DEFAULT NULL AFTER description;
