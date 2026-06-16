<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('doctor');

$uid  = $_SESSION['user_id'];
$user = getUserById($uid);
$doc  = getDoctorInfo($uid);
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = sanitize($_POST['name'] ?? '');
    $phone  = sanitize($_POST['phone'] ?? '');
    $gender = sanitize($_POST['gender'] ?? '');
    $spec   = sanitize($_POST['specialization'] ?? '');
    $qual   = sanitize($_POST['qualification'] ?? '');
    $exp    = intval($_POST['experience_years'] ?? 0);
    $fee    = floatval($_POST['consultation_fee'] ?? 0);
    $bio    = sanitize($_POST['bio'] ?? '');
    $newpass = $_POST['new_password'] ?? '';

    if (empty($name)) { $error = 'Name cannot be empty.'; }
    else {
        $conn->query("UPDATE users SET name='$name', phone='$phone', gender='$gender' WHERE id=$uid");
        $conn->query("UPDATE doctors SET specialization='$spec', qualification='$qual', experience_years=$exp, consultation_fee=$fee, bio='$bio' WHERE user_id=$uid");

        if (!empty($newpass)) {
            if (strlen($newpass) < 6) $error = 'New password must be at least 6 characters.';
            else {
                $hashed = password_hash($newpass, PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET password='$hashed' WHERE id=$uid");
            }
        }
        if (!$error) {
            $_SESSION['name'] = $name;
            $success = 'Profile updated successfully!';
            $user = getUserById($uid);
            $doc  = getDoctorInfo($uid);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Profile — MediBook</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
    <?php include '../includes/sidebar_doctor.php'; ?>
    <div class="main-content">
        <div class="topbar"><div class="topbar-title">👤 My Profile</div></div>
        <div class="page-content">
            <?php if ($error): ?><div class="alert alert-danger">⚠️ <?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 2fr;gap:24px">
                <!-- Avatar Card -->
                <div class="card" style="text-align:center;padding:36px 24px">
                    <div style="width:90px;height:90px;background:var(--info);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Sora',sans-serif;font-size:2rem;font-weight:700;margin:0 auto 16px">
                        <?= strtoupper(substr($user['name'], 3, 1)) ?>
                    </div>
                    <h3><?= htmlspecialchars($user['name']) ?></h3>
                    <div style="color:var(--primary);font-size:.88rem;font-weight:600;margin-top:4px"><?= htmlspecialchars($doc['specialization'] ?? '') ?></div>
                    <div style="margin-top:14px;font-size:.85rem;color:var(--text-mid)">
                        <div style="margin-bottom:6px">📧 <?= htmlspecialchars($user['email']) ?></div>
                        <div style="margin-bottom:6px">📞 <?= htmlspecialchars($user['phone'] ?? '—') ?></div>
                        <div>🏆 <?= $doc['experience_years'] ?? 0 ?> yrs experience</div>
                    </div>
                    <div style="margin-top:14px">
                        <span class="badge badge-success">Verified Doctor</span>
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
                                    <label>Phone</label>
                                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Gender</label>
                                    <select name="gender" class="form-control">
                                        <?php foreach (['Male','Female','Other'] as $g): ?>
                                        <option value="<?= $g ?>" <?= $user['gender']==$g?'selected':'' ?>><?= $g ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Specialization</label>
                                    <input type="text" name="specialization" class="form-control" value="<?= htmlspecialchars($doc['specialization'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Qualification</label>
                                    <input type="text" name="qualification" class="form-control" value="<?= htmlspecialchars($doc['qualification'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Experience (years)</label>
                                    <input type="number" name="experience_years" class="form-control" value="<?= $doc['experience_years'] ?? 0 ?>" min="0">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Consultation Fee (Rs)</label>
                                <input type="number" name="consultation_fee" class="form-control" value="<?= $doc['consultation_fee'] ?? 0 ?>" min="0" step="50">
                            </div>
                            <div class="form-group">
                                <label>Bio</label>
                                <textarea name="bio" class="form-control" rows="3"><?= htmlspecialchars($doc['bio'] ?? '') ?></textarea>
                            </div>
                            <hr class="divider">
                            <div class="form-group">
                                <label>New Password <span style="font-weight:400;color:var(--text-light);font-size:.8rem">(leave blank to keep current)</span></label>
                                <input type="password" name="new_password" class="form-control" placeholder="Min. 6 characters">
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