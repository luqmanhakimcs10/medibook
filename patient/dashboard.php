<?php
// patient/dashboard.php — UPDATED: upcoming card + reminder badge
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('patient');

$uid = $_SESSION['user_id'];

// Stats
$total_appts   = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE patient_id=$uid")->fetch_assoc()['c'];
$upcoming      = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE patient_id=$uid AND appointment_date >= CURDATE() AND status='confirmed'")->fetch_assoc()['c'];
$completed     = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE patient_id=$uid AND status='completed'")->fetch_assoc()['c'];
$prescriptions = $conn->query("SELECT COUNT(*) as c FROM prescriptions WHERE patient_id=$uid")->fetch_assoc()['c'];

// Next upcoming appointment (for reminder card)
$next_appt = $conn->query(
    "SELECT a.*, u.name as doctor_name, d.specialization
     FROM appointments a
     JOIN users u   ON a.doctor_id = u.id
     JOIN doctors d ON d.user_id   = u.id
     WHERE a.patient_id = $uid
     AND a.appointment_date >= CURDATE()
     AND a.status IN ('confirmed','pending')
     ORDER BY a.appointment_date ASC, a.time_slot ASC
     LIMIT 1"
)->fetch_assoc();

// Days until next appointment
$days_until = null;
if ($next_appt) {
    $today = new DateTime();
    $appt_date = new DateTime($next_appt['appointment_date']);
    $days_until = (int)$today->diff($appt_date)->days;
}

// Recent appointments
$recent = $conn->query(
    "SELECT a.*, u.name as doctor_name, d.specialization
     FROM appointments a
     JOIN users u   ON a.doctor_id = u.id
     JOIN doctors d ON d.user_id   = u.id
     WHERE a.patient_id = $uid
     ORDER BY a.appointment_date DESC LIMIT 5"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard — MediBook</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <style>
        /* ── Reminder / Upcoming Card ── */
        .upcoming-card {
            background: linear-gradient(135deg, #0D9488, #065F46);
            border-radius: 16px;
            padding: 24px 28px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }
        .upcoming-card::after {
            content: '📅';
            position: absolute;
            right: 24px;
            font-size: 5rem;
            opacity: 0.1;
            pointer-events: none;
        }
        .upcoming-card.today {
            background: linear-gradient(135deg, #D97706, #92400E);
        }
        .upcoming-card.tomorrow {
            background: linear-gradient(135deg, #2563EB, #1E40AF);
        }
        .upcoming-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .05em;
            margin-bottom: 10px;
        }
        .upcoming-title {
            font-family: 'Sora', sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 4px;
            color: white;
        }
        .upcoming-meta {
            font-size: .88rem;
            opacity: .85;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .upcoming-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* ── Reminder Badge on notification icon ── */
        .reminder-badge {
            position: absolute;
            top: -3px; right: -3px;
            min-width: 18px; height: 18px;
            background: var(--danger);
            color: white;
            font-size: .62rem;
            font-weight: 700;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
            padding: 0 4px;
        }
        @keyframes pulse-once {
            0%   { transform: scale(1); }
            50%  { transform: scale(1.3); }
            100% { transform: scale(1); }
        }
        .reminder-badge.pulse-once {
            animation: pulse-once .4s ease;
        }
        .notif-wrap {
            position: relative;
            display: inline-block;
        }
    </style>
</head>
<body>
<div class="dashboard-wrapper">
    <?php include '../includes/sidebar_patient.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">Patient Dashboard</div>
            <div class="topbar-right">
                <!-- Notification with reminder badge -->
                <div class="notif-wrap">
                    <a href="notifications.php" class="notif-btn">🔔
                        <?php
                        $notif_count = getUnreadNotifications($uid);
                        if ($notif_count > 0): ?>
                        <span class="notif-dot"></span>
                        <?php endif; ?>
                    </a>
                    <?php if ($upcoming > 0): ?>
                    <span class="reminder-badge"><?= $upcoming ?></span>
                    <?php endif; ?>
                </div>
                <!-- Dark mode toggle -->
                <button class="theme-toggle">🌙</button>
                <a href="book_appointment.php" class="btn btn-primary btn-sm">+ Book Appointment</a>
            </div>
        </div>

        <div class="page-content">

            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div>
                    <h2>Hello, <?= htmlspecialchars(explode(' ', $_SESSION['name'])[0]) ?>! 👋</h2>
                    <p>Welcome back to MediBook. Here's your health summary.</p>
                </div>
                <a href="book_appointment.php" class="btn btn-accent">📅 New Appointment</a>
            </div>

            <!-- ── UPCOMING APPOINTMENT REMINDER CARD ── -->
            <?php if ($next_appt): ?>
            <?php
            $card_class = '';
            $badge_text = '';
            $badge_icon = '⏰';
            if ($days_until === 0) {
                $card_class = 'today';
                $badge_text = 'TODAY';
                $badge_icon = '🔴';
            } elseif ($days_until === 1) {
                $card_class = 'tomorrow';
                $badge_text = 'TOMORROW';
                $badge_icon = '🟡';
            } else {
                $badge_text = "IN $days_until DAYS";
                $badge_icon = '📅';
            }
            ?>
            <div class="upcoming-card <?= $card_class ?>">
                <div style="position:relative;z-index:1">
                    <div class="upcoming-badge">
                        <?= $badge_icon ?> UPCOMING APPOINTMENT — <?= $badge_text ?>
                    </div>
                    <div class="upcoming-title">
                        <?= htmlspecialchars($next_appt['doctor_name']) ?>
                    </div>
                    <div class="upcoming-meta">
                        <span>🩺 <?= htmlspecialchars($next_appt['specialization']) ?></span>
                        <span>📅 <?= formatDate($next_appt['appointment_date']) ?></span>
                        <span>⏰ <?= htmlspecialchars($next_appt['time_slot']) ?></span>
                        <span><?= getStatusBadge($next_appt['status']) ?></span>
                    </div>
                </div>
                <a href="my_appointments.php" class="btn btn-lg"
                   style="background:rgba(255,255,255,.2);color:white;border:2px solid rgba(255,255,255,.4);flex-shrink:0;position:relative;z-index:1">
                    View Details →
                </a>
            </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-card-icon teal">📋</div>
                    <div>
                        <div class="stat-num" data-count="<?= $total_appts ?>"><?= $total_appts ?></div>
                        <div class="stat-label">Total Appointments</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon amber">⏳</div>
                    <div>
                        <div class="stat-num" data-count="<?= $upcoming ?>"><?= $upcoming ?></div>
                        <div class="stat-label">Upcoming</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon green">✅</div>
                    <div>
                        <div class="stat-num" data-count="<?= $completed ?>"><?= $completed ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon blue">💊</div>
                    <div>
                        <div class="stat-num" data-count="<?= $prescriptions ?>"><?= $prescriptions ?></div>
                        <div class="stat-label">Prescriptions</div>
                    </div>
                </div>
            </div>

            <!-- Recent Appointments -->
            <div class="card">
                <div class="card-header">
                    <h3>Recent Appointments</h3>
                    <a href="my_appointments.php" class="btn btn-outline btn-sm">View All</a>
                </div>
                <div class="table-wrap">
                    <?php if ($recent->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th><th>Doctor</th><th>Specialization</th>
                                <th>Date</th><th>Time</th><th>Status</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $i=1; while ($row = $recent->fetch_assoc()): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><strong><?= htmlspecialchars($row['doctor_name']) ?></strong></td>
                            <td><?= htmlspecialchars($row['specialization']) ?></td>
                            <td><?= formatDate($row['appointment_date']) ?></td>
                            <td><?= htmlspecialchars($row['time_slot']) ?></td>
                            <td><?= getStatusBadge($row['status']) ?></td>
                            <td>
                                <?php if (in_array($row['status'], ['pending','confirmed'])): ?>
                                <a href="my_appointments.php?cancel=<?= $row['id'] ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirmCancel()">Cancel</a>
                                <?php elseif ($row['status'] === 'completed'): ?>
                                <a href="prescriptions.php?appt=<?= $row['id'] ?>"
                                   class="btn btn-sm"
                                   style="background:var(--primary-light);color:var(--primary)">💊 Rx</a>
                                <?php else: ?>
                                <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <div class="icon">📅</div>
                        <p>No appointments yet. Book your first one!</p>
                        <a href="book_appointment.php" class="btn btn-primary">Book Appointment</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:20px">
                <?php $actions = [
                    ['book_appointment.php', '📅', 'Book Appointment',  'Find a doctor & pick a slot'],
                    ['prescriptions.php',    '💊', 'Prescriptions',     'View & download your Rx'],
                    ['medical_history.php',  '🩺', 'Medical History',   'Manage health records'],
                ]; ?>
                <?php foreach ($actions as $a): ?>
                <a href="<?= $a[0] ?>"
                   style="background:white;border:1px solid var(--border);border-radius:14px;padding:22px;text-align:center;display:block;transition:all .25s;text-decoration:none"
                   onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='var(--shadow-md)';this.style.borderColor='var(--primary)'"
                   onmouseout="this.style.transform='';this.style.boxShadow='';this.style.borderColor=''">
                    <div style="font-size:1.8rem;margin-bottom:8px"><?= $a[1] ?></div>
                    <div style="font-weight:700;font-size:.9rem;margin-bottom:3px;color:var(--text-dark)"><?= $a[2] ?></div>
                    <div style="font-size:.78rem;color:var(--text-light)"><?= $a[3] ?></div>
                </a>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
