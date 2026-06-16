<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('doctor');

$uid = $_SESSION['user_id'];
$doc = getDoctorInfo($uid);

// Stats
$total     = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE doctor_id=$uid")->fetch_assoc()['c'];
$today     = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE doctor_id=$uid AND appointment_date=CURDATE() AND status!='cancelled'")->fetch_assoc()['c'];
$pending   = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE doctor_id=$uid AND status='pending'")->fetch_assoc()['c'];
$completed = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE doctor_id=$uid AND status='completed'")->fetch_assoc()['c'];

// Today's appointments
$todays = $conn->query(
    "SELECT a.*, u.name as patient_name, u.phone as patient_phone, u.gender as patient_gender
     FROM appointments a
     JOIN users u ON a.patient_id = u.id
     WHERE a.doctor_id = $uid
     AND a.appointment_date = CURDATE()
     AND a.status != 'cancelled'
     ORDER BY a.time_slot"
);

// Pending approvals
$pendings = $conn->query(
    "SELECT a.*, u.name as patient_name
     FROM appointments a
     JOIN users u ON a.patient_id = u.id
     WHERE a.doctor_id = $uid
     AND a.status = 'pending'
     ORDER BY a.appointment_date, a.time_slot
     LIMIT 5"
);

// Handle confirm / complete actions
if (isset($_GET['confirm'])) {
    $id   = intval($_GET['confirm']);
    $conn->query("UPDATE appointments SET status='confirmed' WHERE id=$id AND doctor_id=$uid");
    $appt = $conn->query("SELECT a.*, u.id as pid, u.name as pname FROM appointments a JOIN users u ON a.patient_id=u.id WHERE a.id=$id")->fetch_assoc();
    if ($appt) {
        addNotification($appt['pid'], 'Appointment Confirmed!',
            "Your appointment on {$appt['appointment_date']} at {$appt['time_slot']} has been confirmed.");
    }
    header("Location: dashboard.php");
    exit();
}

if (isset($_GET['complete'])) {
    $id = intval($_GET['complete']);
    $conn->query("UPDATE appointments SET status='completed' WHERE id=$id AND doctor_id=$uid");
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard — MediBook</title>

    <!--
        DARK MODE FIX:
        dark-mode.js must be loaded here in <head> — NOT at the bottom.
        It reads localStorage and sets data-theme on <html> BEFORE
        the page renders, which prevents the white flash on dark mode.
    -->
    <script src="../assets/js/dark-mode.js"></script>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
</head>
<body>
<div class="dashboard-wrapper">

    <?php include '../includes/sidebar_doctor.php'; ?>

    <div class="main-content">

        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-title">🏠 Doctor Dashboard</div>
            <div class="topbar-right">
                <div style="position:relative;display:inline-block">
                    <a href="notifications.php" class="notif-btn">
                        🔔
                        <?php if (getUnreadNotifications($uid) > 0): ?>
                        <span class="notif-dot"></span>
                        <?php endif; ?>
                    </a>
                </div>
                <!-- Dark mode toggle button -->
                <button class="theme-toggle" title="Toggle Dark Mode">🌙</button>
                <a href="write_prescription.php" class="btn btn-primary btn-sm">✍️ Write Prescription</a>
            </div>
        </div>

        <div class="page-content">

            <!-- Welcome Banner -->
            <div class="welcome-banner" style="background:linear-gradient(135deg,#1D4ED8,#1E40AF)">
                <div>
                    <h2>Welcome, <?= htmlspecialchars(explode(' ', $_SESSION['name'])[1] ?? $_SESSION['name']) ?>! 👨‍⚕️</h2>
                    <p><?= htmlspecialchars($doc['specialization'] ?? '') ?> · <?= $doc['experience_years'] ?? 0 ?> years experience</p>
                </div>
                <a href="appointments.php" class="btn btn-accent">📋 View All Appointments</a>
            </div>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-card-icon teal">📋</div>
                    <div>
                        <div class="stat-num" data-count="<?= $total ?>"><?= $total ?></div>
                        <div class="stat-label">Total Appointments</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon amber">📅</div>
                    <div>
                        <div class="stat-num" data-count="<?= $today ?>"><?= $today ?></div>
                        <div class="stat-label">Today's Schedule</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon red">⏳</div>
                    <div>
                        <div class="stat-num" data-count="<?= $pending ?>"><?= $pending ?></div>
                        <div class="stat-label">Pending Approval</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon green">✅</div>
                    <div>
                        <div class="stat-num" data-count="<?= $completed ?>"><?= $completed ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                </div>
            </div>

            <!-- Main Grid -->
            <div style="display:grid;grid-template-columns:3fr 2fr;gap:24px">

                <!-- Today's Appointments -->
                <div class="card">
                    <div class="card-header">
                        <h3>📅 Today's Appointments</h3>
                        <span style="font-size:.82rem;color:var(--text-light)"><?= date('d M Y') ?></span>
                    </div>
                    <div class="table-wrap">
                        <?php if ($todays && $todays->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Patient</th>
                                    <th>Gender</th>
                                    <th>Symptoms</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php while ($r = $todays->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['time_slot']) ?></strong></td>
                                <td><?= htmlspecialchars($r['patient_name']) ?></td>
                                <td><?= htmlspecialchars($r['patient_gender'] ?? '—') ?></td>
                                <td style="max-width:130px;font-size:.82rem;color:var(--text-mid)">
                                    <?= $r['symptoms'] ? htmlspecialchars(substr($r['symptoms'], 0, 40)) . '...' : '—' ?>
                                </td>
                                <td><?= getStatusBadge($r['status']) ?></td>
                                <td>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <a href="?confirm=<?= $r['id'] ?>" class="btn btn-success btn-sm">✓ Confirm</a>
                                    <?php elseif ($r['status'] === 'confirmed'): ?>
                                        <a href="?complete=<?= $r['id'] ?>" class="btn btn-sm" style="background:var(--info);color:white">Complete</a>
                                        <a href="write_prescription.php?appt=<?= $r['id'] ?>" class="btn btn-primary btn-sm">Rx</a>
                                    <?php elseif ($r['status'] === 'completed'): ?>
                                        <a href="write_prescription.php?appt=<?= $r['id'] ?>" class="btn btn-outline btn-sm">📝 Rx</a>
                                    <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <div class="icon">📅</div>
                            <p>No appointments scheduled for today.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pending Approvals -->
                <div class="card">
                    <div class="card-header">
                        <h3>⏳ Pending Approvals</h3>
                        <a href="appointments.php?status=pending" class="btn btn-outline btn-sm">View All</a>
                    </div>
                    <?php if ($pendings && $pendings->num_rows > 0): ?>
                        <?php while ($p = $pendings->fetch_assoc()): ?>
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--border)">
                            <div>
                                <div style="font-weight:600;font-size:.9rem"><?= htmlspecialchars($p['patient_name']) ?></div>
                                <div style="font-size:.8rem;color:var(--text-light)">
                                    <?= formatDate($p['appointment_date']) ?> · <?= htmlspecialchars($p['time_slot']) ?>
                                </div>
                            </div>
                            <a href="?confirm=<?= $p['id'] ?>" class="btn btn-success btn-sm">Confirm</a>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <div class="empty-state" style="padding:30px">
                        <div class="icon" style="font-size:2rem">✅</div>
                        <p>No pending requests.</p>
                    </div>
                    <?php endif; ?>
                </div>

            </div><!-- /grid -->
        </div><!-- /page-content -->
    </div><!-- /main-content -->
</div><!-- /dashboard-wrapper -->

<script src="../assets/js/main.js"></script>
</body>
</html>