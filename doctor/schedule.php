<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('doctor');
$uid = $_SESSION['user_id'];
$doc = getDoctorInfo($uid);
$doc_id = $doc['id'] ?? 0;
$error = $success = '';

if (isset($_POST['add_slot'])) {
    $slot_time = sanitize($_POST['slot_time'] ?? '');
    if ($slot_time && $doc_id) {
        // Convert 24h to 12h AM/PM
        $slot_formatted = date('h:i A', strtotime($slot_time));
        $exists = $conn->query("SELECT id FROM time_slots WHERE doctor_id=$doc_id AND slot_time='$slot_formatted'")->num_rows;
        if ($exists) { $error = 'This slot already exists.'; }
        else { $conn->query("INSERT INTO time_slots (doctor_id,slot_time,is_active) VALUES ($doc_id,'$slot_formatted',1)"); $success = "Slot '$slot_formatted' added successfully."; }
    }
}
if (isset($_GET['toggle'])) { $sid=intval($_GET['toggle']); $conn->query("UPDATE time_slots SET is_active=1-is_active WHERE id=$sid AND doctor_id=$doc_id"); header("Location: schedule.php"); exit(); }
if (isset($_GET['delete'])) { $sid=intval($_GET['delete']); $conn->query("DELETE FROM time_slots WHERE id=$sid AND doctor_id=$doc_id"); $success='Slot deleted.'; }
if (isset($_POST['update_info'])) {
    $days = isset($_POST['days']) ? implode(',',array_map('sanitize',$_POST['days'])) : '';
    $bio  = sanitize($_POST['bio'] ?? '');
    $fee  = floatval($_POST['consultation_fee'] ?? 0);
    $conn->query("UPDATE doctors SET available_days='$days',bio='$bio',consultation_fee=$fee WHERE id=$doc_id");
    $success = 'Schedule information updated!';
    $doc = getDoctorInfo($uid);
}

$slots = $conn->query("SELECT * FROM time_slots WHERE doctor_id=$doc_id ORDER BY STR_TO_DATE(slot_time,'%h:%i %p')");
$all_days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
$active_days = explode(',', $doc['available_days'] ?? '');
$upcoming = $conn->query("SELECT a.*,u.name as patient_name FROM appointments a JOIN users u ON a.patient_id=u.id WHERE a.doctor_id=$uid AND a.appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY) AND a.status!='cancelled' ORDER BY a.appointment_date,a.time_slot");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Schedule — MediBook</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
<?php include '../includes/sidebar_doctor.php'; ?>
<div class="main-content">
<div class="topbar">
    <div class="topbar-title">🗓️ My Schedule</div>
    <div class="topbar-right"><button class="theme-toggle">🌙</button></div>
</div>
<div class="page-content">
    <?php if($error): ?><div class="alert alert-danger">⚠️ <?= $error ?></div><?php endif; ?>
    <?php if($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
        <div class="card">
            <div class="card-header"><h3>🗓️ Availability Settings</h3></div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label>Available Days</label>
                        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:6px">
                            <?php foreach($all_days as $day): ?>
                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;padding:8px 14px;border:2px solid var(--border);border-radius:8px;font-weight:600;font-size:.88rem;transition:all .2s" id="lbl-<?= $day ?>">
                                <input type="checkbox" name="days[]" value="<?= $day ?>" <?= in_array($day,$active_days)?'checked':'' ?> onchange="styleLabel('<?= $day ?>',this.checked)" style="display:none">
                                <?= $day ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group"><label>Consultation Fee (Rs)</label><input type="number" name="consultation_fee" class="form-control" value="<?= $doc['consultation_fee']??0 ?>" min="0" step="50"></div>
                    <div class="form-group"><label>Bio / About</label><textarea name="bio" class="form-control" rows="4" placeholder="Write a short bio..."><?= htmlspecialchars($doc['bio']??'') ?></textarea></div>
                    <button type="submit" name="update_info" class="btn btn-primary">💾 Save Settings</button>
                </form>
            </div>
        </div>
        <div>
            <div class="card" style="margin-bottom:20px">
                <div class="card-header"><h3>⏰ Add Time Slot</h3></div>
                <div class="card-body">
                    <form method="POST" style="display:flex;gap:12px;align-items:flex-end">
                        <div class="form-group" style="flex:1;margin:0"><label>Time</label><input type="time" name="slot_time" class="form-control" required></div>
                        <button type="submit" name="add_slot" class="btn btn-primary">Add</button>
                    </form>
                    <p style="font-size:.8rem;color:var(--text-light);margin-top:10px">Add your available consultation times. These will be shown to patients when booking.</p>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3>Current Time Slots</h3><span style="font-size:.82rem;color:var(--text-light)"><?= $slots->num_rows ?> slots</span></div>
                <div style="padding:16px">
                    <?php if($slots->num_rows>0): ?>
                    <div style="display:flex;flex-wrap:wrap;gap:10px">
                        <?php while($slot=$slots->fetch_assoc()): ?>
                        <div style="display:flex;align-items:center;gap:8px;padding:8px 14px;border:2px solid <?= $slot['is_active']?'var(--primary)':'var(--border)' ?>;border-radius:8px;background:<?= $slot['is_active']?'var(--primary-light)':'var(--bg)' ?>">
                            <span style="font-weight:600;font-size:.88rem;color:<?= $slot['is_active']?'var(--primary)':'var(--text-light)' ?>"><?= htmlspecialchars($slot['slot_time']) ?></span>
                            <a href="?toggle=<?= $slot['id'] ?>" title="<?= $slot['is_active']?'Deactivate':'Activate' ?>" style="color:<?= $slot['is_active']?'var(--primary)':'var(--text-light)' ?>;font-size:.85rem;text-decoration:none"><?= $slot['is_active']?'✓':'○' ?></a>
                            <a href="?delete=<?= $slot['id'] ?>" onclick="return confirm('Delete this slot?')" style="color:var(--danger);font-size:.85rem;text-decoration:none">✕</a>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <p style="font-size:.78rem;color:var(--text-light);margin-top:12px">Click ✓/○ to toggle. Click ✕ to delete.</p>
                    <?php else: ?>
                    <div class="empty-state" style="padding:24px"><div class="icon">⏰</div><p>No slots added yet.</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:24px">
        <div class="card-header"><h3>📅 Upcoming Appointments (Next 7 Days)</h3></div>
        <div class="table-wrap">
            <?php if($upcoming&&$upcoming->num_rows>0): ?>
            <table><thead><tr><th>Patient</th><th>Date</th><th>Time</th><th>Status</th><th>Action</th></tr></thead><tbody>
            <?php while($r=$upcoming->fetch_assoc()): ?>
            <tr><td><strong><?= htmlspecialchars($r['patient_name']) ?></strong></td><td><?= formatDate($r['appointment_date']) ?></td><td><?= htmlspecialchars($r['time_slot']) ?></td><td><?= getStatusBadge($r['status']) ?></td><td><a href="appointments.php" class="btn btn-outline btn-sm">Manage</a></td></tr>
            <?php endwhile; ?>
            </tbody></table>
            <?php else: ?><div class="empty-state"><div class="icon">🗓️</div><p>No appointments in the next 7 days.</p></div><?php endif; ?>
        </div>
    </div>
</div>
</div>
</div>
<script src="../assets/js/main.js"></script>
<script>
document.addEventListener('DOMContentLoaded',()=>{ document.querySelectorAll('input[name="days[]"]').forEach(cb=>styleLabel(cb.value,cb.checked)); });
function styleLabel(day,checked) {
    const lbl=document.getElementById('lbl-'+day);
    if(!lbl) return;
    lbl.style.borderColor=checked?'var(--primary)':'var(--border)';
    lbl.style.background=checked?'var(--primary-light)':'white';
    lbl.style.color=checked?'var(--primary)':'var(--text-mid)';
}
</script>
</body>
</html>