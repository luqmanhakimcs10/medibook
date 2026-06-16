<?php
// patient/medical_history.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('patient');

$uid   = $_SESSION['user_id'];
$error = $success = '';

// Delete record
if (isset($_GET['delete'])) {
    $rid = intval($_GET['delete']);
    $conn->query("DELETE FROM medical_history WHERE id=$rid AND patient_id=$uid");
    header("Location: medical_history.php?msg=deleted"); exit();
}

$msg = $_GET['msg'] ?? '';

// Add new record
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = sanitize($_POST['title'] ?? '');
    $type    = sanitize($_POST['record_type'] ?? 'other');
    $desc    = sanitize($_POST['description'] ?? '');
    $rdate   = sanitize($_POST['record_date'] ?? '');

    if (empty($title)) {
        $error = 'Title is required.';
    } else {
        $rdate_val = !empty($rdate) ? "'$rdate'" : 'NULL';
        $conn->query("INSERT INTO medical_history (patient_id, title, record_type, description, record_date)
                      VALUES ($uid, '$title', '$type', '$desc', $rdate_val)");
        $success = 'Health record added successfully!';
    }
}

$records = $conn->query("SELECT * FROM medical_history WHERE patient_id=$uid ORDER BY record_date DESC, created_at DESC");

$type_icons = [
    'allergy'         => ['icon'=>'🤧','color'=>'#FEE2E2','text'=>'#DC2626'],
    'chronic_disease' => ['icon'=>'💉','color'=>'#FEF3C7','text'=>'#D97706'],
    'surgery'         => ['icon'=>'🏥','color'=>'#DBEAFE','text'=>'#2563EB'],
    'vaccination'     => ['icon'=>'💪','color'=>'#D1FAE5','text'=>'#059669'],
    'lab_result'      => ['icon'=>'🔬','color'=>'#EDE9FE','text'=>'#7C3AED'],
    'other'           => ['icon'=>'📋','color'=>'#F3F4F6','text'=>'#6B7280'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical History — MediBook</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
    <?php include '../includes/sidebar_patient.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">🩺 Medical History</div>
        </div>
        <div class="page-content">
            <?php if ($error):   ?><div class="alert alert-danger">⚠️ <?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
            <?php if ($msg==='deleted'): ?><div class="alert alert-info">Record deleted.</div><?php endif; ?>

            <div style="display:grid;grid-template-columns:3fr 2fr;gap:24px">

                <!-- Records List -->
                <div>
                    <h3 style="margin-bottom:16px;font-size:1rem">Your Health Records</h3>
                    <?php if ($records && $records->num_rows > 0): ?>
                        <?php while ($r = $records->fetch_assoc()):
                            $ti = $type_icons[$r['record_type']] ?? $type_icons['other'];
                        ?>
                        <div class="card" style="margin-bottom:14px">
                            <div class="card-body" style="display:flex;gap:16px;align-items:flex-start">
                                <div style="width:46px;height:46px;border-radius:10px;background:<?= $ti['color'] ?>;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0">
                                    <?= $ti['icon'] ?>
                                </div>
                                <div style="flex:1">
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
                                        <strong style="font-size:.95rem"><?= htmlspecialchars($r['title']) ?></strong>
                                        <a href="?delete=<?= $r['id'] ?>" class="btn btn-danger btn-sm"
                                           onclick="return confirm('Delete this record?')" style="padding:4px 10px;font-size:.75rem">✕</a>
                                    </div>
                                    <span style="background:<?= $ti['color'] ?>;color:<?= $ti['text'] ?>;padding:2px 10px;border-radius:12px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em">
                                        <?= ucfirst(str_replace('_',' ', $r['record_type'])) ?>
                                    </span>
                                    <?php if ($r['description']): ?>
                                    <p style="font-size:.87rem;color:var(--text-mid);margin-top:8px;line-height:1.6"><?= nl2br(htmlspecialchars($r['description'])) ?></p>
                                    <?php endif; ?>
                                    <?php if ($r['record_date']): ?>
                                    <div style="font-size:.78rem;color:var(--text-light);margin-top:6px">📅 <?= formatDate($r['record_date']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <div class="empty-state" style="padding:50px 20px">
                        <div class="icon">🩺</div>
                        <p>No health records added yet.</p>
                        <p style="font-size:.85rem">Add your allergies, chronic conditions, surgeries, and vaccinations so your doctor is always informed.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Add Form -->
                <div>
                    <div class="card" style="position:sticky;top:90px">
                        <div class="card-header"><h3>➕ Add Health Record</h3></div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label>Title *</label>
                                    <input type="text" name="title" class="form-control" placeholder="e.g. Penicillin Allergy" required>
                                </div>
                                <div class="form-group">
                                    <label>Type</label>
                                    <select name="record_type" class="form-control">
                                        <option value="allergy">🤧 Allergy</option>
                                        <option value="chronic_disease">💉 Chronic Disease</option>
                                        <option value="surgery">🏥 Surgery</option>
                                        <option value="vaccination">💪 Vaccination</option>
                                        <option value="lab_result">🔬 Lab Result</option>
                                        <option value="other">📋 Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" class="form-control" rows="3" placeholder="Additional details..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="date" name="record_date" class="form-control">
                                </div>
                                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                                    💾 Save Record
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card" style="margin-top:16px;background:var(--primary-light);border-color:rgba(13,148,136,0.2)">
                        <div class="card-body" style="font-size:.83rem;color:var(--primary-dark)">
                            <strong>💡 Tip:</strong> Keep your medical history updated. Your doctor can see these records when writing prescriptions, helping avoid medication conflicts.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
