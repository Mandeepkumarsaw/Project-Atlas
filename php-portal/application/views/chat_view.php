<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Atlas — AI Market Research Copilot</title>
    <!-- Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Marked & DOMPurify for Safe Markdown Rendering -->
    <script src="https://cdn.jsdelivr.net/npm/marked@12.0.2/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.1.2/dist/purify.min.js"></script>

    <style>
        :root {
            --bg-base: #F8FAFC;
            --bg-surface: #FFFFFF;
            --bg-sidebar: #0F172A;
            --text-primary: #0F172A;
            --text-secondary: #64748B;
            --text-muted: #94A3B8;
            --border-subtle: #E2E8F0;
            --border-focus: #2563EB;
            --brand-primary: #2563EB;
            --brand-hover: #1D4ED8;
            --brand-light: #EFF6FF;
            --accent-cyan: #06B6D4;
            --status-success: #059669;
            --status-success-bg: #ECFDF5;
            --status-success-border: #A7F3D0;
            --status-warning: #D97706;
            --status-warning-bg: #FFFBEB;
            --status-warning-border: #FDE68A;
            --status-critical: #DC2626;
            --status-critical-bg: #FEF2F2;
            --status-critical-border: #FECACA;
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --radius-xl: 18px;
            --shadow-subtle: 0 1px 3px rgba(15, 23, 42, 0.05), 0 1px 2px rgba(15, 23, 42, 0.03);
            --shadow-card: 0 4px 6px -1px rgba(15, 23, 42, 0.05), 0 2px 4px -2px rgba(15, 23, 42, 0.03);
        }

        * {
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-base);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: var(--text-primary);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* Branding & Logo Animation */
        .brand-container {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            cursor: pointer;
        }

        .atlas-logo-wrap {
            position: relative;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.15), rgba(6, 182, 212, 0.2));
            border: 1px solid rgba(56, 189, 248, 0.3);
            box-shadow: 0 0 12px rgba(6, 182, 212, 0.2);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .atlas-logo-wrap:hover {
            transform: scale(1.08) rotate(15deg);
            box-shadow: 0 0 20px rgba(6, 182, 212, 0.5);
            border-color: rgba(56, 189, 248, 0.8);
        }

        .atlas-logo-svg {
            width: 24px;
            height: 24px;
            animation: logo-breathe 6s ease-in-out infinite;
        }

        .orbit-ring {
            transform-origin: center;
            animation: orbit-rotate 8s linear infinite;
        }

        .core-star {
            transform-origin: center;
            animation: core-pulse 4s ease-in-out infinite;
        }

        @keyframes orbit-rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes core-pulse {
            0%, 100% { transform: scale(1); opacity: 0.95; }
            50% { transform: scale(1.15); opacity: 1; filter: drop-shadow(0 0 4px #38BDF8); }
        }

        @keyframes logo-breathe {
            0%, 100% { filter: drop-shadow(0 0 2px rgba(6, 182, 212, 0.3)); }
            50% { filter: drop-shadow(0 0 8px rgba(37, 99, 235, 0.7)); }
        }

        .brand-text-block {
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .atlas-title {
            font-size: 1.18rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            margin: 0;
            line-height: 1.2;
            display: flex;
            align-items: center;
            gap: 6px;
            opacity: 0;
            transform: translateY(8px);
            animation: title-entrance 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards, title-sweep 1.8s ease-in-out 0.4s 1 normal forwards;
            background: linear-gradient(110deg, #FFFFFF 25%, #38BDF8 48%, #818CF8 52%, #FFFFFF 75%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        @keyframes title-entrance {
            0% { opacity: 0; transform: translateY(8px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        @keyframes title-sweep {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .atlas-subtitle {
            font-size: 0.72rem;
            color: #94A3B8;
            font-weight: 500;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            margin: 0;
        }

        .accent-line {
            height: 2px;
            width: 0%;
            background: linear-gradient(90deg, #2563EB, #06B6D4, transparent);
            margin-top: 3px;
            border-radius: 2px;
            animation: accent-grow 1s cubic-bezier(0.16, 1, 0.3, 1) 0.6s forwards;
        }

        @keyframes accent-grow {
            0% { width: 0%; opacity: 0; }
            100% { width: 100%; opacity: 1; }
        }

        /* Top Navbar */
        .top-navbar {
            background-color: var(--bg-sidebar);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 0.75rem 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1030;
            backdrop-filter: blur(8px);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .client-selector-wrap {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: var(--radius-md);
            padding: 3px 10px;
        }

        .client-selector-wrap label {
            color: #94A3B8;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 8px;
            white-space: nowrap;
        }

        .client-select-dropdown {
            background: transparent;
            color: #F8FAFC;
            border: none;
            font-size: 0.82rem;
            font-weight: 500;
            cursor: pointer;
            padding: 4px 6px;
            outline: none;
        }

        .client-select-dropdown option {
            background-color: #0F172A;
            color: #F8FAFC;
        }

        .nav-btn {
            font-size: 0.8rem;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: var(--radius-md);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .nav-btn-reset {
            background: rgba(255, 255, 255, 0.05);
            color: #CBD5E1;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-btn-reset:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #FFFFFF;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #34D399;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            padding: 4px 10px;
            border-radius: 9999px;
        }

        .status-pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background-color: #10B981;
            box-shadow: 0 0 8px #10B981;
        }

        /* Workspace Layout */
        .workspace-wrapper {
            max-width: 1600px;
            margin: 0 auto;
            padding: 1.25rem 1.5rem;
        }

        .chat-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-card);
            display: flex;
            flex-direction: column;
            height: calc(100vh - 110px);
            position: relative;
            overflow: hidden;
        }

        .chat-header {
            padding: 0.9rem 1.5rem;
            background: #FFFFFF;
            border-bottom: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .assistant-avatar-badge {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-sm);
            background: linear-gradient(135deg, #2563EB, #06B6D4);
            color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
        }

        .chat-stream {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            scroll-behavior: smooth;
        }

        .message-row {
            display: flex;
            width: 100%;
        }

        .message-row-user {
            justify-content: flex-end;
        }

        .message-row-assistant {
            justify-content: flex-start;
        }

        .bubble-user {
            max-width: 80%;
            background: var(--brand-light);
            border: 1px solid #BFDBFE;
            border-radius: 16px 16px 4px 16px;
            padding: 0.9rem 1.25rem;
            color: #1E3A8A;
            font-size: 0.92rem;
            line-height: 1.55;
        }

        .bubble-assistant {
            max-width: 90%;
            width: 100%;
            background: #FFFFFF;
            border: 1px solid var(--border-subtle);
            border-radius: 16px 16px 16px 4px;
            padding: 1.2rem 1.4rem;
            color: var(--text-primary);
            box-shadow: var(--shadow-card);
            font-size: 0.92rem;
            line-height: 1.6;
        }

        .bubble-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.6rem;
            font-size: 0.76rem;
            color: var(--text-secondary);
        }

        .confidence-pill {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 9999px;
            background: #ECFDF5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }

        .conflict-box {
            background: var(--status-warning-bg);
            border: 1px solid var(--status-warning-border);
            border-left: 4px solid var(--status-warning);
            border-radius: var(--radius-md);
            padding: 0.85rem 1rem;
            margin-top: 0.85rem;
            color: #78350F;
            font-size: 0.88rem;
        }

        .calc-box {
            background: var(--status-success-bg);
            border: 1px solid var(--status-success-border);
            border-left: 4px solid var(--status-success);
            border-radius: var(--radius-md);
            padding: 0.85rem 1rem;
            margin-top: 0.85rem;
            color: #064E3B;
            font-size: 0.88rem;
        }

        .escalation-box {
            background: var(--status-critical-bg);
            border: 1px solid var(--status-critical-border);
            border-left: 4px solid var(--status-critical);
            border-radius: var(--radius-md);
            padding: 0.85rem 1rem;
            margin-top: 0.85rem;
            color: #991B1B;
            font-size: 0.88rem;
        }

        .citation-item {
            background: #F8FAFC;
            border: 1px solid var(--border-subtle);
            border-left: 3px solid var(--accent-cyan);
            border-radius: var(--radius-sm);
            padding: 0.45rem 0.75rem;
            font-size: 0.82rem;
            color: #334155;
            display: flex;
            align-items: baseline;
            gap: 6px;
        }

        .citation-tag {
            font-weight: 700;
            color: #0284C7;
            font-size: 0.78rem;
            white-space: nowrap;
        }

        /* Feedback Buttons */
        .feedback-actions {
            margin-top: 0.9rem;
            padding-top: 0.75rem;
            border-top: 1px solid #F1F5F9;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .feedback-btn {
            font-size: 0.78rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 9999px;
            border: 1px solid var(--border-subtle);
            background: #FFFFFF;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .feedback-btn-up:hover {
            border-color: var(--status-success);
            color: var(--status-success);
            background: var(--status-success-bg);
            transform: translateY(-1px);
        }

        .feedback-btn-down:hover {
            border-color: var(--status-critical);
            color: var(--status-critical);
            background: var(--status-critical-bg);
            transform: translateY(-1px);
        }

        /* Composer */
        .composer-container {
            padding: 1rem 1.5rem 1.25rem;
            background: #FFFFFF;
            border-top: 1px solid var(--border-subtle);
        }

        .composer-input-group {
            display: flex;
            align-items: center;
            background: #F8FAFC;
            border: 1px solid #CBD5E1;
            border-radius: var(--radius-xl);
            padding: 4px 6px 4px 16px;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
            transition: all 0.25s ease;
        }

        .composer-input-group:focus-within {
            background: #FFFFFF;
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .composer-input {
            flex: 1;
            border: none;
            background: transparent;
            font-size: 0.92rem;
            padding: 10px 4px;
            outline: none;
            color: var(--text-primary);
        }

        .composer-send-btn {
            background: linear-gradient(135deg, #2563EB, #1D4ED8);
            color: #FFFFFF;
            border: none;
            border-radius: var(--radius-lg);
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
            transition: all 0.2s ease;
        }

        .composer-send-btn:hover {
            transform: scale(1.04);
            background: linear-gradient(135deg, #1D4ED8, #1E40AF);
        }

        .composer-hint {
            font-size: 0.72rem;
            color: #94A3B8;
            margin-top: 6px;
            text-align: right;
        }

        /* Welcome Screen */
        .welcome-hero {
            margin: auto;
            text-align: center;
            padding: 2rem 1rem;
            max-width: 640px;
        }

        .welcome-icon-box {
            width: 58px;
            height: 58px;
            margin: 0 auto 1.25rem;
            border-radius: var(--radius-xl);
            background: linear-gradient(135deg, #EFF6FF, #E0F2FE);
            border: 1px solid #BAE6FD;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0284C7;
            font-size: 1.75rem;
        }

        .prompt-chips-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            text-align: left;
        }

        .prompt-chip {
            background: #FFFFFF;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 0.85rem 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-subtle);
        }

        .prompt-chip:hover {
            border-color: #38BDF8;
            background: #F0F9FF;
            transform: translateY(-2px);
            box-shadow: var(--shadow-card);
        }

        .chip-category {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #0284C7;
            margin-bottom: 4px;
        }

        .chip-text {
            font-size: 0.84rem;
            font-weight: 500;
            color: #1E293B;
            line-height: 1.35;
        }

        /* Right Sidebar Context Cards */
        .context-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            height: calc(100vh - 110px);
            overflow-y: auto;
        }

        .context-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-card);
            overflow: hidden;
        }

        .context-card-header {
            padding: 0.85rem 1.25rem;
            background: #FFFFFF;
            border-bottom: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .context-card-title {
            font-size: 0.84rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .context-card-body {
            padding: 1.1rem 1.25rem;
        }

        .client-profile-box {
            background: #F8FAFC;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
        }

        .report-badge-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 1rem;
        }

        .report-badge-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 7px 10px;
            border-radius: var(--radius-md);
            font-size: 0.78rem;
            font-weight: 500;
        }

        .report-paid {
            background: #ECFDF5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }

        .report-public {
            background: #EFF6FF;
            color: #1E40AF;
            border: 1px solid #BFDBFE;
        }

        .report-locked {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }

        .security-policy-note {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: var(--radius-md);
            padding: 0.75rem 0.9rem;
            font-size: 0.75rem;
            color: #64748B;
            line-height: 1.45;
        }

        .ticket-card-item {
            padding: 0.9rem 1.25rem;
            border-bottom: 1px solid #F1F5F9;
        }

        .ticket-status-open {
            background: #FEE2E2;
            color: #B91C1C;
            font-size: 0.68rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 9999px;
        }

        @media (max-width: 992px) {
            .chat-card { height: 600px; }
            .context-sidebar { height: auto; }
            .prompt-chips-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- TOP NAVIGATION -->
    <header class="top-navbar shadow-sm">
        <a href="<?php echo site_url('chat'); ?>" class="brand-container">
            <div class="atlas-logo-wrap" title="Project Atlas Intelligence Core">
                <svg class="atlas-logo-svg" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <ellipse class="orbit-ring" cx="20" cy="20" rx="16" ry="7" stroke="#38BDF8" stroke-width="1.6" stroke-dasharray="4 2" transform="rotate(-30 20 20)"/>
                    <ellipse class="orbit-ring" cx="20" cy="20" rx="14" ry="5.5" stroke="#818CF8" stroke-width="1.2" transform="rotate(45 20 20)"/>
                    <path class="core-star" d="M20 7 L23 17 L33 20 L23 23 L20 33 L17 23 L7 20 L17 17 Z" fill="url(#coreGrad)"/>
                    <circle cx="20" cy="20" r="2.5" fill="#FFFFFF"/>
                    <defs>
                        <linearGradient id="coreGrad" x1="7" y1="7" x2="33" y2="33" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#38BDF8"/>
                            <stop offset="0.5" stop-color="#2563EB"/>
                            <stop offset="1" stop-color="#06B6D4"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <div class="brand-text-block">
                <h1 class="atlas-title">◈ Project Atlas</h1>
                <div class="atlas-subtitle">
                    <span>AI Market Research Copilot</span>
                </div>
                <div class="accent-line"></div>
            </div>
        </a>

        <div class="nav-actions">
            <!-- Active Client Switcher -->
            <form action="<?php echo site_url('chat/switch_client'); ?>" method="POST" class="m-0">
                <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                <div class="client-selector-wrap" title="Switch client authorization context">
                    <i class="bi bi-buildings text-info me-1" style="font-size: 0.85rem;"></i>
                    <label for="clientSelect">Client Context:</label>
                    <select name="client_id" id="clientSelect" class="client-select-dropdown" onchange="this.form.submit()">
                        <?php foreach ($clients as $cid => $cname): ?>
                            <option value="<?php echo html_escape($cid); ?>" <?php echo $cid === $client_id ? 'selected' : ''; ?>>
                                <?php echo html_escape($cid . ' — ' . $cname); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <a href="http://localhost:8000/docs" target="_blank" class="nav-btn btn-outline-info text-info border border-info-subtle" title="FastAPI Swagger Documentation">
                <i class="bi bi-file-earmark-code"></i>
                <span class="d-none d-md-inline">API Docs</span>
            </a>

            <a href="<?php echo site_url('chat/clear'); ?>" class="nav-btn nav-btn-reset" title="Reset conversation session">
                <i class="bi bi-arrow-counterclockwise"></i>
                <span class="d-none d-md-inline">Reset</span>
            </a>

            <div class="status-pill d-none d-sm-inline-flex">
                <span class="status-pulse-dot"></span>
                <span>Copilot Online</span>
            </div>
        </div>
    </header>

    <!-- MAIN TWO-COLUMN WORKSPACE -->
    <div class="workspace-wrapper">
        <div class="row g-4">
            
            <!-- LEFT COLUMN: AI RESEARCH CHAT (~70%) -->
            <div class="col-lg-8">
                <div class="chat-card">
                    <!-- Chat Header -->
                    <div class="chat-header">
                        <div class="d-flex align-items-center gap-2">
                            <div class="assistant-avatar-badge">
                                <i class="bi bi-compass"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold" style="font-size: 0.9rem;">Atlas AI Research Assistant</h6>
                                <p class="mb-0 text-secondary" style="font-size: 0.75rem;">Grounded in licensed library reports &amp; deterministic calculations</p>
                            </div>
                        </div>
                        <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 0.72rem; font-weight: 500;">
                            <i class="bi bi-shield-check text-success me-1"></i>Pre-Retrieval Filter Active
                        </span>
                    </div>

                    <!-- Chat Stream Area -->
                    <div class="chat-stream" id="chatStream">
                        <?php if (empty($messages)): ?>
                            <div class="welcome-hero">
                                <div class="welcome-icon-box">
                                    <i class="bi bi-stars"></i>
                                </div>
                                <h4>Welcome to Project Atlas Research Copilot</h4>
                                <p>
                                    Synthesizing empirical intelligence from verified Cognitive Market Research publications. 
                                    Inquiries are pre-filtered against your licensed library entitlements with conflict auditing and deterministic math.
                                </p>
                                <div class="prompt-chips-grid">
                                    <div class="prompt-chip" onclick="setQuestion('What was the global smart water meter market size in 2024?')">
                                        <div class="chip-category"><i class="bi bi-water"></i> Market Size 2024</div>
                                        <div class="chip-text">Global smart water meter market size in 2024 (R-101)</div>
                                    </div>
                                    <div class="prompt-chip" onclick="setQuestion('Why do two reports provide different market-size figures for smart water meters?')">
                                        <div class="chip-category"><i class="bi bi-intersect"></i> Conflict Auditing</div>
                                        <div class="chip-text">Compare R-101 full-solution vs R-102 hardware-only figures</div>
                                    </div>
                                    <div class="prompt-chip" onclick="setQuestion('What might the APAC smart water meter market be worth in 2030 based on R-101?')">
                                        <div class="chip-category"><i class="bi bi-calculator"></i> Deterministic Math</div>
                                        <div class="chip-text">APAC 2030 market projection using 11.2% CAGR formula</div>
                                    </div>
                                    <div class="prompt-chip" onclick="setQuestion('What might the smart water meter market be worth in 2035?')">
                                        <div class="chip-category" style="color: #D97706;"><i class="bi bi-headset"></i> Horizon Check</div>
                                        <div class="chip-text">Out-of-horizon 2035 inquiry with automatic analyst ticket</div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $msg_idx => $msg): ?>
                                <div class="message-row <?php echo $msg['role'] === 'user' ? 'message-row-user' : 'message-row-assistant'; ?>">
                                    <?php if ($msg['role'] === 'user'): ?>
                                        <div class="bubble-user">
                                            <div class="bubble-meta" style="color: #60A5FA;">
                                                <span class="fw-semibold"><i class="bi bi-person-fill"></i> You (<?php echo html_escape($client_id); ?>)</span>
                                                <span><?php echo html_escape($msg['time']); ?></span>
                                            </div>
                                            <div><?php echo nl2br(html_escape($msg['content'])); ?></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="bubble-assistant">
                                            <div class="bubble-meta">
                                                <span class="fw-bold text-dark d-flex align-items-center gap-2">
                                                    <span class="assistant-avatar-badge" style="width: 22px; height: 22px; font-size: 0.72rem;"><i class="bi bi-compass"></i></span>
                                                    Atlas Copilot
                                                    <?php if (isset($msg['confidence'])): ?>
                                                        <span class="confidence-pill">
                                                            <i class="bi bi-check-circle-fill"></i> <?php echo round($msg['confidence'] * 100); ?>% Confidence
                                                        </span>
                                                    <?php endif; ?>
                                                </span>
                                                <span><?php echo html_escape($msg['time']); ?></span>
                                            </div>

                                            <!-- Rendered Markdown Content -->
                                            <div class="markdown-body msg-content-markdown" data-raw="<?php echo html_escape($msg['content']); ?>">
                                                <?php echo nl2br(html_escape($msg['content'])); ?>
                                            </div>

                                            <!-- Source Conflicts -->
                                            <?php if (!empty($msg['conflicts'])): ?>
                                                <div class="conflict-box">
                                                    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Source Discrepancy Detected:</div>
                                                    <ul class="mb-0 ps-3">
                                                        <?php foreach ($msg['conflicts'] as $conf): ?>
                                                            <li><?php echo html_escape($conf); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Derived Calculations -->
                                            <?php if (!empty($msg['derived_values'])): ?>
                                                <?php foreach ($msg['derived_values'] as $calc): ?>
                                                    <div class="calc-box">
                                                        <div class="fw-bold mb-1"><i class="bi bi-calculator-fill text-success me-1"></i> Derived Valuation: <?php echo html_escape($calc['metric']); ?></div>
                                                        <div class="small"><strong>Formula:</strong> <code><?php echo html_escape($calc['formula']); ?></code></div>
                                                        <div class="small mt-1"><strong>Result:</strong> <span class="badge bg-success text-white px-2 py-1"><?php echo html_escape($calc['result']); ?></span></div>
                                                        <?php if (!empty($calc['assumptions'])): ?>
                                                            <div class="small text-muted mt-1"><strong>Assumptions:</strong> <?php echo html_escape(implode(' | ', $calc['assumptions'])); ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>

                                            <!-- Escalation Status -->
                                            <?php if (!empty($msg['escalated'])): ?>
                                                <div class="escalation-box">
                                                    <div class="fw-bold mb-1"><i class="bi bi-headset text-danger me-1"></i> Escalated to Senior Research Analyst</div>
                                                    <div>A custom econometric ticket has been created: <code><?php echo html_escape($msg['ticket_id'] ?: 'ESC-PENDING'); ?></code>. Review ongoing in institutional queue.</div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Citations -->
                                            <?php if (!empty($msg['citations'])): ?>
                                                <div class="mt-3 pt-2 border-top">
                                                    <div class="small fw-bold text-secondary mb-2"><i class="bi bi-journal-bookmark-fill text-info me-1"></i> Grounded Citations:</div>
                                                    <div class="d-flex flex-column gap-1">
                                                        <?php foreach ($msg['citations'] as $cit): ?>
                                                            <div class="citation-item">
                                                                <span class="citation-tag">[<?php echo html_escape($cit['report_id']); ?>, Page <?php echo (int)$cit['page']; ?>]</span>
                                                                <span><?php echo html_escape($cit['claim']); ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Thumbs Up / Down Feedback -->
                                            <?php $f_id = !empty($msg['id']) ? (int)$msg['id'] : $msg_idx + 1; ?>
                                            <div class="feedback-actions" id="feedback-block-<?php echo $f_id; ?>">
                                                <span class="small text-muted fw-semibold me-1"><i class="bi bi-chat-heart text-primary me-1"></i> Feedback:</span>
                                                <button type="button" class="feedback-btn feedback-btn-up" onclick="submitFeedback(<?php echo $f_id; ?>, 1)">
                                                    👍 Thumbs Up
                                                </button>
                                                <button type="button" class="feedback-btn feedback-btn-down" onclick="submitFeedback(<?php echo $f_id; ?>, -1)">
                                                    👎 Thumbs Down
                                                </button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Floating Composer Input -->
                    <div class="composer-container">
                        <form action="<?php echo site_url('chat/send'); ?>" method="POST" id="chatForm">
                            <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                            <div class="composer-input-group">
                                <i class="bi bi-chat-left-dots text-muted me-2" style="font-size: 1.05rem;"></i>
                                <input type="text" id="questionInput" name="question" class="composer-input" 
                                       placeholder="Ask a market research question (e.g. 2024 market valuations, CAGRs, scope differences)..." 
                                       required autofocus autocomplete="off">
                                <button type="submit" class="composer-send-btn" title="Send Question">
                                    <i class="bi bi-arrow-up"></i>
                                </button>
                            </div>
                            <div class="composer-hint">
                                Press <strong>Enter ↵</strong> to send &bull; Grounded in certified market intelligence
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: RESEARCH CONTEXT (~30%) -->
            <div class="col-lg-4">
                <div class="context-sidebar">
                    
                    <!-- CARD A: ENTITLEMENT BOUNDARY -->
                    <div class="context-card">
                        <div class="context-card-header">
                            <h6 class="context-card-title">
                                <i class="bi bi-shield-lock-fill text-primary"></i>
                                Entitlement Context
                            </h6>
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.7rem; font-weight: 600;">
                                Verified Active
                            </span>
                        </div>
                        <div class="context-card-body">
                            <div class="client-profile-box">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="text-secondary small fw-semibold">Authenticated Organization:</span>
                                    <span class="badge bg-primary text-white">
                                        <?php echo $client_id === 'C-01' ? 'Enterprise' : ($client_id === 'C-02' ? 'Standard' : 'Trial'); ?>
                                    </span>
                                </div>
                                <h6 class="fw-bold mb-0 text-dark"><?php echo html_escape($client_id . ' — ' . $clients[$client_id]); ?></h6>
                            </div>

                            <label class="text-secondary fw-semibold small mb-2 d-block">Authorized Report Entitlements:</label>
                            <div class="report-badge-list">
                                <?php if ($client_id === 'C-01'): ?>
                                    <div class="report-badge-item report-paid">
                                        <span><i class="bi bi-check-circle-fill me-1"></i> <strong>R-101</strong>: Smart Water Meter 2025</span>
                                        <span class="badge bg-success text-white py-0">Paid</span>
                                    </div>
                                    <div class="report-badge-item report-paid">
                                        <span><i class="bi bi-check-circle-fill me-1"></i> <strong>R-103</strong>: Water Purifier Market 2025</span>
                                        <span class="badge bg-success text-white py-0">Paid</span>
                                    </div>
                                    <div class="report-badge-item report-public">
                                        <span><i class="bi bi-eye-fill me-1"></i> <strong>R-102</strong>: Metering Outlook 2024</span>
                                        <span class="badge bg-info text-dark py-0">Excerpt</span>
                                    </div>
                                <?php elseif ($client_id === 'C-02'): ?>
                                    <div class="report-badge-item report-locked">
                                        <span><i class="bi bi-lock-fill me-1"></i> <strong>R-101</strong>: Full Solution (No License)</span>
                                        <span class="badge bg-danger text-white py-0">Locked</span>
                                    </div>
                                    <div class="report-badge-item report-paid">
                                        <span><i class="bi bi-check-circle-fill me-1"></i> <strong>R-103</strong>: Water Purifier Market 2025</span>
                                        <span class="badge bg-success text-white py-0">Paid</span>
                                    </div>
                                    <div class="report-badge-item report-public">
                                        <span><i class="bi bi-eye-fill me-1"></i> <strong>R-102</strong>: Metering Outlook 2024</span>
                                        <span class="badge bg-info text-dark py-0">Excerpt</span>
                                    </div>
                                <?php else: ?>
                                    <div class="report-badge-item report-locked">
                                        <span><i class="bi bi-lock-fill me-1"></i> <strong>R-101</strong>: Smart Water Meter (Locked)</span>
                                        <span class="badge bg-danger text-white py-0">Locked</span>
                                    </div>
                                    <div class="report-badge-item report-locked">
                                        <span><i class="bi bi-lock-fill me-1"></i> <strong>R-103</strong>: Water Purifier (Locked)</span>
                                        <span class="badge bg-danger text-white py-0">Locked</span>
                                    </div>
                                    <div class="report-badge-item report-public">
                                        <span><i class="bi bi-eye-fill me-1"></i> <strong>R-102</strong>: Metering Outlook 2024</span>
                                        <span class="badge bg-info text-dark py-0">Excerpt</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="security-policy-note">
                                <i class="bi bi-info-circle-fill text-primary me-1"></i>
                                <strong>Pre-Retrieval Isolation:</strong> Qdrant vector filters enforce client report access before retrieval. Non-licensed chunks never enter the model's context.
                            </div>
                        </div>
                    </div>

                    <!-- CARD B: ANALYST ESCALATION TICKETS -->
                    <div class="context-card">
                        <div class="context-card-header">
                            <h6 class="context-card-title">
                                <i class="bi bi-ticket-detailed-fill text-warning"></i>
                                Analyst Escalation Tickets
                            </h6>
                            <span class="badge bg-warning text-dark"><?php echo count($escalations); ?></span>
                        </div>
                        <div class="context-card-body p-0">
                            <div class="ticket-list">
                                <?php if (empty($escalations)): ?>
                                    <div class="p-3 text-center text-muted small">No active escalations recorded for this client.</div>
                                <?php else: ?>
                                    <?php foreach (array_slice($escalations, 0, 6) as $esc): ?>
                                        <div class="ticket-card-item">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="ticket-status-open"><?php echo html_escape($esc['status']); ?></span>
                                                <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.7rem;"><?php echo html_escape($esc['ticket_id']); ?></span>
                                            </div>
                                            <div class="small fw-semibold text-truncate text-dark mb-1"><?php echo html_escape($esc['question']); ?></div>
                                            <div class="text-secondary" style="font-size: 0.74rem; line-height: 1.4;"><?php echo html_escape(substr($esc['reason'], 0, 140)) . (strlen($esc['reason']) > 140 ? '...' : ''); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- JAVASCRIPT LOGIC & MARKDOWN CONVERSION -->
    <script>
        function setQuestion(text) {
            const input = document.getElementById('questionInput');
            input.value = text;
            input.focus();
        }

        // Auto scroll chat to bottom
        const stream = document.getElementById('chatStream');
        if (stream) {
            stream.scrollTop = stream.scrollHeight;
        }

        // Safe Markdown Parser for PHP pre-rendered messages
        function safeParseMarkdown(text) {
            if (!text) return "";
            try {
                if (typeof marked !== "undefined" && typeof DOMPurify !== "undefined") {
                    marked.setOptions({ gfm: true, breaks: true });
                    return DOMPurify.sanitize(marked.parse(text));
                }
            } catch (e) {}
            // Zero-dependency regex fallback
            let out = text
                .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
            out = out.replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');
            out = out.replace(/`([^`]+)`/g, '<code class="code-inline">$1</code>');
            out = out.replace(/\*\*\*([^*]+)\*\*\*/g, '<strong><em>$1</em></strong>');
            out = out.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
            out = out.replace(/\*([^*]+)\*/g, '<em>$1</em>');
            out = out.replace(/(?:^|\n)- ([^\n]+)/g, '<br>&bull; $1');
            out = out.replace(/(?:^|\n)(\d+)\. ([^\n]+)/g, '<br>$1. $2');
            out = out.replace(/\n/g, '<br>');
            return out;
        }

        // Automatically format any existing assistant markdown messages
        document.querySelectorAll('.msg-content-markdown').forEach(el => {
            const raw = el.getAttribute('data-raw');
            if (raw) {
                el.innerHTML = safeParseMarkdown(raw);
            }
        });

        // Submit feedback asynchronously to MySQL without page reload
        function submitFeedback(messageId, rating) {
            const csrfName = "<?php echo $this->security->get_csrf_token_name(); ?>";
            const csrfHash = "<?php echo $this->security->get_csrf_hash(); ?>";
            const formData = new URLSearchParams();
            formData.append('message_id', messageId);
            formData.append('rating', rating);
            formData.append(csrfName, csrfHash);

            fetch("<?php echo site_url('chat/feedback'); ?>", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData.toString()
            })
            .then(res => res.json())
            .then(data => {
                const block = document.getElementById('feedback-block-' + messageId);
                if (block) {
                    block.innerHTML = '<span class="badge bg-success text-white py-1 px-3 small"><i class="bi bi-check-circle-fill me-1"></i> Feedback recorded in MySQL</span>';
                }
            })
            .catch(err => {
                console.error('Feedback submission error:', err);
            });
        }
    </script>
</body>
</html>
