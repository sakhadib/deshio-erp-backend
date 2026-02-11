<?php

// Generate CSV of git commit history
$csvFile = 'commit_history.csv';

// Git log command with custom format
// %an = author name, %ai = author date ISO, %s = subject (commit message)
$gitCommand = 'git log --pretty=format:"%an|%ai|%s"';

// Execute git command
exec($gitCommand, $output, $returnCode);

if ($returnCode !== 0) {
    die("Error: Git command failed. Make sure you're in a git repository.\n");
}

// Open CSV file for writing
$fp = fopen($csvFile, 'w');

if (!$fp) {
    die("Error: Could not create CSV file.\n");
}

// Write CSV header
fputcsv($fp, ['committed_by', 'commit_msg', 'timestamp']);

// Process each commit
foreach ($output as $line) {
    // Split by pipe delimiter
    $parts = explode('|', $line, 3);
    
    if (count($parts) === 3) {
        $committedBy = trim($parts[0]);
        $timestamp = trim($parts[1]);
        $commitMsg = trim($parts[2]);
        
        // Write to CSV
        fputcsv($fp, [$committedBy, $commitMsg, $timestamp]);
    }
}

fclose($fp);

echo "✅ CSV generated successfully: $csvFile\n";
echo "Total commits: " . count($output) . "\n";

