<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveType;
use App\Models\Department;
use App\Models\Position;

class HrmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedLeaveTypes();
        $this->seedDepartments();
        $this->seedPositions();
    }

    /**
     * Seed default leave types
     */
    private function seedLeaveTypes()
    {
        $leaveTypes = [
            [
                'name' => 'Annual Leave',
                'code' => 'annual',
                'description' => 'Paid annual vacation leave',
                'default_days_per_year' => 10,
                'is_paid' => true,
                'requires_approval' => true,
                'requires_document' => false,
                'max_consecutive_days' => 10,
                'min_advance_notice_days' => 7,
                'carry_forward' => true,
                'max_carry_forward_days' => 5,
                'color' => '#3B82F6', // Blue
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'sick',
                'description' => 'Paid sick leave',
                'default_days_per_year' => 30,
                'is_paid' => true,
                'requires_approval' => false,
                'requires_document' => true,
                'max_consecutive_days' => 30,
                'min_advance_notice_days' => 0,
                'carry_forward' => false,
                'max_carry_forward_days' => null,
                'color' => '#EF4444', // Red
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Personal Leave',
                'code' => 'personal',
                'description' => 'Personal or emergency leave',
                'default_days_per_year' => 3,
                'is_paid' => true,
                'requires_approval' => true,
                'requires_document' => false,
                'max_consecutive_days' => 3,
                'min_advance_notice_days' => 1,
                'carry_forward' => false,
                'max_carry_forward_days' => null,
                'color' => '#F59E0B', // Orange
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Maternity Leave',
                'code' => 'maternity',
                'description' => 'Maternity leave for expecting mothers',
                'default_days_per_year' => 90,
                'is_paid' => true,
                'requires_approval' => true,
                'requires_document' => true,
                'max_consecutive_days' => 90,
                'min_advance_notice_days' => 30,
                'carry_forward' => false,
                'max_carry_forward_days' => null,
                'color' => '#EC4899', // Pink
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Paternity Leave',
                'code' => 'paternity',
                'description' => 'Paternity leave for new fathers',
                'default_days_per_year' => 5,
                'is_paid' => true,
                'requires_approval' => true,
                'requires_document' => true,
                'max_consecutive_days' => 5,
                'min_advance_notice_days' => 7,
                'carry_forward' => false,
                'max_carry_forward_days' => null,
                'color' => '#06B6D4', // Cyan
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Unpaid Leave',
                'code' => 'unpaid',
                'description' => 'Unpaid leave without salary',
                'default_days_per_year' => 0,
                'is_paid' => false,
                'requires_approval' => true,
                'requires_document' => false,
                'max_consecutive_days' => 30,
                'min_advance_notice_days' => 14,
                'carry_forward' => false,
                'max_carry_forward_days' => null,
                'color' => '#6B7280', // Gray
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Compensatory Leave',
                'code' => 'compensatory',
                'description' => 'Leave earned for overtime or weekend work',
                'default_days_per_year' => 0,
                'is_paid' => true,
                'requires_approval' => true,
                'requires_document' => false,
                'max_consecutive_days' => 5,
                'min_advance_notice_days' => 3,
                'carry_forward' => true,
                'max_carry_forward_days' => 10,
                'color' => '#8B5CF6', // Purple
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Study Leave',
                'code' => 'study',
                'description' => 'Leave for education or training purposes',
                'default_days_per_year' => 5,
                'is_paid' => true,
                'requires_approval' => true,
                'requires_document' => true,
                'max_consecutive_days' => 10,
                'min_advance_notice_days' => 30,
                'carry_forward' => false,
                'max_carry_forward_days' => null,
                'color' => '#10B981', // Green
                'is_active' => true,
                'sort_order' => 8,
            ],
            [
                'name' => 'Bereavement Leave',
                'code' => 'bereavement',
                'description' => 'Leave for family member death',
                'default_days_per_year' => 3,
                'is_paid' => true,
                'requires_approval' => false,
                'requires_document' => true,
                'max_consecutive_days' => 5,
                'min_advance_notice_days' => 0,
                'carry_forward' => false,
                'max_carry_forward_days' => null,
                'color' => '#1F2937', // Dark Gray
                'is_active' => true,
                'sort_order' => 9,
            ],
            [
                'name' => 'Marriage Leave',
                'code' => 'marriage',
                'description' => 'Leave for employee\'s wedding',
                'default_days_per_year' => 3,
                'is_paid' => true,
                'requires_approval' => true,
                'requires_document' => true,
                'max_consecutive_days' => 3,
                'min_advance_notice_days' => 14,
                'carry_forward' => false,
                'max_carry_forward_days' => null,
                'color' => '#F472B6', // Light Pink
                'is_active' => true,
                'sort_order' => 10,
            ],
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::firstOrCreate(
                ['code' => $leaveType['code']],
                $leaveType
            );
        }

        $this->command->info('✓ Leave types seeded successfully');
    }

    /**
     * Seed sample departments
     */
    private function seedDepartments()
    {
        $departments = [
            [
                'name' => 'Executive Management',
                'code' => 'EXEC',
                'description' => 'Executive leadership and strategic planning',
                'parent_id' => null,
                'manager_id' => null,
                'location' => 'Head Office',
                'phone' => null,
                'email' => 'exec@company.com',
                'budget' => 50000000,
                'is_active' => true,
            ],
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'description' => 'Employee relations, recruitment, and development',
                'parent_id' => null,
                'manager_id' => null,
                'location' => 'Head Office',
                'phone' => null,
                'email' => 'hr@company.com',
                'budget' => 10000000,
                'is_active' => true,
            ],
            [
                'name' => 'Finance & Accounting',
                'code' => 'FIN',
                'description' => 'Financial management and accounting operations',
                'parent_id' => null,
                'manager_id' => null,
                'location' => 'Head Office',
                'phone' => null,
                'email' => 'finance@company.com',
                'budget' => 15000000,
                'is_active' => true,
            ],
            [
                'name' => 'Information Technology',
                'code' => 'IT',
                'description' => 'Technology infrastructure and software development',
                'parent_id' => null,
                'manager_id' => null,
                'location' => 'Tech Hub',
                'phone' => null,
                'email' => 'it@company.com',
                'budget' => 30000000,
                'is_active' => true,
            ],
            [
                'name' => 'Sales & Marketing',
                'code' => 'SALES',
                'description' => 'Revenue generation and brand promotion',
                'parent_id' => null,
                'manager_id' => null,
                'location' => 'Head Office',
                'phone' => null,
                'email' => 'sales@company.com',
                'budget' => 25000000,
                'is_active' => true,
            ],
            [
                'name' => 'Operations',
                'code' => 'OPS',
                'description' => 'Day-to-day business operations and logistics',
                'parent_id' => null,
                'manager_id' => null,
                'location' => 'Operations Center',
                'phone' => null,
                'email' => 'ops@company.com',
                'budget' => 20000000,
                'is_active' => true,
            ],
            [
                'name' => 'Customer Service',
                'code' => 'CS',
                'description' => 'Customer support and satisfaction',
                'parent_id' => null,
                'manager_id' => null,
                'location' => 'Service Center',
                'phone' => null,
                'email' => 'service@company.com',
                'budget' => 8000000,
                'is_active' => true,
            ],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ['code' => $dept['code']],
                $dept
            );
        }

        $this->command->info('✓ Departments seeded successfully');
    }

    /**
     * Seed sample positions
     */
    private function seedPositions()
    {
        // Get departments
        $hr = Department::where('code', 'HR')->first();
        $it = Department::where('code', 'IT')->first();
        $finance = Department::where('code', 'FIN')->first();
        $sales = Department::where('code', 'SALES')->first();

        if (!$hr || !$it || !$finance || !$sales) {
            $this->command->warn('! Departments not found, skipping position seeding');
            return;
        }

        $positions = [
            // HR Positions
            [
                'title' => 'HR Manager',
                'code' => 'HR-MGR',
                'description' => 'Oversee all HR operations and strategy',
                'department_id' => $hr->id,
                'level' => 'manager',
                'min_salary' => 60000,
                'max_salary' => 90000,
                'requirements' => 'Bachelor\'s degree in HR, 5+ years experience',
                'responsibilities' => 'Strategic HR planning, team management, policy development',
                'is_active' => true,
            ],
            [
                'title' => 'HR Executive',
                'code' => 'HR-EXEC',
                'description' => 'Handle recruitment and employee relations',
                'department_id' => $hr->id,
                'level' => 'mid',
                'min_salary' => 30000,
                'max_salary' => 45000,
                'requirements' => 'Bachelor\'s degree, 2+ years in HR',
                'responsibilities' => 'Recruitment, onboarding, employee engagement',
                'is_active' => true,
            ],
            // IT Positions
            [
                'title' => 'Senior Software Engineer',
                'code' => 'IT-SSE',
                'description' => 'Lead software development projects',
                'department_id' => $it->id,
                'level' => 'senior',
                'min_salary' => 70000,
                'max_salary' => 120000,
                'requirements' => 'Bachelor\'s in CS, 5+ years programming experience',
                'responsibilities' => 'Software development, code review, mentoring',
                'is_active' => true,
            ],
            [
                'title' => 'Software Developer',
                'code' => 'IT-DEV',
                'description' => 'Develop and maintain software applications',
                'department_id' => $it->id,
                'level' => 'mid',
                'min_salary' => 40000,
                'max_salary' => 70000,
                'requirements' => 'Bachelor\'s in CS, 2+ years experience',
                'responsibilities' => 'Code development, testing, bug fixing',
                'is_active' => true,
            ],
            // Finance Positions
            [
                'title' => 'Accountant',
                'code' => 'FIN-ACC',
                'description' => 'Manage financial records and reporting',
                'department_id' => $finance->id,
                'level' => 'mid',
                'min_salary' => 35000,
                'max_salary' => 55000,
                'requirements' => 'Bachelor\'s in Accounting, CPA preferred',
                'responsibilities' => 'Financial reporting, tax compliance, auditing',
                'is_active' => true,
            ],
            // Sales Positions
            [
                'title' => 'Sales Manager',
                'code' => 'SALES-MGR',
                'description' => 'Lead sales team and drive revenue',
                'department_id' => $sales->id,
                'level' => 'manager',
                'min_salary' => 50000,
                'max_salary' => 80000,
                'requirements' => 'Bachelor\'s degree, 5+ years sales experience',
                'responsibilities' => 'Team management, sales strategy, client relationships',
                'is_active' => true,
            ],
            [
                'title' => 'Sales Executive',
                'code' => 'SALES-EXEC',
                'description' => 'Drive sales and meet targets',
                'department_id' => $sales->id,
                'level' => 'mid',
                'min_salary' => 25000,
                'max_salary' => 40000,
                'requirements' => 'Bachelor\'s degree, 1+ year sales experience',
                'responsibilities' => 'Client acquisition, sales presentations, target achievement',
                'is_active' => true,
            ],
        ];

        foreach ($positions as $position) {
            Position::firstOrCreate(
                ['code' => $position['code']],
                $position
            );
        }

        $this->command->info('✓ Positions seeded successfully');
    }
}
