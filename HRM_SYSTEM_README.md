# 🏢 HRM (Human Resource Management) System

## Overview

A comprehensive, enterprise-grade Human Resource Management system built for Laravel 11 with premium features and beautiful UI.

## ✨ Features

### 1. **Employee Management** 👥
- Complete employee profiles with personal and professional information
- Employment history tracking
- Document management (contracts, certificates, ID cards)
- Employee hierarchy and reporting structure
- Skills, certifications, and language tracking
- Photo and bio management
- Export to CSV functionality

### 2. **Department & Position Management** 🏢
- Hierarchical department structure
- Department budgets and managers
- Position definitions with salary ranges
- Organization chart visualization
- Department-wise employee analytics

### 3. **Attendance & Time Tracking** ⏰
- Daily attendance recording
- Check-in/Check-out with location and IP tracking
- Late arrival and early departure detection
- Work hours calculation with break time
- Overtime tracking and calculation
- Monthly attendance reports
- Department-wise attendance summaries
- Weekend and holiday management

### 4. **Leave Management** 🌴
- Multiple leave types (Annual, Sick, Personal, Maternity, etc.)
- Leave balance tracking per employee
- Leave request workflow with approval system
- Half-day leave support
- Leave calendar visualization
- Automatic leave balance deduction
- Leave carry-forward functionality
- Document attachment support
- Email notifications for approvals/rejections

### 5. **Payroll Management** 💰
- Monthly payroll generation
- Comprehensive salary components:
  - Basic salary
  - Allowances
  - Bonuses
  - Overtime pay
  - Commissions
- Automatic deductions:
  - Tax calculation (Thai progressive tax)
  - Social security (5% capped at 750 THB)
  - Provident fund
  - Loan deductions
- Bulk payroll generation
- Payroll approval workflow
- Payment tracking
- Payslip generation (PDF)
- Export to CSV

### 6. **Performance Management** 📊
- Performance reviews with multiple rating categories:
  - Technical skills
  - Communication
  - Teamwork
  - Leadership
  - Problem solving
  - Productivity
  - Quality of work
  - Punctuality
  - Initiative
  - Adaptability
- Goal setting and tracking (KPIs)
- Progress monitoring
- Performance ratings and grades
- 360-degree feedback support
- Review periods (Probation, Quarterly, Semi-annual, Annual)
- Promotion and salary increase recommendations

### 7. **Recruitment** 📢
- Job posting management
- Application tracking system (ATS)
- Multi-stage recruitment workflow:
  - New → Reviewing → Shortlisted
  - Interview Scheduled → Interviewed
  - Offer Sent → Offer Accepted/Rejected
- Resume and cover letter management
- Interview scheduling
- Candidate evaluation and rating
- Offer management
- Public job portal integration
- Application forms with comprehensive fields

### 8. **Training & Development** 📚
- Training course management
- Course categories and types
- Enrollment tracking
- Attendance monitoring
- Assessment and certification
- Certificate issuance with validity tracking
- Training calendar
- Employee training history
- Mandatory training management
- Training cost tracking

### 9. **HRM Dashboard** 📈
- Key HR metrics and KPIs
- Employee headcount by department
- Employment type distribution
- Headcount trend (12 months)
- Attendance statistics
- Pending approvals (leaves, payrolls)
- Upcoming birthdays
- Work anniversaries
- Expiring documents alerts
- Average employee tenure
- New hires and terminations tracking

## 🗄️ Database Structure

### Core Tables (15 tables)

1. **departments** - Organization departments
2. **positions** - Job positions
3. **employees** - Employee profiles
4. **employee_documents** - Employee files and documents
5. **attendance_records** - Daily attendance
6. **leave_types** - Leave categories
7. **leave_requests** - Leave applications
8. **payroll_records** - Monthly payroll
9. **salary_components** - Salary items
10. **performance_reviews** - Performance evaluations
11. **performance_goals** - KPIs and goals
12. **job_postings** - Job advertisements
13. **job_applications** - Candidate applications
14. **training_courses** - Training programs
15. **training_enrollments** - Training records

## 🎨 Technology Stack

- **Backend**: Laravel 11 (PHP 8.1+)
- **Database**: MySQL 8.0+ / MariaDB
- **Frontend**: Tailwind CSS 3.4, Alpine.js 3.13
- **Charts**: Chart.js 4.4.1
- **Authentication**: Laravel Sanctum

## 🚀 Installation

### 1. Run Migrations

```bash
php artisan migrate
```

### 2. Seed Default Data

```bash
php artisan db:seed --class=HrmSeeder
```

This will create:
- 10 default leave types
- 7 sample departments
- 7 sample positions

### 3. Access HRM System

Navigate to: `/admin/hrm/dashboard`

## 📁 File Structure

```
app/
├── Models/
│   ├── Department.php
│   ├── Position.php
│   ├── Employee.php
│   ├── EmployeeDocument.php
│   ├── AttendanceRecord.php
│   ├── LeaveType.php
│   ├── LeaveRequest.php
│   ├── PayrollRecord.php
│   ├── SalaryComponent.php
│   ├── PerformanceReview.php
│   ├── PerformanceGoal.php
│   ├── JobPosting.php
│   ├── JobApplication.php
│   ├── TrainingCourse.php
│   └── TrainingEnrollment.php
├── Services/
│   ├── HrmService.php
│   ├── PayrollService.php
│   └── AttendanceService.php
└── Http/Controllers/Admin/
    ├── HrmDashboardController.php
    ├── EmployeeController.php
    ├── DepartmentController.php
    ├── AttendanceController.php
    ├── LeaveController.php
    └── PayrollController.php

database/
├── migrations/
│   └── 2024_11_06_000001_create_departments_table.php (+ 14 more)
└── seeders/
    └── HrmSeeder.php

routes/
└── admin.php (HRM routes added)
```

## 🔐 Permissions

The HRM system respects the existing admin authentication and permission system. Ensure users have appropriate permissions to access HRM features.

## 📊 Default Leave Types

1. **Annual Leave** - 10 days/year, carry forward 5 days
2. **Sick Leave** - 30 days/year, requires medical certificate
3. **Personal Leave** - 3 days/year
4. **Maternity Leave** - 90 days
5. **Paternity Leave** - 5 days
6. **Unpaid Leave** - As needed, unpaid
7. **Compensatory Leave** - Earned from overtime
8. **Study Leave** - 5 days/year
9. **Bereavement Leave** - 3 days
10. **Marriage Leave** - 3 days

## 🎯 Key Routes

### Admin Routes (Prefix: `/admin/hrm`)

```
/admin/hrm/dashboard              - HRM Dashboard
/admin/hrm/employees              - Employee Management
/admin/hrm/departments            - Department Management
/admin/hrm/positions              - Position Management
/admin/hrm/attendance             - Attendance Tracking
/admin/hrm/leave                  - Leave Management
/admin/hrm/leave/types            - Leave Types Config
/admin/hrm/payroll                - Payroll Management
/admin/hrm/performance/reviews    - Performance Reviews
/admin/hrm/performance/goals      - Goal Management
/admin/hrm/recruitment/jobs       - Job Postings
/admin/hrm/recruitment/applications - Applications
/admin/hrm/training/courses       - Training Courses
/admin/hrm/training/enrollments   - Training Enrollments
```

## 💡 Usage Examples

### Creating an Employee

```php
use App\Models\Employee;
use App\Models\User;

$user = User::create([...]);

$employee = Employee::create([
    'user_id' => $user->id,
    'employee_id' => 'EMP001',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'department_id' => 1,
    'position_id' => 1,
    'hire_date' => now(),
    'employment_type' => 'full_time',
    'employment_status' => 'active',
    'basic_salary' => 50000,
    'work_email' => 'john@company.com',
]);
```

### Generating Payroll

```php
use App\Services\PayrollService;

$payrollService = new PayrollService();

// Generate for single employee
$payroll = $payrollService->generatePayroll($employeeId, 11, 2024);

// Bulk generate for department
$result = $payrollService->bulkGeneratePayrolls(11, 2024, $departmentId);
```

### Checking Attendance

```php
use App\Services\AttendanceService;

$attendanceService = new AttendanceService();

// Check in
$attendance = $attendanceService->checkIn($employeeId, 'Office');

// Check out
$attendance = $attendanceService->checkOut($employeeId, 'Office');
```

### Processing Leave Request

```php
use App\Models\LeaveRequest;

$leaveRequest = LeaveRequest::find($id);

// Approve
$leaveRequest->approve($approverId, 'Approved with remarks');

// Reject
$leaveRequest->reject($rejectorId, 'Insufficient balance');
```

## 🔧 Configuration

### Payroll Settings

Thai tax calculation and social security are configured in `PayrollService.php`:
- Progressive tax rates
- Social security: 5% capped at 750 THB
- Provident fund: Configurable per employee

### Attendance Settings

Configure work hours in employee records:
- `work_start_time` - Default start time
- `work_end_time` - Default end time
- Late detection is automatic

## 🎨 UI Features

- **Premium Design** - Modern, clean interface with Tailwind CSS
- **Dark Mode Support** - Consistent with existing admin panel
- **Responsive Layout** - Mobile-friendly design
- **Data Tables** - Sortable, filterable, and searchable
- **Charts & Graphs** - Visual analytics with Chart.js
- **Export Functions** - CSV export for reports
- **Calendar Views** - Leave calendar, training calendar
- **Modal Forms** - Quick actions without page reload

## 🔄 Integration

The HRM system integrates seamlessly with existing modules:
- **User Management** - Links to Laravel User model
- **Wallet System** - Can integrate with employee salaries
- **Notification System** - For approvals and alerts
- **Email System** - For automated notifications
- **Permission System** - Role-based access control

## 📝 Future Enhancements

- [ ] PDF payslip generation
- [ ] Employee self-service portal
- [ ] Mobile app for attendance
- [ ] Biometric integration
- [ ] Advanced analytics and reports
- [ ] Employee performance dashboard
- [ ] Automated birthday/anniversary emails
- [ ] Talent pool management
- [ ] Succession planning
- [ ] Employee engagement surveys

## 🐛 Troubleshooting

### Migration Issues

If migrations fail, ensure:
1. Database connection is configured
2. Required tables don't already exist
3. Foreign key constraints are satisfied

### Permission Errors

Ensure your admin user has appropriate roles:
```php
$user->role = 'admin';
$user->save();
```

## 📄 License

This HRM system is part of the Thaiprompt-Affiliate project.

## 👨‍💻 Credits

Built with Laravel 11 and Tailwind CSS for enterprise-grade HR management.

---

**For support or questions, please refer to the main project documentation.**
