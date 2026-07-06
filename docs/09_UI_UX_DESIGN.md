# UI / UX Design System

## Design Philosophy

Keywords:

- Zen
- calm
- spacious
- focused
- quiet
- human

The roleplay customer may be difficult or uncomfortable.

The application shell should feel safe.

## What to Avoid

- generic corporate blue dashboard
- cyberpunk AI visuals
- neon
- excessive gradients
- excessive glassmorphism
- casino gamification
- red-heavy score screens
- robot illustrations everywhere
- dense template dashboards
- decorative Japanese cosplay
- random kanji
- torii gates
- bamboo decorations used as theme props

Zen is a design philosophy, not a restaurant theme.

## Visual Language

Conceptual inspiration:

- quiet interiors
- stone gardens
- natural light
- paper surfaces
- sand
- muted natural materials

Suggested palette direction:

- warm off-white base
- stone gray surfaces
- muted sage accent
- charcoal text
- restrained warning colors

Exact color tokens belong in Tailwind/theme configuration.

Use:

- generous whitespace
- clear typography hierarchy
- subtle borders
- restrained shadows
- intentional rounded surfaces

## Typography

Use a highly readable web font available through a normal web delivery mechanism or robust system-font fallback.

Do not make the UI dependent on proprietary local font files.

Typography goals:

- calm
- modern
- readable on mobile
- Indonesian text-friendly

## Motion

Motion should feel intentional and breathing.

Use subtle motion for:

- page transitions
- microphone activation
- connection state
- voice activity
- evaluation analysis

Roleplay central visualization concept:

- AI speaking: gentle organic motion
- salesperson speaking: responsive motion tied to microphone energy
- silence: stillness

Avoid aggressive spectrum equalizers.

Respect `prefers-reduced-motion`.

Do not let animation degrade audio performance.

## Authentication Screens

Use the same calm visual language.

Registration fields:

- Nama Lengkap
- Email
- Password
- Konfirmasi Password

Helper:

> Gunakan nama lengkap Anda. Nama ini akan digunakan pada riwayat dan laporan training.

Required screens:

- Login
- Daftar Akun
- Lupa Password
- Reset Password
- Menunggu Persetujuan HQ
- Akun Ditangguhkan

## Sales Training Dashboard

Prioritize:

- continue/recent training context
- available scenarios
- recent results
- simple improvement trend

Do not show CRM data.

Scenario cards may display:

- scenario name
- short context
- difficulty
- estimated/max duration
- allowed persona modes

## Scenario Setup Flow

Suggested flow:

1. Select scenario
2. Review difficulty
3. Select Persona Mode when multiple modes are allowed
4. Resolve/show permitted persona information
5. Review briefing
6. Pre-call screen
7. Microphone check
8. Start call

Keep the number of screens low.

## Pre-Call Experience

Brief calming copy:

> Tarik napas sebentar.

> Dengarkan dulu.

> Tidak perlu terburu-buru.

Primary CTA:

`Mulai Panggilan`

Do not force a long meditation ritual.

Frequent users should proceed quickly.

## Roleplay Screen

Mobile-first.

Display only what helps conversation:

- customer public identity when permitted
- scenario title/context
- difficulty
- call duration
- connection/session state
- central audio visualization
- microphone state
- `Akhiri Panggilan`

Do not display:

- trust score
- irritation score
- objection state
- hidden information
- persona sliders
- Actor Instructions
- Director Notes
- full live transcript as primary UI

Simplified states:

- Menyiapkan sesi
- Menghubungkan
- Siap
- Konsumen sedang berbicara
- Mendengarkan
- Menghubungkan kembali
- Mengakhiri sesi
- Menganalisis percakapan
- Hasil siap

## 14-Minute Warning

Use a subtle visual indication.

Do not open a disruptive modal.

At 15 minutes:

> Sesi latihan telah mencapai batas 15 menit.

Then transition to transcript finalization/evaluation.

## Evaluation Waiting State

The evaluation may run asynchronously through Hostinger cron/queue.

Display a calm progress screen/state:

> Menganalisis percakapan

Explain that the training session has been saved.

Do not imply the session is lost when the evaluator is delayed.

If evaluation fails:

> Sesi latihan tersimpan, tetapi evaluasi belum berhasil diproses.

Provide an appropriate retry/status path based on authorization.

## Result Page

Do not design like a school exam.

Recommended hierarchy:

1. Overall Performance
2. Session Summary
3. Score Breakdown
4. What You Did Well
5. What Weakened the Conversation
6. Critical Mistakes
7. Missed Opportunities
8. Customer Emotional Journey
9. Better Response Examples
10. Next Training Focus
11. Transcript

Use calm direct language.

Avoid humiliating visual treatment.

## Transcript Evidence

Findings should link to transcript sequences.

Example finding:

> Anda berpindah ke penjelasan produk sebelum menggali kekhawatiran cicilan.

Associated transcript:

Customer:

> Sebenarnya saya takut cicilannya berat.

Sales:

> Kalau rumah kami luas tanahnya...

Explain why the moment matters.

## Training History

Sales sees own:

- session date
- scenario
- persona when permitted
- persona mode
- difficulty
- duration
- ending type
- score
- evaluation status

Hidden Persona history must not expose raw hidden configuration.

## HQ Dashboard

Training intelligence only.

Possible metrics:

- total sessions
- active sales users
- sessions by branch
- average performance
- branch performance
- weakest competencies
- common mistakes
- common missed opportunities
- most-used scenarios
- evaluator failure rate
- AI-ended session rate

Filters:

- date
- branch
- salesperson
- scenario
- persona
- Persona Mode
- difficulty

## Admin Persona Builder

Do not present one giant form.

Use sections/steps:

1. Identitas
2. Kondisi & Kebutuhan Rumah
3. Pengetahuan & Keyakinan
4. Kepribadian
5. Cara Berkomunikasi
6. Human Behavior Traits
7. Objection
8. Informasi Tersembunyi
9. Initial State & Sensitivity advanced section
10. Salience preview

Admin-facing behavior controls use human labels:

- Sangat Rendah
- Rendah
- Sedang
- Tinggi
- Sangat Tinggi

Do not expose temperature/top-p as the primary persona design interface.

Show a compiled behavioral hierarchy preview:

- Primary Behavior
- Secondary Behavior
- Background Tendencies

Do not show the full private Actor Instructions unless in an explicit Super Admin technical/debug view.

## Persona Lab UI

Secondary feature.

Persona Lab may expose technical panels:

- current Dynamic State
- normalized events
- objection states
- disclosure states
- boundary state
- Director Notes

Clearly label it as an Admin simulation/debugging environment.

## Accessibility

- keyboard-accessible admin controls
- visible focus states
- sufficient contrast
- reduced-motion support
- clear microphone permission guidance
- status changes should have accessible text, not animation alone
- do not encode difficulty or evaluation solely by color
