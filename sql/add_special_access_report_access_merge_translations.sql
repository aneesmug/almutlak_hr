-- Translation keys introduced by merging Report Access into the Special Access tab
-- (app_settings.php: buildReportAccessBlockHtml/wireReportAccessBlock, grouped-by-category
-- Special Access grid, and the removal of the standalone Report Permissions tab).

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('check_reports_user_can_view', 'en', 'Check the reports this user can view'),
('check_reports_user_can_view', 'ar', 'حدد التقارير التي يمكن لهذا المستخدم عرضها'),
('report_access', 'en', 'Report Access'),
('report_access', 'ar', 'صلاحية التقارير'),
('report_access_not_applicable_for_employees', 'en', 'Report access doesn\'t apply to plain employee accounts.'),
('report_access_not_applicable_for_employees', 'ar', 'صلاحية التقارير لا تنطبق على حسابات الموظفين العاديين.'),
('all_default', 'en', 'All (default)'),
('all_default', 'ar', 'الكل (افتراضي)'),
('currently_granted', 'en', 'Currently granted'),
('currently_granted', 'ar', 'الصلاحيات الممنوحة حاليًا'),
('no_reports', 'en', 'No reports'),
('no_reports', 'ar', 'لا توجد تقارير'),
('report_access_is_also_managed_here', 'en', 'Report access (which reports a user can view) is also managed here, per user.'),
('report_access_is_also_managed_here', 'ar', 'تتم إدارة صلاحية التقارير (أي التقارير التي يمكن للمستخدم عرضها) من هنا أيضًا، لكل مستخدم.'),
('this_will_remove_special_access_and_report_access_for_this_user', 'en', 'This will remove all special access AND report access customizations for this user.'),
('this_will_remove_special_access_and_report_access_for_this_user', 'ar', 'سيؤدي هذا إلى إزالة جميع صلاحيات الوصول الخاصة وصلاحيات التقارير المخصصة لهذا المستخدم.'),
('employee_rejoin_report', 'en', 'Employee Rejoin Report'),
('employee_rejoin_report', 'ar', 'تقرير عودة الموظف')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
