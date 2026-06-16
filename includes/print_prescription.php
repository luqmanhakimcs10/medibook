<?php
// includes/print_prescription.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireLogin();

$id  = intval($_GET['id'] ?? 0);
$uid = $_SESSION['user_id'];

$rx = $conn->query("SELECT p.*, 
                    u1.name as doctor_name, u1.phone as doctor_phone, u1.email as doctor_email,
                    u2.name as patient_name, u2.phone as patient_phone, u2.gender as patient_gender, u2.date_of_birth,
                    d.specialization, d.qualification,
                    a.appointment_date, a.time_slot, a.symptoms
                    FROM prescriptions p
                    JOIN users u1 ON p.doctor_id = u1.id
                    JOIN users u2 ON p.patient_id = u2.id
                    JOIN doctors d ON d.user_id = u1.id
                    JOIN appointments a ON p.appointment_id = a.id
                    WHERE p.id = $id")->fetch_assoc();

if (!$rx) { die('Prescription not found.'); }
// Only doctor or patient of this Rx can view
if ($uid != $rx['doctor_id'] && $uid != $rx['patient_id'] && $_SESSION['role'] != 'admin') {
    die('Access denied.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prescription #<?= $id ?> — MediBook</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap');
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:#f5f5f5; padding:30px; }
        .rx-page { max-width:760px; margin:0 auto; background:white; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.1); }
        .rx-top { background:linear-gradient(135deg,#0D9488,#0A7A70); color:white; padding:28px 36px; display:flex; justify-content:space-between; align-items:flex-start; }
        .rx-brand { font-family:'Sora',sans-serif; font-size:1.8rem; font-weight:800; }
        .rx-brand span { color:#F59E0B; }
        .rx-brand small { display:block; font-size:.75rem; font-weight:400; opacity:.8; margin-top:2px; }
        .rx-num { text-align:right; font-size:.85rem; opacity:.85; }
        .rx-num strong { font-size:1.1rem; font-weight:700; }
        .rx-body { padding:32px 36px; }
        .info-row { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px; padding-bottom:24px; border-bottom:2px dashed #e5e7eb; }
        .info-block label { font-size:.72rem; font-weight:700; color:#9CA3AF; text-transform:uppercase; letter-spacing:.1em; display:block; margin-bottom:4px; }
        .info-block p { font-size:.95rem; color:#111827; font-weight:500; }
        .rx-section { margin-bottom:22px; }
        .rx-section h3 { font-family:'Sora',sans-serif; font-size:.8rem; font-weight:700; color:#0D9488; text-transform:uppercase; letter-spacing:.12em; border-bottom:1px solid #e5e7eb; padding-bottom:6px; margin-bottom:12px; }
        .rx-section p { color:#374151; line-height:1.7; font-size:.95rem; }
        .medicine-table { width:100%; border-collapse:collapse; font-size:.88rem; }
        .medicine-table th { background:#F0FDFC; padding:10px 14px; text-align:left; font-size:.76rem; font-weight:700; color:#0D9488; text-transform:uppercase; }
        .medicine-table td { padding:10px 14px; border-bottom:1px solid #F3F4F6; }
        .rx-footer { margin-top:30px; padding-top:20px; border-top:2px solid #e5e7eb; display:flex; justify-content:space-between; align-items:flex-end; }
        .sig-area { text-align:center; }
        .sig-line { width:180px; border-top:2px solid #374151; margin:0 auto 8px; }
        .sig-label { font-size:.78rem; color:#6B7280; }
        .sig-name { font-family:'Sora',sans-serif; font-weight:700; font-size:.95rem; }
        .watermark { color:#0D9488; font-size:.75rem; opacity:.6; }
        .print-btn { position:fixed; top:20px; right:20px; background:#0D9488; color:white; border:none; padding:12px 24px; border-radius:8px; font-family:'DM Sans',sans-serif; font-weight:600; cursor:pointer; font-size:.9rem; }
        .print-btn:hover { background:#0A7A70; }
        @media print { .print-btn { display:none; } body { background:white; padding:0; } .rx-page { box-shadow:none; border-radius:0; } }
    </style>
</head>
<body>
<button class="print-btn" onclick="window.print()">🖨️ Print / Save PDF</button>
<div class="rx-page">
    <div class="rx-top">
        <div>
            <div class="rx-brand">Medi<span>Book</span></div>
            <small>Online Clinic Appointment System</small>
            <div style="margin-top:12px;font-size:.85rem;opacity:.85">
                📞 <?= htmlspecialchars($rx['doctor_phone']) ?> &nbsp;|&nbsp; ✉️ <?= htmlspecialchars($rx['doctor_email']) ?>
            </div>
        </div>
        <div class="rx-num">
            <div>Prescription</div>
            <strong>#RX-<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></strong>
            <div style="margin-top:6px;font-size:.8rem">Date: <?= formatDate($rx['created_at']) ?></div>
        </div>
    </div>

    <div class="rx-body">
        <div class="info-row">
            <div>
                <div class="info-block" style="margin-bottom:12px">
                    <label>Patient Name</label>
                    <p><?= htmlspecialchars($rx['patient_name']) ?></p>
                </div>
                <div class="info-block" style="margin-bottom:12px">
                    <label>Phone</label>
                    <p><?= htmlspecialchars($rx['patient_phone'] ?: '—') ?></p>
                </div>
                <div class="info-block">
                    <label>Gender</label>
                    <p><?= htmlspecialchars($rx['patient_gender'] ?: '—') ?></p>
                </div>
            </div>
            <div>
                <div class="info-block" style="margin-bottom:12px">
                    <label>Doctor</label>
                    <p><?= htmlspecialchars($rx['doctor_name']) ?></p>
                </div>
                <div class="info-block" style="margin-bottom:12px">
                    <label>Specialization</label>
                    <p><?= htmlspecialchars($rx['specialization']) ?></p>
                </div>
                <div class="info-block">
                    <label>Appointment Date</label>
                    <p><?= formatDate($rx['appointment_date']) ?> at <?= htmlspecialchars($rx['time_slot']) ?></p>
                </div>
            </div>
        </div>

        <?php if ($rx['symptoms']): ?>
        <div class="rx-section">
            <h3>Chief Complaints / Symptoms</h3>
            <p><?= nl2br(htmlspecialchars($rx['symptoms'])) ?></p>
        </div>
        <?php endif; ?>

        <div class="rx-section">
            <h3>Diagnosis</h3>
            <p><?= nl2br(htmlspecialchars($rx['diagnosis'] ?: '—')) ?></p>
        </div>

        <div class="rx-section">
            <h3>Rx — Prescribed Medicines</h3>
            <?php
            $meds = array_filter(array_map('trim', explode("\n", $rx['medicines'] ?? '')));
            if (count($meds) > 0):
            ?>
            <table class="medicine-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Medicine / Dosage</th>
                    </tr>
                </thead>
                <tbody>
                <?php $n=1; foreach ($meds as $med): ?>
                    <tr>
                        <td><?= $n++ ?></td>
                        <td><?= htmlspecialchars($med) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>—</p>
            <?php endif; ?>
        </div>

        <div class="rx-section">
            <h3>Instructions / Advice</h3>
            <p><?= nl2br(htmlspecialchars($rx['instructions'] ?: '—')) ?></p>
        </div>

        <?php if ($rx['follow_up_date']): ?>
        <div class="rx-section">
            <h3>Follow-up Date</h3>
            <p><?= formatDate($rx['follow_up_date']) ?></p>
        </div>
        <?php endif; ?>

        <div class="rx-footer">
            <div class="watermark">MediBook — Verified Digital Prescription</div>
            <div class="sig-area">
                <div class="sig-line"></div>
                <div class="sig-name"><?= htmlspecialchars($rx['doctor_name']) ?></div>
                <div class="sig-label"><?= htmlspecialchars($rx['specialization']) ?> · <?= htmlspecialchars($rx['qualification']) ?></div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
