-- ================================================================================
-- Add Department Color Column
-- ================================================================================
-- Adds dept_clr to department table for storing department display color.
-- Safe to re-run.
-- ================================================================================

SET @column_exists := (
	SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE()
	  AND TABLE_NAME = 'department'
	  AND COLUMN_NAME = 'dept_clr'
);

SET @sql := IF(
	@column_exists = 0,
	"ALTER TABLE department ADD COLUMN dept_clr VARCHAR(20) NOT NULL DEFAULT 'custom' AFTER dep_nme_ar",
	"SELECT 'department.dept_clr already exists' AS info"
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Normalize any pre-existing empty values to the default color class.
UPDATE department
SET dept_clr = 'custom'
WHERE dept_clr IS NULL OR TRIM(dept_clr) = '';

-- Seed color class mapping table used by CSS classes.
INSERT INTO `dept_clr` (`id`, `dept_name`, `color`) VALUES
(2, '2', 'purple'),
(3, '5', 'primary'),
(4, '7', 'success'),
(5, '6', 'custom'),
(6, '9', 'purple'),
(7, '10', 'primary'),
(8, '8', 'success'),
(9, '11', 'custom'),
(10, '12', 'purple'),
(11, '13', 'primary'),
(12, '14', 'success'),
(13, '3', 'custom'),
(15, '15', 'purple'),
(16, '4', 'success'),
(17, '17', 'custom'),
(18, '16', 'success'),
(19, '18', 'purple'),
(20, '19', 'primary')
ON DUPLICATE KEY UPDATE
`dept_name` = VALUES(`dept_name`),
`color` = VALUES(`color`);

-- Apply mapped CSS color classes into department.dept_clr by department id.
UPDATE department d
INNER JOIN dept_clr dc ON d.id = CAST(dc.dept_name AS UNSIGNED)
SET d.dept_clr = dc.color;

-- Convert any legacy hex/unknown value to default CSS class.
UPDATE department
SET dept_clr = 'custom'
WHERE dept_clr IS NULL
	OR TRIM(dept_clr) = ''
	OR dept_clr NOT IN ('custom', 'purple', 'primary', 'success');
