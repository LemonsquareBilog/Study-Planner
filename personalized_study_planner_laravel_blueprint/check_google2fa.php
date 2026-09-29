<?php
require 'vendor/autoload.php';
$r = new ReflectionClass('Pragmarx\Google2FA\Google2FAServiceProvider');
echo $r->getFileName();