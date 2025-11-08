<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AccountingSetting;
use App\Models\AccountingCompany;
use App\Models\AccountingChartOfAccount;
use App\Models\AccountingContact;
use App\Models\AccountingProduct;
use App\Models\AccountingInvoice;
use App\Models\AccountingInvoiceItem;
use App\Models\AccountingExpense;
use App\Models\AccountingExpenseItem;
use App\Models\AccountingPayment;
use App\Models\AccountingBankAccount;
use App\Models\AccountingTaxRate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountingDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            // Get first user (or create if not exists)
            $user = User::first();

            if (!$user) {
                $this->command->error('ไม่พบ User ในระบบ กรุณาสร้าง User ก่อน');
                return;
            }

            $this->command->info("Creating demo data for user: {$user->name}");

            // 1. Create Accounting Settings
            $this->command->info('1. Creating accounting settings...');
            $settings = AccountingSetting::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'is_enabled' => true,
                    'currency' => 'THB',
                    'date_format' => 'd/m/Y',
                    'timezone' => 'Asia/Bangkok',
                    'fiscal_year_start' => '01-01',
                    'auto_sync_flowaccount' => false,
                    'tax_settings' => [
                        'default_tax_rate' => 7.00,
                        'tax_inclusive' => false,
                        'withholding_tax' => false,
                        'default_withholding_rate' => 3.00,
                    ],
                    'invoice_settings' => [
                        'default_payment_terms' => 'credit',
                        'default_due_days' => 30,
                        'auto_number' => true,
                        'number_prefix' => 'INV',
                        'number_padding' => 5,
                        'show_company_logo' => true,
                        'default_note' => 'ขอบคุณที่ใช้บริการ',
                        'default_terms' => 'ชำระภายใน 30 วัน',
                    ],
                    'expense_settings' => [
                        'auto_number' => true,
                        'number_prefix' => 'EXP',
                        'number_padding' => 5,
                        'require_receipt' => false,
                    ],
                ]
            );

            // 2. Create Company
            $this->command->info('2. Creating company...');
            $company = AccountingCompany::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'tax_id' => '0105559123456',
                ],
                [
                    'name' => 'บริษัท ไทยพร๊อมท์ จำกัด',
                    'name_eng' => 'Thai Prompt Co., Ltd.',
                    'branch_code' => '00000',
                    'phone' => '02-123-4567',
                    'email' => 'info@thaiprompt.com',
                    'website' => 'https://thaiprompt.com',
                    'address' => '123 ถนนสุขุมวิท',
                    'district' => 'คลองเตย',
                    'province' => 'กรุงเทพมหานคร',
                    'postal_code' => '10110',
                    'country' => 'Thailand',
                    'is_default' => true,
                ]
            );

            // 3. Create Chart of Accounts
            $this->command->info('3. Creating chart of accounts...');
            $chartAccounts = $this->createChartOfAccounts($user);

            // 4. Create Bank Accounts
            $this->command->info('4. Creating bank accounts...');
            $bankAccount1 = AccountingBankAccount::create([
                'user_id' => $user->id,
                'bank_name' => 'ธนาคารกสิกรไทย',
                'account_name' => 'บริษัท ไทยพร๊อมท์ จำกัด',
                'account_number' => '123-4-56789-0',
                'branch' => 'สาขาสุขุมวิท',
                'account_type' => 'current',
                'opening_balance' => 500000.00,
                'current_balance' => 500000.00,
                'chart_account_id' => $chartAccounts['cash_in_bank']->id,
                'is_active' => true,
            ]);

            $bankAccount2 = AccountingBankAccount::create([
                'user_id' => $user->id,
                'bank_name' => 'ธนาคารไทยพาณิชย์',
                'account_name' => 'บริษัท ไทยพร๊อมท์ จำกัด',
                'account_number' => '987-6-54321-0',
                'branch' => 'สาขาอโศก',
                'account_type' => 'savings',
                'opening_balance' => 300000.00,
                'current_balance' => 300000.00,
                'is_active' => true,
            ]);

            // 5. Create Tax Rates
            $this->command->info('5. Creating tax rates...');
            $taxRate7 = AccountingTaxRate::create([
                'user_id' => $user->id,
                'name' => 'VAT 7%',
                'rate' => 7.00,
                'is_default' => true,
                'is_active' => true,
            ]);

            $taxRate0 = AccountingTaxRate::create([
                'user_id' => $user->id,
                'name' => 'ยกเว้นภาษี',
                'rate' => 0.00,
                'is_default' => false,
                'is_active' => true,
            ]);

            // 6. Create Contacts (Customers)
            $this->command->info('6. Creating contacts (customers)...');
            $customers = [];

            $customers[] = AccountingContact::create([
                'user_id' => $user->id,
                'type' => 'customer',
                'name' => 'บริษัท สยามเทคโนโลยี จำกัด',
                'name_eng' => 'Siam Technology Co., Ltd.',
                'tax_id' => '0105558111111',
                'branch_code' => '00000',
                'phone' => '02-234-5678',
                'mobile' => '089-123-4567',
                'email' => 'contact@siamtech.com',
                'address' => '456 ถนนพระราม 4',
                'district' => 'ปทุมวัน',
                'province' => 'กรุงเทพมหานคร',
                'postal_code' => '10330',
                'country' => 'Thailand',
                'credit_limit' => 500000.00,
                'credit_days' => 30,
                'payment_terms' => 'credit',
                'is_active' => true,
            ]);

            $customers[] = AccountingContact::create([
                'user_id' => $user->id,
                'type' => 'customer',
                'name' => 'บริษัท ดิจิทัล มาร์เก็ตติ้ง จำกัด',
                'name_eng' => 'Digital Marketing Co., Ltd.',
                'tax_id' => '0105558222222',
                'phone' => '02-345-6789',
                'email' => 'info@digitalmarketing.com',
                'address' => '789 ถนนสีลม',
                'district' => 'บางรัก',
                'province' => 'กรุงเทพมหานคร',
                'postal_code' => '10500',
                'country' => 'Thailand',
                'credit_limit' => 300000.00,
                'credit_days' => 30,
                'payment_terms' => 'credit',
                'is_active' => true,
            ]);

            $customers[] = AccountingContact::create([
                'user_id' => $user->id,
                'type' => 'customer',
                'name' => 'ห้างหุ้นส่วนจำกัด เอสเอ็มอี คอนซัลติ้ง',
                'email' => 'contact@smeconsult.com',
                'phone' => '02-456-7890',
                'address' => '321 ถนนเพชรบุรี',
                'district' => 'ราชเทวี',
                'province' => 'กรุงเทพมหานคร',
                'postal_code' => '10400',
                'country' => 'Thailand',
                'credit_days' => 15,
                'payment_terms' => 'cash',
                'is_active' => true,
            ]);

            // 7. Create Contacts (Vendors)
            $this->command->info('7. Creating contacts (vendors)...');
            $vendors = [];

            $vendors[] = AccountingContact::create([
                'user_id' => $user->id,
                'type' => 'vendor',
                'name' => 'บริษัท ออฟฟิศ ซัพพลาย จำกัด',
                'name_eng' => 'Office Supply Co., Ltd.',
                'tax_id' => '0105558333333',
                'phone' => '02-567-8901',
                'email' => 'sales@officesupply.com',
                'address' => '111 ถนนวิภาวดีรังสิต',
                'district' => 'จตุจักร',
                'province' => 'กรุงเทพมหานคร',
                'postal_code' => '10900',
                'country' => 'Thailand',
                'payment_terms' => 'credit',
                'credit_days' => 30,
                'is_active' => true,
            ]);

            $vendors[] = AccountingContact::create([
                'user_id' => $user->id,
                'type' => 'vendor',
                'name' => 'บริษัท โฮสติ้ง เซอร์วิส จำกัด',
                'email' => 'billing@hosting.com',
                'phone' => '02-678-9012',
                'payment_terms' => 'cash',
                'is_active' => true,
            ]);

            // 8. Create Products
            $this->command->info('8. Creating products...');
            $products = [];

            $products[] = AccountingProduct::create([
                'user_id' => $user->id,
                'type' => 'service',
                'name' => 'บริการพัฒนาเว็บไซต์',
                'name_eng' => 'Website Development Service',
                'description' => 'บริการออกแบบและพัฒนาเว็บไซต์ครบวงจร',
                'unit' => 'โครงการ',
                'price' => 50000.00,
                'cost' => 30000.00,
                'income_account_id' => $chartAccounts['service_revenue']->id,
                'is_taxable' => true,
                'tax_rate' => 7.00,
                'is_active' => true,
            ]);

            $products[] = AccountingProduct::create([
                'user_id' => $user->id,
                'type' => 'service',
                'name' => 'บริการ SEO',
                'name_eng' => 'SEO Service',
                'description' => 'บริการปรับแต่งเว็บไซต์ให้ติดอันดับ Google',
                'unit' => 'เดือน',
                'price' => 15000.00,
                'cost' => 8000.00,
                'income_account_id' => $chartAccounts['service_revenue']->id,
                'is_taxable' => true,
                'tax_rate' => 7.00,
                'is_active' => true,
            ]);

            $products[] = AccountingProduct::create([
                'user_id' => $user->id,
                'type' => 'service',
                'name' => 'บริการดูแลระบบ',
                'name_eng' => 'Maintenance Service',
                'description' => 'บริการดูแลและบำรุงรักษาเว็บไซต์รายเดือน',
                'unit' => 'เดือน',
                'price' => 5000.00,
                'cost' => 2000.00,
                'income_account_id' => $chartAccounts['service_revenue']->id,
                'is_taxable' => true,
                'tax_rate' => 7.00,
                'is_active' => true,
            ]);

            $products[] = AccountingProduct::create([
                'user_id' => $user->id,
                'type' => 'service',
                'name' => 'บริการให้คำปรึกษา',
                'name_eng' => 'Consulting Service',
                'description' => 'บริการให้คำปรึกษาด้าน Digital Marketing',
                'unit' => 'ชั่วโมง',
                'price' => 3000.00,
                'cost' => 1500.00,
                'income_account_id' => $chartAccounts['service_revenue']->id,
                'is_taxable' => true,
                'tax_rate' => 7.00,
                'is_active' => true,
            ]);

            $products[] = AccountingProduct::create([
                'user_id' => $user->id,
                'type' => 'product',
                'name' => 'ระบบจัดการเนื้อหา (CMS)',
                'name_eng' => 'Content Management System',
                'description' => 'ระบบจัดการเนื้อหาสำเร็จรูป',
                'unit' => 'ชุด',
                'price' => 80000.00,
                'cost' => 40000.00,
                'income_account_id' => $chartAccounts['product_sales']->id,
                'is_taxable' => true,
                'tax_rate' => 7.00,
                'is_active' => true,
            ]);

            // 9. Create Invoices
            $this->command->info('9. Creating invoices...');

            // Invoice 1 - Paid
            $invoice1 = AccountingInvoice::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'contact_id' => $customers[0]->id,
                'document_type' => 'tax_invoice',
                'document_number' => 'INV-' . now()->format('ym') . '-00001',
                'document_date' => now()->subDays(30),
                'due_date' => now()->subDays(0),
                'status' => 'paid',
                'note' => 'ขอบคุณที่ใช้บริการ',
                'terms' => 'ชำระภายใน 30 วัน',
                'discount_amount' => 0,
            ]);

            AccountingInvoiceItem::create([
                'invoice_id' => $invoice1->id,
                'product_id' => $products[0]->id,
                'description' => 'พัฒนาเว็บไซต์ E-Commerce',
                'quantity' => 1,
                'unit' => 'โครงการ',
                'unit_price' => 50000.00,
                'discount_amount' => 0,
                'tax_rate' => 7.00,
                'sort_order' => 0,
            ]);

            AccountingInvoiceItem::create([
                'invoice_id' => $invoice1->id,
                'product_id' => $products[2]->id,
                'description' => 'บริการดูแลระบบ 3 เดือน',
                'quantity' => 3,
                'unit' => 'เดือน',
                'unit_price' => 5000.00,
                'discount_amount' => 0,
                'tax_rate' => 7.00,
                'sort_order' => 1,
            ]);

            $invoice1->calculateTotals();
            $invoice1->save();

            // Create payment for invoice 1
            $payment1 = $invoice1->recordPayment($invoice1->total_amount, [
                'payment_date' => now()->subDays(25),
                'payment_method' => 'bank_transfer',
                'bank_account_id' => $bankAccount1->id,
                'reference' => 'TRF202411060001',
                'note' => 'โอนผ่านธนาคารกสิกรไทย',
            ]);

            // Invoice 2 - Pending
            $invoice2 = AccountingInvoice::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'contact_id' => $customers[1]->id,
                'document_type' => 'invoice',
                'document_number' => 'INV-' . now()->format('ym') . '-00002',
                'document_date' => now()->subDays(15),
                'due_date' => now()->addDays(15),
                'status' => 'pending',
                'note' => 'กรุณาชำระภายในกำหนด',
                'terms' => 'ชำระภายใน 30 วัน',
                'discount_amount' => 0,
            ]);

            AccountingInvoiceItem::create([
                'invoice_id' => $invoice2->id,
                'product_id' => $products[1]->id,
                'description' => 'บริการ SEO รายเดือน',
                'quantity' => 1,
                'unit' => 'เดือน',
                'unit_price' => 15000.00,
                'discount_amount' => 0,
                'tax_rate' => 7.00,
                'sort_order' => 0,
            ]);

            $invoice2->calculateTotals();
            $invoice2->save();

            // Invoice 3 - Partial Payment
            $invoice3 = AccountingInvoice::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'contact_id' => $customers[2]->id,
                'document_type' => 'tax_invoice',
                'document_number' => 'INV-' . now()->format('ym') . '-00003',
                'document_date' => now()->subDays(10),
                'due_date' => now()->addDays(5),
                'status' => 'partial',
                'discount_amount' => 5000,
            ]);

            AccountingInvoiceItem::create([
                'invoice_id' => $invoice3->id,
                'product_id' => $products[4]->id,
                'description' => 'ระบบจัดการเนื้อหา (CMS)',
                'quantity' => 1,
                'unit' => 'ชุด',
                'unit_price' => 80000.00,
                'discount_amount' => 0,
                'tax_rate' => 7.00,
                'sort_order' => 0,
            ]);

            AccountingInvoiceItem::create([
                'invoice_id' => $invoice3->id,
                'product_id' => $products[3]->id,
                'description' => 'ให้คำปรึกษาการใช้งาน',
                'quantity' => 5,
                'unit' => 'ชั่วโมง',
                'unit_price' => 3000.00,
                'discount_amount' => 0,
                'tax_rate' => 7.00,
                'sort_order' => 1,
            ]);

            $invoice3->calculateTotals();
            $invoice3->save();

            // Partial payment
            $payment3 = $invoice3->recordPayment(50000, [
                'payment_date' => now()->subDays(5),
                'payment_method' => 'cash',
                'reference' => 'CASH001',
                'note' => 'ชำระมัดจำ 50%',
            ]);

            // Invoice 4 - Overdue
            $invoice4 = AccountingInvoice::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'contact_id' => $customers[0]->id,
                'document_type' => 'invoice',
                'document_number' => 'INV-' . now()->format('ym') . '-00004',
                'document_date' => now()->subDays(50),
                'due_date' => now()->subDays(20),
                'status' => 'overdue',
            ]);

            AccountingInvoiceItem::create([
                'invoice_id' => $invoice4->id,
                'product_id' => $products[1]->id,
                'description' => 'บริการ SEO รายเดือน',
                'quantity' => 2,
                'unit' => 'เดือน',
                'unit_price' => 15000.00,
                'discount_amount' => 0,
                'tax_rate' => 7.00,
                'sort_order' => 0,
            ]);

            $invoice4->calculateTotals();
            $invoice4->save();

            // Invoice 5 - Draft
            $invoice5 = AccountingInvoice::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'contact_id' => $customers[1]->id,
                'document_type' => 'quotation',
                'document_number' => 'QUO-' . now()->format('ym') . '-00001',
                'document_date' => now(),
                'due_date' => now()->addDays(30),
                'status' => 'draft',
            ]);

            AccountingInvoiceItem::create([
                'invoice_id' => $invoice5->id,
                'product_id' => $products[0]->id,
                'description' => 'พัฒนาเว็บไซต์ Corporate',
                'quantity' => 1,
                'unit' => 'โครงการ',
                'unit_price' => 50000.00,
                'discount_amount' => 0,
                'tax_rate' => 7.00,
                'sort_order' => 0,
            ]);

            $invoice5->calculateTotals();
            $invoice5->save();

            // 10. Create Expenses
            $this->command->info('10. Creating expenses...');

            // Expense 1 - Paid
            $expense1 = AccountingExpense::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'contact_id' => $vendors[0]->id,
                'category_id' => $chartAccounts['office_supplies']->id,
                'document_type' => 'expense',
                'document_number' => 'EXP-' . now()->format('ym') . '-00001',
                'document_date' => now()->subDays(20),
                'status' => 'paid',
                'note' => 'ซื้อวัสดุสำนักงาน',
            ]);

            AccountingExpenseItem::create([
                'expense_id' => $expense1->id,
                'chart_account_id' => $chartAccounts['office_supplies']->id,
                'description' => 'กระดาษ A4, ปากกา, แฟ้มเอกสาร',
                'quantity' => 1,
                'unit' => 'ชุด',
                'unit_price' => 2500.00,
                'tax_rate' => 7.00,
                'sort_order' => 0,
            ]);

            $expense1->calculateTotals();
            $expense1->save();

            $expense1->recordPayment($expense1->total_amount, [
                'payment_date' => now()->subDays(20),
                'payment_method' => 'bank_transfer',
                'bank_account_id' => $bankAccount1->id,
                'reference' => 'TRF202411060002',
            ]);

            // Expense 2 - Paid
            $expense2 = AccountingExpense::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'contact_id' => $vendors[1]->id,
                'category_id' => $chartAccounts['utilities']->id,
                'document_type' => 'expense',
                'document_number' => 'EXP-' . now()->format('ym') . '-00002',
                'document_date' => now()->subDays(15),
                'status' => 'paid',
                'note' => 'ค่าบริการ Hosting รายเดือน',
            ]);

            AccountingExpenseItem::create([
                'expense_id' => $expense2->id,
                'chart_account_id' => $chartAccounts['utilities']->id,
                'description' => 'Hosting + Domain',
                'quantity' => 1,
                'unit' => 'เดือน',
                'unit_price' => 1500.00,
                'tax_rate' => 7.00,
                'sort_order' => 0,
            ]);

            $expense2->calculateTotals();
            $expense2->save();

            $expense2->recordPayment($expense2->total_amount, [
                'payment_date' => now()->subDays(15),
                'payment_method' => 'credit_card',
                'reference' => 'CC-20241106001',
            ]);

            // Expense 3 - Pending
            $expense3 = AccountingExpense::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'category_id' => $chartAccounts['salaries']->id,
                'document_type' => 'expense',
                'document_number' => 'EXP-' . now()->format('ym') . '-00003',
                'document_date' => now()->subDays(5),
                'status' => 'pending',
                'note' => 'เงินเดือนพนักงานประจำเดือน',
            ]);

            AccountingExpenseItem::create([
                'expense_id' => $expense3->id,
                'chart_account_id' => $chartAccounts['salaries']->id,
                'description' => 'เงินเดือนพนักงาน 3 คน',
                'quantity' => 3,
                'unit' => 'คน',
                'unit_price' => 25000.00,
                'tax_rate' => 0.00,
                'sort_order' => 0,
            ]);

            $expense3->calculateTotals();
            $expense3->save();

            // Expense 4 - Paid
            $expense4 = AccountingExpense::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'category_id' => $chartAccounts['marketing']->id,
                'document_type' => 'expense',
                'document_number' => 'EXP-' . now()->format('ym') . '-00004',
                'document_date' => now()->subDays(10),
                'status' => 'paid',
                'note' => 'ค่าโฆษณา Google Ads',
            ]);

            AccountingExpenseItem::create([
                'expense_id' => $expense4->id,
                'chart_account_id' => $chartAccounts['marketing']->id,
                'description' => 'Google Ads Campaign',
                'quantity' => 1,
                'unit' => 'เดือน',
                'unit_price' => 10000.00,
                'tax_rate' => 7.00,
                'sort_order' => 0,
            ]);

            $expense4->calculateTotals();
            $expense4->save();

            $expense4->recordPayment($expense4->total_amount, [
                'payment_date' => now()->subDays(10),
                'payment_method' => 'bank_transfer',
                'bank_account_id' => $bankAccount1->id,
                'reference' => 'TRF202411060003',
            ]);

            DB::commit();

            $this->command->info('✅ Demo data created successfully!');
            $this->command->info('');
            $this->command->info('Summary:');
            $this->command->info('- 1 Company');
            $this->command->info('- ' . count($chartAccounts) . ' Chart of Accounts');
            $this->command->info('- 2 Bank Accounts');
            $this->command->info('- 2 Tax Rates');
            $this->command->info('- ' . count($customers) . ' Customers');
            $this->command->info('- ' . count($vendors) . ' Vendors');
            $this->command->info('- ' . count($products) . ' Products/Services');
            $this->command->info('- 5 Invoices (1 Paid, 1 Pending, 1 Partial, 1 Overdue, 1 Draft)');
            $this->command->info('- 4 Expenses (3 Paid, 1 Pending)');
            $this->command->info('- 4 Payments');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error: ' . $e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }

    /**
     * Create Chart of Accounts
     */
    private function createChartOfAccounts($user): array
    {
        $accounts = [];

        // Assets
        $accounts['cash_in_bank'] = AccountingChartOfAccount::create([
            'user_id' => $user->id,
            'code' => '1120',
            'name' => 'เงินฝากธนาคาร',
            'name_eng' => 'Cash in Bank',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'level' => 1,
            'is_system' => true,
            'is_active' => true,
            'opening_balance' => 800000.00,
            'current_balance' => 800000.00,
        ]);

        $accounts['accounts_receivable'] = AccountingChartOfAccount::create([
            'user_id' => $user->id,
            'code' => '1130',
            'name' => 'ลูกหนี้การค้า',
            'name_eng' => 'Accounts Receivable',
            'type' => 'asset',
            'sub_type' => 'current_asset',
            'level' => 1,
            'is_system' => true,
            'is_active' => true,
        ]);

        // Liabilities
        $accounts['accounts_payable'] = AccountingChartOfAccount::create([
            'user_id' => $user->id,
            'code' => '2110',
            'name' => 'เจ้าหนี้การค้า',
            'name_eng' => 'Accounts Payable',
            'type' => 'liability',
            'sub_type' => 'current_liability',
            'level' => 1,
            'is_system' => true,
            'is_active' => true,
        ]);

        $accounts['output_vat'] = AccountingChartOfAccount::create([
            'user_id' => $user->id,
            'code' => '2130',
            'name' => 'ภาษีขาย',
            'name_eng' => 'Output VAT',
            'type' => 'liability',
            'sub_type' => 'current_liability',
            'level' => 1,
            'is_system' => true,
            'is_active' => true,
        ]);

        // Equity
        $accounts['retained_earnings'] = AccountingChartOfAccount::create([
            'user_id' => $user->id,
            'code' => '3200',
            'name' => 'กำไร (ขาดทุน) สะสม',
            'name_eng' => 'Retained Earnings',
            'type' => 'equity',
            'sub_type' => 'owner_equity',
            'level' => 1,
            'is_system' => true,
            'is_active' => true,
        ]);

        // Revenue
        $accounts['product_sales'] = AccountingChartOfAccount::create([
            'user_id' => $user->id,
            'code' => '4110',
            'name' => 'รายได้จากการขายสินค้า',
            'name_eng' => 'Product Sales',
            'type' => 'revenue',
            'sub_type' => 'revenue',
            'level' => 1,
            'is_system' => true,
            'is_active' => true,
        ]);

        $accounts['service_revenue'] = AccountingChartOfAccount::create([
            'user_id' => $user->id,
            'code' => '4120',
            'name' => 'รายได้จากการให้บริการ',
            'name_eng' => 'Service Revenue',
            'type' => 'revenue',
            'sub_type' => 'revenue',
            'level' => 1,
            'is_system' => true,
            'is_active' => true,
        ]);

        // Expenses
        $accounts['cost_of_goods'] = AccountingChartOfAccount::create([
            'user_id' => $user->id,
            'code' => '5110',
            'name' => 'ต้นทุนสินค้า',
            'name_eng' => 'Cost of Goods',
            'type' => 'expense',
            'sub_type' => 'cost_of_goods_sold',
            'level' => 1,
            'is_system' => true,
            'is_active' => true,
        ]);

        $accounts['salaries'] = AccountingChartOfAccount::create([
            'user_id' => $user->id,
            'code' => '5210',
            'name' => 'เงินเดือนและค่าจ้าง',
            'name_eng' => 'Salaries and Wages',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'level' => 1,
            'is_system' => true,
            'is_active' => true,
        ]);

        $accounts['rent'] = AccountingChartOfAccount::create([
            'user_id' => $user->id,
            'code' => '5220',
            'name' => 'ค่าเช่า',
            'name_eng' => 'Rent Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'level' => 1,
            'is_system' => true,
            'is_active' => true,
        ]);

        $accounts['utilities'] = AccountingChartOfAccount::create([
            'user_id' => $user->id,
            'code' => '5230',
            'name' => 'ค่าน้ำ ค่าไฟ',
            'name_eng' => 'Utilities Expense',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'level' => 1,
            'is_system' => true,
            'is_active' => true,
        ]);

        $accounts['office_supplies'] = AccountingChartOfAccount::create([
            'user_id' => $user->id,
            'code' => '5260',
            'name' => 'ค่าวัสดุสำนักงาน',
            'name_eng' => 'Office Supplies',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'level' => 1,
            'is_system' => true,
            'is_active' => true,
        ]);

        $accounts['marketing'] = AccountingChartOfAccount::create([
            'user_id' => $user->id,
            'code' => '5280',
            'name' => 'ค่าการตลาดและโฆษณา',
            'name_eng' => 'Marketing and Advertising',
            'type' => 'expense',
            'sub_type' => 'operating_expense',
            'level' => 1,
            'is_system' => true,
            'is_active' => true,
        ]);

        return $accounts;
    }
}
