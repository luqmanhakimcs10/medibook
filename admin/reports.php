<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireRole('admin');

$monthly = [];
for ($m=1;$m<=12;$m++) { $r=$conn->query("SELECT COUNT(*) as c FROM appointments WHERE MONTH(appointment_date)=$m AND YEAR(appointment_date)=YEAR(CURDATE())")->fetch_assoc(); $monthly[$m]=intval($r['c']); }

$statuses = [];
foreach(['pending','confirmed','completed','cancelled'] as $s) { $r=$conn->query("SELECT COUNT(*) as c FROM appointments WHERE status='$s'")->fetch_assoc(); $statuses[$s]=intval($r['c']); }

$top_docs = $conn->query(
    "SELECT u.name, d.specialization, COUNT(a.id) as total, ROUND(AVG(r.rating),1) as avg_rating, COUNT(DISTINCT r.id) as total_ratings
     FROM users u
     JOIN doctors d ON d.user_id=u.id
     LEFT JOIN appointments a ON a.doctor_id=u.id
     LEFT JOIN doctor_ratings r ON r.doctor_id=u.id
     WHERE u.role='doctor'
     GROUP BY u.id
     ORDER BY total DESC LIMIT 5"
);

$daily = [];
for ($i=6;$i>=0;$i--) { $date=date('Y-m-d',strtotime("-$i days")); $label=date('D d',strtotime($date)); $r=$conn->query("SELECT COUNT(*) as c FROM appointments WHERE appointment_date='$date'")->fetch_assoc(); $daily[$label]=intval($r['c']); }

$revenue      = $conn->query("SELECT SUM(d.consultation_fee) as total FROM appointments a JOIN doctors d ON d.user_id=a.doctor_id WHERE a.status='completed'")->fetch_assoc()['total'] ?? 0;
$month_rev    = $conn->query("SELECT SUM(d.consultation_fee) as total FROM appointments a JOIN doctors d ON d.user_id=a.doctor_id WHERE a.status='completed' AND MONTH(a.appointment_date)=MONTH(CURDATE())")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports — MediBook Admin</title>
    <script src="../assets/js/dark-mode.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
</head>
<body>
<div class="dashboard-wrapper">
<?php include '../includes/sidebar_admin.php'; ?>
<div class="main-content">
<div class="topbar">
    <div class="topbar-title">📊 Reports & Analytics</div>
    <div class="topbar-right"><button class="theme-toggle">🌙</button></div>
</div>
<div class="page-content">
    <!-- Revenue Stats -->
    <div class="stats-row" style="margin-bottom:28px">
        <div class="stat-card"><div class="stat-card-icon green">💰</div><div><div class="stat-num">Rs <?= number_format($revenue,0) ?></div><div class="stat-label">Total Revenue</div></div></div>
        <div class="stat-card"><div class="stat-card-icon amber">📅</div><div><div class="stat-num">Rs <?= number_format($month_rev,0) ?></div><div class="stat-label">This Month Revenue</div></div></div>
        <div class="stat-card"><div class="stat-card-icon teal">✅</div><div><div class="stat-num" data-count="<?= $statuses['completed'] ?>"><?= $statuses['completed'] ?></div><div class="stat-label">Completed</div></div></div>
        <div class="stat-card"><div class="stat-card-icon red">✕</div><div><div class="stat-num" data-count="<?= $statuses['cancelled'] ?>"><?= $statuses['cancelled'] ?></div><div class="stat-label">Cancelled</div></div></div>
    </div>

    <!-- Charts Row -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:24px">
        <div class="card">
            <div class="card-header"><h3>📈 Monthly Appointments (<?= date('Y') ?>)</h3></div>
            <div class="card-body"><canvas id="monthlyChart" height="110"></canvas></div>
        </div>
        <div class="card">
            <div class="card-header"><h3>🍩 Appointment Status</h3></div>
            <div class="card-body" style="display:flex;align-items:center;justify-content:center">
                <canvas id="statusChart" height="180"></canvas>
            </div>
        </div>
    </div>

    <!-- Last 7 Days + Top Doctors -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
        <div class="card">
            <div class="card-header"><h3>📆 Last 7 Days</h3></div>
            <div class="card-body"><canvas id="dailyChart" height="160"></canvas></div>
        </div>
        <div class="card">
            <div class="card-header"><h3>🏆 Top Doctors by Appointments</h3></div>
            <div>
                <?php if($top_docs&&$top_docs->num_rows>0):
                    $medals=['🥇','🥈','🥉','4️⃣','5️⃣']; $rank=1;
                    while($td=$top_docs->fetch_assoc()): ?>
                <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border)">
                    <span style="font-size:1.3rem"><?= $medals[$rank-1]??$rank ?></span>
                    <div style="flex:1">
                        <div style="font-weight:600;font-size:.9rem"><?= htmlspecialchars($td['name']) ?></div>
                        <div style="font-size:.78rem;color:var(--text-light)"><?= htmlspecialchars($td['specialization']) ?></div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-family:'Sora',sans-serif;font-weight:700;font-size:1.1rem"><?= $td['total'] ?></div>
                        <div style="font-size:.72rem;color:var(--text-light)">appointments</div>
                        <?php if($td['avg_rating']>0): ?>
                        <div style="font-size:.78rem;color:var(--accent)">⭐ <?= $td['avg_rating'] ?>/5 (<?= $td['total_ratings'] ?>)</div>
                        <?php else: ?>
                        <div style="font-size:.72rem;color:var(--text-light)">No ratings yet</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php $rank++; endwhile; endif; ?>
            </div>
        </div>
    </div>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../assets/js/main.js"></script>
<script>
new Chart(document.getElementById('monthlyChart'),{type:'line',data:{labels:['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],datasets:[{label:'Appointments',data:<?= json_encode(array_values($monthly)) ?>,borderColor:'#0D9488',backgroundColor:'rgba(13,148,136,0.08)',borderWidth:2.5,pointBackgroundColor:'#0D9488',pointRadius:4,fill:true,tension:0.4}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'#F1F5F9'},ticks:{stepSize:1}},x:{grid:{display:false}}}}});
new Chart(document.getElementById('statusChart'),{type:'doughnut',data:{labels:['Pending','Confirmed','Completed','Cancelled'],datasets:[{data:<?= json_encode(array_values($statuses)) ?>,backgroundColor:['#FEF3C7','#D1FAE5','#DBEAFE','#FEE2E2'],borderColor:['#D97706','#059669','#2563EB','#DC2626'],borderWidth:2}]},options:{cutout:'65%',plugins:{legend:{position:'bottom',labels:{font:{size:11}}}}}});
new Chart(document.getElementById('dailyChart'),{type:'bar',data:{labels:<?= json_encode(array_keys($daily)) ?>,datasets:[{label:'Appointments',data:<?= json_encode(array_values($daily)) ?>,backgroundColor:'rgba(13,148,136,0.15)',borderColor:'#0D9488',borderWidth:2,borderRadius:6}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1},grid:{color:'#F1F5F9'}},x:{grid:{display:false}}}}});
</script>
</body>
</html>