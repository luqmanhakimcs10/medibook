<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('admin');

$msg = $_GET['msg'] ?? '';

$pending = $conn->query(
    "SELECT u.id as user_id, u.name, u.email, u.phone, u.gender, u.city, u.created_at,
            d.id as doctor_id, d.specialization, d.qualification,
            d.experience_years, d.consultation_fee, d.status, d.applied_at,
            (SELECT COUNT(*) FROM doctor_documents dd WHERE dd.doctor_id=d.id) as doc_count
     FROM users u
     JOIN doctors d ON d.user_id=u.id
     WHERE d.status='pending'
     ORDER BY d.applied_at DESC"
);

$approved_count = $conn->query("SELECT COUNT(*) as c FROM doctors WHERE status='approved'")->fetch_assoc()['c'];
$rejected_count = $conn->query("SELECT COUNT(*) as c FROM doctors WHERE status='rejected'")->fetch_assoc()['c'];

$all_apps = $conn->query(
    "SELECT u.name, u.email, u.city, d.specialization, d.status, d.applied_at, d.id as doctor_id
     FROM users u JOIN doctors d ON d.user_id=u.id
     WHERE u.role='doctor'
     ORDER BY d.applied_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Applications — MediBook</title>
    <script src="../assets/js/dark-mode.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
</head>
<body>
<div class="dashboard-wrapper">
<?php include '../includes/sidebar_admin.php'; ?>
<div class="main-content">
<div class="topbar">
    <div class="topbar-title">📋 Doctor Applications</div>
    <div class="topbar-right"><button class="theme-toggle">🌙</button></div>
</div>
<div class="page-content">
    <?php if($msg==='approved'): ?><div class="alert alert-success">✅ Doctor approved! They can now login.</div><?php endif; ?>
    <?php if($msg==='rejected'): ?><div class="alert alert-danger">Application rejected. Doctor notified.</div><?php endif; ?>

    <!-- Stats -->
    <div class="stats-row" style="margin-bottom:24px">
        <div class="stat-card"><div class="stat-card-icon amber">⏳</div><div><div class="stat-num"><?= $pending->num_rows ?></div><div class="stat-label">Pending Review</div></div></div>
        <div class="stat-card"><div class="stat-card-icon green">✅</div><div><div class="stat-num"><?= $approved_count ?></div><div class="stat-label">Approved Doctors</div></div></div>
        <div class="stat-card"><div class="stat-card-icon red">✕</div><div><div class="stat-num"><?= $rejected_count ?></div><div class="stat-label">Rejected</div></div></div>
    </div>

    <!-- Pending Applications -->
    <div class="card" style="margin-bottom:24px">
        <div class="card-header">
            <h3>⏳ Pending Applications</h3>
            <span style="font-size:.85rem;color:var(--text-light)"><?= $pending->num_rows ?> awaiting review</span>
        </div>
        <?php if($pending->num_rows>0): ?>
            <?php while($doc=$pending->fetch_assoc()): ?>
            <div style="padding:22px 24px;border-bottom:1px solid var(--border)">
                <div style="display:flex;gap:16px;align-items:flex-start">
                    <div style="width:52px;height:52px;background:var(--info);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Sora',sans-serif;font-weight:700;font-size:1.2rem;flex-shrink:0">
                        <?= strtoupper(substr($doc['name'],3,1)) ?>
                    </div>
                    <div style="flex:1">
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px">
                            <strong style="font-size:1rem"><?= htmlspecialchars($doc['name']) ?></strong>
                            <span class="badge badge-warning">Pending</span>
                            <span style="font-size:.78rem;color:var(--text-light)">Applied: <?= date('d M Y',strtotime($doc['applied_at'])) ?></span>
                        </div>
                        <div style="display:flex;gap:20px;flex-wrap:wrap;font-size:.85rem;color:var(--text-mid);margin-bottom:10px">
                            <span>🏥 <?= htmlspecialchars($doc['specialization']) ?></span>
                            <span>🎓 <?= htmlspecialchars($doc['qualification']) ?></span>
                            <span>⏱ <?= $doc['experience_years'] ?> yrs</span>
                            <span>💰 Rs <?= number_format($doc['consultation_fee'],0) ?></span>
                            <span>📍 <?= htmlspecialchars($doc['city']??'N/A') ?></span>
                            <span>✉️ <?= htmlspecialchars($doc['email']) ?></span>
                        </div>
                        <div style="font-size:.82rem;color:var(--text-light)">📎 <?= $doc['doc_count'] ?> document(s) uploaded</div>
                    </div>
                    <a href="view_doctor_application.php?id=<?= $doc['doctor_id'] ?>" class="btn btn-primary btn-sm" style="flex-shrink:0">👁️ Review Application</a>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
        <div class="empty-state" style="padding:50px"><div class="icon">✅</div><p>No pending applications. All caught up!</p></div>
        <?php endif; ?>
    </div>

    <!-- All Applications History -->
    <div class="card">
        <div class="card-header"><h3>📋 All Applications History</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Doctor</th><th>Email</th><th>City</th><th>Specialization</th><th>Applied</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php while($r=$all_apps->fetch_assoc()):
                    $badge = ['pending'=>'badge-warning','approved'=>'badge-success','rejected'=>'badge-danger'];
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
                    <td style="font-size:.85rem"><?= htmlspecialchars($r['email']) ?></td>
                    <td style="font-size:.85rem"><?= htmlspecialchars($r['city']??'—') ?></td>
                    <td><?= htmlspecialchars($r['specialization']) ?></td>
                    <td style="font-size:.82rem"><?= date('d M Y',strtotime($r['applied_at'])) ?></td>
                    <td><span class="badge <?= $badge[$r['status']]??'badge-secondary' ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td><a href="view_doctor_application.php?id=<?= $r['doctor_id'] ?>" class="btn btn-outline btn-sm">Review</a></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>