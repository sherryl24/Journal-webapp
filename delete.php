<?php
require_once __DIR__ . '/includes/functions.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id > 0 && deleteEntry($id)) {
    $_SESSION['flash'] = '🗑️ Entry deleted.';
} else {
    $_SESSION['flash'] = 'Could not delete entry.';
}

header('Location: index.php');
exit;
