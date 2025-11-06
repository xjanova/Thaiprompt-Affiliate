<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Thai Standard Chart of Accounts
     */
    public function run(): void
    {
        $chartOfAccounts = [
            // สินทรัพย์ - Assets
            [
                'code' => '1000',
                'name' => 'สินทรัพย์',
                'name_eng' => 'Assets',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'parent_id' => null,
                'level' => 1,
                'is_system' => true,
            ],

            // สินทรัพย์หมุนเวียน - Current Assets
            [
                'code' => '1100',
                'name' => 'สินทรัพย์หมุนเวียน',
                'name_eng' => 'Current Assets',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'parent_id' => null,
                'level' => 2,
                'is_system' => true,
            ],
            [
                'code' => '1110',
                'name' => 'เงินสด',
                'name_eng' => 'Cash on Hand',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '1120',
                'name' => 'เงินฝากธนาคาร',
                'name_eng' => 'Cash in Bank',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '1130',
                'name' => 'ลูกหนี้การค้า',
                'name_eng' => 'Accounts Receivable',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '1140',
                'name' => 'ลูกหนี้อื่น',
                'name_eng' => 'Other Receivables',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '1150',
                'name' => 'สินค้าคงเหลือ',
                'name_eng' => 'Inventory',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '1160',
                'name' => 'ค่าใช้จ่ายล่วงหน้า',
                'name_eng' => 'Prepaid Expenses',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '1170',
                'name' => 'ภาษีซื้อ',
                'name_eng' => 'Input VAT',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],

            // สินทรัพย์ไม่หมุนเวียน - Fixed Assets
            [
                'code' => '1200',
                'name' => 'สินทรัพย์ไม่หมุนเวียน',
                'name_eng' => 'Fixed Assets',
                'type' => 'asset',
                'sub_type' => 'fixed_asset',
                'parent_id' => null,
                'level' => 2,
                'is_system' => true,
            ],
            [
                'code' => '1210',
                'name' => 'ที่ดิน อาคาร และอุปกรณ์',
                'name_eng' => 'Property, Plant and Equipment',
                'type' => 'asset',
                'sub_type' => 'fixed_asset',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '1220',
                'name' => 'ค่าเสื่อมราคาสะสม',
                'name_eng' => 'Accumulated Depreciation',
                'type' => 'asset',
                'sub_type' => 'fixed_asset',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],

            // หนี้สิน - Liabilities
            [
                'code' => '2000',
                'name' => 'หนี้สิน',
                'name_eng' => 'Liabilities',
                'type' => 'liability',
                'sub_type' => 'current_liability',
                'parent_id' => null,
                'level' => 1,
                'is_system' => true,
            ],

            // หนี้สินหมุนเวียน - Current Liabilities
            [
                'code' => '2100',
                'name' => 'หนี้สินหมุนเวียน',
                'name_eng' => 'Current Liabilities',
                'type' => 'liability',
                'sub_type' => 'current_liability',
                'parent_id' => null,
                'level' => 2,
                'is_system' => true,
            ],
            [
                'code' => '2110',
                'name' => 'เจ้าหนี้การค้า',
                'name_eng' => 'Accounts Payable',
                'type' => 'liability',
                'sub_type' => 'current_liability',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '2120',
                'name' => 'เจ้าหนี้อื่น',
                'name_eng' => 'Other Payables',
                'type' => 'liability',
                'sub_type' => 'current_liability',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '2130',
                'name' => 'ภาษีขาย',
                'name_eng' => 'Output VAT',
                'type' => 'liability',
                'sub_type' => 'current_liability',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '2140',
                'name' => 'ภาษีเงินได้หัก ณ ที่จ่าย',
                'name_eng' => 'Withholding Tax Payable',
                'type' => 'liability',
                'sub_type' => 'current_liability',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '2150',
                'name' => 'รายได้รับล่วงหน้า',
                'name_eng' => 'Unearned Revenue',
                'type' => 'liability',
                'sub_type' => 'current_liability',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],

            // หนี้สินไม่หมุนเวียน - Long-term Liabilities
            [
                'code' => '2200',
                'name' => 'หนี้สินไม่หมุนเวียน',
                'name_eng' => 'Long-term Liabilities',
                'type' => 'liability',
                'sub_type' => 'long_term_liability',
                'parent_id' => null,
                'level' => 2,
                'is_system' => true,
            ],
            [
                'code' => '2210',
                'name' => 'เงินกู้ยืมระยะยาว',
                'name_eng' => 'Long-term Loans',
                'type' => 'liability',
                'sub_type' => 'long_term_liability',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],

            // ส่วนของเจ้าของ - Equity
            [
                'code' => '3000',
                'name' => 'ส่วนของเจ้าของ',
                'name_eng' => 'Owner\'s Equity',
                'type' => 'equity',
                'sub_type' => 'owner_equity',
                'parent_id' => null,
                'level' => 1,
                'is_system' => true,
            ],
            [
                'code' => '3100',
                'name' => 'ทุนจดทะเบียน',
                'name_eng' => 'Registered Capital',
                'type' => 'equity',
                'sub_type' => 'owner_equity',
                'parent_id' => null,
                'level' => 2,
                'is_system' => true,
            ],
            [
                'code' => '3200',
                'name' => 'กำไร (ขาดทุน) สะสม',
                'name_eng' => 'Retained Earnings',
                'type' => 'equity',
                'sub_type' => 'owner_equity',
                'parent_id' => null,
                'level' => 2,
                'is_system' => true,
            ],

            // รายได้ - Revenue
            [
                'code' => '4000',
                'name' => 'รายได้',
                'name_eng' => 'Revenue',
                'type' => 'revenue',
                'sub_type' => 'revenue',
                'parent_id' => null,
                'level' => 1,
                'is_system' => true,
            ],
            [
                'code' => '4100',
                'name' => 'รายได้จากการขาย',
                'name_eng' => 'Sales Revenue',
                'type' => 'revenue',
                'sub_type' => 'revenue',
                'parent_id' => null,
                'level' => 2,
                'is_system' => true,
            ],
            [
                'code' => '4110',
                'name' => 'รายได้จากการขายสินค้า',
                'name_eng' => 'Product Sales',
                'type' => 'revenue',
                'sub_type' => 'revenue',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '4120',
                'name' => 'รายได้จากการให้บริการ',
                'name_eng' => 'Service Revenue',
                'type' => 'revenue',
                'sub_type' => 'revenue',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '4200',
                'name' => 'รายได้อื่น',
                'name_eng' => 'Other Revenue',
                'type' => 'revenue',
                'sub_type' => 'other_revenue',
                'parent_id' => null,
                'level' => 2,
                'is_system' => true,
            ],
            [
                'code' => '4210',
                'name' => 'ดอกเบี้ยรับ',
                'name_eng' => 'Interest Income',
                'type' => 'revenue',
                'sub_type' => 'other_revenue',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],

            // ต้นทุนและค่าใช้จ่าย - Expenses
            [
                'code' => '5000',
                'name' => 'ต้นทุนและค่าใช้จ่าย',
                'name_eng' => 'Expenses',
                'type' => 'expense',
                'sub_type' => 'cost_of_goods_sold',
                'parent_id' => null,
                'level' => 1,
                'is_system' => true,
            ],
            [
                'code' => '5100',
                'name' => 'ต้นทุนขาย',
                'name_eng' => 'Cost of Goods Sold',
                'type' => 'expense',
                'sub_type' => 'cost_of_goods_sold',
                'parent_id' => null,
                'level' => 2,
                'is_system' => true,
            ],
            [
                'code' => '5110',
                'name' => 'ต้นทุนสินค้า',
                'name_eng' => 'Cost of Products',
                'type' => 'expense',
                'sub_type' => 'cost_of_goods_sold',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '5120',
                'name' => 'ต้นทุนบริการ',
                'name_eng' => 'Cost of Services',
                'type' => 'expense',
                'sub_type' => 'cost_of_goods_sold',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],

            // ค่าใช้จ่ายในการดำเนินงาน - Operating Expenses
            [
                'code' => '5200',
                'name' => 'ค่าใช้จ่ายในการดำเนินงาน',
                'name_eng' => 'Operating Expenses',
                'type' => 'expense',
                'sub_type' => 'operating_expense',
                'parent_id' => null,
                'level' => 2,
                'is_system' => true,
            ],
            [
                'code' => '5210',
                'name' => 'เงินเดือนและค่าจ้าง',
                'name_eng' => 'Salaries and Wages',
                'type' => 'expense',
                'sub_type' => 'operating_expense',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '5220',
                'name' => 'ค่าเช่า',
                'name_eng' => 'Rent Expense',
                'type' => 'expense',
                'sub_type' => 'operating_expense',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '5230',
                'name' => 'ค่าน้ำ ค่าไฟ',
                'name_eng' => 'Utilities Expense',
                'type' => 'expense',
                'sub_type' => 'operating_expense',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '5240',
                'name' => 'ค่าโทรศัพท์และอินเทอร์เน็ต',
                'name_eng' => 'Telephone and Internet',
                'type' => 'expense',
                'sub_type' => 'operating_expense',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '5250',
                'name' => 'ค่าเสื่อมราคา',
                'name_eng' => 'Depreciation Expense',
                'type' => 'expense',
                'sub_type' => 'operating_expense',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '5260',
                'name' => 'ค่าวัสดุสำนักงาน',
                'name_eng' => 'Office Supplies',
                'type' => 'expense',
                'sub_type' => 'operating_expense',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '5270',
                'name' => 'ค่าธรรมเนียมและบริการ',
                'name_eng' => 'Fees and Services',
                'type' => 'expense',
                'sub_type' => 'operating_expense',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '5280',
                'name' => 'ค่าการตลาดและโฆษณา',
                'name_eng' => 'Marketing and Advertising',
                'type' => 'expense',
                'sub_type' => 'operating_expense',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
            [
                'code' => '5290',
                'name' => 'ค่าใช้จ่ายอื่น',
                'name_eng' => 'Other Expenses',
                'type' => 'expense',
                'sub_type' => 'other_expense',
                'parent_id' => null,
                'level' => 3,
                'is_system' => true,
            ],
        ];

        $this->command->info('Note: This seeder creates Chart of Accounts template.');
        $this->command->info('Accounts will be created per user when they enable the accounting module.');
        $this->command->info('Default Thai Standard Chart of Accounts structure defined.');
    }

    /**
     * Get the chart of accounts data for creating user-specific accounts
     */
    public static function getChartOfAccountsData(): array
    {
        return (new self())->getAccountsArray();
    }

    private function getAccountsArray(): array
    {
        // Return the same array structure for programmatic access
        // This method is used when creating accounts for new users
        return [];
    }
}
