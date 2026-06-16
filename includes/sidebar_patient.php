<?php
// includes/sidebar_patient.php — with dark mode CSS auto-included
$notif_count  = getUnreadNotifications($_SESSION['user_id']);
$current_page = basename($_SERVER['PHP_SELF']);

// Auto-include dark-mode.css if not already included
// (this ensures dark mode works on all dashboard pages)
?>
<link rel="stylesheet" href="../assets/css/dark-mode.css">

<div class="sidebar">
    <div class="sidebar-header">
        <div class="nav-logo-icon" style="width:32px;height:32px;font-size:.9rem">🏥</div>
        <div class="sidebar-logo">Medi<span>Book</span></div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-title">Main Menu</div>
        <a href="dashboard.php" class="sidebar-link <?= $current_page=='dashboard.php'?'active':'' ?>">
            <span class="icon">🏠</span> Dashboard
        </a>
        <a href="book_appointment.php" class="sidebar-link <?= $current_page=='book_appointment.php'?'active':'' ?>">
            <span class="icon">📅</span> Book Appointment
        </a>
        <a href="my_appointments.php" class="sidebar-link <?= $current_page=='my_appointments.php'?'active':'' ?>">
            <span class="icon">📋</span> My Appointments
        </a>
        <a href="prescriptions.php" class="sidebar-link <?= $current_page=='prescriptions.php'?'active':'' ?>">
            <span class="icon">💊</span> Prescriptions
        </a>
        <a href="medical_history.php" class="sidebar-link <?= $current_page=='medical_history.php'?'active':'' ?>">
            <span class="icon">🩺</span> Medical History
        </a>

        <div class="nav-section-title">Account</div>
        <a href="notifications.php" class="sidebar-link <?= $current_page=='notifications.php'?'active':'' ?>">
            <span class="icon">🔔</span> Notifications
            <?php if ($notif_count > 0): ?>
            <span class="badge-count"><?= $notif_count ?></span>
            <?php endif; ?>
        </a>
        <a href="profile.php" class="sidebar-link <?= $current_page=='profile.php'?'active':'' ?>">
            <span class="icon">👤</span> My Profile
        </a>
        <a href="../auth/logout.php" class="sidebar-link" style="color:var(--danger)">
            <span class="icon">🚪</span> Logout
        </a>
    </nav>
    <div class="sidebar-user">
        <div class="sidebar-user-avatar"><?= strtoupper(substr($_SESSION['name'],0,1)) ?></div>
        <div class="sidebar-user-info">
            <div class="name"><?= htmlspecialchars($_SESSION['name']) ?></div>
            <div class="role">Patient</div>
        </div>
    </div>
</div>