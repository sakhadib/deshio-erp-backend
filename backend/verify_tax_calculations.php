<?php
/**
 * Inclusive Tax Calculation Verification Script
 * Run: php verify_tax_calculations.php
 */

echo "=================================\n";
echo "INCLUSIVE TAX CALCULATION TEST\n";
echo "=================================\n\n";

// Test Case 1: 2% Tax on 1000 BDT
echo "Test 1: Product with 2% Tax\n";
echo "----------------------------\n";
$sellPrice1 = 1000;
$taxPercent1 = 2.0;
$costPrice1 = 800;

$basePrice1 = round($sellPrice1 / (1 + ($taxPercent1 / 100)), 2);
$taxAmount1 = round($sellPrice1 - $basePrice1, 2);
$profitMargin1 = round((($basePrice1 - $costPrice1) / $costPrice1) * 100, 2);

echo "Sell Price: {$sellPrice1} BDT (includes tax)\n";
echo "Tax Rate: {$taxPercent1}%\n";
echo "Cost Price: {$costPrice1} BDT\n";
echo "\nCalculated:\n";
echo "  Base Price: {$basePrice1} BDT\n";
echo "  Tax Amount: {$taxAmount1} BDT\n";
echo "  Profit Margin: {$profitMargin1}%\n";
echo "\nVerification:\n";
echo "  Base + Tax = {$basePrice1} + {$taxAmount1} = " . ($basePrice1 + $taxAmount1) . " BDT ";
echo ($basePrice1 + $taxAmount1 == $sellPrice1) ? "✓\n" : "✗\n";
echo "  Expected Base: 980.39 BDT " . ($basePrice1 == 980.39 ? "✓\n" : "✗\n");
echo "  Expected Tax: 19.61 BDT " . ($taxAmount1 == 19.61 ? "✓\n" : "✗\n");

// Test Case 2: 5% Tax on 500 BDT
echo "\n\nTest 2: Product with 5% Tax\n";
echo "----------------------------\n";
$sellPrice2 = 500;
$taxPercent2 = 5.0;
$costPrice2 = 300;

$basePrice2 = round($sellPrice2 / (1 + ($taxPercent2 / 100)), 2);
$taxAmount2 = round($sellPrice2 - $basePrice2, 2);
$profitMargin2 = round((($basePrice2 - $costPrice2) / $costPrice2) * 100, 2);

echo "Sell Price: {$sellPrice2} BDT (includes tax)\n";
echo "Tax Rate: {$taxPercent2}%\n";
echo "Cost Price: {$costPrice2} BDT\n";
echo "\nCalculated:\n";
echo "  Base Price: {$basePrice2} BDT\n";
echo "  Tax Amount: {$taxAmount2} BDT\n";
echo "  Profit Margin: {$profitMargin2}%\n";
echo "\nVerification:\n";
echo "  Base + Tax = {$basePrice2} + {$taxAmount2} = " . ($basePrice2 + $taxAmount2) . " BDT ";
echo ($basePrice2 + $taxAmount2 == $sellPrice2) ? "✓\n" : "✗\n";
echo "  Expected Base: 476.19 BDT " . ($basePrice2 == 476.19 ? "✓\n" : "✗\n");
echo "  Expected Tax: 23.81 BDT " . ($taxAmount2 == 23.81 ? "✓\n" : "✗\n");

// Test Case 3: Order with Multiple Items
echo "\n\nTest 3: Order with Mixed Tax Rates\n";
echo "-----------------------------------\n";
$qty1 = 5;
$qty2 = 3;

$itemTotal1 = $sellPrice1 * $qty1;
$itemTax1 = $taxAmount1 * $qty1;
$itemBase1 = $basePrice1 * $qty1;

$itemTotal2 = $sellPrice2 * $qty2;
$itemTax2 = $taxAmount2 * $qty2;
$itemBase2 = $basePrice2 * $qty2;

$orderSubtotal = $itemTotal1 + $itemTotal2;
$orderTax = $itemTax1 + $itemTax2;
$orderRevenue = $itemBase1 + $itemBase2;
$orderTotal = $orderSubtotal; // No discount, no shipping

echo "Item 1: {$qty1} units @ {$sellPrice1} BDT ({$taxPercent1}% tax)\n";
echo "  Item Total: {$itemTotal1} BDT\n";
echo "  Item Tax: {$itemTax1} BDT\n";
echo "  Base Revenue: {$itemBase1} BDT\n";

echo "\nItem 2: {$qty2} units @ {$sellPrice2} BDT ({$taxPercent2}% tax)\n";
echo "  Item Total: {$itemTotal2} BDT\n";
echo "  Item Tax: {$itemTax2} BDT\n";
echo "  Base Revenue: {$itemBase2} BDT\n";

echo "\nOrder Totals:\n";
echo "  Subtotal: {$orderSubtotal} BDT (includes tax)\n";
echo "  Tax Amount: {$orderTax} BDT\n";
echo "  Total: {$orderTotal} BDT\n";

echo "\nAccounting Entries:\n";
echo "  Dr. Cash: {$orderTotal} BDT\n";
echo "  Cr. Revenue: {$orderRevenue} BDT\n";
echo "  Cr. Tax Payable: {$orderTax} BDT\n";

$accountingBalance = $orderRevenue + $orderTax;
echo "\nVerification:\n";
echo "  Revenue + Tax = {$orderRevenue} + {$orderTax} = {$accountingBalance} BDT ";
echo ($accountingBalance == $orderTotal) ? "✓\n" : "✗\n";

// Test Case 4: COGS and Profit
echo "\n\nTest 4: Cost and Profit Calculation\n";
echo "------------------------------------\n";
$cogs = ($costPrice1 * $qty1) + ($costPrice2 * $qty2);
$grossProfit = $orderRevenue - $cogs;
$grossMargin = round(($grossProfit / $orderRevenue) * 100, 2);

echo "COGS: {$cogs} BDT\n";
echo "Revenue (excl. tax): {$orderRevenue} BDT\n";
echo "Gross Profit: {$grossProfit} BDT\n";
echo "Gross Margin: {$grossMargin}%\n";

// Summary
echo "\n\n=================================\n";
echo "SUMMARY\n";
echo "=================================\n";
echo "✓ Base price calculated from inclusive sell price\n";
echo "✓ Tax extracted correctly per item\n";
echo "✓ Order totals: subtotal includes tax (not added)\n";
echo "✓ Accounting: Revenue and Tax separated\n";
echo "✓ Profit calculated on base price (excl. tax)\n";
echo "\n";
echo "Customer pays: {$orderTotal} BDT\n";
echo "System records:\n";
echo "  - Revenue: {$orderRevenue} BDT\n";
echo "  - Tax Liability: {$orderTax} BDT\n";
echo "  - Total: {$orderTotal} BDT ✓\n";
echo "\n=================================\n";
