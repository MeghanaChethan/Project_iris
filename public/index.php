<?php
    session_start();
    set_time_limit ( 600 );
    
    require __DIR__ . '/../bootstrap/app.php';
    date_default_timezone_set('Asia/Kolkata');

    $app->run();