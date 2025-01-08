<?php

if (!defined('ABSPATH')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

App\Api\Attachment::getInstance();
App\Api\Auth::getInstance();
App\Api\Service::getInstance();
