<?php
// includes/sidebar_doctor.php — UPDATED: My Reviews link added
$notif_count  = getUnreadNotifications($_SESSION['user_id']);
$current_page = basename($_SERVER['PHP_SELF']);
$uid          = $_SESSION['user_id'];
$pwd_reminder = $conn->query("SELECT id FROM notifications WHERE user_id=$uid AND title LIKE '%Approved%' LIMIT 1")->num_rows > 0;

// Count unread notifications for badge
$avg_rating_res = $conn->query("SELECT ROUND(AVG(rating),1) as avg FROM doctor_ratings WHERE doctor_id=$uid");
$avg_rating = $avg_rating_res ? floatval($avg_rating_res->fetch_assoc()['avg']) : 0;
?>
<link rel="stylesheet" href="../assets/css/dark-mode.css">
<script src="../assets/js/dark-mode.js"></script>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="nav-logo-icon" style="width:32px;height:32px;font-size:.9rem">🏥</div>
        <div class="sidebar-logo">Medi<span>Book</span></div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Doctor Panel</div>

        <a href="dashboard.php" class="sidebar-link <?= $current_page==='dashboard.php'?'active':'' ?>">
            <span class="icon">🏠</span> Dashboard
        </a>
        <a href="appointments.php" class="sidebar-link <?= $current_page==='appointments.php'?'active':'' ?>">
            <span class="icon">📋</span> Appointments
        </a>
        <a href="write_prescription.php" class="sidebar-link <?= $current_page==='write_prescription.php'?'active':'' ?>">
            <span class="icon">📝</span> Write Prescription
        </a>
        <a href="my_prescriptions.php" class="sidebar-link <?= $current_page==='my_prescriptions.php'?'active':'' ?>">
            <span class="icon">💊</span> My Prescriptions
        </a>
        <a href="schedule.php" class="sidebar-link <?= $current_page==='schedule.php'?'active':'' ?>">
            <span class="icon">🗓️</span> My Schedule
        </a>

        <!-- My Reviews — shows average star rating in sidebar -->
        <a href="my_ratings.php" class="sidebar-link <?= $current_page==='my_ratings.php'?'active':'' ?>">
            <span class="icon">⭐</span> My Reviews
            <?php if ($avg_rating > 0): ?>
            <span style="margin-left:auto;background:var(--accent-light);color:var(--accent);font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:10px;border:1px solid rgba(245,158,11,.3)">
                <?= $avg_rating ?>/5
            </span>
            <?php endif; ?>
        </a>

        <div class="nav-section-title">Account</div>

        <a href="notifications.php" class="sidebar-link <?= $current_page==='notifications.php'?'active':'' ?>">
            <span class="icon">🔔</span> Notifications
            <?php if ($notif_count > 0): ?>
            <span class="badge-count"><?= $notif_count ?></span>
            <?php endif; ?>
        </a>
        <a href="profile.php" class="sidebar-link <?= $current_page==='profile.php'?'active':'' ?>">
            <span class="icon">👤</span> Profile
        </a>
        <a href="change_password.php"
           class="sidebar-link <?= $current_page==='change_password.php'?'active':'' ?>"
           style="<?= $current_page!=='change_password.php'?'border:1px solid rgba(245,158,11,.3);background:rgba(254,243,199,.4)':'' ?>">
            <span class="icon">🔒</span> Change Password
            <?php if ($pwd_reminder && $current_page!=='change_password.php'): ?>
            <span class="badge-count" style="background:var(--accent)">!</span>
            <?php endif; ?>
        </a>
        <a href="../auth/logout.php" class="sidebar-link" style="color:var(--danger)">
            <span class="icon">🚪</span> Logout
        </a>
    </nav>

    <div class="sidebar-user">
        <div class="sidebar-user-avatar" style="background:var(--info)">
            <?= strtoupper(substr($_SESSION['name'], 3, 1)) ?>
        </div>
        <div class="sidebar-user-info">
            <div class="name"><?= htmlspecialchars($_SESSION['name']) ?></div>
            <div class="role">Doctor</div>
        </div>
    </div>
</div>