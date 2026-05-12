<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    programs: Array,
    news: Array,
});

const expandedProgram = ref(null);
const toggleProgram = (id) => { expandedProgram.value = expandedProgram.value === id ? null : id; };

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
const totalVacancies = (program) => program.openings.reduce((sum, v) => sum + v.seats, 0);
const daysLeft = (d) => Math.max(0, Math.ceil((new Date(d) - new Date()) / 86400000));
const isExpired = (d) => new Date(d) < new Date();
const currentYear = () => new Date().getFullYear();

const steps = [
    { icon: 'bi bi-person-plus-fill fs-2 text-danger', title: 'Register Online', desc: 'Create your account using a valid email address to get started.' },
    { icon: 'bi bi-person-badge-fill fs-2 text-danger', title: 'Complete Profile', desc: 'Upload your passport photo, signature and enter your Date of Birth.' },
    { icon: 'bi bi-file-earmark-text-fill fs-2 text-danger', title: 'Select Opening', desc: 'Browse available openings and choose the subject you wish to apply for.' },
    { icon: 'bi bi-credit-card-fill fs-2 text-danger', title: 'Pay & Submit', desc: 'Pay the application fee online and submit your application before the last date.' },
];
</script>

<template>
    <Head title="Application Portal" />

    <div class="lnmu-portal">

        <!-- TOP BAR -->
        <div class="topbar">
            <div class="container-fluid d-flex justify-content-between align-items-center">
               <b style="font-size:12"> Helpdesk: helpdesk@lnmu.ac.in</b>
                <div class="d-flex gap-3">
                    <Link :href="route('login')" class="topbar-link"><i class="bi bi-person-lock me-1"></i>Admin Login</Link>
                    <Link :href="route('applicant.login')" class="topbar-link"><i class="bi bi-person me-1"></i>Applicant Login</Link>
                    <Link :href="route('applicant.register')" class="topbar-link"><i class="bi bi-person-plus me-1"></i>Register</Link>
                </div>
            </div>
        </div>

        <!-- HEADER -->
        <header class="main-header">
            <div class="container-fluid">
                <div class="d-flex align-items-center gap-4">
                    <img src="/lnmu-logo.png" alt="LNMU Logo" class="header-logo" onerror="this.style.display='none'" />
                    <div class="uni-text">
                        <p class="uni-hindi mb-0">ललित नारायण मिथिला विश्वविद्यालय</p>
                        <h1 class="uni-name mb-0">{{ $page.props.tenant?.name || 'Lalit Narayan Mithila University' }}</h1>
                        <p class="uni-sub mb-0">NAAC B++ Accredited</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- NAV BAR -->
        <nav class="main-nav navbar navbar-expand-lg">
            <div class="container-fluid">
                <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav">
                        <li class="nav-item"><a href="#" class="nav-link">Home</a></li>
                        <li class="nav-item"><a href="#openings" class="nav-link">Current Recruitment</a></li>
                        <li class="nav-item"><a href="#how-to-apply" class="nav-link">How to Apply</a></li>
                        <li class="nav-item"><a href="#contact" class="nav-link">Contact</a></li>
                    </ul>
                    <div class="ms-auto d-flex gap-2">
                        <Link :href="route('applicant.register')" class="btn btn-apply">Apply Now</Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- HERO BANNER -->
        <section class="hero-section">
            <div class="hero-overlay"></div>
            <div class="container-fluid position-relative">
                <div class="hero-content text-center">
                    <div class="marquee-box mb-4">
                        <i class="bi bi-megaphone-fill me-2"></i>
                        <span>Active Recruitment {{ currentYear() }} | Applications Open | Apply Before Last Date</span>
                    </div>
                    <h2 class="hero-title">Recruitment & Application Portal</h2>
                    <p class="hero-sub">{{ $page.props.tenant?.name || 'Portal Name' }}</p>
                    <div class="d-flex gap-3 justify-content-center mt-4 flex-wrap">
                        <a href="#openings" class="btn btn-hero-primary">
                            <i class="bi bi-file-text me-2"></i>View Openings
                        </a>
                        <Link :href="route('applicant.register')" class="btn btn-hero-outline">
                            <i class="bi bi-person-plus me-2"></i>Register & Apply
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- STATS STRIP -->
        <section class="stats-strip">
            <div class="container-fluid">
                <div class="row text-center g-0">
                    <div class="col-6 col-md-3 stat-item">
                        <div class="stat-num">{{ programs.length }}</div>
                        <div class="stat-label">Active Programs</div>
                    </div>
                    <div class="col-6 col-md-3 stat-item">
                        <div class="stat-num">{{ programs.reduce((s,j) => s + j.openings.length, 0) }}</div>
                        <div class="stat-label">Subject Openings</div>
                    </div>
                    <div class="col-6 col-md-3 stat-item">
                        <div class="stat-num">{{ programs.reduce((s,j) => s + j.openings.reduce((vs,v)=>vs+v.seats,0), 0) }}</div>
                        <div class="stat-label">Total Seats</div>
                    </div>
                    <div class="col-6 col-md-3 stat-item">
                        <div class="stat-num">{{ currentYear() }}</div>
                        <div class="stat-label">Session</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- NEWS SECTION -->
        <section v-if="news && news.length > 0" class="news-section py-5 bg-white border-bottom">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="section-heading mb-4 text-start">
                            <h4 class="fw-bold text-red d-flex align-items-center">
                                <i class="bi bi-megaphone me-2"></i> Latest News & Circulars
                            </h4>
                            <div class="heading-line ms-0"></div>
                        </div>
                        <div class="news-list">
                            <div v-for="item in news" :key="item.id" class="news-item border-bottom py-3 d-flex align-items-start gap-3">
                                <div class="news-date-box text-center flex-shrink-0">
                                    <div class="news-day fw-bold">{{ new Date(item.created_at).getDate() }}</div>
                                    <div class="news-month small text-uppercase">{{ new Date(item.created_at).toLocaleString('default', { month: 'short' }) }}</div>
                                </div>
                                <div class="news-content flex-grow-1">
                                    <a :href="item.url" target="_blank" class="news-link d-block fw-semibold mb-1">
                                        {{ item.title }}
                                        <i v-if="item.type === 'file'" class="bi bi-file-earmark-pdf-fill text-danger ms-1"></i>
                                        <i v-else class="bi bi-box-arrow-up-right text-primary small ms-1"></i>
                                    </a>
                                    <span v-if="(new Date() - new Date(item.created_at)) / 86400000 <= 7" class="badge bg-danger rounded-pill x-small pulse-badge">NEW</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- VACANCIES SECTION -->
        <section id="openings" class="openings-section">
            <div class="container-fluid">
                <div class="section-heading">
                    <h2> Notifications</h2>
                    <div class="heading-line"></div>
                    <p class="text-muted">Click on any notification to view subject-wise openings and apply</p>
                </div>

                <div v-if="programs.length === 0" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox display-3 d-block opacity-50 mb-3"></i>
                    <h5>No active openings at this time.</h5>
                    <p>Please check back later or register to receive notifications.</p>
                </div>

                <!-- Notification Card (collapsed by default) -->
                <div v-for="program in programs" :key="program.id"
                    class="notif-card mb-3"
                    :class="{ 'notif-card--open': expandedProgram === program.id }">

                    <!-- Always-visible header row -->
                    <div class="notif-card__header" @click="toggleProgram(program.id)" role="button">
                        <div class="notif-left">
                            <span class="notif-dot"></span>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="badge-new">NEW</span>
                                    <span v-if="isExpired(program.application_end_date)" class="notif-closed-badge">CLOSED</span>
                                    <span v-else-if="daysLeft(program.application_end_date) <= 5" class="notif-urgent-badge">
                                        ⚡ {{ daysLeft(program.application_end_date) }} Days Left!
                                    </span>
                                    <h5 class="notif-title mb-0">{{ program.title }}</h5>
                                </div>
                                <div class="notif-meta">
                                    <span><i class="bi bi-hash"></i> {{ program.job_code }}</span>
                                    <span class="mx-2">·</span>
                                    <span><i class="bi bi-grid"></i> {{ program.openings.length }} Subjects</span>
                                    <span class="mx-2">·</span>
                                    <span><i class="bi bi-person-fill"></i> {{ totalVacancies(program) }} Total Seats</span>
                                    <span class="mx-2">·</span>
                                    <span><i class="bi bi-calendar-x"></i> Last Date: {{ formatDate(program.application_end_date) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="notif-right">
                            <span class="notif-chevron" :class="{ rotated: expandedProgram === program.id }">
                                <i class="bi bi-chevron-down"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Expanded detail panel -->
                    <div v-if="expandedProgram === program.id" class="notif-card__body">

                        <!-- Key date info strip -->
                        <div class="date-strip row g-0">
                            <div class="col-6 col-md-4 date-strip__item">
                                <div class="date-strip__label">Start Date</div>
                                <div class="date-strip__val">{{ formatDate(program.application_start_date) }}</div>
                            </div>
                            <div class="col-6 col-md-4 date-strip__item">
                                <div class="date-strip__label">Last Date</div>
                                <div class="date-strip__val" style="color:#cc0000">{{ formatDate(program.application_end_date) }}</div>
                            </div>
                            <div class="col-6 col-md-4 date-strip__item">
                                <div class="date-strip__label">Total Seats</div>
                                <div class="date-strip__val">{{ totalVacancies(program) }}</div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="program.description" class="notif-desc">
                            <i class="bi bi-info-circle me-2"></i>{{ program.description }}
                        </div>

                        <!-- Subject-wise Opening Table -->
                        <div class="table-responsive">
                            <table class="table opening-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">#</th>
                                        <th>Subject</th>
                                        <th>Code</th>
                                        <th class="text-center">Available Seats</th>
                                        <th class="text-center pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(opening, idx) in program.openings" :key="opening.id">
                                        <td class="ps-4 text-muted small">{{ idx + 1 }}</td>
                                        <td><strong>{{ opening.subject.name }}</strong></td>
                                        <td><span class="badge bg-light text-dark border">{{ opening.subject.code }}</span></td>
                                        <td class="text-center">
                                            <span class="seats-badge">{{ opening.seats }}</span>
                                        </td>
                                        <td class="text-center pe-4">
                                            <span v-if="isExpired(program.application_end_date)"
                                                class="badge bg-light text-muted border px-3 py-2 rounded-pill">
                                                Closed
                                            </span>
                                            <Link v-else :href="route('applicant.login')" class="btn btn-apply-sm">
                                                Apply <i class="bi bi-arrow-right ms-1"></i>
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="3" class="text-end fw-bold text-muted small ps-4">Total Seats</td>
                                        <td class="text-center fw-bold">{{ totalVacancies(program) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- CTA footer -->
                        <div class="notif-cta">
                            <Link :href="route('applicant.register')" class="btn btn-cta-register me-2">
                                <i class="bi bi-person-plus me-1"></i>Register & Apply
                            </Link>
                            <Link :href="route('applicant.login')" class="btn btn-cta-login">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Already Registered? Login
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- HOW TO APPLY -->
        <section id="how-to-apply" class="how-section">
            <div class="container-fluid">
                <div class="section-heading">
                    <h2>How to Apply</h2>
                    <div class="heading-line"></div>
                </div>
                <div class="row g-4">
                    <div class="col-md-3 col-sm-6" v-for="(step, i) in steps" :key="i">
                        <div class="step-card text-center">
                            <div class="step-num">{{ i + 1 }}</div>
                            <div class="step-icon"><i :class="step.icon"></i></div>
                            <h6 class="fw-bold mt-3 mb-2">{{ step.title }}</h6>
                            <p class="text-muted small mb-0">{{ step.desc }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FOOTER -->
        <footer id="contact" class="main-footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <h6 class="footer-heading">About {{ $page.props.tenant?.name || 'Portal' }}</h6>
                        <p class="footer-text">Lalit Narayan Mithila University, Darbhanga is a teaching-cum-affiliating university established in 1972. It is NAAC accredited and serves a large student population across Mithila region.</p>
                    </div>
                    <div class="col-md-4 mb-4">
                        <h6 class="footer-heading">Important Links</h6>
                        <ul class="footer-links">
                            <li><a href="https://new.lnmu.ac.in" target="_blank">LNMU Official Website</a></li>
                            <li><Link :href="route('applicant.register')">Applicant Registration</Link></li>
                            <li><Link :href="route('applicant.login')">Applicant Login</Link></li>
                            <li><Link :href="route('login')">Admin Login</Link></li>
                        </ul>
                    </div>
                    <div class="col-md-4 mb-4">
                        <h6 class="footer-heading">Contact Us</h6>
                        <ul class="footer-contact">
                            <li><i class="bi bi-geo-alt-fill me-2"></i>LNMU Campus, Darbhanga – 846004, Bihar</li>
                            <li><i class="bi bi-telephone-fill me-2"></i>0621-2226784</li>
                            <li><i class="bi bi-envelope-fill me-2"></i>helpdesk@lnmu.ac.in</li>
                        </ul>
                    </div>
                </div>
                <div class="footer-bottom">
                    <p class="mb-0">© {{ currentYear() }} {{ $page.props.tenant?.name || 'Portal Name' }}. All rights reserved.</p>
                    <p class="mb-0 text-muted small">Guest Teacher Recruitment Portal — Academic Affairs Division</p>
                </div>
            </div>
        </footer>

    </div>
</template>



<style scoped>
/* ===== GLOBAL ===== */
.lnmu-portal {
    font-family: 'Segoe UI', sans-serif;
    color: #222;
    background: #f5f5f5;
}

/* ===== TOPBAR ===== */
.topbar {
    background: #1a1a2e;
    color: #ccc;
    font-size: 0.75rem;
    padding: 6px 20px;
}
.topbar-link {
    color: #f0d080;
    text-decoration: none;
    font-size: 0.75rem;
    transition: color 0.2s;
}
.topbar-link:hover { color: #fff; }

/* ===== HEADER ===== */
.main-header {
    background: linear-gradient(135deg, #8b0000 0%, #cc0000 50%, #8b0000 100%);
    padding: 18px 24px;
    color: white;
}
.header-logo { height: 80px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3)); }
.uni-hindi { font-size: 1.1rem; font-weight: 600; color: #ffd700; letter-spacing: 0.5px; }
.uni-name { font-size: 1.6rem; font-weight: 800; letter-spacing: 0.5px; }
.uni-sub { font-size: 0.78rem; color: rgba(255,255,255,0.8); }

/* ===== NAVBAR ===== */
.main-nav {
    background: #6b0000;
    padding: 0 24px;
}
.main-nav .nav-link {
    color: rgba(255,255,255,0.85) !important;
    padding: 14px 16px !important;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.2s;
}
.main-nav .nav-link:hover { color: #ffd700 !important; background: rgba(255,255,255,0.05); }
.btn-apply { background: #ffd700; color: #8b0000; font-weight: 700; font-size: 0.82rem; padding: 8px 20px; border-radius: 4px; border: none; }
.btn-apply:hover { background: #ffed4a; color: #6b0000; }

/* ===== HERO ===== */
.hero-section {
    background: url('https://new.lnmu.ac.in/assets/img/slider/slider1.jpg') center/cover no-repeat, 
                linear-gradient(135deg, #8b0000, #cc0000);
    position: relative;
    padding: 80px 24px;
    min-height: 340px;
    display: flex;
    align-items: center;
}
.hero-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(139,0,0,0.92) 0%, rgba(30,10,10,0.85) 100%);
}
.hero-content { position: relative; z-index: 1; }
.marquee-box {
    display: inline-block;
    background: rgba(255, 215, 0, 0.15);
    border: 1px solid rgba(255, 215, 0, 0.4);
    color: #ffd700;
    padding: 6px 20px;
    border-radius: 100px;
    font-size: 0.82rem;
    font-weight: 600;
    animation: pulse-border 2s infinite;
}
@keyframes pulse-border {
    0%, 100% { border-color: rgba(255,215,0,0.4); }
    50% { border-color: rgba(255,215,0,0.9); }
}
.hero-title { font-size: 2.4rem; font-weight: 800; color: #fff; text-shadow: 0 2px 10px rgba(0,0,0,0.4); }
.hero-sub { color: rgba(255,255,255,0.7); font-size: 1rem; }
.btn-hero-primary { background: #ffd700; color: #8b0000; font-weight: 700; padding: 12px 32px; border-radius: 4px; border: none; font-size: 0.9rem; transition: all 0.2s; }
.btn-hero-primary:hover { background: #fff; color: #8b0000; transform: translateY(-2px); }
.btn-hero-outline { background: transparent; color: #fff; border: 2px solid rgba(255,255,255,0.6); font-weight: 600; padding: 11px 28px; border-radius: 4px; font-size: 0.9rem; transition: all 0.2s; }
.btn-hero-outline:hover { background: rgba(255,255,255,0.1); border-color: #fff; transform: translateY(-2px); }

/* ===== STATS ===== */
.stats-strip { background: #fff; border-bottom: 1px solid #eee; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.stat-item { padding: 20px 10px; border-right: 1px solid #f0f0f0; }
.stat-item:last-child { border-right: none; }
.stat-num { font-size: 2rem; font-weight: 800; color: #8b0000; line-height: 1; }
.stat-label { font-size: 0.75rem; color: #666; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }

/* ===== VACANCIES SECTION ===== */
.openings-section { padding: 50px 24px; background: #f7f3f3; }
.section-heading { text-align: center; margin-bottom: 36px; }
.section-heading h2 { font-size: 1.7rem; font-weight: 800; color: #8b0000; }
.heading-line { width: 60px; height: 4px; background: linear-gradient(90deg, #8b0000, #ffd700); border-radius: 2px; margin: 10px auto 14px; }

/* Notification card */
.notif-card { background: #fff; border: 1px solid #e0d5d5; border-left: 5px solid #cc0000; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: box-shadow 0.2s; }
.notif-card:hover { box-shadow: 0 4px 18px rgba(139,0,0,0.12); }
.notif-card--open { border-left-color: #8b0000; box-shadow: 0 4px 20px rgba(139,0,0,0.15); }

.notif-card__header { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 20px; cursor: pointer; user-select: none; }
.notif-card__header:hover { background: #fdf8f8; }

.notif-left { display: flex; align-items: flex-start; gap: 14px; flex: 1; min-width: 0; }
.notif-dot { width: 10px; height: 10px; background: #cc0000; border-radius: 50%; margin-top: 6px; flex-shrink: 0; animation: blink 2s infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }

.notif-title { font-size: 1rem; font-weight: 700; color: #1a0000; }
.notif-meta { font-size: 0.75rem; color: #888; margin-top: 4px; }
.notif-right { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }

.badge-new { background: #ffd700; color: #8b0000; font-size: 0.62rem; font-weight: 800; padding: 2px 8px; border-radius: 100px; letter-spacing: 1px; }
.notif-closed-badge { background: #dc3545; color: #fff; font-size: 0.62rem; font-weight: 800; padding: 2px 8px; border-radius: 100px; letter-spacing: 1px; }
.notif-urgent-badge { background: #fff3cd; color: #856404; border: 1px solid #ffc107; font-size: 0.68rem; font-weight: 700; padding: 2px 10px; border-radius: 100px; }

.fee-pill { background: #8b0000; color: #ffd700; font-size: 0.72rem; font-weight: 700; padding: 4px 12px; border-radius: 100px; white-space: nowrap; }
.notif-chevron { color: #999; font-size: 0.85rem; transition: transform 0.25s; }
.notif-chevron.rotated { transform: rotate(180deg); color: #8b0000; }

/* Body */
.notif-card__body { border-top: 1px solid #f0e8e8; animation: slideDown 0.2s ease; }
@keyframes slideDown { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:translateY(0)} }

/* Date strip */
.date-strip { background: #fdf5f5; border-bottom: 1px solid #f0e8e8; }
.date-strip__item { padding: 12px 20px; border-right: 1px solid #f0e8e8; }
.date-strip__item:last-child { border-right: none; }
.date-strip__label { font-size: 0.66rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #999; margin-bottom: 2px; }
.date-strip__val { font-size: 0.88rem; font-weight: 700; color: #1a0000; }

.notif-desc { padding: 10px 20px; font-size: 0.82rem; color: #777; background: #fffdf8; border-bottom: 1px solid #f0e8e8; font-style: italic; }

/* Table */
.opening-table th { background: #fafafa; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #555; border-bottom: 2px solid #e8e8e8; padding: 10px 16px; }
.opening-table td { padding: 10px 16px; font-size: 0.85rem; border-color: #f5f0f0; vertical-align: middle; }
.seats-badge { background: #8b0000; color: #fff; font-weight: 700; padding: 3px 14px; border-radius: 100px; font-size: 0.8rem; display: inline-block; }
.btn-apply-sm { background: #8b0000; color: #fff; font-size: 0.78rem; font-weight: 600; padding: 6px 16px; border-radius: 4px; border: none; transition: all 0.2s; }
.btn-apply-sm:hover { background: #6b0000; color: #ffd700; }

/* CTA footer */
.notif-cta { padding: 14px 20px; background: #fdf8f8; border-top: 1px solid #f0e8e8; display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }
.btn-cta-register { background: #8b0000; color: #ffd700; font-size: 0.82rem; font-weight: 700; padding: 8px 22px; border-radius: 4px; border: none; transition: all 0.2s; }
.btn-cta-register:hover { background: #6b0000; color: #fff; }
.btn-cta-login { background: transparent; color: #8b0000; font-size: 0.82rem; font-weight: 600; padding: 7px 20px; border-radius: 4px; border: 1px solid #8b0000; transition: all 0.2s; }
.btn-cta-login:hover { background: #8b0000; color: #fff; }

/* ===== HOW TO APPLY ===== */
.how-section { background: #fdf8f8; padding: 50px 24px; }
.step-card { background: #fff; border: 1px solid #f0d0d0; border-radius: 10px; padding: 30px 20px; height: 100%; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
.step-card:hover { border-color: #8b0000; box-shadow: 0 6px 20px rgba(139,0,0,0.1); transform: translateY(-4px); }
.step-num { width: 36px; height: 36px; background: #8b0000; color: #fff; border-radius: 50%; font-weight: 800; font-size: 1rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; }
.step-icon { font-size: 2.2rem; color: #8b0000; }

/* ===== FOOTER ===== */
.main-footer { background: #1a0000; color: #ccc; padding: 40px 24px 0; }
.footer-heading { color: #ffd700; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; }
.footer-text { font-size: 0.82rem; line-height: 1.7; color: #999; }
.footer-links { list-style: none; padding: 0; }
.footer-links li { margin-bottom: 8px; }
.footer-links a { color: #aaa; text-decoration: none; font-size: 0.83rem; transition: color 0.2s; }
.footer-links a:hover { color: #ffd700; }
.footer-contact { list-style: none; padding: 0; }
.footer-contact li { font-size: 0.82rem; color: #999; margin-bottom: 10px; line-height: 1.5; }
.footer-contact i { color: #cc0000; }
.footer-bottom { border-top: 1px solid #2d0000; margin: 0 -24px; padding: 16px 24px; text-align: center; color: #666; font-size: 0.8rem; margin-top: 30px; }
/* ===== NEWS SECTION ===== */
.news-section {
    background-image: linear-gradient(to right, #ffffff, #fffdf8);
}
.news-date-box {
    width: 50px;
    background: #f8f9fa;
    border: 1px solid #eee;
    border-radius: 6px;
    padding: 4px;
}
.news-day { font-size: 1.2rem; color: #8b0000; line-height: 1; }
.news-month { font-size: 0.65rem; color: #666; }
.news-link { color: #333; text-decoration: none; transition: color 0.2s; font-size: 0.95rem; }
.news-link:hover { color: #cc0000; }
.news-item:last-child { border-bottom: none !important; }

.x-small { font-size: 0.6rem; padding: 2px 6px; }
.pulse-badge {
    animation: pulse-red 2s infinite;
}
@keyframes pulse-red {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 5px rgba(220, 53, 69, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}
</style>
