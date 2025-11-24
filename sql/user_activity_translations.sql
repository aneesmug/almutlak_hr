-- User Activity Translations
-- Insert these into your translations table

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
-- Page Title & Headers
('user_activity_log', 'en', 'User Activity Log'),
('user_activity_log', 'ar', 'سجل نشاط المستخدم'),
('user_activity_description', 'en', 'Track user login sessions, location, device information, and browsing details.'),
('user_activity_description', 'ar', 'تتبع جلسات تسجيل دخول المستخدم والموقع ومعلومات الجهاز وتفاصيل التصفح.'),

-- Statistics Cards
('active_sessions', 'en', 'Active Sessions'),
('active_sessions', 'ar', 'الجلسات النشطة'),
('today_logins', 'en', 'Today\'s Logins'),
('today_logins', 'ar', 'تسجيلات اليوم'),
('unique_locations', 'en', 'Unique Locations'),
('unique_locations', 'ar', 'المواقع الفريدة'),
('device_types', 'en', 'Device Types'),
('device_types', 'ar', 'أنواع الأجهزة'),

-- Table Headers
('login_time', 'en', 'Login Time'),
('login_time', 'ar', 'وقت تسجيل الدخول'),
('logout_time', 'en', 'Logout Time'),
('logout_time', 'ar', 'وقت تسجيل الخروج'),
('duration', 'en', 'Duration'),
('duration', 'ar', 'المدة'),
('ip_address', 'en', 'IP Address'),
('ip_address', 'ar', 'عنوان IP'),
('location', 'en', 'Location'),
('location', 'ar', 'الموقع'),
('device', 'en', 'Device'),
('device', 'ar', 'الجهاز'),
('browser', 'en', 'Browser'),
('browser', 'ar', 'المتصفح'),
('os', 'en', 'OS'),
('os', 'ar', 'نظام التشغيل'),
('screen', 'en', 'Screen'),
('screen', 'ar', 'الشاشة'),

-- Status Values
('active', 'en', 'Active'),
('active', 'ar', 'نشط'),
('logged_out', 'en', 'Logged Out'),
('logged_out', 'ar', 'تم تسجيل الخروج'),
('timeout', 'en', 'Timeout'),
('timeout', 'ar', 'انتهت المهلة'),

-- Device Types
('desktop', 'en', 'Desktop'),
('desktop', 'ar', 'سطح المكتب'),
('mobile', 'en', 'Mobile'),
('mobile', 'ar', 'جوال'),
('tablet', 'en', 'Tablet'),
('tablet', 'ar', 'لوحي'),

-- Modal Details
('activity_details', 'en', 'Activity Details'),
('activity_details', 'ar', 'تفاصيل النشاط'),
('employee_name', 'en', 'Employee Name'),
('employee_name', 'ar', 'اسم الموظف'),
('session_duration', 'en', 'Session Duration'),
('session_duration', 'ar', 'مدة الجلسة'),
('region_city', 'en', 'Region/City'),
('region_city', 'ar', 'المنطقة/المدينة'),
('isp', 'en', 'ISP'),
('isp', 'ar', 'مزود الخدمة'),
('operating_system', 'en', 'Operating System'),
('operating_system', 'ar', 'نظام التشغيل'),
('screen_resolution', 'en', 'Screen Resolution'),
('screen_resolution', 'ar', 'دقة الشاشة'),
('user_agent', 'en', 'User Agent'),
('user_agent', 'ar', 'وكيل المستخدم'),
('still_active', 'en', 'Still Active'),
('still_active', 'ar', 'لا يزال نشطًا'),

-- Time Units
('hours', 'en', 'hours'),
('hours', 'ar', 'ساعات'),
('minutes', 'en', 'minutes'),
('minutes', 'ar', 'دقائق'),
('hrs', 'en', 'hrs'),
('hrs', 'ar', 'س'),

-- Filters
('all_users', 'en', 'All Users'),
('all_users', 'ar', 'جميع المستخدمين'),
('all_status', 'en', 'All Status'),
('all_status', 'ar', 'جميع الحالات'),
('all_devices', 'en', 'All Devices'),
('all_devices', 'ar', 'جميع الأجهزة'),
('all_locations', 'en', 'All Locations'),
('all_locations', 'ar', 'جميع المواقع'),

-- Messages
('no_data_available', 'en', 'No data available'),
('no_data_available', 'ar', 'لا توجد بيانات متاحة'),
('loading', 'en', 'Loading...'),
('loading', 'ar', 'جاري التحميل...'),
('error_loading_data', 'en', 'Error loading data'),
('error_loading_data', 'ar', 'خطأ في تحميل البيانات'),
('unknown', 'en', 'Unknown'),
('unknown', 'ar', 'غير معروف'),
('not_available', 'en', 'N/A'),
('not_available', 'ar', 'غير متوفر');
