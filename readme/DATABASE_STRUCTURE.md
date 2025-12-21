# Database Structure - Delivery System

## New Table: `general_request_deliveries`

Tracks which employee received the delivered items and when.

```sql
CREATE TABLE `general_request_deliveries` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique delivery record ID',
    `request_inv_no` VARCHAR(50) NOT NULL COMMENT 'Reference to general_requests.inv_no',
    `received_by` VARCHAR(20) NOT NULL COMMENT 'Employee ID who received items',
    `delivery_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When items were delivered',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation time',
    
    FOREIGN KEY (`request_inv_no`) REFERENCES `general_requests`(`inv_no`) ON DELETE CASCADE,
    FOREIGN KEY (`received_by`) REFERENCES `employees`(`emp_id`) ON DELETE RESTRICT,
    
    INDEX `idx_request_inv_no` (`request_inv_no`),
    INDEX `idx_received_by` (`received_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Sample Data
```
| id | request_inv_no | received_by | delivery_date | created_at |
|----|----|----|----|----| 
| 1 | GR10220241245 | EMP001 | 2024-12-17 10:30:00 | 2024-12-17 10:30:00 |
| 2 | GR20220241246 | EMP003 | 2024-12-17 11:15:00 | 2024-12-17 11:15:00 |
```

## Modified Table: `general_request_items`

Added columns to track individual item delivery status.

### New Columns

```sql
ALTER TABLE `general_request_items` ADD (
    `delivery_status` VARCHAR(20) NULL DEFAULT 'pending' COMMENT 'pending|delivered|canceled',
    `delivery_id` INT NULL COMMENT 'Reference to general_request_deliveries.id'
);

-- Add foreign key
ALTER TABLE `general_request_items` 
ADD CONSTRAINT fk_item_delivery 
FOREIGN KEY (`delivery_id`) REFERENCES `general_request_deliveries`(`id`) ON DELETE SET NULL;
```

### Updated Structure
```
Original Columns:
- id (INT) PRIMARY KEY
- request_inv_no (VARCHAR)
- item_name (VARCHAR)
- item_type (VARCHAR)
- quantity (INT)
- specifications (TEXT)
- created_at (TIMESTAMP)

NEW Columns:
+ delivery_status (VARCHAR) ← 'pending', 'delivered', 'canceled'
+ delivery_id (INT) ← References general_request_deliveries.id
```

### Sample Data
```
| id | request_inv_no | item_name | quantity | delivery_status | delivery_id |
|----|----|----|----|----|----| 
| 1 | GR10220241245 | Laptop | 5 | delivered | 1 |
| 2 | GR10220241245 | Monitor | 5 | pending | 1 |
| 3 | GR10220241245 | Keyboard | 5 | canceled | 1 |
| 4 | GR20220241246 | Printer | 2 | delivered | 2 |
```

## Modified Table: `general_requests`

Added column to track completion time.

### New Column

```sql
ALTER TABLE `general_requests` ADD (
    `completed_at` DATETIME NULL COMMENT 'When all items marked as delivered'
);
```

### Updated Structure
```
Original Columns:
- inv_no (VARCHAR) PRIMARY KEY
- request_title (VARCHAR)
- department_to (VARCHAR)
- request_category (VARCHAR)
- priority (VARCHAR)
- description (TEXT)
- emp_id (VARCHAR)
- emp_name (VARCHAR)
- user_dept (INT)
- current_status (VARCHAR) - draft|pending_approval|approved|rejected|completed
- current_approval_level (INT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

NEW Column:
+ completed_at (DATETIME) ← When delivery marked complete
```

### Sample Data
```
| inv_no | request_title | current_status | completed_at |
|----|----|----|----| 
| GR10220241245 | Office Equipment | completed | 2024-12-17 10:30:00 |
| GR20220241246 | IT Supplies | approved | NULL |
```

## Relationships Diagram

```
employees (emp_id)
    ↓
    ├─→ general_request_deliveries (received_by) ← [NEW]
    │       ↓
    │       └─→ general_request_items (delivery_id) ← [MODIFIED]
    │
    └─→ admin_login
            ↓
            └─→ general_requests
                    ↓
                    └─→ general_request_items (request_inv_no)
                    └─→ general_request_attachments
                    └─→ request_approvers
```

## Query Examples

### Get Delivery Info for a Request
```sql
SELECT 
    d.id,
    d.request_inv_no,
    d.received_by,
    e.name as employee_name,
    d.delivery_date
FROM general_request_deliveries d
LEFT JOIN employees e ON e.emp_id = d.received_by
WHERE d.request_inv_no = 'GR10220241245';
```

### Get Items with Delivery Status
```sql
SELECT 
    i.id,
    i.item_name,
    i.quantity,
    i.delivery_status,
    d.received_by,
    e.name as received_employee
FROM general_request_items i
LEFT JOIN general_request_deliveries d ON d.id = i.delivery_id
LEFT JOIN employees e ON e.emp_id = d.received_by
WHERE i.request_inv_no = 'GR10220241245'
ORDER BY i.id;
```

### Get Completed Requests with Delivery Info
```sql
SELECT 
    gr.inv_no,
    gr.request_title,
    gr.current_status,
    gr.completed_at,
    grd.received_by,
    e.name as received_employee,
    COUNT(DISTINCT gri.id) as total_items,
    SUM(CASE WHEN gri.delivery_status = 'delivered' THEN 1 ELSE 0 END) as delivered_items,
    SUM(CASE WHEN gri.delivery_status = 'pending' THEN 1 ELSE 0 END) as pending_items,
    SUM(CASE WHEN gri.delivery_status = 'canceled' THEN 1 ELSE 0 END) as canceled_items
FROM general_requests gr
LEFT JOIN general_request_deliveries grd ON grd.request_inv_no = gr.inv_no
LEFT JOIN employees e ON e.emp_id = grd.received_by
LEFT JOIN general_request_items gri ON gri.request_inv_no = gr.inv_no
WHERE gr.current_status = 'completed'
GROUP BY gr.inv_no
ORDER BY gr.completed_at DESC;
```

### Get Delivery Summary
```sql
SELECT 
    d.received_by,
    e.name,
    COUNT(DISTINCT d.id) as delivery_count,
    COUNT(DISTINCT i.id) as total_items_received,
    SUM(CASE WHEN i.delivery_status = 'delivered' THEN i.quantity ELSE 0 END) as total_qty_delivered
FROM general_request_deliveries d
LEFT JOIN employees e ON e.emp_id = d.received_by
LEFT JOIN general_request_items i ON i.delivery_id = d.id AND i.delivery_status = 'delivered'
GROUP BY d.received_by, e.name
ORDER BY delivery_count DESC;
```

## Indexes for Performance

```sql
-- Existing indexes are maintained

-- New indexes for delivery queries
CREATE INDEX idx_delivery_status ON general_request_items (delivery_status);
CREATE INDEX idx_delivery_id ON general_request_items (delivery_id);
CREATE INDEX idx_completed_at ON general_requests (completed_at);
CREATE INDEX idx_request_inv_no_delivery ON general_request_deliveries (request_inv_no);
```

## Migration SQL

Complete migration script to run:

```sql
-- Add columns to general_request_items
ALTER TABLE `general_request_items` 
ADD COLUMN `delivery_status` VARCHAR(20) NULL DEFAULT 'pending' COMMENT 'Status: pending, delivered, canceled',
ADD COLUMN `delivery_id` INT NULL;

-- Add column to general_requests
ALTER TABLE `general_requests`
ADD COLUMN `completed_at` DATETIME NULL COMMENT 'When all items were delivered';

-- Create general_request_deliveries table
CREATE TABLE IF NOT EXISTS `general_request_deliveries` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `request_inv_no` VARCHAR(50) NOT NULL,
    `received_by` VARCHAR(20) NOT NULL,
    `delivery_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`request_inv_no`) REFERENCES `general_requests`(`inv_no`) ON DELETE CASCADE,
    FOREIGN KEY (`received_by`) REFERENCES `employees`(`emp_id`) ON DELETE RESTRICT,
    INDEX `idx_request_inv_no` (`request_inv_no`),
    INDEX `idx_received_by` (`received_by`)
);

-- Create indexes
CREATE INDEX idx_delivery_status ON general_request_items (delivery_status);
CREATE INDEX idx_delivery_id ON general_request_items (delivery_id);
CREATE INDEX idx_completed_at ON general_requests (completed_at);
```

## Backward Compatibility

✅ All changes are backward compatible:
- New columns have default values
- No existing data is modified
- Existing queries continue to work
- New tables don't conflict with existing schema

## Data Types Reference

| Type | Size | Purpose |
|------|------|---------|
| INT | 4 bytes | IDs |
| VARCHAR(20) | 20 bytes | Employee IDs |
| VARCHAR(50) | 50 bytes | Invoice numbers |
| DATETIME | 8 bytes | Timestamps with time |
| TIMESTAMP | 4 bytes | Auto-timestamp |
