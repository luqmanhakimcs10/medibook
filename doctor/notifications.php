<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('doctor');
$uid = $_SESSION['user_id'];
$conn->query("UPDATE notifications SET is_read=1 WHERE user_id=$uid");
$notifications = $conn->query("SELECT * FROM notifications WHERE user_id=$uid ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Notifications — MediBook</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
<?php include '../includes/sidebar_doctor.php'; ?>
<div class="main-content">
<div class="topbar">
    <div class="topbar-title">🔔 Notifications</div>
    <div class="topbar-right"><button class="theme-toggle">🌙</button></div>
</div>
<div class="page-content">
    <div class="card">
        <div class="card-header"><h3>All Notifications</h3><span style="font-size:.85rem;color:var(--text-light)"><?= $notifications->num_rows ?> total</span></div>
        <?php if($notifications->num_rows>0): ?>
            <?php while($n=$notifications->fetch_assoc()): ?>
            <div style="padding:18px 24px;border-bottom:1px solid var(--border);display:flex;gap:14px;align-items:flex-start">
                <div style="width:42px;height:42px;min-width:42px;background:#DBEAFE;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem">🔔</div>
                <div style="flex:1">
                    <div style="font-weight:600;margin-bottom:3px"><?= htmlspecialchars($n['title']) ?></div>
                    <div style="font-size:.9rem;color:var(--text-mid)"><?= htmlspecialchars($n['message']) ?></div>
                    <div style="font-size:.78rem;color:var(--text-light);margin-top:6px"><?= date('d M Y, h:i A',strtotime($n['created_at'])) ?></div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
        <div class="empty-state"><div class="icon">🔔</div><p>No notifications yet.</p></div>
        <?php endif; ?>
    </div>
</div>
</div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>