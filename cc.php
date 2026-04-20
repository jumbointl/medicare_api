<?php
try{
    // Change directory to the Laravel project root
chdir(__DIR__);
echo "1";
$output = shell_exec('php artisan storage:link');
echo "2";
echo "<pre>$output</pre>";
    
}
catch (Exception $e) {
    echo $e;
}

