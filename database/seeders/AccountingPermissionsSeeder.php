<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class AccountingPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * 📌 Smart Seeding Strategy:
     * Adds missing accounting permissions only, preserves existing ones.
     */
    public function run(): void
    {
        $existingPermissionsCount = Permission::where('category', 'accounting')->count();

        if ($existingPermissionsCount > 0) {
            $this->updateMode();
        } else {
            $this->freshInstallMode();
        }
    }

    /**
     * Fresh install mode - seed all permissions
     */
    private function freshInstallMode(): void
    {
        $this->command->info('🌱 Fresh install: Seeding all accounting permissions...');

        $permissions = $this->getAllPermissions();

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        $this->command->info('✅ Accounting permissions seeded successfully: ' . count($permissions) . ' permissions');
    }

    /**
     * Update mode - add only missing permissions
     */
    private function updateMode(): void
    {
        $this->command->warn('⚠️  Existing accounting permissions detected!');
        $this->command->info('   Running in UPDATE mode (adding missing permissions only)...');

        $permissions = $this->getAllPermissions();
        $added = 0;
        $skipped = 0;

        foreach ($permissions as $permission) {
            if (!Permission::where('name', $permission['name'])->exists()) {
                Permission::create($permission);
                $this->command->info("   ➕ Added: {$permission['name']}");
                $added++;
            } else {
                $skipped++;
            }
        }

        if ($added > 0) {
            $this->command->info("✅ Added {$added} new accounting permissions.");
        }

        if ($skipped > 0) {
            $this->command->info("   ⏭️  Skipped {$skipped} existing permissions (preserved).");
        }
    }

    /**
     * Get all accounting permissions
     */
    private function getAllPermissions(): array
    {
        return [
            // Dashboard
            [
                'name' => 'accounting.view_dashboard',
                'display_name' => 'View Accounting Dashboard',
                'description' => 'Can view accounting dashboard and reports',
                'category' => 'accounting',
            ],

            // Settings
            [
                'name' => 'accounting.manage_settings',
                'display_name' => 'Manage Accounting Settings',
                'description' => 'Can manage accounting system settings',
                'category' => 'accounting',
            ],

            // Companies
            [
                'name' => 'accounting.view_companies',
                'display_name' => 'View Companies',
                'description' => 'Can view company information',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.create_companies',
                'display_name' => 'Create Companies',
                'description' => 'Can create new companies',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.edit_companies',
                'display_name' => 'Edit Companies',
                'description' => 'Can edit company information',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.delete_companies',
                'display_name' => 'Delete Companies',
                'description' => 'Can delete companies',
                'category' => 'accounting',
            ],

            // Chart of Accounts
            [
                'name' => 'accounting.view_chart_accounts',
                'display_name' => 'View Chart of Accounts',
                'description' => 'Can view chart of accounts',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.manage_chart_accounts',
                'display_name' => 'Manage Chart of Accounts',
                'description' => 'Can create, edit, and delete chart of accounts',
                'category' => 'accounting',
            ],

            // Contacts
            [
                'name' => 'accounting.view_contacts',
                'display_name' => 'View Contacts',
                'description' => 'Can view customers and vendors',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.create_contacts',
                'display_name' => 'Create Contacts',
                'description' => 'Can create new contacts',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.edit_contacts',
                'display_name' => 'Edit Contacts',
                'description' => 'Can edit contact information',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.delete_contacts',
                'display_name' => 'Delete Contacts',
                'description' => 'Can delete contacts',
                'category' => 'accounting',
            ],

            // Products
            [
                'name' => 'accounting.view_products',
                'display_name' => 'View Products',
                'description' => 'Can view products and services',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.create_products',
                'display_name' => 'Create Products',
                'description' => 'Can create new products',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.edit_products',
                'display_name' => 'Edit Products',
                'description' => 'Can edit product information',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.delete_products',
                'display_name' => 'Delete Products',
                'description' => 'Can delete products',
                'category' => 'accounting',
            ],

            // Invoices
            [
                'name' => 'accounting.view_invoices',
                'display_name' => 'View Invoices',
                'description' => 'Can view invoices and receipts',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.create_invoices',
                'display_name' => 'Create Invoices',
                'description' => 'Can create new invoices',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.edit_invoices',
                'display_name' => 'Edit Invoices',
                'description' => 'Can edit invoices',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.delete_invoices',
                'display_name' => 'Delete Invoices',
                'description' => 'Can delete invoices',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.approve_invoices',
                'display_name' => 'Approve Invoices',
                'description' => 'Can approve and finalize invoices',
                'category' => 'accounting',
            ],

            // Expenses
            [
                'name' => 'accounting.view_expenses',
                'display_name' => 'View Expenses',
                'description' => 'Can view expenses and bills',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.create_expenses',
                'display_name' => 'Create Expenses',
                'description' => 'Can create new expenses',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.edit_expenses',
                'display_name' => 'Edit Expenses',
                'description' => 'Can edit expenses',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.delete_expenses',
                'display_name' => 'Delete Expenses',
                'description' => 'Can delete expenses',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.approve_expenses',
                'display_name' => 'Approve Expenses',
                'description' => 'Can approve and finalize expenses',
                'category' => 'accounting',
            ],

            // Payments
            [
                'name' => 'accounting.view_payments',
                'display_name' => 'View Payments',
                'description' => 'Can view payment transactions',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.create_payments',
                'display_name' => 'Create Payments',
                'description' => 'Can record new payments',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.edit_payments',
                'display_name' => 'Edit Payments',
                'description' => 'Can edit payment records',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.delete_payments',
                'display_name' => 'Delete Payments',
                'description' => 'Can delete payment records',
                'category' => 'accounting',
            ],

            // Bank Accounts
            [
                'name' => 'accounting.view_bank_accounts',
                'display_name' => 'View Bank Accounts',
                'description' => 'Can view bank account information',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.manage_bank_accounts',
                'display_name' => 'Manage Bank Accounts',
                'description' => 'Can create, edit, and delete bank accounts',
                'category' => 'accounting',
            ],

            // Reports
            [
                'name' => 'accounting.view_reports',
                'display_name' => 'View Reports',
                'description' => 'Can view accounting reports',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.export_reports',
                'display_name' => 'Export Reports',
                'description' => 'Can export reports to PDF/Excel',
                'category' => 'accounting',
            ],

            // FlowAccount Integration
            [
                'name' => 'accounting.manage_flowaccount',
                'display_name' => 'Manage FlowAccount Integration',
                'description' => 'Can configure and sync with FlowAccount',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.sync_flowaccount',
                'display_name' => 'Sync FlowAccount',
                'description' => 'Can manually trigger FlowAccount sync',
                'category' => 'accounting',
            ],

            // Journal Entries
            [
                'name' => 'accounting.view_journal_entries',
                'display_name' => 'View Journal Entries',
                'description' => 'Can view journal entries',
                'category' => 'accounting',
            ],
            [
                'name' => 'accounting.create_journal_entries',
                'display_name' => 'Create Journal Entries',
                'description' => 'Can create manual journal entries',
                'category' => 'accounting',
            ],

            // Export Templates
            [
                'name' => 'accounting.manage_export_templates',
                'display_name' => 'Manage Export Templates',
                'description' => 'Can create and edit export templates',
                'category' => 'accounting',
            ],
        ];
    }
}
