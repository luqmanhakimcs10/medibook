<?php
// doctor/change_password.php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('doctor');

$uid   = $_SESSION['user_id'];
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current  = $_POST['current_password']  ?? '';
    $new      = $_POST['new_password']      ?? '';
    $confirm  = $_POST['confirm_password']  ?? '';

    $user = getUserById($uid);

    if (!password_verify($current, $user['password'])) {
        $error = 'Your current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } elseif ($current === $new) {
        $error = 'New password must be different from your current password.';
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password='$hashed' WHERE id=$uid");
        addNotification($uid, 'Password Changed', 'Your password was changed successfully. If you did not do this, contact support immediately.');
        $success = 'Password changed successfully! Please use your new password next time you login.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password — MediBook</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
    <?php include '../includes/sidebar_doctor.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">🔒 Change Password</div>
            <div class="topbar-right">
                <a href="dashboard.php" class="btn btn-outline btn-sm">← Dashboard</a>
            </div>
        </div>
        <div class="page-content">
            <div style="max-width:520px;margin:0 auto">

                <?php if ($error):   ?><div class="alert alert-danger">⚠️ <?= $error ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>

                <!-- Security Notice (shown on first visit or for newly approved doctors) -->
                <div style="background:var(--accent-light);border:1px solid rgba(245,158,11,0.3);border-radius:var(--radius-md);padding:18px 20px;margin-bottom:24px;display:flex;gap:14px">
                    <span style="font-size:1.5rem">⚠️</span>
                    <div>
                        <div style="font-weight:700;color:#92400E;margin-bottom:4px">Security Recommendation</div>
                        <p style="font-size:.88rem;color:#92400E;line-height:1.6">
                            If your account was recently approved by an admin, change your password now to ensure only you can access your account.
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3>🔒 Change Your Password</h3></div>
                    <div class="card-body">
                        <form method="POST" id="change-pwd-form">

                            <div class="form-group">
                                <label>Current Password <span style="color:var(--danger)">*</span></label>
                                <div style="position:relative">
                                    <input type="password" name="current_password" id="current_pwd"
                                           class="form-control" placeholder="Enter your current password" required>
                                    <button type="button" onclick="togglePwd('current_pwd', this)"
                                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:1rem;cursor:pointer;color:var(--text-light)">👁️</button>
                                </div>
                            </div>

                            <hr class="divider">

                            <div class="form-group">
                                <label>New Password <span style="color:var(--danger)">*</span>
                                    <span style="font-weight:400;color:var(--text-light);font-size:.78rem">(min. 8 characters)</span>
                                </label>
                                <div style="position:relative">
                                    <input type="password" name="new_password" id="new_pwd"
                                           class="form-control" placeholder="Enter new password"
                                           required minlength="8">
                                    <button type="button" onclick="togglePwd('new_pwd', this)"
                                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:1rem;cursor:pointer;color:var(--text-light)">👁️</button>
                                </div>
                                <!-- Strength bar -->
                                <div style="margin-top:8px">
                                    <div style="height:5px;border-radius:3px;background:var(--border);overflow:hidden">
                                        <div id="strength-bar" style="height:100%;width:0;transition:all .3s;border-radius:3px"></div>
                                    </div>
                                    <div id="strength-label" style="font-size:.75rem;color:var(--text-light);margin-top:4px"></div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Confirm New Password <span style="color:var(--danger)">*</span></label>
                                <div style="position:relative">
                                    <input type="password" name="confirm_password" id="confirm_pwd"
                                           class="form-control" placeholder="Repeat new password" required>
                                    <button type="button" onclick="togglePwd('confirm_pwd', this)"
                                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:1rem;cursor:pointer;color:var(--text-light)">👁️</button>
                                </div>
                                <div id="match-msg" style="font-size:.78rem;margin-top:4px"></div>
                            </div>

                            <!-- Password Requirements -->
                            <div style="background:var(--bg);border-radius:var(--radius-sm);padding:14px 16px;margin-bottom:20px">
                                <div style="font-size:.78rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px">Password Requirements</div>
                                <div id="reqs" style="display:flex;flex-direction:column;gap:6px;font-size:.82rem">
                                    <div id="req-len"  class="req">○ At least 8 characters</div>
                                    <div id="req-upper" class="req">○ One uppercase letter</div>
                                    <div id="req-num"  class="req">○ One number</div>
                                    <div id="req-sym"  class="req">○ One special character (recommended)</div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:13px">
                                🔒 Update Password
                            </button>
                        </form>
                    </div>
                </div>

                <div style="margin-top:14px;text-align:center;font-size:.82rem;color:var(--text-light)">
                    🛡️ Your password is encrypted and never stored in plain text.
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
<script>
function togglePwd(inputId, btn) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') { input.type = 'text';     btn.textContent = '🙈'; }
    else                           { input.type = 'password'; btn.textContent = '👁️'; }
}

document.getElementById('new_pwd').addEventListener('input', function() {
    const v = this.value;
    let s = 0;
    const setReq = (id, ok) => {
        const el = document.getElementById(id);
        el.textContent = (ok ? '✅ ' : '○ ') + el.textContent.slice(2);
        el.style.color = ok ? 'var(--success)' : 'var(--text-light)';
        return ok ? 1 : 0;
    };
    s += setReq('req-len',   v.length >= 8);
    s += setReq('req-upper', /[A-Z]/.test(v));
    s += setReq('req-num',   /[0-9]/.test(v));
    setReq('req-sym',  /[^A-Za-z0-9]/.test(v));

    const bar   = document.getElementById('strength-bar');
    const label = document.getElementById('strength-label');
    const cols  = ['','#EF4444','#F59E0B','#10B981','#0D9488'];
    const labs  = ['','Weak','Fair','Good','Strong 💪'];
    const wids  = ['0%','33%','55%','77%','100%'];
    bar.style.width      = wids[Math.min(s,4)];
    bar.style.background = cols[Math.min(s,4)];
    label.textContent    = labs[Math.min(s,4)] || '';
    label.style.color    = cols[Math.min(s,4)] || '';
});

document.getElementById('confirm_pwd').addEventListener('input', function() {
    const pw1 = document.getElementById('new_pwd').value;
    const msg = document.getElementById('match-msg');
    if (!this.value) { msg.textContent = ''; return; }
    if (this.value === pw1) { msg.textContent = '✅ Passwords match';    msg.style.color = 'var(--success)'; }
    else                    { msg.textContent = '✗ Passwords do not match'; msg.style.color = 'var(--danger)'; }
});
</script>
</body>
</html>