-- Adds a per-line description/comment column so manually added allowance
-- entries (e.g. "Taxi", "Parking") can carry a note explaining what they are.
ALTER TABLE emp_business_trip_allowance_items
    ADD COLUMN description VARCHAR(255) NULL AFTER allowance_type;
