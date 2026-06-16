// MediBook — main.js (Desktop-focused, no mobile sidebar)

document.addEventListener('DOMContentLoaded', () => {

    // ── 1. Dark Mode ──────────────────────────────────────
    const THEME_KEY = 'medibook_theme';
    const html      = document.documentElement;

    // Apply saved theme immediately (prevents flash)
    const saved = localStorage.getItem(THEME_KEY) || 'light';
    html.setAttribute('data-theme', saved);
    updateToggleIcon(saved);

    // Toggle button click
    document.querySelectorAll('.theme-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const current = html.getAttribute('data-theme');
            const next    = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            localStorage.setItem(THEME_KEY, next);
            updateToggleIcon(next);
        });
    });

    function updateToggleIcon(theme) {
        document.querySelectorAll('.theme-toggle').forEach(btn => {
            btn.textContent = theme === 'dark' ? '☀️' : '🌙';
            btn.title       = theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode';
        });
    }

    // ── 2. Navbar scroll effect ───────────────────────────
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 30);
        }, { passive: true });
    }

    // ── 3. Auto-dismiss alerts ────────────────────────────
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity .5s, transform .5s';
            alert.style.opacity    = '0';
            alert.style.transform  = 'translateY(-8px)';
            setTimeout(() => alert.remove(), 500);
        }, 4500);
    });

    // ── 4. Animated counters ──────────────────────────────
    const counters = document.querySelectorAll(
        '.counter-num[data-target], .stat-num[data-count]'
    );
    const countObs = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (!e.isIntersecting) return;
            const el     = e.target;
            const target = parseInt(el.dataset.target || el.dataset.count || 0);
            const suffix = el.dataset.suffix || '';
            animateCount(el, target, suffix);
            countObs.unobserve(el);
        });
    }, { threshold: 0.5 });
    counters.forEach(c => countObs.observe(c));

    function animateCount(el, target, suffix) {
        const duration = 1500;
        const start    = performance.now();
        const easeOut  = t => 1 - Math.pow(1 - t, 3);
        const tick     = now => {
            const p = Math.min((now - start) / duration, 1);
            el.textContent = Math.floor(easeOut(p) * target).toLocaleString() + suffix;
            if (p < 1) requestAnimationFrame(tick);
            else el.textContent = target.toLocaleString() + suffix;
        };
        requestAnimationFrame(tick);
    }

    // ── 5. Scroll-reveal ─────────────────────────────────
    const reveals = document.querySelectorAll('.animate-in');
    if (reveals.length) {
        const revObs = new IntersectionObserver(entries => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    e.target.style.animationPlayState = 'running';
                    revObs.unobserve(e.target);
                }
            });
        }, { threshold: 0.12 });
        reveals.forEach(el => {
            el.style.animationPlayState = 'paused';
            revObs.observe(el);
        });
    }

    // ── 6. Doctor search (landing page) ──────────────────
    const searchInput = document.getElementById('doctor-search');
    const searchClear = document.getElementById('search-clear');
    const doctorCards = document.querySelectorAll('.doctor-card[data-search]');

    if (searchInput && doctorCards.length) {
        searchInput.addEventListener('input', function () {
            const q     = this.value.trim().toLowerCase();
            let   found = 0;
            if (searchClear) searchClear.style.display = q ? 'flex' : 'none';

            doctorCards.forEach(card => {
                const match = !q || card.dataset.search.toLowerCase().includes(q);
                card.style.display   = match ? '' : 'none';
                card.style.animation = match ? 'fadeIn .3s ease' : '';
                if (match) found++;
            });

            let noRes = document.getElementById('no-results');
            if (!found && q) {
                if (!noRes) {
                    noRes = document.createElement('div');
                    noRes.id        = 'no-results';
                    noRes.className = 'empty-state';
                    noRes.style.gridColumn = '1/-1';
                    noRes.innerHTML = `<div class="icon">🔍</div><p>No doctors found for "<strong>${q}</strong>"</p>`;
                    document.querySelector('.doctors-grid')?.appendChild(noRes);
                }
            } else { noRes?.remove(); }
        });

        searchClear?.addEventListener('click', () => {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.focus();
        });
    }

    // ── 7. Spec filter → doctor search ───────────────────
    document.querySelectorAll('.spec-pill[data-spec]').forEach(pill => {
        pill.addEventListener('click', function () {
            document.querySelectorAll('.spec-pill').forEach(p => {
                p.style.borderColor = '';
                p.style.background  = '';
            });
            this.style.borderColor = 'var(--primary)';
            this.style.background  = 'var(--primary-light)';

            const spec = this.dataset.spec;
            if (searchInput) {
                searchInput.value = spec === 'all' ? '' : spec;
                searchInput.dispatchEvent(new Event('input'));
                document.getElementById('doctors')
                    ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ── 8. FAQ accordion ─────────────────────────────────
    document.querySelectorAll('.faq-question').forEach(q => {
        q.addEventListener('click', function () {
            const item   = this.closest('.faq-item');
            const answer = item.querySelector('.faq-answer');
            const icon   = this.querySelector('.faq-icon');
            const isOpen = item.classList.contains('open');

            // Close all
            document.querySelectorAll('.faq-item.open').forEach(open => {
                open.classList.remove('open');
                open.querySelector('.faq-answer').style.maxHeight = '0';
                open.querySelector('.faq-answer').style.opacity   = '0';
                const i = open.querySelector('.faq-icon');
                if (i) i.style.transform = 'rotate(0deg)';
            });

            // Open clicked (if was closed)
            if (!isOpen) {
                item.classList.add('open');
                answer.style.maxHeight = answer.scrollHeight + 'px';
                answer.style.opacity   = '1';
                if (icon) icon.style.transform = 'rotate(45deg)';
            }
        });
    });

    // ── 9. Reminder badge pulse ───────────────────────────
    const badge = document.querySelector('.reminder-badge');
    if (badge) {
        setInterval(() => badge.classList.toggle('pulse-once'), 3000);
    }

    // ── 10. Time slot selection ───────────────────────────
    document.addEventListener('click', e => {
        if (e.target.classList.contains('slot-btn')) {
            document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
            e.target.classList.add('selected');
            const inp = document.getElementById('selected_slot');
            if (inp) inp.value = e.target.dataset.slot || e.target.textContent.trim();
        }
    });

    // ── 11. Ripple on buttons ─────────────────────────────
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            const rect   = this.getBoundingClientRect();
            const size   = Math.max(rect.width, rect.height);
            const ripple = document.createElement('span');
            ripple.style.cssText = `
                position:absolute;width:${size}px;height:${size}px;
                top:${e.clientY-rect.top-size/2}px;
                left:${e.clientX-rect.left-size/2}px;
                border-radius:50%;background:rgba(255,255,255,.22);
                transform:scale(0);animation:ripple .5s linear;
                pointer-events:none;`;
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 500);
        });
    });

    if (!document.getElementById('ripple-style')) {
        const s = document.createElement('style');
        s.id = 'ripple-style';
        s.textContent = '@keyframes ripple{to{transform:scale(4);opacity:0}}';
        document.head.appendChild(s);
    }

    // ── 12. Date min = today ──────────────────────────────
    document.querySelectorAll('input#appointment_date[type="date"]').forEach(inp => {
        inp.min = new Date().toISOString().split('T')[0];
    });

    // ── 13. Confirm helpers ───────────────────────────────
    window.confirmDelete = m => confirm(m || 'Are you sure you want to delete this?');
    window.confirmCancel = () => confirm('Are you sure you want to cancel this appointment?');
    window.printPrescription = id =>
        window.open(`../includes/print_prescription.php?id=${id}`, '_blank');

    // ── 14. Load slots (AJAX) ─────────────────────────────
    window.loadSlots = function (doctorId, date) {
        const container = document.getElementById('slots-container');
        const slotInput = document.getElementById('selected_slot');
        if (!container || !doctorId || !date) return;

        container.innerHTML = `
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                ${[1,2,3,4].map(() =>
                    '<div class="skeleton" style="width:90px;height:36px;border-radius:8px"></div>'
                ).join('')}
            </div>`;
        if (slotInput) slotInput.value = '';

        fetch(`../includes/get_slots.php?doctor_id=${doctorId}&date=${date}`)
            .then(r => r.json())
            .then(data => {
                if (data.slots?.length) {
                    container.innerHTML = '<div class="slots-grid">' +
                        data.slots.map(s =>
                            `<button type="button" class="slot-btn" data-slot="${s}">${s}</button>`
                        ).join('') + '</div>';
                } else {
                    container.innerHTML = `
                        <div class="alert alert-warning" style="margin:0">
                            ⚠️ No slots available for this date. Please try another date.
                        </div>`;
                }
            })
            .catch(() => {
                container.innerHTML =
                    '<div class="alert alert-danger" style="margin:0">Could not load slots. Refresh and try again.</div>';
            });
    };

});

// ── Global helpers called inline ────────────────────────
function selectSlot(btn, slotTime) {
    document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    const inp = document.getElementById('selected_slot');
    if (inp) inp.value = slotTime;
}

function onDateChange() {
    const docId = document.getElementById('doctor_id')?.value;
    const date  = document.getElementById('appointment_date')?.value;
    if (docId && date) loadSlots(docId, date);
}
