require('dotenv').config();
const express  = require('express');
const multer   = require('multer');
const { DatabaseSync: Database } = require('node:sqlite');
const ExcelJS  = require('exceljs');
const archiver = require('archiver');
const nodemailer = require('nodemailer');
const jwt      = require('jsonwebtoken');
const cors     = require('cors');
const path     = require('path');
const fs       = require('fs');

const app  = express();
const PORT = process.env.PORT || 4000;
const JWT_SECRET      = process.env.JWT_SECRET      || 'ica2026-dev-secret';
const ADMIN_PASSWORD  = process.env.ADMIN_PASSWORD  || 'admin123';

/* ══════════════════════════════════════════════════════════
   MIDDLEWARE
══════════════════════════════════════════════════════════ */
app.use(cors());
app.use(express.json());
// Serve ICA static files (index, about, registration-payment, admin, etc.)
app.use(express.static(path.join(__dirname, '..')));

/* ══════════════════════════════════════════════════════════
   FILE UPLOAD
══════════════════════════════════════════════════════════ */
const UPLOADS_DIR = path.join(__dirname, 'uploads');
if (!fs.existsSync(UPLOADS_DIR)) fs.mkdirSync(UPLOADS_DIR, { recursive: true });

const storage = multer.diskStorage({
    destination: UPLOADS_DIR,
    filename: (req, file, cb) => {
        const ext  = path.extname(file.originalname).toLowerCase();
        const name = `${Date.now()}-${Math.random().toString(36).slice(2, 9)}${ext}`;
        cb(null, name);
    }
});
const upload = multer({
    storage,
    limits: { fileSize: 10 * 1024 * 1024 },   // 10 MB
    fileFilter: (req, file, cb) => {
        const allowed = ['.pdf', '.jpg', '.jpeg', '.png'];
        if (allowed.includes(path.extname(file.originalname).toLowerCase())) cb(null, true);
        else cb(new Error('Only PDF, JPG, PNG files are allowed (max 10 MB)'));
    }
});

/* ══════════════════════════════════════════════════════════
   DATABASE
══════════════════════════════════════════════════════════ */
const DB_PATH = path.join(__dirname, 'ica_registrations.db');
const db = new Database(DB_PATH);

db.exec(`
    CREATE TABLE IF NOT EXISTS registrations (
        id                      INTEGER PRIMARY KEY AUTOINCREMENT,
        created_at              TEXT    DEFAULT (datetime('now', 'localtime')),
        full_name               TEXT    NOT NULL,
        email                   TEXT    NOT NULL,
        format                  TEXT    NOT NULL,
        attendee_status         TEXT,
        discount_tier           TEXT    NOT NULL DEFAULT 'none',
        selected_rate           TEXT,
        price                   INTEGER,
        payment_url             TEXT,
        payment_status          TEXT    NOT NULL DEFAULT 'pending',
        discount_approval_status TEXT   NOT NULL DEFAULT 'not_required',
        document_filename       TEXT,
        document_original_name  TEXT
    );
`);

/* ══════════════════════════════════════════════════════════
   PRICE MAP  (mirrors registration-payment.html)
══════════════════════════════════════════════════════════ */
const PRICE_MAP = {
    onsite: {
        student: {
            early:    { none: { price: 3000, url: 'https://ofas.chula.ac.th/Service/DetailTraining?data=9aiLPPbPYWmBMdu3a5XIDQ' },
                        '20pct': { price: 2400, url: 'https://ofas.chula.ac.th/Service/DetailTraining?data=VMTejit_AIMZyEV-WIsB4w' },
                        '100pct': { price: 0, url: null } },
            standard: { none: { price: 4500, url: 'https://ofas.chula.ac.th/Service/DetailTraining?data=CRXtjZ4Z1cGoZMJhISte6g' },
                        '20pct': { price: 3600, url: 'https://ofas.chula.ac.th/Service/DetailTraining?data=Ohsi8u1aEML0teGkThWVwQ' },
                        '100pct': { price: 0, url: null } }
        },
        academic: {
            early:    { none: { price: 5000, url: 'https://ofas.chula.ac.th/Service/DetailTraining?data=xZ_6PEN01bQLvZ47BGcTsQ' },
                        '20pct': { price: 4000, url: 'https://ofas.chula.ac.th/Service/DetailTraining?data=Kx-hxOTLzC6iS19WxA7g4w' },
                        '100pct': { price: 0, url: null } },
            standard: { none: { price: 8000, url: 'https://ofas.chula.ac.th/Service/DetailTraining?data=yNyQXCjQXg7IGF7HEwBLfg' },
                        '20pct': { price: 6400, url: 'https://ofas.chula.ac.th/Service/DetailTraining?data=Vxp7PXyRrXhY2VNClLwxeA' },
                        '100pct': { price: 0, url: null } }
        }
    },
    virtual: {
        student: {
            early:    { none: { price: 2000, url: 'https://ofas.chula.ac.th/Service/DetailTraining?data=yYaWaIkEK7m70pjvlb7yqQ' },
                        '20pct': { price: 1600, url: 'https://ofas.chula.ac.th/Service/DetailTraining?data=_v8Zu7FWvQGUNfxOTQToqw' },
                        '100pct': { price: 0, url: null } },
            standard: { none: { price: 3500, url: 'https://ofas.chula.ac.th/Service/DetailTraining?data=6yj5RYkypZIaSS8zoKWrRA' },
                        '20pct': { price: 2800, url: 'https://ofas.chula.ac.th/Service/DetailTraining?data=Lh__Pmmigs2IgchDGa83jA' },
                        '100pct': { price: 0, url: null } }
        },
        academic: {
            early:    { none: { price: 3500, url: 'https://ofas.chula.ac.th/Service/DetailTraining?data=o4cEZKdqAIqhIT8kf5-X2Q' },
                        '20pct': { price: 2800, url: 'https://ofas.chula.ac.th/Service/DetailTraining?data=hqI8xQcw8M5pkQv-TAEwig' },
                        '100pct': { price: 0, url: null } },
            standard: { none: { price: 5500, url: 'https://ofas.chula.ac.th/Service/DetailTraining?data=37xkkPSRrJeiaBg-WDdF0A' },
                        '20pct': { price: 4400, url: 'https://ofas.chula.ac.th/Service/DetailTraining?data=YJgIi1xltB27YpRvZYxMmA' },
                        '100pct': { price: 0, url: null } }
        }
    },
    non: {
        all: {
            early:    { none: { price: 2500, url: 'https://ofas.chula.ac.th/Service/DetailTraining?data=wFd5cxYh6sGPyv5xM3HZbw' },
                        '20pct': { price: 2000, url: 'https://ofas.chula.ac.th/Service/DetailTraining?data=NyDbLMLs1F9z_123qBtHiw' },
                        '100pct': { price: 0, url: null } },
            standard: { none: { price: 4500, url: 'https://ofas.chula.ac.th/Service/DetailTraining?data=T67Rvql7XsczWPVYUrNTvA' },
                        '20pct': { price: 3600, url: 'https://ofas.chula.ac.th/Service/DetailTraining?data=fjJIj8Ww439WWy10dOdh4Q' },
                        '100pct': { price: 0, url: null } }
        }
    }
};

function lookupPrice(format, attendeeStatus, rate, discountTier) {
    const statusKey = format === 'non' ? 'all' : attendeeStatus;
    return PRICE_MAP[format]?.[statusKey]?.[rate]?.[discountTier] || null;
}

/* ══════════════════════════════════════════════════════════
   EMAIL
══════════════════════════════════════════════════════════ */
const FORMAT_LABELS = { onsite: 'Onsite / Poster Presenter', virtual: 'Virtual Presenter', non: 'Non Presenter' };
const STATUS_LABELS = { student: 'Student', academic: 'Academic / Faculty / Professional' };

function createTransporter() {
    return nodemailer.createTransport({
        host:   process.env.SMTP_HOST || 'smtp.gmail.com',
        port:   parseInt(process.env.SMTP_PORT || '587'),
        secure: false,
        auth:   { user: process.env.SMTP_USER, pass: process.env.SMTP_PASS }
    });
}

async function sendEmail(to, subject, html) {
    if (!process.env.SMTP_USER) {
        console.log(`[EMAIL SKIPPED — configure SMTP in .env]\n  To: ${to}\n  Subject: ${subject}`);
        return;
    }
    const t = createTransporter();
    await t.sendMail({
        from: process.env.SMTP_FROM || `ICA-TH 2026 <${process.env.SMTP_USER}>`,
        to, subject, html
    });
    console.log(`[EMAIL SENT] ${subject} → ${to}`);
}

async function emailApproved(reg) {
    const isFree = reg.discount_tier === '100pct';
    const fmt    = FORMAT_LABELS[reg.format] || reg.format;
    const sts    = reg.attendee_status ? (STATUS_LABELS[reg.attendee_status] || reg.attendee_status) : 'All Participants';

    if (isFree) {
        await sendEmail(
            reg.email,
            'ICA-TH 2026 — Free Registration Confirmed',
            `<div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:32px;">
            <h2 style="color:#1a1a2e;">Your Free Registration is Confirmed!</h2>
            <p>Dear <strong>${reg.full_name}</strong>,</p>
            <p>We are pleased to confirm that your <strong>100% discount</strong> has been approved.
               You are registered for <em>ICA Regional Hub Thailand 2026</em> at <strong>no cost</strong>.</p>
            <table style="width:100%;border-collapse:collapse;margin:20px 0;">
                <tr><td style="padding:8px;color:#666;">Name</td><td style="padding:8px;font-weight:600;">${reg.full_name}</td></tr>
                <tr style="background:#f8f8f8;"><td style="padding:8px;color:#666;">Email</td><td style="padding:8px;">${reg.email}</td></tr>
                <tr><td style="padding:8px;color:#666;">Format</td><td style="padding:8px;">${fmt}</td></tr>
                <tr style="background:#f8f8f8;"><td style="padding:8px;color:#666;">Status</td><td style="padding:8px;">${sts}</td></tr>
                <tr><td style="padding:8px;color:#666;">Discount</td><td style="padding:8px;color:#16a34a;font-weight:700;">100% FREE</td></tr>
            </table>
            <p style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px;">
               ✅ <strong>No payment is required.</strong> You will receive further event details closer to the conference date.
            </p>
            <p style="color:#666;font-size:13px;">If you have any questions please reply to this email.</p>
            </div>`
        );
    } else {
        const statusKey = reg.format === 'non' ? 'all' : reg.attendee_status;
        const eb  = PRICE_MAP[reg.format][statusKey].early['20pct'];
        const std = PRICE_MAP[reg.format][statusKey].standard['20pct'];

        await sendEmail(
            reg.email,
            'ICA-TH 2026 — Discount Approved — Complete Your Payment',
            `<div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:32px;">
            <h2 style="color:#1a1a2e;">Discount Request Approved!</h2>
            <p>Dear <strong>${reg.full_name}</strong>,</p>
            <p>Your <strong>20% discount</strong> has been approved. Please choose your payment rate below to complete your registration.</p>
            <table style="width:100%;border-collapse:collapse;margin:20px 0;">
                <tr><td style="padding:8px;color:#666;">Name</td><td style="padding:8px;font-weight:600;">${reg.full_name}</td></tr>
                <tr style="background:#f8f8f8;"><td style="padding:8px;color:#666;">Email</td><td style="padding:8px;">${reg.email}</td></tr>
                <tr><td style="padding:8px;color:#666;">Format</td><td style="padding:8px;">${fmt}</td></tr>
                <tr style="background:#f8f8f8;"><td style="padding:8px;color:#666;">Status</td><td style="padding:8px;">${sts}</td></tr>
                <tr><td style="padding:8px;color:#666;">Discount</td><td style="padding:8px;color:#d97706;font-weight:700;">20% OFF</td></tr>
            </table>
            <p style="font-weight:700;margin-top:24px;">Choose your payment rate:</p>
            <div style="display:flex;gap:16px;flex-wrap:wrap;margin:16px 0;">
                <a href="${eb.url}" style="display:inline-block;background:#f59e0b;color:#1c1917;text-decoration:none;padding:14px 28px;border-radius:10px;font-weight:700;font-size:15px;">
                    🐦 Early Bird Rate<br><span style="font-size:20px;">${eb.price.toLocaleString()} THB</span>
                </a>
                <a href="${std.url}" style="display:inline-block;background:#6366f1;color:#fff;text-decoration:none;padding:14px 28px;border-radius:10px;font-weight:700;font-size:15px;">
                    📅 Standard Rate<br><span style="font-size:20px;">${std.price.toLocaleString()} THB</span>
                </a>
            </div>
            <p style="color:#666;font-size:13px;margin-top:24px;">Please complete your payment at your earliest convenience to secure your spot.</p>
            </div>`
        );
    }
}

async function emailRejected(reg) {
    await sendEmail(
        reg.email,
        'ICA-TH 2026 — Discount Request Update',
        `<div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:32px;">
        <h2 style="color:#1a1a2e;">Discount Request Update</h2>
        <p>Dear <strong>${reg.full_name}</strong>,</p>
        <p>We regret to inform you that your discount request could not be approved at this time.</p>
        <p>You are welcome to register at the full rate. Please visit our registration page to complete your registration.</p>
        <p style="color:#666;font-size:13px;">If you believe this is an error or have questions, please reply to this email.</p>
        </div>`
    );
}

/* ══════════════════════════════════════════════════════════
   AUTH MIDDLEWARE
══════════════════════════════════════════════════════════ */
function adminAuth(req, res, next) {
    const auth = req.headers.authorization || '';
    if (!auth.startsWith('Bearer ')) return res.status(401).json({ error: 'Unauthorized' });
    try {
        jwt.verify(auth.slice(7), JWT_SECRET);
        next();
    } catch {
        res.status(401).json({ error: 'Invalid or expired token' });
    }
}

/* ══════════════════════════════════════════════════════════
   ROUTES — PUBLIC
══════════════════════════════════════════════════════════ */

// POST /api/register — called when user clicks Pay (no discount)
app.post('/api/register', (req, res) => {
    const { fullName, email, format, attendeeStatus, rate } = req.body;
    if (!fullName || !email || !format || !rate) {
        return res.status(400).json({ error: 'Missing required fields' });
    }
    const priceData = lookupPrice(format, attendeeStatus, rate, 'none');
    if (!priceData) return res.status(400).json({ error: 'Invalid combination' });

    const result = db.prepare(`
        INSERT INTO registrations
            (full_name, email, format, attendee_status, discount_tier,
             selected_rate, price, payment_url, payment_status, discount_approval_status)
        VALUES (?, ?, ?, ?, 'none', ?, ?, ?, 'pending', 'not_required')
    `).run(fullName, email, format, attendeeStatus || null, rate, priceData.price, priceData.url);

    res.json({ id: result.lastInsertRowid, paymentUrl: priceData.url });
});

// POST /api/discount-request — submit discount request with document
app.post('/api/discount-request', upload.single('document'), async (req, res) => {
    try {
        const { fullName, email, format, attendeeStatus, discountTier } = req.body;
        if (!fullName || !email || !format || !discountTier)
            return res.status(400).json({ error: 'Missing required fields' });
        if (!['20pct', '100pct'].includes(discountTier))
            return res.status(400).json({ error: 'Invalid discount tier' });
        if (!req.file)
            return res.status(400).json({ error: 'Supporting document is required' });

        const result = db.prepare(`
            INSERT INTO registrations
                (full_name, email, format, attendee_status, discount_tier,
                 payment_status, discount_approval_status,
                 document_filename, document_original_name)
            VALUES (?, ?, ?, ?, ?, 'pending', 'pending', ?, ?)
        `).run(fullName, email, format, attendeeStatus || null, discountTier,
               req.file.filename, req.file.originalname);

        res.json({ id: result.lastInsertRowid, message: 'Discount request submitted. You will receive an email once reviewed.' });
    } catch (err) {
        console.error(err);
        // Clean up uploaded file if DB fails
        if (req.file && fs.existsSync(path.join(UPLOADS_DIR, req.file.filename)))
            fs.unlinkSync(path.join(UPLOADS_DIR, req.file.filename));
        res.status(500).json({ error: err.message });
    }
});

/* ══════════════════════════════════════════════════════════
   ROUTES — ADMIN
══════════════════════════════════════════════════════════ */

// POST /api/admin/login
app.post('/api/admin/login', (req, res) => {
    const { password } = req.body || {};
    if (password !== ADMIN_PASSWORD) return res.status(401).json({ error: 'Invalid password' });
    const token = jwt.sign({ admin: true }, JWT_SECRET, { expiresIn: '12h' });
    res.json({ token });
});

// GET /api/admin/discount-requests
app.get('/api/admin/discount-requests', adminAuth, (req, res) => {
    const rows = db.prepare(`
        SELECT * FROM registrations
        WHERE discount_approval_status IN ('pending','approved','rejected')
        ORDER BY created_at DESC
    `).all();
    res.json(rows);
});

// GET /api/admin/registrations
app.get('/api/admin/registrations', adminAuth, (req, res) => {
    const rows = db.prepare(`
        SELECT * FROM registrations ORDER BY created_at DESC
    `).all();
    res.json(rows);
});

// POST /api/admin/discount-requests/:id/approve
app.post('/api/admin/discount-requests/:id/approve', adminAuth, async (req, res) => {
    const reg = db.prepare('SELECT * FROM registrations WHERE id = ?').get(req.params.id);
    if (!reg) return res.status(404).json({ error: 'Not found' });
    if (reg.discount_approval_status !== 'pending') return res.status(400).json({ error: 'Not in pending state' });

    const newPaymentStatus = reg.discount_tier === '100pct' ? 'paid' : 'pending';
    db.prepare(`
        UPDATE registrations
        SET discount_approval_status = 'approved', payment_status = ?
        WHERE id = ?
    `).run(newPaymentStatus, reg.id);

    try { await emailApproved(reg); }
    catch (e) { console.error('[EMAIL ERROR]', e.message); }

    res.json({ success: true });
});

// POST /api/admin/discount-requests/:id/reject
app.post('/api/admin/discount-requests/:id/reject', adminAuth, async (req, res) => {
    const reg = db.prepare('SELECT * FROM registrations WHERE id = ?').get(req.params.id);
    if (!reg) return res.status(404).json({ error: 'Not found' });

    db.prepare(`UPDATE registrations SET discount_approval_status = 'rejected' WHERE id = ?`).run(reg.id);

    try { await emailRejected(reg); }
    catch (e) { console.error('[EMAIL ERROR]', e.message); }

    res.json({ success: true });
});

// POST /api/admin/registrations/:id/mark-paid
app.post('/api/admin/registrations/:id/mark-paid', adminAuth, (req, res) => {
    db.prepare(`UPDATE registrations SET payment_status = 'paid' WHERE id = ?`).run(req.params.id);
    res.json({ success: true });
});

// POST /api/admin/registrations/:id/mark-pending
app.post('/api/admin/registrations/:id/mark-pending', adminAuth, (req, res) => {
    db.prepare(`UPDATE registrations SET payment_status = 'pending' WHERE id = ?`).run(req.params.id);
    res.json({ success: true });
});

// GET /api/admin/uploads/:filename — serve document (admin only)
app.get('/api/admin/uploads/:filename', adminAuth, (req, res) => {
    // Prevent path traversal
    const safe = path.basename(req.params.filename);
    const filePath = path.join(UPLOADS_DIR, safe);
    if (!fs.existsSync(filePath)) return res.status(404).json({ error: 'File not found' });
    res.sendFile(filePath);
});

/* ══════════════════════════════════════════════════════════
   EXPORT — Discount Requests ZIP (Excel + documents/)
══════════════════════════════════════════════════════════ */
app.get('/api/admin/export-discounts', adminAuth, async (req, res) => {
    try {
        let query  = `SELECT * FROM registrations WHERE discount_approval_status IN ('pending','approved','rejected')`;
        const params = [];
        if (req.query.from) { query += ` AND date(created_at) >= date(?)`; params.push(req.query.from); }
        if (req.query.to)   { query += ` AND date(created_at) <= date(?)`; params.push(req.query.to);   }
        query += ' ORDER BY created_at DESC';
        const rows = db.prepare(query).all(...params);

        /* ─ Excel ─ */
        const wb = new ExcelJS.Workbook();
        const ws = wb.addWorksheet('Discount Requests');
        ws.columns = [
            { header: '#',               key: 'id',     width: 6  },
            { header: 'Date',            key: 'date',   width: 20 },
            { header: 'Full Name',       key: 'name',   width: 26 },
            { header: 'Email',           key: 'email',  width: 32 },
            { header: 'Format',          key: 'format', width: 22 },
            { header: 'Status',          key: 'status', width: 24 },
            { header: 'Discount',        key: 'disc',   width: 12 },
            { header: 'Document',        key: 'doc',    width: 34 },
            { header: 'Approval Status', key: 'appr',   width: 16 },
        ];

        // Header style
        const hdrRow = ws.getRow(1);
        hdrRow.font = { bold: true, color: { argb: 'FFFFFFFF' } };
        hdrRow.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1A1A2E' } };
        hdrRow.alignment = { vertical: 'middle' };
        hdrRow.height = 22;

        const APPR_LABEL = { pending: 'Pending', approved: 'Approved', rejected: 'Rejected' };
        const DISC_LABEL = { '20pct': '20% OFF', '100pct': '100% Free', none: 'None' };

        rows.forEach((r, i) => {
            const row = ws.addRow({
                id:     r.id,
                date:   r.created_at,
                name:   r.full_name,
                email:  r.email,
                format: FORMAT_LABELS[r.format] || r.format,
                status: r.attendee_status ? (STATUS_LABELS[r.attendee_status] || r.attendee_status) : '—',
                disc:   DISC_LABEL[r.discount_tier] || r.discount_tier,
                doc:    r.document_filename ? r.document_original_name : '—',
                appr:   APPR_LABEL[r.discount_approval_status] || r.discount_approval_status,
            });

            // Clickable hyperlink for document
            if (r.document_filename) {
                const cell = row.getCell('doc');
                cell.value = { text: r.document_original_name, hyperlink: `documents/${r.document_filename}` };
                cell.font  = { color: { argb: 'FF0563C1' }, underline: true };
            }

            // Alternate row fill
            if (i % 2 === 0) row.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF4F4FB' } };

            // Colour approval status cell
            const apprCell = row.getCell('appr');
            if (r.discount_approval_status === 'approved') apprCell.font = { color: { argb: 'FF16A34A' }, bold: true };
            if (r.discount_approval_status === 'rejected') apprCell.font = { color: { argb: 'FFDC2626' }, bold: true };
            if (r.discount_approval_status === 'pending')  apprCell.font = { color: { argb: 'FFD97706' }, bold: true };
        });

        const excelBuf = await wb.xlsx.writeBuffer();

        /* ─ ZIP ─ */
        res.setHeader('Content-Type', 'application/zip');
        res.setHeader('Content-Disposition', `attachment; filename="ica-discount-requests-${Date.now()}.zip"`);

        const arc = archiver('zip', { zlib: { level: 9 } });
        arc.on('error', err => { throw err; });
        arc.pipe(res);
        arc.append(excelBuf, { name: 'discount-requests.xlsx' });

        rows.forEach(r => {
            if (r.document_filename) {
                const fp = path.join(UPLOADS_DIR, r.document_filename);
                if (fs.existsSync(fp)) arc.file(fp, { name: `documents/${r.document_filename}` });
            }
        });

        await arc.finalize();
    } catch (err) {
        console.error(err);
        if (!res.headersSent) res.status(500).json({ error: err.message });
    }
});

/* ══════════════════════════════════════════════════════════
   EXPORT — All Registrations Excel
══════════════════════════════════════════════════════════ */
app.get('/api/admin/export-registrations', adminAuth, async (req, res) => {
    try {
        let query = 'SELECT * FROM registrations';
        const params = [];
        const conds = [];
        if (req.query.from) { conds.push('date(created_at) >= date(?)'); params.push(req.query.from); }
        if (req.query.to)   { conds.push('date(created_at) <= date(?)'); params.push(req.query.to);   }
        if (conds.length)   query += ' WHERE ' + conds.join(' AND ');
        query += ' ORDER BY created_at DESC';
        const rows = db.prepare(query).all(...params);

        const wb = new ExcelJS.Workbook();
        const ws = wb.addWorksheet('Registrations');
        ws.columns = [
            { header: '#',               key: 'id',     width: 6  },
            { header: 'Date',            key: 'date',   width: 20 },
            { header: 'Full Name',       key: 'name',   width: 26 },
            { header: 'Email',           key: 'email',  width: 32 },
            { header: 'Format',          key: 'format', width: 22 },
            { header: 'Status',          key: 'status', width: 24 },
            { header: 'Discount',        key: 'disc',   width: 12 },
            { header: 'Rate',            key: 'rate',   width: 12 },
            { header: 'Price (THB)',     key: 'price',  width: 14 },
            { header: 'Payment Status',  key: 'pay',    width: 16 },
            { header: 'Approval Status', key: 'appr',   width: 18 },
        ];

        const hdrRow = ws.getRow(1);
        hdrRow.font = { bold: true, color: { argb: 'FFFFFFFF' } };
        hdrRow.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1A1A2E' } };
        hdrRow.height = 22;

        const DISC_LABEL = { '20pct': '20% OFF', '100pct': '100% Free', none: 'None' };
        const APPR_LABEL = { not_required: '—', pending: 'Pending', approved: 'Approved', rejected: 'Rejected' };

        rows.forEach((r, i) => {
            const row = ws.addRow({
                id:     r.id,
                date:   r.created_at,
                name:   r.full_name,
                email:  r.email,
                format: FORMAT_LABELS[r.format] || r.format,
                status: r.attendee_status ? (STATUS_LABELS[r.attendee_status] || r.attendee_status) : '—',
                disc:   DISC_LABEL[r.discount_tier] || r.discount_tier,
                rate:   r.selected_rate === 'early' ? 'Early Bird' : r.selected_rate === 'standard' ? 'Standard' : '—',
                price:  r.price || 0,
                pay:    r.payment_status === 'paid' ? 'Paid ✓' : 'Pending',
                appr:   APPR_LABEL[r.discount_approval_status] || r.discount_approval_status,
            });
            if (i % 2 === 0) row.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF4F4FB' } };

            const payCell = row.getCell('pay');
            if (r.payment_status === 'paid') payCell.font = { color: { argb: 'FF16A34A' }, bold: true };
        });

        res.setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        res.setHeader('Content-Disposition', `attachment; filename="ica-registrations-${Date.now()}.xlsx"`);
        await wb.xlsx.write(res);
        res.end();
    } catch (err) {
        console.error(err);
        if (!res.headersSent) res.status(500).json({ error: err.message });
    }
});

/* ══════════════════════════════════════════════════════════
   START
══════════════════════════════════════════════════════════ */
app.listen(PORT, () => {
    console.log('');
    console.log('  ✅  ICA-TH 2026 Registration Server');
    console.log(`  🌐  http://localhost:${PORT}`);
    console.log(`  🔧  Admin: http://localhost:${PORT}/admin.html`);
    console.log(`  📁  Uploads: ${UPLOADS_DIR}`);
    console.log(`  🗄️   Database: ${DB_PATH}`);
    console.log('');
});
