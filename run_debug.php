<?php

// A simple php script to run the artisan command directly without bash execution problems
$output = shell_exec('php artisan debug:renew 2>&1');
file_put_contents(__DIR__.'/debug-renew.txt', $output);
echo 'Output saved to debug-renew.txt';
