<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Carbon\Carbon;

class ExpensesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get category IDs
        $vendorPayments = ExpenseCategory::where('name', 'Vendor Payments')->first();
        $salaries = ExpenseCategory::where('name', 'Employee Salaries')->first();
        $utilities = ExpenseCategory::where('name', 'Utilities')->first();
        $logistics = ExpenseCategory::where('name', 'Logistics')->first();
        $marketing = ExpenseCategory::where('name', 'Marketing')->first();
        $maintenance = ExpenseCategory::where('name', 'Maintenance')->first();
        $insurance = ExpenseCategory::where('name', 'Insurance')->first();
        $supplies = ExpenseCategory::where('name', 'Supplies')->first();
        $travel = ExpenseCategory::where('name', 'Travel')->first();
        $training = ExpenseCategory::where('name', 'Training')->first();
        $software = ExpenseCategory::where('name', 'Software Licenses')->first();
        $bankCharges = ExpenseCategory::where('name', 'Bank Charges')->first();
        $miscellaneous = ExpenseCategory::where('name', 'Miscellaneous')->first();

        $expenses = [
            // One-time expenses
            [
                'expense_category_id' => $vendorPayments->id,
                'title' => 'Raw Material Purchase - Q4 2024',
                'description' => 'Bulk purchase of raw materials for production',
                'amount' => 45000.00,
                'status' => 'approved',
                'expense_date' => Carbon::now()->subDays(5),
                'due_date' => Carbon::now()->addDays(10),
                'is_recurring' => false,
                'recurrence_type' => null,
                'recurrence_interval' => null,
                'next_recurrence_date' => null,
                'approved_by' => 1, // Assuming admin user ID
                'approved_at' => Carbon::now()->subDays(3),
                'metadata' => [
                    'vendor_name' => 'ABC Suppliers Ltd',
                    'invoice_number' => 'INV-2024-001',
                    'payment_terms' => 'Net 30 days'
                ],
            ],
            [
                'expense_category_id' => $utilities->id,
                'title' => 'Electricity Bill - October 2024',
                'description' => 'Monthly electricity consumption for office and warehouse',
                'amount' => 12500.00,
                'status' => 'paid',
                'expense_date' => Carbon::now()->subDays(15),
                'due_date' => Carbon::now()->subDays(5),
                'is_recurring' => true,
                'recurrence_type' => 'monthly',
                'recurrence_interval' => 1,
                'next_recurrence_date' => Carbon::now()->addDays(15),
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(12),
                'metadata' => [
                    'utility_provider' => 'Dhaka Electric Supply Company',
                    'account_number' => 'DESCO-123456',
                    'billing_period' => 'October 1-31, 2024'
                ],
            ],
            [
                'expense_category_id' => $marketing->id,
                'title' => 'Social Media Advertising Campaign',
                'description' => 'Facebook and Instagram ads for product promotion',
                'amount' => 25000.00,
                'status' => 'pending_approval',
                'expense_date' => Carbon::now(),
                'due_date' => Carbon::now()->addDays(7),
                'is_recurring' => false,
                'recurrence_type' => null,
                'recurrence_interval' => null,
                'next_recurrence_date' => null,
                'approved_by' => null,
                'approved_at' => null,
                'metadata' => [
                    'campaign_name' => 'Winter Collection Launch',
                    'platforms' => ['Facebook', 'Instagram'],
                    'target_audience' => '18-35 years, Dhaka region'
                ],
            ],
            [
                'expense_category_id' => $salaries->id,
                'title' => 'Monthly Salary Payment - October 2024',
                'description' => 'Salary payment for all employees',
                'amount' => 85000.00,
                'status' => 'approved',
                'expense_date' => Carbon::now()->subDays(1),
                'due_date' => Carbon::now()->addDays(5),
                'is_recurring' => true,
                'recurrence_type' => 'monthly',
                'recurrence_interval' => 1,
                'next_recurrence_date' => Carbon::now()->addDays(29),
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(1),
                'metadata' => [
                    'payroll_period' => 'October 1-31, 2024',
                    'number_of_employees' => 15,
                    'payment_method' => 'Bank Transfer'
                ],
            ],
            [
                'expense_category_id' => $logistics->id,
                'title' => 'Courier Service Charges',
                'description' => 'Shipping charges for customer orders',
                'amount' => 8500.00,
                'status' => 'paid',
                'expense_date' => Carbon::now()->subDays(10),
                'due_date' => Carbon::now()->subDays(2),
                'is_recurring' => false,
                'recurrence_type' => null,
                'recurrence_interval' => null,
                'next_recurrence_date' => null,
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(8),
                'metadata' => [
                    'courier_service' => 'Pathao Courier',
                    'number_of_shipments' => 45,
                    'service_type' => 'Express Delivery'
                ],
            ],
            [
                'expense_category_id' => $maintenance->id,
                'title' => 'Equipment Maintenance',
                'description' => 'Quarterly maintenance of production equipment',
                'amount' => 18000.00,
                'status' => 'approved',
                'expense_date' => Carbon::now()->subDays(20),
                'due_date' => Carbon::now()->addDays(10),
                'is_recurring' => true,
                'recurrence_type' => 'quarterly',
                'recurrence_interval' => 3,
                'next_recurrence_date' => Carbon::now()->addMonths(3),
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(18),
                'metadata' => [
                    'maintenance_type' => 'Preventive Maintenance',
                    'equipment_list' => ['Sewing Machines', 'Packaging Equipment'],
                    'service_provider' => 'TechMaintenance Ltd'
                ],
            ],
            [
                'expense_category_id' => $insurance->id,
                'title' => 'Business Insurance Premium',
                'description' => 'Annual insurance premium for property and liability',
                'amount' => 35000.00,
                'status' => 'paid',
                'expense_date' => Carbon::now()->subDays(30),
                'due_date' => Carbon::now()->subDays(15),
                'is_recurring' => true,
                'recurrence_type' => 'yearly',
                'recurrence_interval' => 12,
                'next_recurrence_date' => Carbon::now()->addMonths(12),
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(25),
                'metadata' => [
                    'insurance_type' => 'Comprehensive Business Insurance',
                    'coverage_period' => 'November 2024 - October 2025',
                    'insurance_provider' => 'Green Delta Insurance'
                ],
            ],
            [
                'expense_category_id' => $supplies->id,
                'title' => 'Office Supplies Purchase',
                'description' => 'Stationery and office consumables',
                'amount' => 3200.00,
                'status' => 'approved',
                'expense_date' => Carbon::now()->subDays(7),
                'due_date' => Carbon::now()->addDays(3),
                'is_recurring' => false,
                'recurrence_type' => null,
                'recurrence_interval' => null,
                'next_recurrence_date' => null,
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(5),
                'metadata' => [
                    'supplier' => 'Office Depot Bangladesh',
                    'items' => ['Printer Paper', 'Pens', 'Notebooks', 'Cleaning Supplies']
                ],
            ],
            [
                'expense_category_id' => $travel->id,
                'title' => 'Business Trip to Chittagong',
                'description' => 'Travel expenses for supplier meeting and market research',
                'amount' => 15000.00,
                'status' => 'pending_approval',
                'expense_date' => Carbon::now(),
                'due_date' => Carbon::now()->addDays(14),
                'is_recurring' => false,
                'recurrence_type' => null,
                'recurrence_interval' => null,
                'next_recurrence_date' => null,
                'approved_by' => null,
                'approved_at' => null,
                'metadata' => [
                    'destination' => 'Chittagong',
                    'purpose' => 'Supplier meeting and market research',
                    'travelers' => ['John Doe (Sales Manager)', 'Jane Smith (Procurement Officer)'],
                    'duration' => '3 days'
                ],
            ],
            [
                'expense_category_id' => $training->id,
                'title' => 'Employee Training Program',
                'description' => 'Digital marketing training for sales team',
                'amount' => 22000.00,
                'status' => 'approved',
                'expense_date' => Carbon::now()->subDays(12),
                'due_date' => Carbon::now()->addDays(18),
                'is_recurring' => false,
                'recurrence_type' => null,
                'recurrence_interval' => null,
                'next_recurrence_date' => null,
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(10),
                'metadata' => [
                    'training_provider' => 'Digital Marketing Institute',
                    'participants' => 8,
                    'training_topic' => 'Social Media Marketing and E-commerce',
                    'duration' => '2 days'
                ],
            ],
            [
                'expense_category_id' => $software->id,
                'title' => 'ERP Software License Renewal',
                'description' => 'Annual license renewal for ERP system',
                'amount' => 12000.00,
                'status' => 'paid',
                'expense_date' => Carbon::now()->subDays(25),
                'due_date' => Carbon::now()->subDays(10),
                'is_recurring' => true,
                'recurrence_type' => 'yearly',
                'recurrence_interval' => 12,
                'next_recurrence_date' => Carbon::now()->addMonths(12),
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(20),
                'metadata' => [
                    'software_name' => 'Enterprise Resource Planning System',
                    'license_type' => 'Annual Subscription',
                    'validity_period' => 'November 2024 - October 2025'
                ],
            ],
            [
                'expense_category_id' => $bankCharges->id,
                'title' => 'Monthly Bank Service Charges',
                'description' => 'Account maintenance and transaction fees',
                'amount' => 1500.00,
                'status' => 'paid',
                'expense_date' => Carbon::now()->subDays(8),
                'due_date' => Carbon::now()->subDays(1),
                'is_recurring' => true,
                'recurrence_type' => 'monthly',
                'recurrence_interval' => 1,
                'next_recurrence_date' => Carbon::now()->addDays(22),
                'approved_by' => 1,
                'approved_at' => Carbon::now()->subDays(6),
                'metadata' => [
                    'bank_name' => 'Dutch-Bangla Bank Ltd',
                    'account_type' => 'Business Current Account',
                    'fee_breakdown' => [
                        'Account maintenance' => 500.00,
                        'Online banking' => 300.00,
                        'Transaction fees' => 700.00
                    ]
                ],
            ],
            [
                'expense_category_id' => $miscellaneous->id,
                'title' => 'Office Renovation',
                'description' => 'Minor office renovation and decoration',
                'amount' => 28000.00,
                'status' => 'pending_approval',
                'expense_date' => Carbon::now(),
                'due_date' => Carbon::now()->addDays(21),
                'is_recurring' => false,
                'recurrence_type' => null,
                'recurrence_interval' => null,
                'next_recurrence_date' => null,
                'approved_by' => null,
                'approved_at' => null,
                'metadata' => [
                    'renovation_type' => 'Office decoration and minor repairs',
                    'contractor' => 'Home Decor Ltd',
                    'estimated_completion' => '2 weeks'
                ],
            ],
        ];

        foreach ($expenses as $expenseData) {
            Expense::create($expenseData);
        }
    }
}
