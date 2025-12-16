#!/usr/bin/env php
<?php
/**
 * .env File Validator
 * Run this to check if your .env file is properly formatted
 */

echo "=== .env File Validator ===\n\n";

// Possible .env locations
$possiblePaths = [
    __DIR__ . '/.env',
    dirname(__DIR__) . '/.env',
];

$envPath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $envPath = $path;
        break;
    }
}

if (!$envPath) {
    echo "❌ No .env file found in any of these locations:\n";
    foreach ($possiblePaths as $path) {
        echo "   - $path\n";
    }
    echo "\n";
    echo "📝 Create one by copying .env.example:\n";
    echo "   cp .env.example ../.env\n";
    exit(1);
}

echo "✅ Found .env file at: $envPath\n\n";

// Read the file
$contents = file_get_contents($envPath);
$lines = explode("\n", $contents);

$errors = [];
$warnings = [];
$validVars = [];

// Valid variable names for this application
$expectedVars = [
    'OPENAI_API_KEY',
    'ANTHROPIC_API_KEY',
    'DB_HOST',
    'DB_NAME',
    'DB_USER',
    'DB_PASS',
    'APP_ENV',
    'BASE_URL' // optional
];

$unexpectedVars = [];

foreach ($lines as $lineNum => $line) {
    $lineNumber = $lineNum + 1;
    $line = trim($line);
    
    // Skip empty lines and comments
    if (empty($line) || $line[0] === '#') {
        continue;
    }
    
    // Check if line contains =
    if (strpos($line, '=') === false) {
        $errors[] = "Line $lineNumber: Invalid format (missing '='): $line";
        continue;
    }
    
    // Parse the line
    list($name, $value) = explode('=', $line, 2);
    $name = trim($name);
    $value = trim($value);
    
    // Validate variable name
    // Valid: Letters, numbers, underscore - must start with letter or underscore
    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
        $errors[] = "Line $lineNumber: Invalid variable name '$name' (only letters, numbers, underscore allowed)";
        continue;
    }
    
    // Check for uncommon patterns that might cause issues
    if (strpos($name, ',') !== false) {
        $errors[] = "Line $lineNumber: Variable name contains comma: $name";
        continue;
    }
    
    // Track if this is an expected variable
    if (!in_array($name, $expectedVars)) {
        $unexpectedVars[] = [
            'line' => $lineNumber,
            'name' => $name,
            'value' => $value
        ];
    }
    
    $validVars[] = $name;
}

// Report errors
if (!empty($errors)) {
    echo "❌ ERRORS FOUND:\n";
    foreach ($errors as $error) {
        echo "   $error\n";
    }
    echo "\n";
}

// Report unexpected variables
if (!empty($unexpectedVars)) {
    echo "⚠️  UNEXPECTED VARIABLES (may cause parsing issues):\n";
    foreach ($unexpectedVars as $var) {
        echo "   Line {$var['line']}: {$var['name']} = {$var['value']}\n";
    }
    echo "\n";
    echo "   These variables are not used by this application.\n";
    echo "   Common culprits: SMTP_*, EMAIL_*, MAIL_* variables\n";
    echo "   Consider removing them or ensure they're properly formatted.\n";
    echo "\n";
}

// Report warnings
if (!empty($warnings)) {
    echo "⚠️  WARNINGS:\n";
    foreach ($warnings as $warning) {
        echo "   $warning\n";
    }
    echo "\n";
}

// Check for required variables
echo "📋 Checking Required Variables:\n";
$requiredVars = [
    'OPENAI_API_KEY' => 'OpenAI API key for transcription',
    'ANTHROPIC_API_KEY' => 'Anthropic API key for AI recaps',
    'DB_HOST' => 'Database host',
    'DB_NAME' => 'Database name',
    'DB_USER' => 'Database username',
    'DB_PASS' => 'Database password',
];

$missingVars = [];
foreach ($requiredVars as $var => $description) {
    if (in_array($var, $validVars)) {
        echo "   ✅ $var ($description)\n";
    } else {
        echo "   ❌ $var ($description) - MISSING\n";
        $missingVars[] = $var;
    }
}
echo "\n";

// Summary
echo "=== SUMMARY ===\n";
echo "Total lines: " . count($lines) . "\n";
echo "Valid variables: " . count($validVars) . "\n";
echo "Errors: " . count($errors) . "\n";
echo "Unexpected variables: " . count($unexpectedVars) . "\n";
echo "Missing required: " . count($missingVars) . "\n";
echo "\n";

if (empty($errors) && empty($missingVars)) {
    echo "✅ Your .env file looks good!\n";
    echo "\n";
    echo "You can now safely run your application.\n";
    exit(0);
} else {
    echo "❌ Your .env file has issues that need to be fixed.\n";
    echo "\n";
    
    if (!empty($errors)) {
        echo "Fix the errors listed above.\n";
    }
    
    if (!empty($missingVars)) {
        echo "Add these required variables:\n";
        foreach ($missingVars as $var) {
            echo "   $var={$requiredVars[$var]}\n";
        }
    }
    
    echo "\n";
    echo "📖 See .env.example for a reference template.\n";
    exit(1);
}
