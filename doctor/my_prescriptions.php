<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('doctor');
$uid = $_SESSION['user_id'];
$prescriptions = $conn->query("SELECT p.*,u.name as patient_name,u.phone as patient_phone,a.appointment_date,a.time_slot FROM prescriptions p JOIN users u ON p.patient_id=u.id JOIN appointments a ON p.appointment_id=a.id WHERE p.doctor_id=$uid ORDER BY p.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Prescriptions — MediBook</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
<?php include '../includes/sidebar_doctor.php'; ?>
<div class="main-content">
<div class="topbar">
    <div class="topbar-title">💊 My Prescriptions</div>
    <div class="topbar-right">
        <button class="theme-toggle">🌙</button>
        <a href="write_prescription.php" class="btn btn-primary btn-sm">✍️ Write New</a>
    </div>
</div>
<div class="page-content">
    <div class="card">
        <div class="card-header"><h3>All Issued Prescriptions</h3><span style="font-size:.85rem;color:var(--text-light)"><?= $prescriptions->num_rows ?> total</span></div>
        <div class="table-wrap">
            <?php if($prescriptions->num_rows>0): ?>
            <table>
                <thead><tr><th>#</th><th>Rx No.</th><th>Patient</th><th>Phone</th><th>Appointment</th><th>Diagnosis</th><th>Date Issued</th><th>Actions</th></tr></thead>
                <tbody>
                <?php $i=1; while($rx=$prescriptions->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><span class="badge badge-info">#RX-<?= str_pad($rx['id'],5,'0',STR_PAD_LEFT) ?></span></td>
                    <td><strong><?= htmlspecialchars($rx['patient_name']) ?></strong></td>
                    <td style="font-size:.85rem"><?= htmlspecialchars($rx['patient_phone']??'—') ?></td>
                    <td style="font-size:.85rem"><?= formatDate($rx['appointment_date']) ?> · <?= htmlspecialchars($rx['time_slot']) ?></td>
                    <td style="max-width:160px;font-size:.85rem"><?= htmlspecialchars(substr($rx['diagnosis'],0,50)) ?>...</td>
                    <td style="font-size:.85rem"><?= date('d M Y',strtotime($rx['created_at'])) ?></td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <button onclick="printPrescription(<?= $rx['id'] ?>)" class="btn btn-primary btn-sm">🖨️ Print</button>
                            <a href="write_prescription.php?appt=<?= $rx['appointment_id'] ?>" class="btn btn-outline btn-sm">✏️ Edit</a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state"><div class="icon">📝</div><p>No prescriptions written yet.</p><a href="write_prescription.php" class="btn btn-primary">Write First Prescription</a></div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>