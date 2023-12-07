<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        Permission::truncate();

        $data = [
            ['module' => 'company', 'title' => 'Company access', 'name' => 'company_access'],
            ['module' => 'company', 'title' => 'Company view', 'name' => 'company_view'],
            ['module' => 'company', 'title' => 'Company create', 'name' => 'company_create'],
            ['module' => 'company', 'title' => 'Company edit', 'name' => 'company_edit'],
            ['module' => 'company', 'title' => 'Company delete', 'name' => 'company_delete'],

            ['module' => 'dashboard', 'title' => 'dashboard access', 'name' => 'dashboard_access'],
            ['module' => 'dashboard', 'title' => 'dashboard view', 'name' => 'dashboard_view'],
            ['module' => 'dashboard', 'title' => 'dashboard create', 'name' => 'dashboard_create'],
            ['module' => 'dashboard', 'title' => 'dashboard edit', 'name' => 'dashboard_edit'],
            ['module' => 'dashboard', 'title' => 'dashboard delete', 'name' => 'dashboard_delete'],

            ['module' => 'device', 'title' => 'Device access', 'name' => 'device_access'],
            ['module' => 'device', 'title' => 'Device view', 'name' => 'device_view'],
            ['module' => 'device', 'title' => 'Device create', 'name' => 'device_create'],
            ['module' => 'device', 'title' => 'Device edit', 'name' => 'device_edit'],
            ['module' => 'device', 'title' => 'Device delete', 'name' => 'device_delete'],

            ['module' => 'shift', 'title' => 'Shift access', 'name' => 'shift_access'],
            ['module' => 'shift', 'title' => 'Shift view', 'name' => 'shift_view'],
            ['module' => 'shift', 'title' => 'Shift create', 'name' => 'shift_create'],
            ['module' => 'shift', 'title' => 'Shift edit', 'name' => 'shift_edit'],
            ['module' => 'shift', 'title' => 'Shift delete', 'name' => 'shift_delete'],

            ['module' => 'schedule', 'title' => 'Schedule access', 'name' => 'schedule_access'],
            ['module' => 'schedule', 'title' => 'Schedule view', 'name' => 'schedule_view'],
            ['module' => 'schedule', 'title' => 'Schedule create', 'name' => 'schedule_create'],
            ['module' => 'schedule', 'title' => 'Schedule edit', 'name' => 'schedule_edit'],
            ['module' => 'schedule', 'title' => 'Schedule delete', 'name' => 'schedule_delete'],

            ['module' => 'payroll', 'title' => 'Payroll access', 'name' => 'payroll_access'],
            ['module' => 'payroll', 'title' => 'Payroll view', 'name' => 'payroll_view'],
            ['module' => 'payroll', 'title' => 'Payroll create', 'name' => 'payroll_create'],
            ['module' => 'payroll', 'title' => 'Payroll edit', 'name' => 'payroll_edit'],
            ['module' => 'payroll', 'title' => 'Payroll delete', 'name' => 'payroll_delete'],

            ['module' => 'payroll settings', 'title' => 'Payroll settings access', 'name' => 'payroll_settings_access'],
            ['module' => 'payroll settings', 'title' => 'Payroll settings view', 'name' => 'payroll_settings_view'],
            ['module' => 'payroll settings', 'title' => 'Payroll settings create', 'name' => 'payroll_settings_create'],
            ['module' => 'payroll settings', 'title' => 'Payroll settings edit', 'name' => 'payroll_settings_edit'],
            ['module' => 'payroll settings', 'title' => 'Payroll settings delete', 'name' => 'payroll_settings_delete'],

            ['module' => 'setting', 'title' => 'Setting access', 'name' => 'setting_access'],
            ['module' => 'setting', 'title' => 'Setting view', 'name' => 'setting_view'],
            ['module' => 'setting', 'title' => 'Setting create', 'name' => 'setting_create'],
            ['module' => 'setting', 'title' => 'Setting edit', 'name' => 'setting_edit'],
            ['module' => 'setting', 'title' => 'Setting delete', 'name' => 'setting_delete'],

            ['module' => 'notifications', 'title' => 'Notifications access', 'name' => 'notifications_access'],
            ['module' => 'notifications', 'title' => 'Notifications view', 'name' => 'notifications_view'],
            ['module' => 'notifications', 'title' => 'Notifications create', 'name' => 'notifications_create'],
            ['module' => 'notifications', 'title' => 'Notifications edit', 'name' => 'notifications_edit'],
            ['module' => 'notifications', 'title' => 'Notifications delete', 'name' => 'notifications_delete'],

            ['module' => 'logs', 'title' => 'Log access', 'name' => 'logs_access'],
            ['module' => 'logs', 'title' => 'Log view', 'name' => 'logs_view'],
            ['module' => 'logs', 'title' => 'Log create', 'name' => 'logs_create'],
            ['module' => 'logs', 'title' => 'Log edit', 'name' => 'logs_edit'],
            ['module' => 'logs', 'title' => 'Log delete', 'name' => 'logs_delete'],

            ['module' => 'role', 'title' => 'Role access', 'name' => 'role_access'],
            ['module' => 'role', 'title' => 'Role view', 'name' => 'role_view'],
            ['module' => 'role', 'title' => 'Role create', 'name' => 'role_create'],
            ['module' => 'role', 'title' => 'Role edit', 'name' => 'role_edit'],
            ['module' => 'role', 'title' => 'Role delete', 'name' => 'role_delete'],

            ['module' => 'assign_permission', 'title' => 'Assign permission access', 'name' => 'assign_permission_access'],
            ['module' => 'assign_permission', 'title' => 'Assign permission view', 'name' => 'assign_permission_view'],
            ['module' => 'assign_permission', 'title' => 'Assign permission create', 'name' => 'assign_permission_create'],
            ['module' => 'assign_permission', 'title' => 'Assign permission edit', 'name' => 'assign_permission_edit'],
            ['module' => 'assign_permission', 'title' => 'Assign permission delete', 'name' => 'assign_permission_delete'],

            ['module' => 'department', 'title' => 'Department access', 'name' => 'department_access'],
            ['module' => 'department', 'title' => 'Department view', 'name' => 'department_view'],
            ['module' => 'department', 'title' => 'Department create', 'name' => 'department_create'],
            ['module' => 'department', 'title' => 'Department edit', 'name' => 'department_edit'],
            ['module' => 'department', 'title' => 'Department delete', 'name' => 'department_delete'],

            ['module' => 'sub_department', 'title' => 'Sub department access', 'name' => 'sub_department_access'],
            ['module' => 'sub_department', 'title' => 'Sub department view', 'name' => 'sub_department_view'],
            ['module' => 'sub_department', 'title' => 'Sub department create', 'name' => 'sub_department_create'],
            ['module' => 'sub_department', 'title' => 'Sub department edit', 'name' => 'sub_department_edit'],
            ['module' => 'sub_department', 'title' => 'Sub department delete', 'name' => 'sub_department_delete'],

            ['module' => 'designation', 'title' => 'Designation access', 'name' => 'designation_access'],
            ['module' => 'designation', 'title' => 'Designation view', 'name' => 'designation_view'],
            ['module' => 'designation', 'title' => 'Designation create', 'name' => 'designation_create'],
            ['module' => 'designation', 'title' => 'Designation edit', 'name' => 'designation_edit'],
            ['module' => 'designation', 'title' => 'Designation delete', 'name' => 'designation_delete'],

            ['module' => 'policy', 'title' => 'Policy access', 'name' => 'policy_access'],
            ['module' => 'policy', 'title' => 'Policy view', 'name' => 'policy_view'],
            ['module' => 'policy', 'title' => 'Policy create', 'name' => 'policy_create'],
            ['module' => 'policy', 'title' => 'Policy edit', 'name' => 'policy_edit'],
            ['module' => 'policy', 'title' => 'Policy delete', 'name' => 'policy_delete'],

            ['module' => 'employee', 'title' => 'Employee access', 'name' => 'employee_access'],
            ['module' => 'employee', 'title' => 'Employee view', 'name' => 'employee_view'],
            ['module' => 'employee', 'title' => 'Employee create', 'name' => 'employee_create'],
            ['module' => 'employee', 'title' => 'Employee edit', 'name' => 'employee_edit'],
            ['module' => 'employee', 'title' => 'Employee delete', 'name' => 'employee_delete'],

            ['module' => 'employee profile', 'title' => 'Employee profile access', 'name' => 'employee_profile_access'],
            ['module' => 'employee profile', 'title' => 'Employee profile view', 'name' => 'employee_profile_view'],
            ['module' => 'employee profile', 'title' => 'Employee profile create', 'name' => 'employee_profile_create'],
            ['module' => 'employee profile', 'title' => 'Employee profile edit', 'name' => 'employee_profile_edit'],
            ['module' => 'employee profile', 'title' => 'Employee profile delete', 'name' => 'employee_profile_delete'],

            ['module' => 'employee contact', 'title' => 'Employee contact access', 'name' => 'employee_contact_access'],
            ['module' => 'employee contact', 'title' => 'Employee contact view', 'name' => 'employee_contact_view'],
            ['module' => 'employee contact', 'title' => 'Employee contact create', 'name' => 'employee_contact_create'],
            ['module' => 'employee contact', 'title' => 'Employee contact edit', 'name' => 'employee_contact_edit'],
            ['module' => 'employee contact', 'title' => 'Employee contact delete', 'name' => 'employee_contact_delete'],

            ['module' => 'employee passport', 'title' => 'Employee passport access', 'name' => 'employee_passport_access'],
            ['module' => 'employee passport', 'title' => 'Employee passport view', 'name' => 'employee_passport_view'],
            ['module' => 'employee passport', 'title' => 'Employee passport create', 'name' => 'employee_passport_create'],
            ['module' => 'employee passport', 'title' => 'Employee passport edit', 'name' => 'employee_passport_edit'],
            ['module' => 'employee passport', 'title' => 'Employee passport delete', 'name' => 'employee_passport_delete'],

            ['module' => 'employee emirates', 'title' => 'Employee emirates access', 'name' => 'employee_emirates_access'],
            ['module' => 'employee emirates', 'title' => 'Employee emirates view', 'name' => 'employee_emirates_view'],
            ['module' => 'employee emirates', 'title' => 'Employee emirates create', 'name' => 'employee_emirates_create'],
            ['module' => 'employee emirates', 'title' => 'Employee emirates edit', 'name' => 'employee_emirates_edit'],
            ['module' => 'employee emirates', 'title' => 'Employee emirates delete', 'name' => 'employee_emirates_delete'],

            ['module' => 'employee visa', 'title' => 'Employee visa access', 'name' => 'employee_visa_access'],
            ['module' => 'employee visa', 'title' => 'Employee visa view', 'name' => 'employee_visa_view'],
            ['module' => 'employee visa', 'title' => 'Employee visa create', 'name' => 'employee_visa_create'],
            ['module' => 'employee visa', 'title' => 'Employee visa edit', 'name' => 'employee_visa_edit'],
            ['module' => 'employee visa', 'title' => 'Employee visa delete', 'name' => 'employee_visa_delete'],

            ['module' => 'employee bank', 'title' => 'Employee bank access', 'name' => 'employee_bank_access'],
            ['module' => 'employee bank', 'title' => 'Employee bank view', 'name' => 'employee_bank_view'],
            ['module' => 'employee bank', 'title' => 'Employee bank create', 'name' => 'employee_bank_create'],
            ['module' => 'employee bank', 'title' => 'Employee bank edit', 'name' => 'employee_bank_edit'],
            ['module' => 'employee bank', 'title' => 'Employee bank delete', 'name' => 'employee_bank_delete'],

            ['module' => 'employee document', 'title' => 'Employee document access', 'name' => 'employee_document_access'],
            ['module' => 'employee document', 'title' => 'Employee document view', 'name' => 'employee_document_view'],
            ['module' => 'employee document', 'title' => 'Employee document create', 'name' => 'employee_document_create'],
            ['module' => 'employee document', 'title' => 'Employee document edit', 'name' => 'employee_document_edit'],
            ['module' => 'employee document', 'title' => 'Employee document delete', 'name' => 'employee_document_delete'],

            ['module' => 'employee qualification', 'title' => 'Employee qualification access', 'name' => 'employee_qualification_access'],
            ['module' => 'employee qualification', 'title' => 'Employee qualification view', 'name' => 'employee_qualification_view'],
            ['module' => 'employee qualification', 'title' => 'Employee qualification create', 'name' => 'employee_qualification_create'],
            ['module' => 'employee qualification', 'title' => 'Employee qualification edit', 'name' => 'employee_qualification_edit'],
            ['module' => 'employee qualification', 'title' => 'Employee qualification delete', 'name' => 'employee_qualification_delete'],

            ['module' => 'employee setting', 'title' => 'Employee setting access', 'name' => 'employee_setting_access'],
            ['module' => 'employee setting', 'title' => 'Employee setting view', 'name' => 'employee_setting_view'],
            ['module' => 'employee setting', 'title' => 'Employee setting create', 'name' => 'employee_setting_create'],
            ['module' => 'employee setting', 'title' => 'Employee setting edit', 'name' => 'employee_setting_edit'],
            ['module' => 'employee setting', 'title' => 'Employee setting delete', 'name' => 'employee_setting_delete'],

            ['module' => 'employee payroll', 'title' => 'Employee payroll access', 'name' => 'employee_payroll_access'],
            ['module' => 'employee payroll', 'title' => 'Employee payroll view', 'name' => 'employee_payroll_view'],
            ['module' => 'employee payroll', 'title' => 'Employee payroll create', 'name' => 'employee_payroll_create'],
            ['module' => 'employee payroll', 'title' => 'Employee payroll edit', 'name' => 'employee_payroll_edit'],
            ['module' => 'employee payroll', 'title' => 'Employee payroll delete', 'name' => 'employee_payroll_delete'],

            ['module' => 'employee login', 'title' => 'Employee login access', 'name' => 'employee_login_access'],
            ['module' => 'employee login', 'title' => 'Employee login view', 'name' => 'employee_login_view'],
            ['module' => 'employee login', 'title' => 'Employee login create', 'name' => 'employee_login_create'],
            ['module' => 'employee login', 'title' => 'Employee login edit', 'name' => 'employee_login_edit'],
            ['module' => 'employee login', 'title' => 'Employee login delete', 'name' => 'employee_login_delete'],

            ['module' => 'employee_schedule', 'title' => 'Employee schedule access', 'name' => 'employee_schedule_access'],
            ['module' => 'employee_schedule', 'title' => 'Employee schedule view', 'name' => 'employee_schedule_view'],
            ['module' => 'employee_schedule', 'title' => 'Employee schedule create', 'name' => 'employee_schedule_create'],
            ['module' => 'employee_schedule', 'title' => 'Employee schedule edit', 'name' => 'employee_schedule_edit'],
            ['module' => 'employee_schedule', 'title' => 'Employee schedule delete', 'name' => 'employee_schedule_delete'],

            ['module' => 'attendance_report', 'title' => 'Attendance report access', 'name' => 'attendance_report_access'],
            ['module' => 'attendance_report', 'title' => 'Attendance report view', 'name' => 'attendance_report_view'],
            ['module' => 'attendance_report', 'title' => 'Attendance report create', 'name' => 'attendance_report_create'],
            ['module' => 'attendance_report', 'title' => 'Attendance report edit', 'name' => 'attendance_report_edit'],
            ['module' => 'attendance_report', 'title' => 'Attendance report delete', 'name' => 'attendance_report_delete'],

            ['module' => 'timezone', 'title' => 'Timezone access', 'name' => 'timezone_access'],
            ['module' => 'timezone', 'title' => 'Timezone view', 'name' => 'timezone_view'],
            ['module' => 'timezone', 'title' => 'Timezone create', 'name' => 'timezone_create'],
            ['module' => 'timezone', 'title' => 'Timezone edit', 'name' => 'timezone_edit'],
            ['module' => 'timezone', 'title' => 'Timezone delete', 'name' => 'timezone_delete'],

            ['module' => 'timezone_mapping', 'title' => 'Timezone mapping access', 'name' => 'timezone_mapping_access'],
            ['module' => 'timezone_mapping', 'title' => 'Timezone mapping view', 'name' => 'timezone_mapping_view'],
            ['module' => 'timezone_mapping', 'title' => 'Timezone mapping create', 'name' => 'timezone_mapping_create'],
            ['module' => 'timezone_mapping', 'title' => 'Timezone mapping edit', 'name' => 'timezone_mapping_edit'],
            ['module' => 'timezone_mapping', 'title' => 'Timezone mapping delete', 'name' => 'timezone_mapping_delete'],

            ['module' => 'timezone_device_mapping', 'title' => 'Employee device mapping access', 'name' => 'employee_device_mapping_access'],
            ['module' => 'timezone_device_mapping', 'title' => 'Employee device mapping view', 'name' => 'employee_device_mapping_view'],
            ['module' => 'timezone_device_mapping', 'title' => 'Employee device mapping create', 'name' => 'employee_device_mapping_create'],
            ['module' => 'timezone_device_mapping', 'title' => 'Employee device mapping edit', 'name' => 'employee_device_mapping_edit'],
            ['module' => 'timezone_device_mapping', 'title' => 'Employee device mapping delete', 'name' => 'employee_device_mapping_delete'],

            ['module' => 'announcement', 'title' => 'Announcement access', 'name' => 'announcement_access'],
            ['module' => 'announcement', 'title' => 'Announcement view', 'name' => 'announcement_view'],
            ['module' => 'announcement', 'title' => 'Announcement create', 'name' => 'announcement_create'],
            ['module' => 'announcement', 'title' => 'Announcement edit', 'name' => 'announcement_edit'],
            ['module' => 'announcement', 'title' => 'Announcement delete', 'name' => 'announcement_delete'],

            ['module' => 'leave', 'title' => 'Leave access', 'name' => 'leave_access'],
            ['module' => 'leave', 'title' => 'Leave view', 'name' => 'leave_view'],
            ['module' => 'leave', 'title' => 'Leave create', 'name' => 'leave_create'],
            ['module' => 'leave', 'title' => 'Leave edit', 'name' => 'leave_edit'],
            ['module' => 'leave', 'title' => 'Leave  delete', 'name' => 'leave_delete'],

            ['module' => 'leave_application', 'title' => 'Leave Application access', 'name' => 'leave_application_access'],
            ['module' => 'leave_application', 'title' => 'Leave Application view', 'name' => 'leave_application_view'],
            ['module' => 'leave_application', 'title' => 'Leave Application create', 'name' => 'leave_application_create'],
            ['module' => 'leave_application', 'title' => 'Leave Application edit', 'name' => 'leave_application_edit'],
            ['module' => 'leave_application', 'title' => 'Leave Application delete', 'name' => 'leave_application_delete'],

            ['module' => 'leave_type', 'title' => 'Leave Type access', 'name' => 'leave_type_access'],
            ['module' => 'leave_type', 'title' => 'Leave Type view', 'name' => 'leave_type_view'],
            ['module' => 'leave_type', 'title' => 'Leave Type create', 'name' => 'leave_type_create'],
            ['module' => 'leave_type', 'title' => 'Leave Type edit', 'name' => 'leave_type_edit'],
            ['module' => 'leave_type', 'title' => 'Leave Type  delete', 'name' => 'leave_type_delete'],

            ['module' => 'leave_group', 'title' => 'Leave Group access', 'name' => 'leave_group_access'],
            ['module' => 'leave_group', 'title' => 'Leave Group view', 'name' => 'leave_group_view'],
            ['module' => 'leave_group', 'title' => 'Leave Group create', 'name' => 'leave_group_create'],
            ['module' => 'leave_group', 'title' => 'Leave Group edit', 'name' => 'leave_group_edit'],
            ['module' => 'leave_group', 'title' => 'Leave Group  delete', 'name' => 'leave_group_delete'],

            ['module' => 'holiday', 'title' => 'Holiday access', 'name' => 'holiday_access'],
            ['module' => 'holiday', 'title' => 'Holiday view', 'name' => 'holiday_view'],
            ['module' => 'holiday', 'title' => 'Holiday create', 'name' => 'holiday_create'],
            ['module' => 'holiday', 'title' => 'Holiday edit', 'name' => 'holiday_edit'],
            ['module' => 'holiday', 'title' => 'Holiday  delete', 'name' => 'holiday_delete'],
        ];

        // run this command to seed the data => php artisan db:seed --class=PermissionSeeder
        Permission::insert($data);
    }
}
