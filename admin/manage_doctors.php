<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('admin');

if (isset($_GET['toggle'])) { $id=intval($_GET['toggle']); $conn->query("UPDATE users SET is_active=1-is_active WHERE id=$id AND role='doctor'"); header("Location: manage_doctors.php?msg=toggled"); exit(); }
if (isset($_GET['delete'])) { $id=intval($_GET['delete']); $conn->query("DELETE FROM users WHERE id=$id AND role='doctor'"); header("Location: manage_doctors.php?msg=deleted"); exit(); }

$msg    = $_GET['msg'] ?? '';
$search = sanitize($_GET['q'] ?? '');
$where  = "WHERE u.role='doctor'";
if ($search) $where .= " AND (u.name LIKE '%$search%' OR u.email LIKE '%$search%' OR d.specialization LIKE '%$search%' OR u.city LIKE '%$search%')";
$doctors = $conn->query("SELECT u.*,d.specialization,d.experience_years,d.consultation_fee,d.qualification,d.status as doc_status,ROUND(AVG(r.rating),1) as avg_rating,COUNT(r.id) as total_ratings FROM users u LEFT JOIN doctors d ON d.user_id=u.id LEFT JOIN doctor_ratings r ON r.doctor_id=u.id $where GROUP BY u.id ORDER BY u.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Manage Doctors — MediBook</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
<?php include '../includes/sidebar_admin.php'; ?>
<div class="main-content">
<div class="topbar">
    <div class="topbar-title">👨‍⚕️ Manage Doctors</div>
    <div class="topbar-right"><button class="theme-toggle">🌙</button></div>
</div>
<div class="page-content">
    <?php if($msg==='toggled'): ?><div class="alert alert-info">✅ Doctor status updated.</div><?php endif; ?>
    <?php if($msg==='deleted'): ?><div class="alert alert-danger">Doctor deleted successfully.</div><?php endif; ?>

    <div class="card" style="margin-bottom:20px">
        <div class="card-body" style="padding:14px 20px">
            <form method="GET" style="display:flex;gap:12px">
                <input type="text" name="q" class="form-control" placeholder="Search by name, email, specialization or city..." value="<?= htmlspecialchars($search) ?>" style="max-width:400px">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if($search): ?><a href="manage_doctors.php" class="btn btn-outline">Clear</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>All Doctors</h3><span style="font-size:.85rem;color:var(--text-light)"><?= $doctors->num_rows ?> found</span></div>
        <div class="table-wrap">
            <?php if($doctors->num_rows>0): ?>
            <table>
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>City</th><th>Specialization</th><th>Exp</th><th>Fee</th><th>Rating</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php $i=1; while($row=$doctors->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:34px;height:34px;background:var(--info);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0"><?= strtoupper(substr($row['name'],3,1)) ?></div>
                            <strong><?= htmlspecialchars($row['name']) ?></strong>
                        </div>
                    </td>
                    <td style="font-size:.85rem"><?= htmlspecialchars($row['email']) ?></td>
                    <td style="font-size:.85rem"><?= htmlspecialchars($row['city']??'—') ?></td>
                    <td><?= htmlspecialchars($row['specialization']??'—') ?></td>
                    <td><?= $row['experience_years']??0 ?> yrs</td>
                    <td>Rs <?= number_format($row['consultation_fee']??0,0) ?></td>
                    <td>
                        <?php if($row['avg_rating']>0): ?>
                        <span style="color:var(--accent)">★</span> <?= $row['avg_rating'] ?>/5
                        <span style="font-size:.75rem;color:var(--text-light)">(<?= $row['total_ratings'] ?>)</span>
                        <?php else: ?>
                        <span style="color:var(--text-light);font-size:.82rem">No ratings</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $row['is_active']?'<span class="badge badge-success">Active</span>':'<span class="badge badge-danger">Inactive</span>' ?></td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="?toggle=<?= $row['id'] ?>" class="btn btn-sm <?= $row['is_active']?'btn-outline':'btn-success' ?>"><?= $row['is_active']?'Deactivate':'Activate' ?></a>
                            <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete Dr. <?= htmlspecialchars($row['name']) ?>? This cannot be undone.')">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state"><div class="icon">👨‍⚕️</div><p>No doctors found.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>