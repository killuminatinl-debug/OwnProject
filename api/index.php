<?php
/**
 * Root API compatibility endpoint for the original Travian Kingdoms lobby bundle.
 * The original frontend posts to /api/index.php; our single-root deployment keeps
 * the implementation in /lobby/api/index.php.
 */
require __DIR__ . '/../lobby/api/index.php';
