<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ExpenseCategory;

class ExpenseCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Operating Expenses
            [
                'name' => 'Vendor Payments',
                'description' => 'Payments to suppliers and vendors for goods and services',
                'type' => 'operating',
                'parent_id' => null,
                'budget_limit' => null,
                'approval_threshold' => 50000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Employee Salaries',
                'description' => 'Monthly salary payments to employees',
                'type' => 'operating',
                'parent_id' => null,
                'budget_limit' => null,
                'approval_threshold' => 100000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Utilities',
                'description' => 'Electricity, water, gas, and other utility bills',
                'type' => 'operating',
                'parent_id' => null,
                'budget_limit' => 50000.00,
                'approval_threshold' => 25000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Logistics',
                'description' => 'Shipping, transportation, and delivery costs',
                'type' => 'operating',
                'parent_id' => null,
                'budget_limit' => 75000.00,
                'approval_threshold' => 30000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Maintenance',
                'description' => 'Equipment and facility maintenance costs',
                'type' => 'operating',
                'parent_id' => null,
                'budget_limit' => 25000.00,
                'approval_threshold' => 15000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Marketing',
                'description' => 'Advertising, promotions, and marketing expenses',
                'type' => 'operating',
                'parent_id' => null,
                'budget_limit' => 100000.00,
                'approval_threshold' => 50000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Insurance',
                'description' => 'Business insurance premiums',
                'type' => 'operating',
                'parent_id' => null,
                'budget_limit' => 30000.00,
                'approval_threshold' => 20000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Taxes',
                'description' => 'Business taxes and related fees',
                'type' => 'operating',
                'parent_id' => null,
                'budget_limit' => null,
                'approval_threshold' => 50000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Supplies',
                'description' => 'Office supplies and consumables',
                'type' => 'operating',
                'parent_id' => null,
                'budget_limit' => 15000.00,
                'approval_threshold' => 5000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Travel',
                'description' => 'Business travel and accommodation expenses',
                'type' => 'operating',
                'parent_id' => null,
                'budget_limit' => 50000.00,
                'approval_threshold' => 25000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Training',
                'description' => 'Employee training and development costs',
                'type' => 'operating',
                'parent_id' => null,
                'budget_limit' => 30000.00,
                'approval_threshold' => 15000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Software Licenses',
                'description' => 'Software subscriptions and licensing fees',
                'type' => 'operating',
                'parent_id' => null,
                'budget_limit' => 25000.00,
                'approval_threshold' => 10000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Bank Charges',
                'description' => 'Bank fees, transaction charges, and service fees',
                'type' => 'operating',
                'parent_id' => null,
                'budget_limit' => 5000.00,
                'approval_threshold' => 2000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Depreciation',
                'description' => 'Asset depreciation expenses',
                'type' => 'operating',
                'parent_id' => null,
                'budget_limit' => null,
                'approval_threshold' => 50000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Miscellaneous',
                'description' => 'Other operating expenses not categorized elsewhere',
                'type' => 'operating',
                'parent_id' => null,
                'budget_limit' => 20000.00,
                'approval_threshold' => 10000.00,
                'is_active' => true,
            ],

            // Sub-categories for Vendor Payments
            [
                'name' => 'Raw Materials',
                'description' => 'Payment for raw materials and components',
                'type' => 'operating',
                'parent_id' => 1, // Will be set after creation
                'budget_limit' => null,
                'approval_threshold' => 30000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Packaging',
                'description' => 'Payment for packaging materials',
                'type' => 'operating',
                'parent_id' => 1,
                'budget_limit' => 20000.00,
                'approval_threshold' => 15000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Services',
                'description' => 'Payment for professional services',
                'type' => 'operating',
                'parent_id' => 1,
                'budget_limit' => 50000.00,
                'approval_threshold' => 25000.00,
                'is_active' => true,
            ],

            // Sub-categories for Marketing
            [
                'name' => 'Digital Marketing',
                'description' => 'Online advertising and digital marketing expenses',
                'type' => 'operating',
                'parent_id' => 6,
                'budget_limit' => 40000.00,
                'approval_threshold' => 20000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Print Media',
                'description' => 'Newspaper, magazine, and print advertising',
                'type' => 'operating',
                'parent_id' => 6,
                'budget_limit' => 30000.00,
                'approval_threshold' => 15000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Events',
                'description' => 'Trade shows, exhibitions, and promotional events',
                'type' => 'operating',
                'parent_id' => 6,
                'budget_limit' => 30000.00,
                'approval_threshold' => 15000.00,
                'is_active' => true,
            ],
        ];

        // Create categories with proper parent relationships
        $createdCategories = [];
        foreach ($categories as $categoryData) {
            $parentId = $categoryData['parent_id'];
            if ($parentId && isset($createdCategories[$parentId - 1])) {
                $categoryData['parent_id'] = $createdCategories[$parentId - 1]->id;
            } elseif ($parentId) {
                // Skip sub-categories if parent not found yet
                continue;
            }

            $category = ExpenseCategory::create($categoryData);
            $createdCategories[] = $category;
        }

        // Create sub-categories that were skipped
        $subCategories = [
            [
                'name' => 'Raw Materials',
                'description' => 'Payment for raw materials and components',
                'type' => 'operating',
                'parent_id' => ExpenseCategory::where('name', 'Vendor Payments')->first()->id,
                'budget_limit' => null,
                'approval_threshold' => 30000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Packaging',
                'description' => 'Payment for packaging materials',
                'type' => 'operating',
                'parent_id' => ExpenseCategory::where('name', 'Vendor Payments')->first()->id,
                'budget_limit' => 20000.00,
                'approval_threshold' => 15000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Services',
                'description' => 'Payment for professional services',
                'type' => 'operating',
                'parent_id' => ExpenseCategory::where('name', 'Vendor Payments')->first()->id,
                'budget_limit' => 50000.00,
                'approval_threshold' => 25000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Digital Marketing',
                'description' => 'Online advertising and digital marketing expenses',
                'type' => 'operating',
                'parent_id' => ExpenseCategory::where('name', 'Marketing')->first()->id,
                'budget_limit' => 40000.00,
                'approval_threshold' => 20000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Print Media',
                'description' => 'Newspaper, magazine, and print advertising',
                'type' => 'operating',
                'parent_id' => ExpenseCategory::where('name', 'Marketing')->first()->id,
                'budget_limit' => 30000.00,
                'approval_threshold' => 15000.00,
                'is_active' => true,
            ],
            [
                'name' => 'Events',
                'description' => 'Trade shows, exhibitions, and promotional events',
                'type' => 'operating',
                'parent_id' => ExpenseCategory::where('name', 'Marketing')->first()->id,
                'budget_limit' => 30000.00,
                'approval_threshold' => 15000.00,
                'is_active' => true,
            ],
        ];

        foreach ($subCategories as $subCategoryData) {
            ExpenseCategory::create($subCategoryData);
        }
    }
}
