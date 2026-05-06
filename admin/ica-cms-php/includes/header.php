<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
requireLogin();
$flash       = getFlash();
$currentUser = getCurrentUser();
?><!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Admin') ?> — ICA CMS</title>
    <link rel="icon" href="https://icahubthailand.org/favicon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f1f5f9; }
        .sidebar { width: 240px; min-height: 100vh; background: #1e293b; flex-shrink: 0; }
        .sidebar .brand { color: #fff; font-weight: 700; font-size: 1.05rem; padding: .75rem 1rem 1rem; border-bottom: 1px solid #334155; }
        .sidebar .nav-link { color: #94a3b8; padding: .55rem 1rem; border-radius: 8px; display: flex; align-items: center; gap: .6rem; }
        .sidebar .nav-link:hover { color: #fff; background: #334155; }
        .sidebar .nav-link.active { color: #fff; background: #2563eb; }
        .sidebar .nav-link i { font-size: 1rem; }
        .main-content { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .top-bar { background: #fff; border-bottom: 1px solid #e2e8f0; height: 58px; padding: 0 1.5rem; display: flex; align-items: center; justify-content: space-between; }
        .page-body { padding: 1.5rem; flex: 1; }
        .card { border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: none; }
        .table > :not(caption) > * > * { padding: .75rem 1rem; }
        .badge-published { background: #dcfce7; color: #166534; font-weight: 500; }
        .badge-draft { background: #fef9c3; color: #854d0e; font-weight: 500; }
        .object-fit-cover { object-fit: cover; }
    </style>
</head>
<body>
<div class="d-flex" style="min-height:100vh">

<?php require_once __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
    <div class="top-bar">
        <h6 class="mb-0 fw-semibold"><?= e($pageTitle ?? 'Dashboard') ?></h6>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted small"><i class="bi bi-person-circle me-1"></i><?= e($currentUser['name'] ?? '') ?></span>
            <a href="logout.php" class="btn btn-sm btn-outline-secondary">ออกจากระบบ</a>
        </div>
    </div>
    <div class="page-body">
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show mb-4" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
