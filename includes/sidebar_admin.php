<?php
// includes/sidebar_admin.php — with dark mode CSS auto-included
$current_page  = basename($_SERVER['PHP_SELF']);
$pending_count = $conn->query("SELECT COUNT(*) as c FROM doctors WHERE status='pending'")->fetch_assoc()['c'];
?>
<link rel="stylesheet" href="../assets/css/dark-mode.css">

<div class="sidebar">
    <div class="sidebar-header">
        <div class="nav-logo-icon" style="width:32px;height:32px;font-size:.9rem">🏥</div>
        <div class="sidebar-logo">Medi<span>Book</span></div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-title">Admin Panel</div>
        <a href="dashboard.php" class="sidebar-link <?= $current_page=='dashboard.php'?'active':'' ?>">
            <span class="icon">📊</span> Dashboard
        </a>
        <a href="pending_doctors.php" class="sidebar-link <?= in_array($current_page,['pending_doctors.php','view_doctor_application.php'])?'active':'' ?>">
            <span class="icon">📋</span> Doctor Applications
            <?php if ($pending_count > 0): ?>
            <span class="badge-count"><?= $pending_count ?></span>
            <?php endif; ?>
        </a>
        <a href="manage_doctors.php" class="sidebar-link <?= $current_page=='manage_doctors.php'?'active':'' ?>">
            <span class="icon">👨‍⚕️</span> Manage Doctors
        </a>
        <a href="manage_patients.php" class="sidebar-link <?= $current_page=='manage_patients.php'?'active':'' ?>">
            <span class="icon">🧑</span> Manage Patients
        </a>
        <a href="manage_appointments.php" class="sidebar-link <?= $current_page=='manage_appointments.php'?'active':'' ?>">
            <span class="icon">📅</span> All Appointments
        </a>
        <a href="reports.php" class="sidebar-link <?= $current_page=='reports.php'?'active':'' ?>">
            <span class="icon">📈</span> Reports
        </a>

        <div class="nav-section-title">System</div>
        <a href="../auth/logout.php" class="sidebar-link" style="color:var(--danger)">
            <span class="icon">🚪</span> Logout
        </a>
    </nav>
    <div class="sidebar-user">
        <div class="sidebar-user-avatar" style="background:#7C3AED">
            <?= strtoupper(substr($_SESSION['name'],0,1)) ?>
        </div>
        <div class="sidebar-user-info">
            <div class="name"><?= htmlspecialchars($_SESSION['name']) ?></div>
            <div class="role">Administrator</div>
        </div>
    </div>
</div>
