<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('admin');

if (isset($_GET['toggle'])) { $id=intval($_GET['toggle']); $conn->query("UPDATE users SET is_active=1-is_active WHERE id=$id AND role='patient'"); header("Location: manage_patients.php?msg=toggled"); exit(); }
if (isset($_GET['delete'])) { $id=intval($_GET['delete']); $conn->query("DELETE FROM users WHERE id=$id AND role='patient'"); header("Location: manage_patients.php?msg=deleted"); exit(); }

$msg    = $_GET['msg'] ?? '';
$search = sanitize($_GET['q'] ?? '');
$where  = "WHERE role='patient'";
if ($search) $where .= " AND (name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%' OR city LIKE '%$search%')";
$patients = $conn->query("SELECT u.*,(SELECT COUNT(*) FROM appointments WHERE patient_id=u.id) as total_appts FROM users u $where ORDER BY u.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Manage Patients — MediBook</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
<?php include '../includes/sidebar_admin.php'; ?>
<div class="main-content">
<div class="topbar">
    <div class="topbar-title">🧑 Manage Patients</div>
    <div class="topbar-right"><button class="theme-toggle">🌙</button></div>
</div>
<div class="page-content">
    <?php if($msg==='toggled'): ?><div class="alert alert-info">✅ Patient status updated.</div><?php endif; ?>
    <?php if($msg==='deleted'): ?><div class="alert alert-danger">Patient deleted.</div><?php endif; ?>

    <div class="card" style="margin-bottom:20px">
        <div class="card-body" style="padding:14px 20px">
            <form method="GET" style="display:flex;gap:12px">
                <input type="text" name="q" class="form-control" placeholder="Search by name, email, phone or city..." value="<?= htmlspecialchars($search) ?>" style="max-width:400px">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if($search): ?><a href="manage_patients.php" class="btn btn-outline">Clear</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>All Patients</h3><span style="font-size:.85rem;color:var(--text-light)"><?= $patients->num_rows ?> found</span></div>
        <div class="table-wrap">
            <?php if($patients->num_rows>0): ?>
            <table>
                <thead><tr><th>#</th><th>Patient</th><th>Email</th><th>Phone</th><th>City</th><th>Gender</th><th>Appointments</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php $i=1; while($row=$patients->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:34px;height:34px;background:var(--primary);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0"><?= strtoupper(substr($row['name'],0,1)) ?></div>
                            <strong><?= htmlspecialchars($row['name']) ?></strong>
                        </div>
                    </td>
                    <td style="font-size:.85rem"><?= htmlspecialchars($row['email']) ?></td>
                    <td style="font-size:.85rem"><?= htmlspecialchars($row['phone']??'—') ?></td>
                    <td style="font-size:.85rem"><?= htmlspecialchars($row['city']??'—') ?></td>
                    <td><?= htmlspecialchars($row['gender']??'—') ?></td>
                    <td><span class="badge badge-info"><?= $row['total_appts'] ?> appts</span></td>
                    <td style="font-size:.82rem"><?= date('d M Y',strtotime($row['created_at'])) ?></td>
                    <td><?= $row['is_active']?'<span class="badge badge-success">Active</span>':'<span class="badge badge-danger">Blocked</span>' ?></td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="?toggle=<?= $row['id'] ?>" class="btn btn-sm <?= $row['is_active']?'btn-outline':'btn-success' ?>"><?= $row['is_active']?'Block':'Unblock' ?></a>
                            <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete patient <?= htmlspecialchars($row['name']) ?>?')">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state"><div class="icon">🧑</div><p>No patients found.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>