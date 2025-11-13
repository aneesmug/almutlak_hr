# Database Migration Instructions

## To apply the migration manually:

### Option 1: Using phpMyAdmin (Recommended)
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Select the `almutlak_db` database
3. Click on "SQL" tab
4. Copy and paste the contents of `add_note_attachment_and_type.sql`
5. Click "Go" to execute

### Option 2: Using MySQL Command Line
```bash
# Navigate to XAMPP MySQL bin directory
cd D:\xampp\mysql\bin

# Run the migration (enter password when prompted)
.\mysql.exe -u root -p almutlak_db < "D:\xampp\htdocs\almutlak\system\add_note_attachment_and_type.sql"
```

### Option 3: Direct SQL Execution
Open MySQL command line and run:
```sql
USE almutlak_db;

ALTER TABLE `emp_notice` 
ADD COLUMN `note_type` VARCHAR(100) DEFAULT NULL COMMENT 'Type of note: warning, sick_leave, appreciation, etc.' AFTER `note`,
ADD COLUMN `attachment` VARCHAR(255) DEFAULT NULL COMMENT 'File path for attached document' AFTER `note_type`;

ALTER TABLE `emp_notice` ADD INDEX `idx_note_type` (`note_type`);
```

## Verify Migration
After running the migration, verify the columns were added:
```sql
DESCRIBE emp_notice;
```

You should see:
- note_type (varchar 100)
- attachment (varchar 255)

## Next Steps
Once the database migration is complete:
1. Test adding a note without attachment
2. Test adding a note with attachment
3. Verify files are stored in `assets/emp_notes/` directory
