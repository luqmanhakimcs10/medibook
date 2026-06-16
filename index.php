<?php
// index.php — Complete Landing Page with all features
require_once 'config/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header("Location: " . SITE_URL . "/{$_SESSION['role']}/dashboard.php");
    exit();
}

$doctors = getAllDoctors();
$stats   = getTotalStats();

$ratings = [];
$rRes = $conn->query("SELECT doctor_id, ROUND(AVG(rating),1) as avg FROM doctor_ratings GROUP BY doctor_id");
if ($rRes) while ($r = $rRes->fetch_assoc()) $ratings[$r['doctor_id']] = $r['avg'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MediBook — Book Doctors Online in Pakistan</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/dark-mode.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏥</text></svg>">
  <style>
    /* FAQ Accordion */
    .faq-item { border:1px solid var(--border); border-radius:12px; margin-bottom:10px; overflow:hidden; transition:box-shadow .2s; }
    .faq-item:hover { box-shadow: var(--shadow-sm); }
    .faq-item.open { border-color:var(--primary); }
    .faq-question { padding:18px 22px; cursor:pointer; display:flex; align-items:center; justify-content:space-between; gap:16px; background:white; }
    .faq-question:hover { background:var(--bg); }
    .faq-item.open .faq-question { background:var(--primary-light); }
    .faq-question span.q-text { font-weight:600; font-size:.95rem; color:var(--text-dark); }
    .faq-icon { font-size:1.4rem; color:var(--primary); flex-shrink:0; transition:transform .25s; font-weight:300; line-height:1; }
    .faq-answer { max-height:0; opacity:0; overflow:hidden; transition:max-height .35s ease, opacity .3s ease; background:var(--bg); }
    .faq-answer p { padding:16px 22px; font-size:.92rem; color:var(--text-mid); line-height:1.75; border-top:1px solid var(--border); margin:0; }

    /* Dark mode FAQ */
    [data-theme="dark"] .faq-question { background:#1E293B; }
    [data-theme="dark"] .faq-question:hover { background:#0F172A; }
    [data-theme="dark"] .faq-item.open .faq-question { background:rgba(45,212,191,0.08); }
    [data-theme="dark"] .faq-answer { background:#0F172A; }
    [data-theme="dark"] .faq-answer p { border-top-color:var(--border); }
    [data-theme="dark"] .q-text { color:var(--text-dark); }

    /* Spec pill active */
    .spec-pill.active { border-color:var(--primary) !important; background:var(--primary-light) !important; }
  </style>
</head>
<body>

<!-- ═══════ NAVBAR ═══════ -->
<nav class="navbar">
  <a href="index.php" class="navbar-brand">
    <div class="nav-logo-icon">🏥</div>
    Medi<span>Book</span>
  </a>
  <div class="nav-links">
    <a href="#how-it-works">How It Works</a>
    <a href="#specializations">Specializations</a>
    <a href="#doctors">Doctors</a>
    <a href="#testimonials">Reviews</a>
    <a href="#faq">FAQ</a>
    <button class="theme-toggle" style="margin:0 4px">🌙</button>
    <a href="auth/login.php" class="btn btn-outline" style="padding:8px 18px">Login</a>
    <a href="auth/register.php" class="btn btn-primary" style="padding:8px 18px">Get Started</a>
  </div>
</nav>

<!-- ═══════ HERO ═══════ -->
<section class="hero">
  <div class="hero-inner">
    <div class="hero-content">
      <div class="hero-badge">
        <span style="width:8px;height:8px;background:var(--success);border-radius:50%;animation:pulse 2s infinite"></span>
        <?= $stats['doctors'] ?>+ Verified Doctors Online
      </div>
      <h1>
        Book Your Doctor<br>
        Visit <span class="highlight">Online</span><br>
        In Minutes
      </h1>
      <p class="hero-desc">
        Skip the waiting room. MediBook connects you with top specialists across Pakistan.
        Pick your slot, get digital prescriptions and reminders — all in one place.
      </p>
      <div class="hero-actions">
        <a href="auth/register.php" class="btn btn-primary btn-xl">📅 Book Appointment</a>
        <a href="auth/login.php"    class="btn btn-ghost btn-xl">Sign In →</a>
      </div>
      <div class="hero-trust">
        <div class="hero-trust-avatars">
          <?php
          $colors  = ['#0D9488','#3B82F6','#F59E0B','#EF4444'];
          $letters = ['A','R','S','M'];
          foreach ($letters as $i => $l):
          ?>
          <span style="background:<?= $colors[$i] ?>"><?= $l ?></span>
          <?php endforeach; ?>
        </div>
        <span>Trusted by <strong><?= $stats['patients'] ?>+</strong> patients across Pakistan</span>
      </div>
    </div>

    <div class="hero-visual">
      <div class="hero-card-stack">
        <div class="hero-floating-card">
          <div class="hero-card-icon" style="background:#D1FAE5;color:#059669">👨‍⚕️</div>
          <div>
            <div class="hero-card-num"><?= $stats['doctors'] ?>+</div>
            <div class="hero-card-lbl">Verified Doctors</div>
          </div>
        </div>
        <div class="hero-floating-card" style="animation-delay:.15s">
          <div class="hero-card-icon" style="background:#DBEAFE;color:#2563EB">📅</div>
          <div>
            <div class="hero-card-num"><?= $stats['appointments'] ?>+</div>
            <div class="hero-card-lbl">Appointments Booked</div>
          </div>
        </div>
        <div class="hero-floating-card" style="animation-delay:.3s">
          <div class="hero-card-icon" style="background:#FEF3C7;color:#D97706">⭐</div>
          <div>
            <div class="hero-card-num">4.8/5</div>
            <div class="hero-card-lbl">Patient Rating</div>
          </div>
        </div>
        <div class="hero-floating-card" style="animation-delay:.45s">
          <div class="hero-card-icon" style="background:#EDE9FE;color:#7C3AED">🏙️</div>
          <div>
            <div class="hero-card-num">30+</div>
            <div class="hero-card-lbl">Cities Covered</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════ STATS COUNTER ═══════ -->
<div style="background:white;padding:0 5%">
  <div class="stats-counter-grid" style="max-width:1200px;margin:0 auto">
    <?php
    $counters = [
      [$stats['doctors'],      '+','👨‍⚕️','Expert Doctors'],
      [$stats['patients'],     '+','🧑',  'Happy Patients'],
      [$stats['appointments'], '+','📅',  'Appointments'],
      [30,                     '+','🏙️', 'Cities Covered'],
    ];
    foreach ($counters as $c): ?>
    <div class="counter-item">
      <div style="font-size:1.6rem;margin-bottom:6px"><?= $c[2] ?></div>
      <div class="counter-num" data-target="<?= $c[0] ?>" data-suffix="<?= $c[1] ?>">0<?= $c[1] ?></div>
      <div class="counter-label"><?= $c[3] ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ═══════ SPECIALIZATIONS ═══════ -->
<section class="section" id="specializations" style="background:var(--bg)">
  <div class="section-inner">
    <div class="section-header">
      <div class="section-tag">🩺 Browse by Specialty</div>
      <h2>Find the Right Specialist</h2>
      <p>From general medicine to specialized care — find the doctor you need.</p>
    </div>
    <div class="specs-grid">
      <?php
      $specs = [
        ['all',               '🏥','All Doctors'],
        ['General Physician', '💊','General Physician'],
        ['Cardiologist',      '❤️','Cardiologist'],
        ['Dermatologist',     '🧴','Dermatologist'],
        ['Pediatrician',      '👶','Pediatrician'],
        ['Neurologist',       '🧠','Neurologist'],
        ['Orthopedist',       '🦴','Orthopedist'],
        ['Gynecologist',      '👩‍⚕️','Gynecologist'],
        ['Psychiatrist',      '🧘','Psychiatrist'],
        ['Ophthalmologist',   '👁️','Ophthalmologist'],
        ['ENT Specialist',    '👂','ENT Specialist'],
        ['Urologist',         '🔬','Urologist'],
      ];
      foreach ($specs as $s): ?>
      <div class="spec-pill animate-in" data-spec="<?= $s[0] ?>">
        <div class="icon"><?= $s[1] ?></div>
        <div class="name"><?= $s[2] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════ DOCTORS ═══════ -->
<section class="section" id="doctors" style="background:white">
  <div class="section-inner">
    <div class="section-header">
      <div class="section-tag">👨‍⚕️ Our Team</div>
      <h2>Meet Our Doctors</h2>
      <p>Highly qualified specialists ready to provide the best care for you.</p>
    </div>

    <!-- Search Bar -->
    <div class="search-bar-wrap">
      <div class="search-bar">
        <span style="padding-left:18px;color:var(--text-light);font-size:1.1rem">🔍</span>
        <input type="text" id="doctor-search"
               placeholder="Search by name, specialization, or city..."
               autocomplete="off">
        <button id="search-clear"
                style="display:none;background:none;border:none;padding:8px 14px;cursor:pointer;color:var(--text-light);font-size:1.1rem">✕</button>
        <button class="btn btn-primary" style="border-radius:50px;margin:5px">Search</button>
      </div>
    </div>

    <!-- Doctor Cards -->
    <div class="doctors-grid" id="doctors-grid">
      <?php foreach ($doctors as $doc):
        $avg   = $ratings[$doc['user_id']] ?? 0;
        $stars = $avg > 0
          ? str_repeat('★', round($avg)) . str_repeat('☆', 5-round($avg)) . " $avg"
          : '☆☆☆☆☆ New';
        $searchData = strtolower($doc['name'].' '.$doc['specialization'].' '.($doc['city']??''));
      ?>
      <div class="doctor-card animate-in" data-search="<?= htmlspecialchars($searchData) ?>">
        <div class="doctor-avatar">
          <?= strtoupper(substr(trim(str_replace('Dr.','',$doc['name'])),0,1)) ?>
        </div>
        <h3><?= htmlspecialchars($doc['name']) ?></h3>
        <div class="doctor-spec"><?= htmlspecialchars($doc['specialization']) ?></div>
        <div class="doctor-city">📍 <?= htmlspecialchars($doc['city'] ?? 'Pakistan') ?></div>
        <div class="stars"><?= $stars ?></div>
        <div class="doctor-meta">
          <div class="doctor-meta-item">
            <span class="meta-val"><?= $doc['experience_years'] ?></span>
            <span class="meta-key">Yrs Exp</span>
          </div>
          <div class="doctor-meta-item">
            <span class="meta-val">Rs <?= number_format($doc['consultation_fee'],0) ?></span>
            <span class="meta-key">Fee</span>
          </div>
        </div>
        <a href="auth/register.php" class="btn btn-primary" style="width:100%">📅 Book Now</a>
      </div>
      <?php endforeach; ?>
      <?php if (empty($doctors)): ?>
      <div class="empty-state" style="grid-column:1/-1">
        <div class="icon">👨‍⚕️</div>
        <p>No doctors listed yet. Check back soon!</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ═══════ HOW IT WORKS ═══════ -->
<section class="section" id="how-it-works" style="background:var(--bg)">
  <div class="section-inner">
    <div class="section-header">
      <div class="section-tag">🔄 Process</div>
      <h2>How MediBook Works</h2>
      <p>Getting the care you need takes less than 3 minutes.</p>
    </div>
    <div class="steps-grid">
      <?php
      $steps = [
        ['👤','Create Account',  'Register in 2 minutes. Free for patients.'],
        ['🔍','Find a Doctor',   'Browse specialists by city or specialty.'],
        ['📅','Book a Slot',     'Pick your preferred date and time.'],
        ['💊','Get Prescription','Receive your digital prescription as PDF.'],
      ];
      foreach ($steps as $i => $s): ?>
      <div class="step-card animate-in" data-delay="<?= $i+1 ?>">
        <div class="step-num"><?= $i+1 ?></div>
        <div class="step-icon"><?= $s[0] ?></div>
        <h3><?= $s[1] ?></h3>
        <p><?= $s[2] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════ FEATURES ═══════ -->
<section class="section" style="background:white">
  <div class="section-inner">
    <div class="section-header">
      <div class="section-tag">⚡ Features</div>
      <h2>Everything in One Place</h2>
      <p>A complete digital health platform for patients and doctors alike.</p>
    </div>
    <div class="features-grid">
      <?php
      $features = [
        ['🔐','Secure Role-Based Login',  'Separate portals for patients, doctors, and admin.'],
        ['📅','Smart Appointment Slots',  'Real-time slot availability. No double bookings.'],
        ['📍','Find Nearby Doctors',      'City-based filtering shows doctors closest to you.'],
        ['🔔','Instant Notifications',    'In-app and email notifications for every update.'],
        ['📄','Digital Prescriptions',    'Doctors write online Rx. Patients download as PDF.'],
        ['⭐','Doctor Ratings',           'Rate your experience after every appointment.'],
        ['🩺','Medical History',          'Log allergies, diseases and surgeries securely.'],
        ['📊','Admin Analytics',          'Full reports, charts and appointment statistics.'],
        ['🛡️','Verified Doctors',         'Doctors submit credentials reviewed by admin.'],
      ];
      foreach ($features as $f): ?>
      <div class="feature-card animate-in">
        <div class="feature-icon"><?= $f[0] ?></div>
        <div><h4><?= $f[1] ?></h4><p><?= $f[2] ?></p></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════ TESTIMONIALS ═══════ -->
<section class="section" id="testimonials" style="background:var(--bg)">
  <div class="section-inner">
    <div class="section-header">
      <div class="section-tag">💬 Reviews</div>
      <h2>What Patients Say</h2>
      <p>Real feedback from real patients across Pakistan.</p>
    </div>
    <div class="testimonials-grid">
      <?php
      $testimonials = [
        ['A','Ali Hassan',    'Software Engineer, Islamabad','#0D9488',
         'MediBook saved me hours. I booked a cardiologist from my office, got the slot confirmed in minutes, and received my prescription digitally. Brilliant!',5],
        ['S','Sara Ahmed',    'Teacher, Lahore',            '#3B82F6',
         'Finding a good dermatologist in Lahore used to take days. Now I just open MediBook, filter by city, and I\'m done in 2 minutes. The app is so clean and easy.',5],
        ['R','Rana Khalid',   'Business Owner, Karachi',    '#F59E0B',
         'The best part is the digital prescription. No more losing paper slips. I can access my prescription any time right from my phone. Highly recommended.',5],
        ['F','Fatima Malik',  'Student, Rawalpindi',        '#EF4444',
         'I loved that I could see the doctor\'s city before booking. I found a great GP in my area instantly. The reminder notification was very helpful too.',4],
        ['U','Usman Tariq',   'Doctor, Faisalabad',         '#7C3AED',
         'As a doctor, the schedule management is excellent. I control my own slots, write digital prescriptions and my patients get notified automatically. 10/10.',5],
        ['N','Nadia Javed',   'Housewife, Multan',          '#059669',
         'My children\'s appointments used to be so stressful. Now I book the pediatrician from home and get a reminder the day before. MediBook is a blessing.',5],
      ];
      foreach ($testimonials as $t): ?>
      <div class="testimonial-card animate-in">
        <div class="quote-icon">"</div>
        <p class="testimonial-text"><?= $t[4] ?></p>
        <div class="testimonial-author">
          <div class="author-avatar" style="background:<?= $t[3] ?>"><?= $t[0] ?></div>
          <div>
            <div class="author-name"><?= $t[1] ?></div>
            <div class="author-role"><?= $t[2] ?></div>
            <div class="test-stars"><?= str_repeat('★',$t[5]) ?><?= str_repeat('☆',5-$t[5]) ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════ FAQ ═══════ -->
<section class="section" id="faq" style="background:white">
  <div class="section-inner" style="max-width:800px">
    <div class="section-header">
      <div class="section-tag">❓ FAQ</div>
      <h2>Frequently Asked Questions</h2>
      <p>Everything you need to know about using MediBook.</p>
    </div>
    <?php
    $faqs = [
      ['Is MediBook free for patients?',
       'Yes, creating a patient account and browsing doctors is completely free. You only pay the doctor\'s consultation fee at the time of your appointment.'],
      ['How do I book an appointment?',
       'Register as a patient → go to Book Appointment → filter doctors by your city or specialization → pick a date and time slot → confirm. The whole process takes under 2 minutes.'],
      ['Can I cancel my appointment?',
       'Yes. Go to My Appointments and click Cancel next to any pending or confirmed appointment. We recommend cancelling at least a few hours in advance.'],
      ['How do I get my prescription?',
       'After your appointment is marked as completed by the doctor, your prescription will appear in My Prescriptions. You can view it online or download it as a PDF.'],
      ['How do doctors join MediBook?',
       'Doctors apply through the Doctor Registration page. They fill in their credentials and upload documents (degree, PMDC registration, etc.). Our admin team reviews and approves within 1-2 business days.'],
      ['Is my medical information secure?',
       'Yes. All data is stored securely in an encrypted database. Your medical history, prescriptions and personal details are only visible to you and your treating doctors.'],
      ['What if no doctors are available in my city?',
       'You can remove the city filter to see all doctors across Pakistan. Many patients also consult doctors in nearby cities for specialized care.'],
      ['Can I rate my doctor?',
       'Yes. After any completed appointment, a Rate button appears in My Appointments. Your rating and review help other patients make informed decisions.'],
    ];
    foreach ($faqs as $i => $faq): ?>
    <div class="faq-item animate-in">
      <div class="faq-question">
        <span class="q-text"><?= $faq[0] ?></span>
        <span class="faq-icon">+</span>
      </div>
      <div class="faq-answer">
        <p><?= $faq[1] ?></p>
      </div>
    </div>
    <?php endforeach; ?>

    <div style="text-align:center;margin-top:36px;padding:28px;background:var(--primary-light);border-radius:16px;border:1px solid rgba(13,148,136,.2)">
      <div style="font-size:1.8rem;margin-bottom:10px">💬</div>
      <h3 style="margin-bottom:6px;font-size:1.05rem">Still have questions?</h3>
      <p style="color:var(--text-mid);font-size:.88rem;margin-bottom:16px">Our support team is happy to help.</p>
      <a href="auth/register.php" class="btn btn-primary">Get Started Free</a>
    </div>
  </div>
</section>

<!-- ═══════ DOCTOR JOIN CTA ═══════ -->
<section style="padding:0 5% 60px;background:var(--bg)">
  <div style="max-width:1200px;margin:0 auto;background:linear-gradient(135deg,#1E40AF,#1D4ED8);border-radius:var(--r-xl);padding:52px 56px;display:grid;grid-template-columns:1fr auto;gap:28px;align-items:center;position:relative;overflow:hidden">
    <div style="position:absolute;top:-60px;right:-60px;width:280px;height:280px;background:rgba(255,255,255,.05);border-radius:50%"></div>
    <div style="position:relative;z-index:1">
      <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.15);color:white;padding:5px 14px;border-radius:20px;font-size:.75rem;font-weight:700;letter-spacing:.08em;margin-bottom:12px">👨‍⚕️ FOR DOCTORS</div>
      <h2 style="color:white;font-size:clamp(1.3rem,2.5vw,1.9rem);margin-bottom:10px">Join MediBook as a Verified Doctor</h2>
      <p style="color:rgba(255,255,255,.8);font-size:.92rem;max-width:480px">Register with your credentials, upload your documents, and start accepting patients once approved.</p>
    </div>
    <div style="flex-shrink:0;position:relative;z-index:1">
      <a href="auth/register_doctor.php" class="btn btn-accent btn-xl">Apply Now →</a>
    </div>
  </div>
</section>

<!-- ═══════ CTA ═══════ -->
<div class="cta-section">
  <div style="position:relative;z-index:1">
    <h2>Ready to Book Your Appointment?</h2>
    <p>Join <?= $stats['patients'] ?>+ patients who trust MediBook for their healthcare.</p>
    <div class="cta-actions">
      <a href="auth/register.php" class="btn btn-accent btn-xl">🚀 Create Free Account</a>
      <a href="auth/login.php" class="btn btn-xl" style="background:rgba(255,255,255,.15);color:white;border:2px solid rgba(255,255,255,.35)">Already a member?</a>
    </div>
  </div>
</div>

<!-- ═══════ FOOTER ═══════ -->
<footer class="footer">
  <div class="footer-grid">
    <div>
      <div class="footer-brand">
        <span style="width:32px;height:32px;background:var(--primary);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.9rem">🏥</span>
        Medi<span>Book</span>
      </div>
      <p class="footer-desc">Your trusted online clinic for booking appointments, managing health, and accessing care anywhere in Pakistan.</p>
      <div class="footer-socials">
        <div class="footer-social">📘</div>
        <div class="footer-social">🐦</div>
        <div class="footer-social">📷</div>
        <div class="footer-social">💼</div>
      </div>
    </div>
    <div>
      <h4>Patients</h4>
      <ul>
        <li><a href="auth/register.php">Register</a></li>
        <li><a href="auth/login.php">Login</a></li>
        <li><a href="auth/login.php">Book Appointment</a></li>
        <li><a href="auth/login.php">My Prescriptions</a></li>
        <li><a href="auth/login.php">Medical History</a></li>
      </ul>
    </div>
    <div>
      <h4>Doctors</h4>
      <ul>
        <li><a href="auth/register_doctor.php">Apply as Doctor</a></li>
        <li><a href="auth/login.php">Doctor Portal</a></li>
        <li><a href="auth/login.php">Manage Schedule</a></li>
        <li><a href="auth/login.php">Write Prescriptions</a></li>
      </ul>
    </div>
    <div>
      <h4>Support</h4>
      <ul>
        <li><a href="#faq">Help / FAQ</a></li>
        <li><a href="#">Privacy Policy</a></li>
        <li><a href="#">Terms of Use</a></li>
        <li><a href="#">Contact Us</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© <?= date('Y') ?> MediBook — Online Clinic System. Built for COMSATS University.</span>
    <span>🇵🇰 Made in Pakistan</span>
  </div>
</footer>

<script src="assets/js/main.js"></script>
<script>
function filterBySpec(spec) {
  const input = document.getElementById('doctor-search');
  if (!input) return;
  input.value = spec === 'all' ? '' : spec;
  input.dispatchEvent(new Event('input'));
  document.getElementById('doctors')?.scrollIntoView({ behavior:'smooth' });
}
document.querySelectorAll('.spec-pill').forEach(pill => {
  pill.addEventListener('click', function() {
    document.querySelectorAll('.spec-pill').forEach(p => p.classList.remove('active'));
    this.classList.add('active');
    filterBySpec(this.dataset.spec);
  });
});
</script>
</body>
</html>