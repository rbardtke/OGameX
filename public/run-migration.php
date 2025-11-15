<?php
/**
 * Emergency migration runner
 *
 * This script allows you to run database migrations without being logged in.
 * Access it at: https://your-domain.com/run-migration.php
 *
 * DELETE THIS FILE AFTER USE FOR SECURITY!
 */

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Run migrations
try {
    echo "<h1>Running Database Migrations</h1>";
    echo "<pre>";

    Artisan::call('migrate', ['--force' => true]);
    $output = Artisan::output();

    echo $output;
    echo "\n\n";
    echo "✓ Migrations completed successfully!";
    echo "</pre>";

    echo "<p><strong>IMPORTANT:</strong> Delete this file (run-migration.php) now for security!</p>";

} catch (\Exception $e) {
    echo "<pre>";
    echo "✗ Migration failed:\n";
    echo $e->getMessage();
    echo "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}
