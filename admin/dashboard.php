<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('admin');

$stats        = getTotalStats();
$pending_apps = $conn->query("SELECT COUNT(*) as c FROM doctors WHERE status='pending'")->fetch_assoc()['c'];
$recent_appts = $conn->query("SELECT a.*,u1.name as patient_name,u2.name as doctor_name FROM appointments a JOIN users u1 ON a.patient_id=u1.id JOIN users u2 ON a.doctor_id=u2.id ORDER BY a.created_at DESC LIMIT 8");
$monthly      = [];
for ($m=1;$m<=12;$m++) { $r=$conn->query("SELECT COUNT(*) as c FROM appointments WHERE MONTH(appointment_date)=$m AND YEAR(appointment_date)=YEAR(CURDATE())")->fetch_assoc(); $monthly[]=$r['c']; }
$new_patients = $conn->query("SELECT * FROM users WHERE role='patient' ORDER BY created_at DESC LIMIT 5");
$status_counts= [];
foreach(['pending','confirmed','completed','cancelled'] as $s) { $r=$conn->query("SELECT COUNT(*) as c FROM appointments WHERE status='$s'")->fetch_assoc(); $status_counts[$s]=intval($r['c']); }
$revenue = $conn->query("SELECT SUM(d.consultation_fee) as total FROM appointments a JOIN doctors d ON d.user_id=a.doctor_id WHERE a.status='completed'")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin Dashboard — MediBook</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
<?php include '../includes/sidebar_admin.php'; ?>
<div class="main-content">
<div class="topbar">
    <div class="topbar-title">📊 Admin Dashboard</div>
    <div class="topbar-right">
        <?php if($pending_apps>0): ?>
        <a href="pending_doctors.php" class="btn btn-ghost btn-sm" style="position:relative">
            📋 Applications
            <span style="position:absolute;top:-4px;right:-4px;background:var(--danger);color:white;font-size:.62rem;font-weight:700;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid white"><?= $pending_apps ?></span>
        </a>
        <?php endif; ?>
        <button class="theme-toggle">🌙</button>
        <a href="../auth/logout.php" class="btn btn-ghost btn-sm">Logout</a>
    </div>
</div>
<div class="page-content">
    <!-- Welcome Banner — No "Add Doctor" button since doctors self-register -->
    <div class="welcome-banner" style="background:linear-gradient(135deg,#7C3AED,#5B21B6)">
        <div><h2>Admin Control Panel 🛡️</h2><p>Full system overview — manage doctors, patients and appointments.</p></div>
        <?php if($pending_apps>0): ?>
        <a href="pending_doctors.php" class="btn btn-accent">⏳ <?= $pending_apps ?> Pending Application<?= $pending_apps>1?'s':'' ?></a>
        <?php endif; ?>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card"><div class="stat-card-icon blue">👨‍⚕️</div><div><div class="stat-num" data-count="<?= $stats['doctors'] ?>"><?= $stats['doctors'] ?></div><div class="stat-label">Total Doctors</div></div></div>
        <div class="stat-card"><div class="stat-card-icon teal">🧑</div><div><div class="stat-num" data-count="<?= $stats['patients'] ?>"><?= $stats['patients'] ?></div><div class="stat-label">Total Patients</div></div></div>
        <div class="stat-card"><div class="stat-card-icon amber">📅</div><div><div class="stat-num" data-count="<?= $stats['appointments'] ?>"><?= $stats['appointments'] ?></div><div class="stat-label">All Appointments</div></div></div>
        <div class="stat-card"><div class="stat-card-icon green">💰</div><div><div class="stat-num">Rs <?= number_format($revenue,0) ?></div><div class="stat-label">Total Revenue</div></div></div>
        <?php if($pending_apps>0): ?>
        <div class="stat-card" style="border-color:var(--warning)"><div class="stat-card-icon amber">⏳</div><div><div class="stat-num" style="color:var(--warning)" data-count="<?= $pending_apps ?>"><?= $pending_apps ?></div><div class="stat-label">Pending Applications</div></div></div>
        <?php endif; ?>
    </div>

    <!-- Charts -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px">
        <div class="card">
            <div class="card-header"><h3>📈 Monthly Appointments (<?= date('Y') ?>)</h3><a href="reports.php" class="btn btn-ghost btn-sm">Full Report →</a></div>
            <div class="card-body"><canvas id="monthlyChart" height="110"></canvas></div>
        </div>
        <div class="card">
            <div class="card-header"><h3>🍩 Status Breakdown</h3></div>
            <div class="card-body" style="display:flex;flex-direction:column;align-items:center">
                <canvas id="statusChart" height="160" style="max-width:200px"></canvas>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:16px;width:100%">
                    <?php $sc_info=['pending'=>['#FEF3C7','#D97706'],'confirmed'=>['#D1FAE5','#059669'],'completed'=>['#DBEAFE','#2563EB'],'cancelled'=>['#FEE2E2','#DC2626']];
                    foreach($status_counts as $st=>$cnt): $ci=$sc_info[$st]??['#F1F5F9','#64748B']; ?>
                    <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:<?= $ci[0] ?>;border-radius:8px">
                        <span style="width:10px;height:10px;background:<?= $ci[1] ?>;border-radius:50%;flex-shrink:0"></span>
                        <div><div style="font-weight:700;font-size:.9rem;color:<?= $ci[1] ?>"><?= $cnt ?></div><div style="font-size:.68rem;color:<?= $ci[1] ?>;text-transform:capitalize"><?= $st ?></div></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Row -->
    <div style="display:grid;grid-template-columns:3fr 2fr;gap:20px">
        <div class="card">
            <div class="card-header"><h3>📋 Recent Appointments</h3><a href="manage_appointments.php" class="btn btn-ghost btn-sm">View All →</a></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Patient</th><th>Doctor</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php while($row=$recent_appts->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['patient_name']) ?></td>
                        <td style="color:var(--text-mid)"><?= htmlspecialchars($row['doctor_name']) ?></td>
                        <td style="font-size:.82rem"><?= formatDate($row['appointment_date']) ?></td>
                        <td><?= getStatusBadge($row['status']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:16px">
            <div class="card">
                <div class="card-header"><h3>🆕 New Patients</h3><a href="manage_patients.php" class="btn btn-ghost btn-sm">All →</a></div>
                <?php while($u=$new_patients->fetch_assoc()): ?>
                <div style="display:flex;align-items:center;gap:10px;padding:11px 18px;border-bottom:1px solid var(--border)">
                    <div style="width:34px;height:34px;background:var(--primary);color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0"><?= strtoupper(substr($u['name'],0,1)) ?></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600;font-size:.87rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($u['name']) ?></div>
                        <div style="font-size:.74rem;color:var(--text-light)"><?= htmlspecialchars($u['city']??'N/A') ?></div>
                    </div>
                    <div style="font-size:.73rem;color:var(--text-light);white-space:nowrap"><?= date('d M',strtotime($u['created_at'])) ?></div>
                </div>
                <?php endwhile; ?>
            </div>
            <div class="card">
                <div class="card-header"><h3>⚡ Quick Access</h3></div>
                <div style="padding:12px">
                    <?php $quick=[['pending_doctors.php','📋','Doctor Applications',$pending_apps>0?"⚠️ $pending_apps pending":'Review credentials'],['manage_doctors.php','👨‍⚕️','Manage Doctors','View & control accounts'],['manage_patients.php','🧑','Manage Patients','View patient accounts'],['manage_appointments.php','📅','All Appointments','View & update bookings'],['reports.php','📊','Reports','Charts & analytics']];
                    foreach($quick as $q): ?>
                    <a href="<?= $q[0] ?>" style="display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:var(--r-sm);transition:all .2s;margin-bottom:4px;text-decoration:none" onmouseover="this.style.background='var(--primary-light)'" onmouseout="this.style.background='transparent'">
                        <span style="font-size:1.2rem;min-width:24px;text-align:center"><?= $q[1] ?></span>
                        <div style="flex:1;min-width:0"><div style="font-weight:600;font-size:.87rem;color:var(--text-dark)"><?= $q[2] ?></div><div style="font-size:.73rem;color:var(--text-light)"><?= $q[3] ?></div></div>
                        <span style="color:var(--text-light);font-size:.8rem">›</span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../assets/js/main.js"></script>
<script>
new Chart(document.getElementById('monthlyChart'),{type:'bar',data:{labels:['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],datasets:[{label:'Appointments',data:<?= json_encode($monthly) ?>,backgroundColor:'rgba(13,148,136,0.12)',borderColor:'#0D9488',borderWidth:2,borderRadius:7,borderSkipped:false}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'#F1F5F9'},ticks:{stepSize:1}},x:{grid:{display:false}}}}});
new Chart(document.getElementById('statusChart'),{type:'doughnut',data:{labels:['Pending','Confirmed','Completed','Cancelled'],datasets:[{data:<?= json_encode(array_values($status_counts)) ?>,backgroundColor:['#FEF3C7','#D1FAE5','#DBEAFE','#FEE2E2'],borderColor:['#D97706','#059669','#2563EB','#DC2626'],borderWidth:2}]},options:{cutout:'68%',plugins:{legend:{display:false}}}});
</script>
</body>
</html>