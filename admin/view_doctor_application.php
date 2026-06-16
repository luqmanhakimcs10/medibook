<?php
// admin/view_doctor_application.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('admin');

$doc_id = intval($_GET['id'] ?? 0);
if (!$doc_id) { header("Location: pending_doctors.php"); exit(); }

// Fetch full application
$app = $conn->query(
    "SELECT u.*, d.id as doctor_id, d.specialization, d.qualification,
            d.experience_years, d.consultation_fee, d.bio, d.available_days,
            d.status, d.rejection_reason, d.applied_at
     FROM doctors d JOIN users u ON d.user_id = u.id
     WHERE d.id = $doc_id"
)->fetch_assoc();

if (!$app) { header("Location: pending_doctors.php"); exit(); }

// Fetch documents
$docs = $conn->query("SELECT * FROM doctor_documents WHERE doctor_id = $doc_id ORDER BY uploaded_at");

// Handle Approve
if (isset($_POST['approve'])) {
    $uid = intval($app['id']);
    $conn->query("UPDATE doctors SET status='approved' WHERE id=$doc_id");
    $conn->query("UPDATE users SET is_active=1 WHERE id=$uid");

    // Add default time slots if none exist
    $has_slots = $conn->query("SELECT COUNT(*) as c FROM time_slots WHERE doctor_id=$doc_id")->fetch_assoc()['c'];
    if ($has_slots == 0) {
        $default_slots = ['09:00 AM','09:30 AM','10:00 AM','10:30 AM','11:00 AM','11:30 AM','02:00 PM','02:30 PM','03:00 PM','03:30 PM','04:00 PM'];
        foreach ($default_slots as $s) {
            $s = $conn->real_escape_string($s);
            $conn->query("INSERT INTO time_slots (doctor_id, slot_time, is_active) VALUES ($doc_id, '$s', 1)");
        }
    }

    addNotification($uid, '🎉 Application Approved!',
        'Congratulations! Your MediBook doctor account has been approved. You can now login and start accepting appointments.');
    sendEmailReminder($app['email'], $app['name'],
        'Your MediBook Application is Approved!',
        'Congratulations! Your doctor account on MediBook has been <strong>approved</strong>. You can now <a href="' . SITE_URL . '/auth/login.php">login here</a> and start managing your appointments.');

    header("Location: pending_doctors.php?msg=approved"); exit();
}

// Handle Reject
if (isset($_POST['reject'])) {
    $reason = sanitize($_POST['rejection_reason'] ?? 'Application did not meet our requirements.');
    $uid    = intval($app['id']);
    $conn->query("UPDATE doctors SET status='rejected', rejection_reason='$reason' WHERE id=$doc_id");

    addNotification($uid, 'Application Update',
        "Your MediBook doctor application was not approved. Reason: $reason. Please contact support for more information.");
    sendEmailReminder($app['email'], $app['name'],
        'MediBook Application Update',
        "We regret to inform you that your doctor application was not approved at this time. Reason: <strong>$reason</strong>. Please contact support if you have questions.");

    header("Location: pending_doctors.php?msg=rejected"); exit();
}

$doc_type_labels = [
    'degree_certificate' => '🎓 Degree Certificate',
    'pmdc_registration'  => '🏥 PMDC Registration',
    'experience_letter'  => '📋 Experience Letter',
    'cnic'               => '🪪 CNIC / National ID',
    'other'              => '📄 Other Document',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Application — MediBook Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
    <?php include '../includes/sidebar_admin.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">👁️ Review Doctor Application</div>
            <div class="topbar-right">
                <a href="pending_doctors.php" class="btn btn-outline btn-sm">← Back to Applications</a>
            </div>
        </div>
        <div class="page-content">

            <!-- Status Banner -->
            <?php
            $status_styles = [
                'pending'  => ['background:#FEF3C7;border-color:#FDE68A;color:#92400E', '⏳ Pending Review'],
                'approved' => ['background:#D1FAE5;border-color:#A7F3D0;color:#065F46', '✅ Approved'],
                'rejected' => ['background:#FEE2E2;border-color:#FCA5A5;color:#991B1B', '❌ Rejected'],
            ];
            $ss = $status_styles[$app['status']] ?? $status_styles['pending'];
            ?>
            <div style="padding:14px 20px;border-radius:var(--radius-sm);border:1px solid;margin-bottom:24px;font-weight:600;<?= $ss[0] ?>">
                Status: <?= $ss[1] ?>
                <?php if ($app['status'] === 'rejected' && $app['rejection_reason']): ?>
                    — <em style="font-weight:400"><?= htmlspecialchars($app['rejection_reason']) ?></em>
                <?php endif; ?>
            </div>

            <div style="display:grid;grid-template-columns:3fr 2fr;gap:24px">

                <!-- Left: Full Details -->
                <div style="display:flex;flex-direction:column;gap:20px">

                    <!-- Personal Info -->
                    <div class="card">
                        <div class="card-header"><h3>👤 Personal Information</h3></div>
                        <div class="card-body">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                                <?php $fields = [
                                    ['Full Name',    $app['name']],
                                    ['Email',        $app['email']],
                                    ['Phone',        $app['phone'] ?? '—'],
                                    ['Gender',       $app['gender'] ?? '—'],
                                    ['Applied On',   date('d M Y, h:i A', strtotime($app['applied_at']))],
                                ];
                                foreach ($fields as $f): ?>
                                <div>
                                    <div style="font-size:.72rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.08em;margin-bottom:3px"><?= $f[0] ?></div>
                                    <div style="font-weight:500"><?= htmlspecialchars($f[1]) ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Professional Info -->
                    <div class="card">
                        <div class="card-header"><h3>🏥 Professional Information</h3></div>
                        <div class="card-body">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                                <?php $pfields = [
                                    ['Specialization',      $app['specialization']],
                                    ['Qualification',       $app['qualification']],
                                    ['Experience',          $app['experience_years'] . ' years'],
                                    ['Consultation Fee',    'Rs ' . number_format($app['consultation_fee'], 0)],
                                    ['Available Days',      $app['available_days']],
                                ];
                                foreach ($pfields as $f): ?>
                                <div>
                                    <div style="font-size:.72rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.08em;margin-bottom:3px"><?= $f[0] ?></div>
                                    <div style="font-weight:500"><?= htmlspecialchars($f[1]) ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($app['bio']): ?>
                            <div>
                                <div style="font-size:.72rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px">Bio</div>
                                <p style="font-size:.9rem;color:var(--text-mid);line-height:1.6"><?= nl2br(htmlspecialchars($app['bio'])) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Uploaded Documents -->
                    <div class="card">
                        <div class="card-header">
                            <h3>📄 Uploaded Documents</h3>
                            <span style="font-size:.85rem;color:var(--text-light)"><?= $docs->num_rows ?> file(s)</span>
                        </div>
                        <div class="card-body">
                            <?php if ($docs->num_rows > 0): ?>
                            <div style="display:flex;flex-direction:column;gap:12px">
                                <?php while ($d = $docs->fetch_assoc()):
                                    $ext = strtolower(pathinfo($d['file_path'], PATHINFO_EXTENSION));
                                    $is_image = in_array($ext, ['jpg','jpeg','png']);
                                    $doc_url  = SITE_URL . '/' . $d['file_path'];
                                ?>
                                <div style="display:flex;align-items:center;gap:14px;padding:14px 16px;background:var(--bg);border-radius:var(--radius-sm);border:1px solid var(--border)">
                                    <span style="font-size:1.6rem"><?= $is_image ? '🖼️' : '📄' ?></span>
                                    <div style="flex:1">
                                        <div style="font-weight:600;font-size:.88rem"><?= $doc_type_labels[$d['doc_type']] ?? $d['doc_type'] ?></div>
                                        <div style="font-size:.78rem;color:var(--text-light)"><?= htmlspecialchars($d['file_name']) ?></div>
                                    </div>
                                    <a href="<?= $doc_url ?>" target="_blank" class="btn btn-primary btn-sm">
                                        <?= $is_image ? '🖼️ View' : '📄 Open' ?>
                                    </a>
                                    <a href="<?= $doc_url ?>" download="<?= htmlspecialchars($d['file_name']) ?>" class="btn btn-outline btn-sm">⬇️</a>
                                </div>
                                <?php endwhile; ?>
                            </div>
                            <?php else: ?>
                            <div class="empty-state" style="padding:30px"><div class="icon">📄</div><p>No documents uploaded.</p></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right: Decision Panel -->
                <div style="display:flex;flex-direction:column;gap:16px">

                    <?php if ($app['status'] === 'pending'): ?>

                    <!-- Approve -->
                    <div class="card" style="border-color:rgba(16,185,129,0.3)">
                        <div class="card-header" style="background:#D1FAE5"><h3 style="color:#059669">✅ Approve Application</h3></div>
                        <div class="card-body">
                            <p style="font-size:.88rem;color:var(--text-mid);margin-bottom:16px">
                                Approving will activate the doctor's account immediately. They will be notified by email and in-app notification.
                            </p>
                            <form method="POST" onsubmit="return confirm('Approve Dr. <?= htmlspecialchars($app['name']) ?>? This will activate their account.')">
                                <button type="submit" name="approve" class="btn btn-success" style="width:100%;justify-content:center;padding:13px">
                                    ✅ Approve & Activate Account
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Reject -->
                    <div class="card" style="border-color:rgba(239,68,68,0.3)">
                        <div class="card-header" style="background:#FEE2E2"><h3 style="color:#DC2626">❌ Reject Application</h3></div>
                        <div class="card-body">
                            <form method="POST" onsubmit="return confirm('Reject this application? The doctor will be notified.')">
                                <div class="form-group">
                                    <label>Rejection Reason <span style="color:var(--danger)">*</span></label>
                                    <textarea name="rejection_reason" class="form-control" rows="4" required
                                        placeholder="e.g. Degree certificate is not legible. Please reapply with clear documents."></textarea>
                                </div>
                                <button type="submit" name="reject" class="btn btn-danger" style="width:100%;justify-content:center">
                                    ❌ Reject Application
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php else: ?>
                    <!-- Already decided -->
                    <div class="card">
                        <div class="card-body" style="text-align:center;padding:30px">
                            <div style="font-size:2.5rem;margin-bottom:12px">
                                <?= $app['status']==='approved' ? '✅' : '❌' ?>
                            </div>
                            <div style="font-weight:700;font-size:1rem;margin-bottom:6px">
                                Application <?= ucfirst($app['status']) ?>
                            </div>
                            <?php if ($app['status']==='rejected' && $app['rejection_reason']): ?>
                            <p style="font-size:.85rem;color:var(--text-mid)">Reason: <?= htmlspecialchars($app['rejection_reason']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Checklist -->
                    <div class="card">
                        <div class="card-header"><h3>📋 Review Checklist</h3></div>
                        <div class="card-body">
                            <div style="display:flex;flex-direction:column;gap:10px;font-size:.85rem">
                                <?php
                                $checks = [
                                    'Name and email are legitimate',
                                    'Specialization matches qualification',
                                    'Degree certificate is valid and legible',
                                    'PMDC registration is verifiable',
                                    'Experience claimed matches documents',
                                    'CNIC is valid',
                                ];
                                foreach ($checks as $c): ?>
                                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;color:var(--text-mid)">
                                    <input type="checkbox" style="width:16px;height:16px;accent-color:var(--primary)">
                                    <?= $c ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
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