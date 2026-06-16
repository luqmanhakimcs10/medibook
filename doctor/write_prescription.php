<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('doctor');
$uid     = $_SESSION['user_id'];
$error   = $success = '';
$appt_id = intval($_GET['appt'] ?? 0);

$appointments = $conn->query("SELECT a.*,u.name as patient_name,u.gender as patient_gender FROM appointments a JOIN users u ON a.patient_id=u.id WHERE a.doctor_id=$uid AND a.status IN ('confirmed','completed') ORDER BY a.appointment_date DESC");

$selected_appt = null;
if ($appt_id) {
    $res = $conn->query("SELECT a.*,u.name as patient_name,u.gender as patient_gender FROM appointments a JOIN users u ON a.patient_id=u.id WHERE a.id=$appt_id AND a.doctor_id=$uid");
    $selected_appt = $res ? $res->fetch_assoc() : null;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $appt_id_post = intval($_POST['appointment_id'] ?? 0);
    $diagnosis    = sanitize($_POST['diagnosis'] ?? '');
    $medicines    = $conn->real_escape_string($_POST['medicines'] ?? '');
    $instructions = sanitize($_POST['instructions'] ?? '');
    $follow_up    = sanitize($_POST['follow_up_date'] ?? '');

    if (!$appt_id_post || !$diagnosis || !$medicines) {
        $error = 'Please fill in appointment, diagnosis and medicines.';
    } else {
        $appt_row = $conn->query("SELECT patient_id FROM appointments WHERE id=$appt_id_post AND doctor_id=$uid")->fetch_assoc();
        if (!$appt_row) {
            $error = 'Invalid appointment selected.';
        } else {
            $patient_id = $appt_row['patient_id'];
            $follow_val = !empty($follow_up) ? "'$follow_up'" : 'NULL';
            $exists = $conn->query("SELECT id FROM prescriptions WHERE appointment_id=$appt_id_post")->num_rows;
            if ($exists > 0) {
                $conn->query("UPDATE prescriptions SET diagnosis='$diagnosis',medicines='$medicines',instructions='$instructions',follow_up_date=$follow_val WHERE appointment_id=$appt_id_post");
            } else {
                $conn->query("INSERT INTO prescriptions (appointment_id,doctor_id,patient_id,diagnosis,medicines,instructions,follow_up_date) VALUES ($appt_id_post,$uid,$patient_id,'$diagnosis','$medicines','$instructions',$follow_val)");
            }
            $conn->query("UPDATE appointments SET status='completed' WHERE id=$appt_id_post AND doctor_id=$uid");
            addNotification($patient_id,'Prescription Ready!','Your doctor has written a prescription. Login to view and download it.');
            $patient = getUserById($patient_id);
            sendEmailReminder($patient['email'],$patient['name'],'Your Prescription is Ready — MediBook','Your doctor has written a digital prescription. Please login to MediBook to view and download it.');
            $success = 'Prescription saved successfully! Patient has been notified.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Write Prescription — MediBook</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
<?php include '../includes/sidebar_doctor.php'; ?>
<div class="main-content">
<div class="topbar">
    <div class="topbar-title">📝 Write Prescription</div>
    <div class="topbar-right">
        <button class="theme-toggle">🌙</button>
        <a href="my_prescriptions.php" class="btn btn-outline btn-sm">View All Prescriptions</a>
    </div>
</div>
<div class="page-content">
    <?php if($error): ?><div class="alert alert-danger">⚠️ <?= $error ?></div><?php endif; ?>
    <?php if($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px">
        <div class="card">
            <div class="card-header"><h3>📝 New Prescription</h3></div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label>Select Appointment *</label>
                        <select name="appointment_id" id="appt_select" class="form-control" required onchange="loadPatientInfo(this)">
                            <option value="">-- Select Patient Appointment --</option>
                            <?php if($appointments){ $appointments->data_seek(0); while($a=$appointments->fetch_assoc()): ?>
                            <option value="<?= $a['id'] ?>" data-name="<?= htmlspecialchars($a['patient_name']) ?>" data-gender="<?= htmlspecialchars($a['patient_gender']??'') ?>" data-date="<?= $a['appointment_date'] ?>" data-time="<?= htmlspecialchars($a['time_slot']) ?>" <?= ($appt_id&&$appt_id==$a['id'])?'selected':'' ?>>
                                <?= htmlspecialchars($a['patient_name']) ?> — <?= formatDate($a['appointment_date']) ?> <?= htmlspecialchars($a['time_slot']) ?> (<?= ucfirst($a['status']) ?>)
                            </option>
                            <?php endwhile; } ?>
                        </select>
                    </div>
                    <div id="patient-preview" style="display:none;background:var(--bg);border-radius:var(--r-sm);padding:14px 18px;margin-bottom:20px;font-size:.88rem">
                        <div style="display:flex;gap:24px;flex-wrap:wrap">
                            <div><span style="color:var(--text-light)">Patient:</span> <strong id="prev-name">—</strong></div>
                            <div><span style="color:var(--text-light)">Gender:</span> <strong id="prev-gender">—</strong></div>
                            <div><span style="color:var(--text-light)">Date:</span> <strong id="prev-date">—</strong></div>
                            <div><span style="color:var(--text-light)">Time:</span> <strong id="prev-time">—</strong></div>
                        </div>
                    </div>
                    <div class="form-group"><label>Diagnosis *</label><textarea name="diagnosis" class="form-control" rows="3" placeholder="e.g. Acute pharyngitis, Hypertension Stage 1..." required></textarea></div>
                    <div class="form-group">
                        <label>Medicines / Dosage * <span style="font-weight:400;color:var(--text-light);font-size:.8rem">(one per line)</span></label>
                        <textarea name="medicines" class="form-control" rows="6" placeholder="e.g.&#10;Panadol 500mg — 1 tablet 3x daily for 5 days&#10;Augmentin 625mg — 1 tablet 2x daily for 7 days" required></textarea>
                    </div>
                    <div class="form-group"><label>Instructions / Advice</label><textarea name="instructions" class="form-control" rows="3" placeholder="e.g. Drink plenty of fluids. Rest for 2 days. Avoid cold drinks."></textarea></div>
                    <div class="form-group"><label>Follow-up Date <span style="font-weight:400;color:var(--text-light);font-size:.8rem">(optional)</span></label><input type="date" name="follow_up_date" class="form-control" min="<?= date('Y-m-d') ?>"></div>
                    <div style="display:flex;gap:12px">
                        <button type="submit" class="btn btn-primary btn-lg">💾 Save Prescription</button>
                        <a href="appointments.php" class="btn btn-outline btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:16px">
            <div class="card">
                <div class="card-header"><h3>📌 Writing Tips</h3></div>
                <div class="card-body" style="font-size:.88rem;color:var(--text-mid)">
                    <ul style="padding-left:16px;display:flex;flex-direction:column;gap:10px">
                        <li>Write one medicine per line for clarity</li>
                        <li>Include dosage, frequency and duration</li>
                        <li>Mention if taken before or after food</li>
                        <li>Add clear follow-up instructions</li>
                        <li>Patient will be notified automatically</li>
                    </ul>
                </div>
            </div>
            <div class="card" style="background:var(--primary-light);border-color:rgba(13,148,136,.2)">
                <div class="card-body"><div style="font-size:1.4rem;margin-bottom:8px">💊</div><p style="font-size:.88rem;color:var(--primary-dark);font-weight:500">Once saved, the prescription is immediately available to the patient. They can view and print it as a PDF.</p></div>
            </div>
        </div>
    </div>
</div>
</div>
</div>
<script src="../assets/js/main.js"></script>
<script>
function loadPatientInfo(select) {
    const opt = select.options[select.selectedIndex];
    const preview = document.getElementById('patient-preview');
    if (select.value) {
        document.getElementById('prev-name').textContent   = opt.dataset.name   || '—';
        document.getElementById('prev-gender').textContent = opt.dataset.gender || '—';
        document.getElementById('prev-date').textContent   = opt.dataset.date   || '—';
        document.getElementById('prev-time').textContent   = opt.dataset.time   || '—';
        preview.style.display = 'block';
    } else { preview.style.display = 'none'; }
}
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('appt_select');
    if (sel && sel.value) loadPatientInfo(sel);
});
</script>
</body>
</html>