<?php

ini_set('memory_limit', '512M');

putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';

require __DIR__.'/../vendor/autoload.php';
