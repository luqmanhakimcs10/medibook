<?php
// patient/my_appointments.php — UPDATED: Rate button for completed appointments
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('patient');

$uid = $_SESSION['user_id'];

// Handle cancel
if (isset($_GET['cancel'])) {
    $appt_id = intval($_GET['cancel']);
    $conn->query("UPDATE appointments SET status='cancelled' WHERE id=$appt_id AND patient_id=$uid");
    addNotification($uid, 'Appointment Cancelled', 'Your appointment has been cancelled successfully.');
    header("Location: my_appointments.php?msg=cancelled");
    exit();
}

$msg    = $_GET['msg'] ?? '';
$filter = sanitize($_GET['status'] ?? '');
$where  = "WHERE a.patient_id = $uid";
if ($filter) $where .= " AND a.status = '$filter'";

$appointments = $conn->query(
    "SELECT a.*, u.name as doctor_name, d.specialization, d.consultation_fee,
            (SELECT id FROM doctor_ratings WHERE appointment_id = a.id) as rating_id
     FROM appointments a
     JOIN users u   ON a.doctor_id = u.id
     JOIN doctors d ON d.user_id   = u.id
     $where
     ORDER BY a.appointment_date DESC, a.time_slot"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments — MediBook</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
    <?php include '../includes/sidebar_patient.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">📋 My Appointments</div>
            <div class="topbar-right">
                <a href="book_appointment.php" class="btn btn-primary btn-sm">+ New Appointment</a>
            </div>
        </div>
        <div class="page-content">
            <?php if ($msg === 'cancelled'): ?>
                <div class="alert alert-info">✅ Appointment cancelled successfully.</div>
            <?php endif; ?>

            <!-- Filter -->
            <div class="card" style="margin-bottom:20px">
                <div class="card-body" style="padding:14px 24px">
                    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                        <span style="font-weight:600;font-size:.9rem;color:var(--text-mid)">Filter:</span>
                        <?php foreach ([''=>'All','pending'=>'Pending','confirmed'=>'Confirmed','completed'=>'Completed','cancelled'=>'Cancelled'] as $val=>$label): ?>
                        <a href="?status=<?= $val ?>" class="btn btn-sm <?= $filter===$val?'btn-primary':'btn-outline' ?>" style="border-radius:20px">
                            <?= $label ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="table-wrap">
                    <?php if ($appointments && $appointments->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Doctor</th>
                                <th>Specialization</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Fee</th>
                                <th>Status</th>
                                <th>Symptoms</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $i=1; while ($row = $appointments->fetch_assoc()): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><strong><?= htmlspecialchars($row['doctor_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['specialization']) ?></td>
                                <td><?= formatDate($row['appointment_date']) ?></td>
                                <td><?= htmlspecialchars($row['time_slot']) ?></td>
                                <td>Rs <?= number_format($row['consultation_fee'], 0) ?></td>
                                <td><?= getStatusBadge($row['status']) ?></td>
                                <td style="max-width:130px;font-size:.82rem;color:var(--text-mid)">
                                    <?= $row['symptoms'] ? htmlspecialchars(substr($row['symptoms'],0,40)).'...' : '—' ?>
                                </td>
                                <td>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                                        <?php if ($row['status'] === 'completed'): ?>
                                            <!-- View prescription -->
                                            <a href="prescriptions.php?appt=<?= $row['id'] ?>"
                                               class="btn btn-sm" style="background:var(--primary-light);color:var(--primary)">💊 Rx</a>
                                            <!-- Rate doctor — show ⭐ if not yet rated, ✓ if already rated -->
                                            <?php if ($row['rating_id']): ?>
                                                <span class="btn btn-sm" style="background:#FEF3C7;color:#D97706;cursor:default">⭐ Rated</span>
                                            <?php else: ?>
                                                <a href="rate_doctor.php?appt=<?= $row['id'] ?>"
                                                   class="btn btn-sm" style="background:var(--accent-light);color:var(--accent)">⭐ Rate</a>
                                            <?php endif; ?>

                                        <?php elseif ($row['status'] === 'pending' || $row['status'] === 'confirmed'): ?>
                                            <a href="?cancel=<?= $row['id'] ?>&status=<?= $filter ?>"
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirmCancel()">Cancel</a>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
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
                        <p>No <?= $filter ?: '' ?> appointments found.</p>
                        <a href="book_appointment.php" class="btn btn-primary">Book an Appointment</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
