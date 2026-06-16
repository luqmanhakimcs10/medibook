<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('doctor');
$uid = $_SESSION['user_id'];

// Handle actions
if (isset($_GET['confirm'])) {
    $id = intval($_GET['confirm']);
    $conn->query("UPDATE appointments SET status='confirmed' WHERE id=$id AND doctor_id=$uid");
    $appt = $conn->query("SELECT a.*,u.id as pid,u.name as pname FROM appointments a JOIN users u ON a.patient_id=u.id WHERE a.id=$id")->fetch_assoc();
    if ($appt) addNotification($appt['pid'],'Appointment Confirmed!',"Your appointment on {$appt['appointment_date']} at {$appt['time_slot']} has been confirmed.");
    header("Location: appointments.php?msg=confirmed"); exit();
}
if (isset($_GET['complete'])) {
    $id = intval($_GET['complete']);
    $conn->query("UPDATE appointments SET status='completed' WHERE id=$id AND doctor_id=$uid");
    header("Location: appointments.php?msg=completed"); exit();
}
if (isset($_GET['cancel'])) {
    $id = intval($_GET['cancel']);
    $conn->query("UPDATE appointments SET status='cancelled' WHERE id=$id AND doctor_id=$uid");
    header("Location: appointments.php?msg=cancelled"); exit();
}

$filter = sanitize($_GET['status'] ?? '');
$msg    = $_GET['msg'] ?? '';
$where  = "WHERE a.doctor_id=$uid";
if ($filter) $where .= " AND a.status='$filter'";
$appointments = $conn->query("SELECT a.*,u.name as patient_name,u.phone as patient_phone,u.gender as patient_gender FROM appointments a JOIN users u ON a.patient_id=u.id $where ORDER BY a.appointment_date DESC,a.time_slot");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Appointments — MediBook</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
<?php include '../includes/sidebar_doctor.php'; ?>
<div class="main-content">
<div class="topbar">
    <div class="topbar-title">📋 Appointments</div>
    <div class="topbar-right">
        <button class="theme-toggle">🌙</button>
        <a href="write_prescription.php" class="btn btn-primary btn-sm">✍️ Write Prescription</a>
    </div>
</div>
<div class="page-content">
    <?php if($msg==='confirmed'): ?><div class="alert alert-success">✅ Appointment confirmed successfully.</div><?php endif; ?>
    <?php if($msg==='completed'): ?><div class="alert alert-info">✅ Appointment marked as completed.</div><?php endif; ?>
    <?php if($msg==='cancelled'): ?><div class="alert alert-danger">Appointment cancelled.</div><?php endif; ?>

    <!-- Filter Bar -->
    <div class="card" style="margin-bottom:20px">
        <div class="card-body" style="padding:14px 24px">
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                <span style="font-weight:600;font-size:.9rem;color:var(--text-mid)">Filter by status:</span>
                <?php foreach([''=>'All','pending'=>'Pending','confirmed'=>'Confirmed','completed'=>'Completed','cancelled'=>'Cancelled'] as $val=>$label): ?>
                <a href="?status=<?= $val ?>" class="btn btn-sm <?= $filter===$val?'btn-primary':'btn-outline' ?>" style="border-radius:20px"><?= $label ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <?php if($appointments&&$appointments->num_rows>0): ?>
            <table>
                <thead><tr><th>#</th><th>Patient</th><th>Phone</th><th>Gender</th><th>Date</th><th>Time</th><th>Symptoms</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php $i=1; while($row=$appointments->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($row['patient_name']) ?></strong></td>
                    <td style="font-size:.85rem"><?= htmlspecialchars($row['patient_phone']??'—') ?></td>
                    <td><?= htmlspecialchars($row['patient_gender']??'—') ?></td>
                    <td><?= formatDate($row['appointment_date']) ?></td>
                    <td><?= htmlspecialchars($row['time_slot']) ?></td>
                    <td style="max-width:140px;font-size:.82rem;color:var(--text-mid)"><?= $row['symptoms']?htmlspecialchars(substr($row['symptoms'],0,50)).'...':'—' ?></td>
                    <td><?= getStatusBadge($row['status']) ?></td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <?php if($row['status']==='pending'): ?>
                            <a href="?confirm=<?= $row['id'] ?>&status=<?= $filter ?>" class="btn btn-success btn-sm">✓ Confirm</a>
                            <a href="?cancel=<?= $row['id'] ?>&status=<?= $filter ?>" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this appointment?')">✗</a>
                        <?php elseif($row['status']==='confirmed'): ?>
                            <a href="?complete=<?= $row['id'] ?>&status=<?= $filter ?>" class="btn btn-sm" style="background:var(--info);color:white">Complete</a>
                            <a href="write_prescription.php?appt=<?= $row['id'] ?>" class="btn btn-primary btn-sm">📝 Rx</a>
                        <?php elseif($row['status']==='completed'): ?>
                            <a href="write_prescription.php?appt=<?= $row['id'] ?>" class="btn btn-outline btn-sm">📝 Rx</a>
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
            <div class="empty-state"><div class="icon">📋</div><p>No <?= $filter ?: '' ?> appointments found.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>