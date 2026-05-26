<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!defined('BASE_URL')) define('BASE_URL', '/vtasis/');
require_once __DIR__ . '/functions.php';
$flash   = getFlash();
$role    = $_SESSION['role']      ?? '';
$uname   = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? '');
$initials = strtoupper(substr($uname, 0, 1)) . (strpos($uname, ' ') !== false ? strtoupper(substr(strrchr($uname, ' '), 1, 1)) : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($pageTitle ?? 'VTA SIS') ?> — VTA SIS</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
<div class="app-layout">

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><img src="<?= BASE_URL ?>assets/logo.png" alt="VTA Logo" style="width:32px;height:32px;object-fit:contain;"></div>
    <div class="brand-text">
      <h2>VTA SIS</h2>
      <span>Ambegoda</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>
    <a href="<?= BASE_URL ?>modules/dashboard/index.php" class="<?= activeModule('dashboard') ?>">
      <span class="nav-icon">📊</span> Dashboard
    </a>

    <?php if (in_array($role, ['admin','support'])): ?>
    <div class="nav-section-label">People</div>
    <a href="<?= BASE_URL ?>modules/students/index.php" class="<?= activeModule('students') ?>">
      <span class="nav-icon">👨‍🎓</span> Students
    </a>
    <a href="<?= BASE_URL ?>modules/faculty/index.php" class="<?= activeModule('faculty') ?>">
      <span class="nav-icon">👨‍🏫</span> Faculty
    </a>
    <a href="<?= BASE_URL ?>modules/departments/index.php" class="<?= activeModule('departments') ?>">
      <span class="nav-icon">🏛️</span> Departments
    </a>
    <?php endif; ?>

    <?php if ($role === 'student'): ?>
    <div class="nav-section-label">My Records</div>
    <a href="<?= BASE_URL ?>modules/enrollment/index.php" class="<?= activeModule('enrollment') ?>">
      <span class="nav-icon">📋</span> My Courses
    </a>
    <a href="<?= BASE_URL ?>modules/attendance/index.php" class="<?= activeModule('attendance') ?>">
      <span class="nav-icon">📅</span> Attendance
    </a>
    <a href="<?= BASE_URL ?>modules/grades/index.php" class="<?= activeModule('grades') ?>">
      <span class="nav-icon">📝</span> Grades
    </a>
    <?php endif; ?>

    <?php if (in_array($role, ['admin','support','faculty'])): ?>
    <div class="nav-section-label">Academic</div>
    <a href="<?= BASE_URL ?>modules/courses/index.php" class="<?= activeModule('courses') ?>">
      <span class="nav-icon">📚</span> Courses
    </a>
    <a href="<?= BASE_URL ?>modules/enrollment/index.php" class="<?= activeModule('enrollment') ?>">
      <span class="nav-icon">📋</span> Enrollment
    </a>
    <a href="<?= BASE_URL ?>modules/attendance/index.php" class="<?= activeModule('attendance') ?>">
      <span class="nav-icon">📅</span> Attendance
    </a>
    <a href="<?= BASE_URL ?>modules/grades/index.php" class="<?= activeModule('grades') ?>">
      <span class="nav-icon">📝</span> Grades
    </a>
    <?php endif; ?>

    <?php if (in_array($role, ['admin','support'])): ?>
    <div class="nav-section-label">Reports</div>
    <a href="<?= BASE_URL ?>modules/reports/academic.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'reports/academic') !== false ? 'active' : '' ?>">
      <span class="nav-icon">📈</span> Academic Report
    </a>
    <a href="<?= BASE_URL ?>modules/reports/attendance.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'reports/attendance') !== false ? 'active' : '' ?>">
      <span class="nav-icon">📉</span> Attendance Report
    </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar"><?= h($initials ?: '?') ?></div>
      <div class="user-details">
        <div class="user-name"><?= h($uname) ?></div>
        <div class="user-role"><?= h($role) ?></div>
      </div>
    </div>
    <a href="<?= BASE_URL ?>modules/auth/logout.php" class="btn-logout">🚪 Logout</a>
  </div>
</aside>

<!-- MAIN -->
<div class="main-content">
  <div class="topbar">
    <div class="topbar-title"><?= h($pageTitle ?? '') ?></div>
    <div class="topbar-right">
      <span style="font-size:.8rem;color:var(--text-muted);"><?= date('D, d M Y') ?></span>
    </div>
  </div>

  <div class="page-content">
  <?php if ($flash): ?>
  <div class="alert alert-<?= h($flash['type']) ?>">
    <?= $flash['type'] === 'success' ? '✅' : ($flash['type'] === 'error' ? '❌' : 'ℹ️') ?>
    <?= h($flash['msg']) ?>
  </div>
  <?php endif; ?>
