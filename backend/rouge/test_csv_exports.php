<?php

/**
 * CSV Export Testing Script
 * Tests all CSV export functions with actual database data
 * Created: March 5, 2026
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\ReportingController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ProductDispatchController;
use App\Models\Employee;
use App\Models\PurchaseOrder;
use App\Models\ProductDispatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Output directory for CSVs
$outputDir = __DIR__ . '/csv_output';
if (!file_exists($outputDir)) {
    mkdir($outputDir, 0777, true);
}

// Log file
$logFile = $outputDir . '/test_log.txt';
file_put_contents($logFile, "CSV Export Test - " . date('Y-m-d H:i:s') . "\n\n");

function logMessage($message) {
    global $logFile;
    echo $message . "\n";
    file_put_contents($logFile, $message . "\n", FILE_APPEND);
}

function saveCsvResponse($response, $filename) {
    global $outputDir;
    $filepath = $outputDir . '/' . $filename;
    
    if ($response instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();
        file_put_contents($filepath, $content);
        return $filepath;
    } elseif ($response instanceof \Illuminate\Http\Response) {
        file_put_contents($filepath, $response->getContent());
        return $filepath;
    }
    
    return false;
}

function analyzeCSV($filepath, $reportName) {
    logMessage("\n--- Analyzing: $reportName ---");
    
    if (!file_exists($filepath)) {
        logMessage("ERROR: File not found - $filepath");
        return;
    }
    
    $filesize = filesize($filepath);
    logMessage("File size: " . number_format($filesize) . " bytes");
    
    $handle = fopen($filepath, 'r');
    if (!$handle) {
        logMessage("ERROR: Cannot open file");
        return;
    }
    
    // Read header
    $header = fgetcsv($handle);
    if ($header === false) {
        logMessage("ERROR: Empty file or invalid CSV");
        fclose($handle);
        return;
    }
    
    logMessage("Columns (" . count($header) . "): " . implode(', ', $header));
    
    // Count rows and check for data
    $rowCount = 0;
    $sampleRows = [];
    $emptyColumns = array_fill_keys($header, 0);
    
    while (($row = fgetcsv($handle)) !== false) {
        $rowCount++;
        
        // Sample first 3 rows
        if ($rowCount <= 3) {
            $sampleRows[] = $row;
        }
        
        // Check for empty columns
        foreach ($row as $index => $value) {
            if (empty($value) && $value !== '0') {
                if (isset($header[$index])) {
                    $emptyColumns[$header[$index]]++;
                }
            }
        }
    }
    
    fclose($handle);
    
    logMessage("Total data rows: $rowCount");
    
    if ($rowCount === 0) {
        logMessage("WARNING: No data rows found!");
    } else {
        logMessage("\nSample rows (first 3):");
        foreach ($sampleRows as $i => $row) {
            $rowNum = $i + 1;
            logMessage("Row $rowNum: " . json_encode($row, JSON_UNESCAPED_UNICODE));
        }
        
        // Report columns with many empty values
        logMessage("\nEmpty value analysis:");
        foreach ($emptyColumns as $col => $count) {
            $percentage = ($count / $rowCount) * 100;
            if ($percentage > 50) {
                logMessage("  ⚠️  $col: $count/$rowCount empty (" . number_format($percentage, 1) . "%)");
            } elseif ($count > 0) {
                logMessage("  $col: $count/$rowCount empty (" . number_format($percentage, 1) . "%)");
            }
        }
    }
}

// Get first employee for testing
$employee = Employee::first();
if (!$employee) {
    logMessage("ERROR: No employees found in database!");
    exit(1);
}

logMessage("Testing with Employee ID: {$employee->id} ({$employee->name})");
logMessage("Database: " . DB::connection()->getDatabaseName());
logMessage(str_repeat("=", 80));

// Mock authentication
auth()->guard('api')->setUser($employee);

try {
    // TEST 1: Category Sales CSV
    logMessage("\n\n1. Testing exportCategorySalesCsv...");
    $controller = new ReportingController();
    $request = Request::create('/test', 'GET', [
        'start_date' => date('Y-m-d', strtotime('-30 days')),
        'end_date' => date('Y-m-d'),
        'store_id' => null
    ]);
    $response = $controller->exportCategorySalesCsv($request);
    $filepath = saveCsvResponse($response, '01_category_sales.csv');
    analyzeCSV($filepath, 'Category Sales');
    
    // TEST 2: Sales CSV (old report)
    logMessage("\n\n2. Testing exportSalesCsv...");
    $request = Request::create('/test', 'GET', [
        'start_date' => date('Y-m-d', strtotime('-30 days')),
        'end_date' => date('Y-m-d')
    ]);
    $response = $controller->exportSalesCsv($request);
    $filepath = saveCsvResponse($response, '02_sales.csv');
    analyzeCSV($filepath, 'Sales Report');
    
    // TEST 3: Stock CSV
    logMessage("\n\n3. Testing exportStockCsv...");
    $request = Request::create('/test', 'GET', ['store_id' => null]);
    $response = $controller->exportStockCsv($request);
    $filepath = saveCsvResponse($response, '03_stock.csv');
    analyzeCSV($filepath, 'Stock Report');
    
    // TEST 4: Booking CSV
    logMessage("\n\n4. Testing exportBookingCsv...");
    $request = Request::create('/test', 'GET', [
        'start_date' => date('Y-m-d', strtotime('-30 days')),
        'end_date' => date('Y-m-d')
    ]);
    $response = $controller->exportBookingCsv($request);
    $filepath = saveCsvResponse($response, '04_booking.csv');
    analyzeCSV($filepath, 'Booking Report');
    
    // TEST 5: Payment Breakdown CSV
    logMessage("\n\n5. Testing exportPaymentBreakdownCsv...");
    $request = Request::create('/test', 'GET', [
        'start_date' => date('Y-m-d', strtotime('-30 days')),
        'end_date' => date('Y-m-d')
    ]);
    $response = $controller->exportPaymentBreakdownCsv($request);
    $filepath = saveCsvResponse($response, '05_payment_breakdown.csv');
    analyzeCSV($filepath, 'Payment Breakdown');
    
    // TEST 6: Installments CSV
    logMessage("\n\n6. Testing exportInstallmentsCsv...");
    $request = Request::create('/test', 'GET', [
        'start_date' => date('Y-m-d', strtotime('-30 days')),
        'end_date' => date('Y-m-d'),
        'customer_id' => null
    ]);
    $response = $controller->exportInstallmentsCsv($request);
    $filepath = saveCsvResponse($response, '06_installments.csv');
    analyzeCSV($filepath, 'Customer Installments');
    
    // TEST 7: Order Details CSV
    logMessage("\n\n7. Testing exportOrderDetailsCsv...");
    $request = Request::create('/test', 'GET', [
        'start_date' => date('Y-m-d', strtotime('-30 days')),
        'end_date' => date('Y-m-d'),
        'store_id' => null
    ]);
    $response = $controller->exportOrderDetailsCsv($request);
    $filepath = saveCsvResponse($response, '07_order_details.csv');
    analyzeCSV($filepath, 'Order Details');
    
    // TEST 8: Customer History CSV
    logMessage("\n\n8. Testing exportCustomerHistoryCsv...");
    $request = Request::create('/test', 'GET', [
        'start_date' => null,
        'end_date' => null,
        'customer_id' => null
    ]);
    $response = $controller->exportCustomerHistoryCsv($request);
    $filepath = saveCsvResponse($response, '08_customer_history.csv');
    analyzeCSV($filepath, 'Customer Purchase History');
    
    // TEST 9: Customer Summary CSV
    logMessage("\n\n9. Testing exportCustomerSummaryCsv...");
    $request = Request::create('/test', 'GET', [
        'start_date' => null,
        'end_date' => null
    ]);
    $response = $controller->exportCustomerSummaryCsv($request);
    $filepath = saveCsvResponse($response, '09_customer_summary.csv');
    analyzeCSV($filepath, 'Customer Summary');
    
    // TEST 10: Purchase Order Detail CSV
    logMessage("\n\n10. Testing Purchase Order exportCsv...");
    $purchaseOrder = PurchaseOrder::first();
    if ($purchaseOrder) {
        $poController = new PurchaseOrderController();
        $response = $poController->exportCsv($purchaseOrder->id);
        $filepath = saveCsvResponse($response, '10_purchase_order_detail.csv');
        analyzeCSV($filepath, 'Purchase Order Detail (ID: ' . $purchaseOrder->id . ')');
    } else {
        logMessage("SKIPPED: No purchase orders found");
    }
    
    // TEST 11: Purchase Order Barcodes CSV
    logMessage("\n\n11. Testing Purchase Order exportBarcodesCsv...");
    if ($purchaseOrder) {
        $response = $poController->exportBarcodesCsv($purchaseOrder->id);
        $filepath = saveCsvResponse($response, '11_purchase_order_barcodes.csv');
        analyzeCSV($filepath, 'Purchase Order Barcodes (ID: ' . $purchaseOrder->id . ')');
    } else {
        logMessage("SKIPPED: No purchase orders found");
    }
    
    // TEST 12: Product Dispatch CSV
    logMessage("\n\n12. Testing Product Dispatch exportCSV...");
    $dispatch = ProductDispatch::first();
    $dispatchController = new ProductDispatchController();
    $request = Request::create('/test', 'GET', [
        'start_date' => date('Y-m-d', strtotime('-30 days')),
        'end_date' => date('Y-m-d')
    ]);
    $response = $dispatchController->exportCSV($request);
    $filepath = saveCsvResponse($response, '12_product_dispatch.csv');
    analyzeCSV($filepath, 'Product Dispatch');
    
    // TEST 13: Dispatch Barcode Detailed CSV
    logMessage("\n\n13. Testing Product Dispatch exportBarcodesDetailedCsv...");
    if ($dispatch) {
        $request = Request::create('/test', 'GET', ['dispatch_id' => $dispatch->id]);
        $response = $dispatchController->exportBarcodesDetailedCsv($request);
        $filepath = saveCsvResponse($response, '13_dispatch_barcodes_detailed.csv');
        analyzeCSV($filepath, 'Dispatch Barcode Breakdown (ID: ' . $dispatch->id . ')');
    } else {
        logMessage("SKIPPED: No product dispatches found");
    }
    
    logMessage("\n\n" . str_repeat("=", 80));
    logMessage("TEST COMPLETE!");
    logMessage("CSV files saved to: $outputDir");
    logMessage("Log file: $logFile");
    
} catch (\Exception $e) {
    logMessage("\n\nFATAL ERROR: " . $e->getMessage());
    logMessage("Stack trace:\n" . $e->getTraceAsString());
}
