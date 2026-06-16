<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('admin');

if (isset($_POST['update_status'])) {
    $appt_id    = intval($_POST['appt_id']);
    $new_status = sanitize($_POST['new_status']);
    if (in_array($new_status, ['pending','confirmed','completed','cancelled'])) {
        $conn->query("UPDATE appointments SET status='$new_status' WHERE id=$appt_id");
    }
    header("Location: manage_appointments.php?msg=updated"); exit();
}
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM appointments WHERE id=$id");
    header("Location: manage_appointments.php?msg=deleted"); exit();
}

$msg    = $_GET['msg']    ?? '';
$filter = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['q']      ?? '');
$where  = "WHERE 1=1";
if ($filter) $where .= " AND a.status='$filter'";
if ($search) $where .= " AND (u1.name LIKE '%$search%' OR u2.name LIKE '%$search%')";

$appointments = $conn->query(
    "SELECT a.*, u1.name as patient_name, u2.name as doctor_name, d.specialization
     FROM appointments a
     JOIN users u1   ON a.patient_id = u1.id
     JOIN users u2   ON a.doctor_id  = u2.id
     JOIN doctors d  ON d.user_id    = u2.id
     $where
     ORDER BY a.appointment_date DESC, a.time_slot"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Appointments — MediBook</title>
    <script src="../assets/js/dark-mode.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
</head>
<body>
<div class="dashboard-wrapper">
<?php include '../includes/sidebar_admin.php'; ?>
<div class="main-content">
<div class="topbar">
    <div class="topbar-title">📅 All Appointments</div>
    <div class="topbar-right">
        <button class="theme-toggle">🌙</button>
    </div>
</div>
<div class="page-content">
    <?php if($msg==='updated'): ?><div class="alert alert-success">✅ Status updated successfully.</div><?php endif; ?>
    <?php if($msg==='deleted'): ?><div class="alert alert-danger">Appointment deleted.</div><?php endif; ?>

    <!-- Filters -->
    <div class="card" style="margin-bottom:20px">
        <div class="card-body" style="padding:14px 20px">
            <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
                <form method="GET" style="display:flex;gap:10px;flex:1">
                    <input type="text" name="q" class="form-control" placeholder="Search patient or doctor name..."
                           value="<?= htmlspecialchars($search) ?>" style="max-width:280px">
                    <input type="hidden" name="status" value="<?= htmlspecialchars($filter) ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if($search): ?><a href="manage_appointments.php" class="btn btn-outline">Clear</a><?php endif; ?>
                </form>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <?php foreach([''=>'All','pending'=>'Pending','confirmed'=>'Confirmed','completed'=>'Completed','cancelled'=>'Cancelled'] as $v=>$l): ?>
                    <a href="?status=<?= $v ?>&q=<?= urlencode($search) ?>"
                       class="btn btn-sm <?= $filter===$v?'btn-primary':'btn-outline' ?>"
                       style="border-radius:20px"><?= $l ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Appointments</h3>
            <span style="font-size:.85rem;color:var(--text-light)"><?= $appointments->num_rows ?> records</span>
        </div>
        <div class="table-wrap">
            <?php if($appointments->num_rows>0): ?>
            <table>
                <thead>
                    <tr><th>#</th><th>Patient</th><th>Doctor</th><th>Specialization</th><th>Date</th><th>Time</th><th>Status</th><th>Change Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php $i=1; while($row=$appointments->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($row['patient_name']) ?></strong></td>
                    <td><?= htmlspecialchars($row['doctor_name']) ?></td>
                    <td style="font-size:.82rem"><?= htmlspecialchars($row['specialization']) ?></td>
                    <td><?= formatDate($row['appointment_date']) ?></td>
                    <td><?= htmlspecialchars($row['time_slot']) ?></td>
                    <td><?= getStatusBadge($row['status']) ?></td>
                    <td>
                        <form method="POST" style="display:flex;gap:6px">
                            <input type="hidden" name="appt_id" value="<?= $row['id'] ?>">
                            <select name="new_status" class="form-control" style="padding:5px 8px;font-size:.82rem;width:120px">
                                <?php foreach(['pending','confirmed','completed','cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $row['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                        </form>
                    </td>
                    <td>
                        <a href="?delete=<?= $row['id'] ?>&status=<?= $filter ?>&q=<?= urlencode($search) ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Delete this appointment?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state"><div class="icon">📅</div><p>No appointments found.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>