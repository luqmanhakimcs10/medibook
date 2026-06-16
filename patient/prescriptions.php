<?php
// patient/prescriptions.php
// BUG FIX: This file was accidentally getting the content of print_prescription.php
//           which caused "Prescription not found." to show at the patient URL.
//           This is the correct prescriptions LIST page for patients.

require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('patient');

$uid         = $_SESSION['user_id'];
$appt_filter = intval($_GET['appt'] ?? 0);

$where = "WHERE p.patient_id = $uid";
if ($appt_filter > 0) {
    $where .= " AND p.appointment_id = $appt_filter";
}

$prescriptions = $conn->query(
    "SELECT p.*,
            u.name  AS doctor_name,
            u.phone AS doctor_phone,
            d.specialization,
            d.qualification,
            a.appointment_date,
            a.time_slot,
            a.symptoms
     FROM prescriptions p
     JOIN users u       ON p.doctor_id       = u.id
     JOIN doctors d     ON d.user_id         = u.id
     JOIN appointments a ON p.appointment_id = a.id
     $where
     ORDER BY p.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Prescriptions — MediBook</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
    <?php include '../includes/sidebar_patient.php'; ?>
    <div class="main-content">

        <div class="topbar">
            <div class="topbar-title">💊 My Prescriptions</div>
            <?php if ($appt_filter): ?>
            <div class="topbar-right">
                <a href="prescriptions.php" class="btn btn-outline btn-sm">← All Prescriptions</a>
            </div>
            <?php endif; ?>
        </div>

        <div class="page-content">

            <?php if (!$prescriptions || $prescriptions->num_rows === 0): ?>
            <!-- EMPTY STATE — shown when no prescriptions exist yet -->
            <div class="empty-state" style="padding:80px 20px">
                <div class="icon">💊</div>
                <p style="font-size:1rem;margin-bottom:8px">
                    <?php if ($appt_filter): ?>
                        No prescription has been written for this appointment yet.
                    <?php else: ?>
                        You don't have any prescriptions yet.
                    <?php endif; ?>
                </p>
                <p style="font-size:.85rem;color:var(--text-light);margin-bottom:24px">
                    Prescriptions appear here after your doctor completes your appointment and writes a prescription.
                </p>
                <a href="book_appointment.php" class="btn btn-primary">Book an Appointment</a>
            </div>

            <?php else: ?>
            <!-- PRESCRIPTION CARDS -->
            <?php while ($rx = $prescriptions->fetch_assoc()): ?>
            <div class="rx-card" style="margin-bottom:24px">

                <!-- Card Header -->
                <div class="rx-header">
                    <div>
                        <div style="font-size:.75rem;opacity:.8;margin-bottom:3px;text-transform:uppercase;letter-spacing:.08em">Prescription</div>
                        <div style="font-family:'Sora',sans-serif;font-weight:800;font-size:1.15rem">
                            #RX-<?= str_pad($rx['id'], 5, '0', STR_PAD_LEFT) ?>
                        </div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-weight:600;font-size:.95rem"><?= htmlspecialchars($rx['doctor_name']) ?></div>
                        <div style="font-size:.82rem;opacity:.85"><?= htmlspecialchars($rx['specialization']) ?></div>
                        <div style="font-size:.78rem;opacity:.7;margin-top:3px"><?= htmlspecialchars($rx['qualification']) ?></div>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="rx-body">

                    <!-- Meta Info Row -->
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:22px;padding-bottom:20px;border-bottom:1px dashed var(--border)">
                        <div>
                            <div style="font-size:.72rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.1em;margin-bottom:4px">Appointment Date</div>
                            <div style="font-weight:600">📅 <?= formatDate($rx['appointment_date']) ?></div>
                            <div style="font-size:.82rem;color:var(--text-mid)">⏰ <?= htmlspecialchars($rx['time_slot']) ?></div>
                        </div>
                        <div>
                            <div style="font-size:.72rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.1em;margin-bottom:4px">Issued On</div>
                            <div style="font-weight:600"><?= date('d M Y', strtotime($rx['created_at'])) ?></div>
                        </div>
                        <?php if ($rx['follow_up_date']): ?>
                        <div>
                            <div style="font-size:.72rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.1em;margin-bottom:4px">Follow-up</div>
                            <div style="font-weight:600;color:var(--accent)">📆 <?= formatDate($rx['follow_up_date']) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Diagnosis -->
                    <div class="rx-section">
                        <h4>🔬 Diagnosis</h4>
                        <p style="font-size:.95rem;line-height:1.6"><?= nl2br(htmlspecialchars($rx['diagnosis'] ?: '—')) ?></p>
                    </div>

                    <!-- Medicines -->
                    <div class="rx-section">
                        <h4>💊 Prescribed Medicines</h4>
                        <?php
                        $meds = array_filter(array_map('trim', explode("\n", $rx['medicines'] ?? '')));
                        if ($meds):
                        ?>
                        <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px">
                            <?php $n=1; foreach ($meds as $med): ?>
                            <div style="display:flex;align-items:center;gap:12px;padding:10px 16px;background:var(--bg);border-radius:8px;border-left:3px solid var(--primary)">
                                <span style="width:22px;height:22px;background:var(--primary);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;flex-shrink:0"><?= $n++ ?></span>
                                <span style="font-size:.92rem"><?= htmlspecialchars($med) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p>—</p>
                        <?php endif; ?>
                    </div>

                    <!-- Instructions -->
                    <?php if ($rx['instructions']): ?>
                    <div class="rx-section">
                        <h4>📋 Instructions / Advice</h4>
                        <p style="font-size:.92rem;line-height:1.7;color:var(--text-mid)"><?= nl2br(htmlspecialchars($rx['instructions'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border);display:flex;gap:12px;flex-wrap:wrap">
                        <button onclick="printPrescription(<?= $rx['id'] ?>)" class="btn btn-primary">
                            🖨️ Print / Download PDF
                        </button>
                        <?php if ($rx['follow_up_date']): ?>
                        <a href="book_appointment.php" class="btn btn-outline">
                            📅 Book Follow-up
                        </a>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
            <?php endwhile; ?>
            <?php endif; ?>

        </div><!-- /page-content -->
    </div><!-- /main-content -->
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>