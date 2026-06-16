<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('patient');

$uid = $_SESSION['user_id'];
$user = getUserById($uid);
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = sanitize($_POST['name'] ?? '');
    $phone   = sanitize($_POST['phone'] ?? '');
    $gender  = sanitize($_POST['gender'] ?? '');
    $dob     = sanitize($_POST['dob'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $newpass = $_POST['new_password'] ?? '';

    if (empty($name)) { $error = 'Name cannot be empty.'; }
    else {
        $dob_val = !empty($dob) ? "'$dob'" : 'NULL';
        $conn->query("UPDATE users SET name='$name', phone='$phone', gender='$gender', date_of_birth=$dob_val, address='$address' WHERE id=$uid");
        if (!empty($newpass)) {
            if (strlen($newpass) < 6) { $error = 'New password must be at least 6 characters.'; }
            else {
                $hashed = password_hash($newpass, PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET password='$hashed' WHERE id=$uid");
            }
        }
        if (!$error) {
            $_SESSION['name'] = $name;
            $success = 'Profile updated successfully!';
            $user = getUserById($uid);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — MediBook</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
    <?php include '../includes/sidebar_patient.php'; ?>
    <div class="main-content">
        <div class="topbar"><div class="topbar-title">👤 My Profile</div></div>
        <div class="page-content">
            <?php if ($error): ?><div class="alert alert-danger">⚠️ <?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 2fr;gap:24px">
                <!-- Avatar Card -->
                <div class="card" style="text-align:center;padding:36px 24px">
                    <div style="width:90px;height:90px;background:var(--primary);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Sora',sans-serif;font-size:2rem;font-weight:700;margin:0 auto 16px">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <h3><?= htmlspecialchars($user['name']) ?></h3>
                    <div style="color:var(--text-light);font-size:.85rem;margin-top:4px">Patient</div>
                    <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);font-size:.85rem;color:var(--text-mid)">
                        <div style="margin-bottom:8px">📧 <?= htmlspecialchars($user['email']) ?></div>
                        <?php if ($user['phone']): ?><div>📞 <?= htmlspecialchars($user['phone']) ?></div><?php endif; ?>
                    </div>
                    <div style="margin-top:14px">
                        <span class="badge badge-success">Active Account</span>
                    </div>
                </div>

                <!-- Edit Form -->
                <div class="card">
                    <div class="card-header"><h3>Edit Profile</h3></div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Full Name *</label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Gender</label>
                                    <select name="gender" class="form-control">
                                        <option value="">Select</option>
                                        <?php foreach (['Male','Female','Other'] as $g): ?>
                                        <option value="<?= $g ?>" <?= $user['gender']==$g?'selected':'' ?>><?= $g ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Date of Birth</label>
                                    <input type="date" name="dob" class="form-control" value="<?= $user['date_of_birth'] ?? '' ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Address</label>
                                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($user['address'] ?? '') ?>">
                            </div>
                            <hr class="divider">
                            <div class="form-group">
                                <label>New Password <span style="color:var(--text-light);font-weight:400">(leave blank to keep current)</span></label>
                                <input type="password" name="new_password" class="form-control" placeholder="Enter new password">
                            </div>
                            <div style="display:flex;gap:12px">
                                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                                <a href="dashboard.php" class="btn btn-outline">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>