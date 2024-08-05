<?php

// index.php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
require_once 'functions.php';

// Load plugins
foreach (glob("plugins/*.php") as $filename) {
    require_once $filename;
}

// Create Plugin Manager instance
$pluginManager = new PluginManager();

// Register plugins
$pluginManager->registerPlugin(new FaceMatchPlugin());
//$pluginManager->registerPlugin(new AnotherPlugin());

// Initialize plugins
$pluginManager->initPlugins();

// Execute hooks
// HookManager::executeHook('sample_hook');

