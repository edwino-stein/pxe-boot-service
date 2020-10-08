<?php

// Reponse for anything in /resources
if (preg_match('/^\/resources\/.*$/', $_SERVER["REQUEST_URI"])) return false;

// Start Symfony framework normally
include 'index.php';
