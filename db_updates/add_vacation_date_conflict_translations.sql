-- Add new translation keys for vacation date conflict messages

INSERT INTO translations (`key`, lang, value) VALUES
('vacation_date_conflict', 'en', 'Vacation Date Conflict'),
('vacation_date_conflict', 'ar', 'تعارض في تاريخ الإجازة'),
('vacation_dates_overlap_with_existing_request', 'en', 'Your vacation dates overlap with an existing request'),
('vacation_dates_overlap_with_existing_request', 'ar', 'تتداخل تواريخ إجازتك مع طلب موجود'),
('existing_vacation', 'en', 'Existing vacation'),
('existing_vacation', 'ar', 'الإجازة الموجودة'),
('please_choose_different_dates', 'en', 'Please choose different dates that do not conflict'),
('please_choose_different_dates', 'ar', 'يرجى اختيار تواريخ مختلفة لا تتعارض')
ON DUPLICATE KEY UPDATE value=VALUES(value);
