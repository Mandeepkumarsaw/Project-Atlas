<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytical Intelligence — Project Atlas</title>
    <!-- Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Chart.js 4.4 -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
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

        .brand-text-block {
            display: flex;
            flex-direction: column;
        }

        .atlas-title {
            font-size: 1.18rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            margin: 0;
            line-height: 1.2;
            background: linear-gradient(110deg, #FFFFFF 25%, #38BDF8 48%, #818CF8 52%, #FFFFFF 75%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .atlas-subtitle {
            font-size: 0.72rem;
            font-weight: 600;
            color: #94A3B8;
            letter-spacing: 0.05em;
            text-transform: uppercase;
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

        .nav-view-switcher {
            display: inline-flex;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: var(--radius-md);
            padding: 3px;
            gap: 4px;
        }

        .nav-tab-btn {
            font-size: 0.82rem;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            color: #94A3B8;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .nav-tab-btn:hover {
            color: #F8FAFC;
            background: rgba(255, 255, 255, 0.08);
        }

        .nav-tab-btn.active {
            color: #FFFFFF;
            background: linear-gradient(135deg, #2563EB, #0284C7);
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.4);
        }

        .badge-pill-new {
            background: rgba(56, 189, 248, 0.25);
            color: #38BDF8;
            border: 1px solid rgba(56, 189, 248, 0.5);
            font-size: 0.62rem;
            padding: 1px 6px;
            border-radius: 9999px;
            letter-spacing: 0.05em;
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
            background-color: #34D399;
            border-radius: 50%;
            box-shadow: 0 0 0 rgba(52, 211, 153, 0.7);
            animation: pulse-ring 2s infinite;
        }

        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.7); }
            70% { box-shadow: 0 0 0 6px rgba(52, 211, 153, 0); }
            100% { box-shadow: 0 0 0 0 rgba(52, 211, 153, 0); }
        }

        /* Analytics Workspace Layout */
        .analytics-container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 1.75rem 1.5rem;
        }

        /* Stat Cards */
        .stat-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 1.25rem 1.5rem;
            box-shadow: var(--shadow-subtle);
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-card);
        }

        .stat-card-glow {
            position: absolute;
            top: 0;
            right: 0;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            filter: blur(25px);
            opacity: 0.15;
            pointer-events: none;
        }

        .stat-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.35rem;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.1;
            margin-bottom: 0.35rem;
        }

        .stat-subtext {
            font-size: 0.78rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Chart Cards */
        .chart-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-subtle);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .chart-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-subtle);
        }

        .chart-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-subtitle {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin: 0;
        }

        .chart-body-wrap {
            position: relative;
            flex-grow: 1;
            min-height: 290px;
        }

        /* Interactive Playground Card */
        .playground-card {
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-lg);
            padding: 1.75rem;
            color: #F8FAFC;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2);
        }

        .playground-slider-val {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            color: #38BDF8;
        }

        .formula-box {
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(56, 189, 248, 0.25);
            border-radius: var(--radius-md);
            padding: 1rem 1.25rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
        }

        /* Table Styling */
        .data-table-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-subtle);
        }

        .custom-table {
            margin-bottom: 0;
            font-size: 0.85rem;
        }

        .custom-table th {
            background: #F1F5F9;
            color: #475569;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--border-subtle);
        }

        .custom-table td {
            padding: 0.85rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #F1F5F9;
        }

        .custom-table tr:hover {
            background-color: #F8FAFC;
        }

        .entitlement-badge-unlocked {
            background-color: #ECFDF5;
            color: #065F46;
            border: 1px solid #A7F3D0;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .entitlement-badge-locked {
            background-color: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .entitlement-badge-public {
            background-color: #EFF6FF;
            color: #1E40AF;
            border: 1px solid #BFDBFE;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .cagr-badge {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            background: #F0FDF4;
            color: #166534;
            border: 1px solid #BBF7D0;
            padding: 2px 7px;
            border-radius: var(--radius-sm);
            font-size: 0.78rem;
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
                    <span>Market Research Intelligence</span>
                </div>
            </div>
        </a>

        <!-- CENTER: View Navigation Switcher -->
        <div class="nav-view-switcher">
            <a href="<?php echo site_url('chat'); ?>" class="nav-tab-btn" title="AI Research Copilot">
                <i class="bi bi-chat-dots"></i>
                <span>Copilot Chat</span>
            </a>
            <a href="<?php echo site_url('chat/analytics'); ?>" class="nav-tab-btn active" title="Market Research Analytical Dashboard">
                <i class="bi bi-bar-chart-line-fill"></i>
                <span>Analytical Dashboard</span>
                <span class="badge-pill-new">LIVE</span>
            </a>
        </div>

        <div class="nav-actions">
            <!-- Active Client Switcher -->
            <form action="<?php echo site_url('chat/switch_client'); ?>" method="POST" class="m-0">
                <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                <input type="hidden" name="redirect_to" value="/chat/analytics">
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

            <div class="status-pill d-none d-sm-inline-flex">
                <span class="status-pulse-dot"></span>
                <span>Copilot Online</span>
            </div>
        </div>
    </header>

    <!-- MAIN ANALYTICAL WORKSPACE -->
    <main class="analytics-container">

        <!-- Page Intro Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h3 class="fw-bold mb-1" style="font-size: 1.45rem; letter-spacing: -0.02em;">
                    <i class="bi bi-graph-up-arrow text-primary me-2"></i>Market Research Analytics &amp; Quantitative Intelligence
                </h3>
                <p class="text-secondary mb-0" style="font-size: 0.85rem;">
                    Multi-sector quantitative metrics, compound annual growth rate projections (2025–2030), and entitlement boundaries for <strong><?php echo html_escape($client_id . ' (' . $clients[$client_id] . ')'); ?></strong>.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?php echo site_url('chat'); ?>" class="btn btn-primary btn-sm px-3 py-2 fw-semibold shadow-sm" style="border-radius: var(--radius-md);">
                    <i class="bi bi-chat-text-fill me-1"></i>Ask Copilot Question
                </a>
            </div>
        </div>

        <?php
            // Calculate aggregate KPI metrics dynamically from DB
            $total_base_market = 0;
            $total_projected_2030 = 0;
            $cagr_sum = 0;
            $cagr_count = 0;
            $apac_shares = [];
            $entitled_count = 0;
            $distinct_reports = [];

            foreach ($metrics as $m) {
                if ($m['unit'] === 'USD Billion') {
                    $total_base_market += (float)$m['metric_value'];
                    if (!empty($m['projected_2030'])) {
                        $total_projected_2030 += (float)$m['projected_2030'];
                    }
                    if (!empty($m['cagr_percent'])) {
                        $cagr_sum += (float)$m['cagr_percent'];
                        $cagr_count++;
                    }
                }
                if ($m['unit'] === 'Percent' && stripos($m['metric_name'], 'APAC') !== false) {
                    $apac_shares[] = (float)$m['metric_value'];
                }
                if (!isset($distinct_reports[$m['report_id']])) {
                    $distinct_reports[$m['report_id']] = $m['is_entitled'];
                    if ($m['is_entitled']) {
                        $entitled_count++;
                    }
                }
            }

            $avg_cagr = $cagr_count > 0 ? round($cagr_sum / $cagr_count, 1) : 11.0;
            $avg_apac = !empty($apac_shares) ? round(array_sum($apac_shares) / count($apac_shares), 1) : 35.7;
        ?>

        <!-- 1. KEY KPI STAT CARDS -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-card-glow" style="background-color: #2563EB;"></div>
                    <div class="stat-title">Total Monitored Market</div>
                    <div class="stat-value text-primary">$<?php echo number_format($total_base_market, 2); ?>B</div>
                    <div class="stat-subtext">
                        <i class="bi bi-clock-history text-primary"></i> 2024 Base Year Valuation
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-card-glow" style="background-color: #059669;"></div>
                    <div class="stat-title">Average Sector CAGR</div>
                    <div class="stat-value text-success"><?php echo $avg_cagr; ?>%</div>
                    <div class="stat-subtext">
                        <i class="bi bi-arrow-up-right text-success"></i> 2025–2030 Compound Velocity
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-card-glow" style="background-color: #06B6D4;"></div>
                    <div class="stat-title">2030 Projected Valuation</div>
                    <div class="stat-value" style="color: #0284C7;">$<?php echo number_format($total_projected_2030, 2); ?>B</div>
                    <div class="stat-subtext">
                        <i class="bi bi-calculator text-info"></i> Deterministic \(Start \times (1+r)^6\)
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-card-glow" style="background-color: #8B5CF6;"></div>
                    <div class="stat-title">Licensed Report Access</div>
                    <div class="stat-value text-purple" style="color: #7C3AED;"><?php echo $entitled_count; ?> of <?php echo count($distinct_reports); ?> Reports</div>
                    <div class="stat-subtext">
                        <i class="bi bi-shield-check text-purple"></i> Active Context: <?php echo html_escape($client_id); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. MAIN CHARTS ROW 1 (Grouped Bar Market Size & CAGR Ranking) -->
        <div class="row g-4 mb-4">
            <!-- Plot 1: 2024 Base vs 2030 Projected Market Size -->
            <div class="col-lg-8">
                <div class="chart-card">
                    <div class="chart-card-header">
                        <div>
                            <h5 class="chart-title"><i class="bi bi-bar-chart-fill text-primary"></i>Market Valuation: 2024 Base vs. 2030 Forecast</h5>
                            <p class="chart-subtitle">Direct comparison between 2024 base values and 2030 projected valuations (USD Billion) across research sectors</p>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">USD Billion</span>
                    </div>
                    <div class="chart-body-wrap">
                        <canvas id="marketSizeChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Plot 2: CAGR Velocity Ranking -->
            <div class="col-lg-4">
                <div class="chart-card">
                    <div class="chart-card-header">
                        <div>
                            <h5 class="chart-title"><i class="bi bi-speedometer2 text-success"></i>CAGR Growth Velocity</h5>
                            <p class="chart-subtitle">Compound Annual Growth Rate ranking (2025–2030)</p>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">% Annual</span>
                    </div>
                    <div class="chart-body-wrap">
                        <canvas id="cagrRankingChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. MAIN CHARTS ROW 2 (Regional APAC Share & Multi-Year Projection Curves) -->
        <div class="row g-4 mb-4">
            <!-- Plot 3: Multi-Year Trajectory Line Curve (2024-2030) -->
            <div class="col-lg-7">
                <div class="chart-card">
                    <div class="chart-card-header">
                        <div>
                            <h5 class="chart-title"><i class="bi bi-graph-up text-info"></i>Multi-Year Growth Trajectory (2024–2030)</h5>
                            <p class="chart-subtitle">Annual compounding curves derived deterministically from research baseline metrics</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-light text-secondary border">Interactive Curve</span>
                        </div>
                    </div>
                    <div class="chart-body-wrap">
                        <canvas id="trajectoryLineChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Plot 4: APAC Regional Share Distribution -->
            <div class="col-lg-5">
                <div class="chart-card">
                    <div class="chart-card-header">
                        <div>
                            <h5 class="chart-title"><i class="bi bi-pie-chart-fill text-warning"></i>APAC Regional Market Share</h5>
                            <p class="chart-subtitle">Asia-Pacific regional penetration percentage by covered sector</p>
                        </div>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">% Share</span>
                    </div>
                    <div class="chart-body-wrap">
                        <canvas id="apacShareChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. DETERMINISTIC CALCULATION PLAYGROUND -->
        <div class="playground-card mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="p-2 rounded bg-primary bg-opacity-25 border border-primary text-info">
                            <i class="bi bi-cpu fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-white">Deterministic Arithmetic Engine Playground</h5>
                            <p class="text-white-50 mb-0" style="font-size: 0.8rem;">Test live compound projection formulas matching Atlas agent's Python calculation node</p>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small mb-1">Select Sector Report Base:</label>
                            <select id="calcSectorSelect" class="form-select form-select-sm bg-dark text-white border-secondary" onchange="runLiveCalculation()">
                                <?php foreach ($metrics as $m): ?>
                                    <?php if ($m['unit'] === 'USD Billion' && !empty($m['cagr_percent'])): ?>
                                        <option value="<?php echo html_escape($m['metric_value']); ?>" 
                                                data-cagr="<?php echo html_escape($m['cagr_percent']); ?>"
                                                data-name="<?php echo html_escape($m['metric_name']); ?>"
                                                data-report="<?php echo html_escape($m['report_id']); ?>">
                                            <?php echo html_escape($m['report_id'] . ': ' . $m['metric_name'] . ' ($' . $m['metric_value'] . 'B)'); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white-50 small mb-1">Compounding Years (n): <span id="sliderYearsVal" class="playground-slider-val">6</span> Years (2024➔2030)</label>
                            <input type="range" class="form-range" id="sliderYears" min="1" max="10" value="6" oninput="runLiveCalculation()">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white-50 small mb-1">Custom CAGR Rate: <span id="sliderCagrVal" class="playground-slider-val">11.2%</span></label>
                            <input type="range" class="form-range" id="sliderCagr" min="1.0" max="25.0" step="0.1" value="11.2" oninput="runLiveCalculation()">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white-50 small mb-1">Regional Share Formulation:</label>
                            <select id="calcShareSelect" class="form-select form-select-sm bg-dark text-white border-secondary" onchange="runLiveCalculation()">
                                <option value="0.38">R-101 APAC Share (38%)</option>
                                <option value="0.41">R-102 Hardware Share (41%)</option>
                                <option value="0.45">R-103 Water Purifier Share (45%)</option>
                                <option value="0.34">R-107 IIoT APAC Share (34%)</option>
                                <option value="1.00">100% Global Total</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="formula-box">
                        <div class="text-white-50 mb-2 small text-uppercase fw-semibold" style="letter-spacing: 0.05em;">
                            <i class="bi bi-terminal me-1 text-info"></i>Execution Output
                        </div>
                        <div class="d-flex justify-content-between align-items-baseline mb-2">
                            <span class="text-white-50">Projected Global Market:</span>
                            <span id="calcGlobalOutput" class="fs-4 fw-bold text-success">$7.98 Billion</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-baseline mb-2">
                            <span class="text-white-50">Projected Regional Share:</span>
                            <span id="calcRegionalOutput" class="fs-5 fw-bold text-info">$3.03 Billion</span>
                        </div>
                        <hr class="border-secondary my-2">
                        <div class="text-white-50 small" id="calcFormulaStep">
                            <code>Global = 4.21 × (1 + 0.112)^6 = $7.98B</code>
                        </div>
                        <div class="text-warning small mt-2" style="font-size: 0.76rem;">
                            <i class="bi bi-info-circle me-1"></i>Base-Year Ambiguity: Compounding from 2024 represents 6 intervals ($7.98B); compounding from 2025 represents 5 intervals ($7.16B).
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. STRUCTURED METRICS DATA TABLE WITH ENTITLEMENT ACCESS -->
        <div class="data-table-card mb-4">
            <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="fw-bold mb-0" style="font-size: 1rem;"><i class="bi bi-table text-primary me-2"></i>Structured Market Metrics Corpus</h5>
                    <p class="text-secondary small mb-0">Verified quantitative parameters indexed into MySQL and entitlement-filtered for <strong>Client <?php echo html_escape($client_id); ?></strong></p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <input type="text" id="tableSearch" class="form-control form-control-sm" placeholder="Filter metrics or sectors..." onkeyup="filterMetricsTable()" style="max-width: 240px;">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table custom-table" id="metricsTable">
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Market Metric &amp; Scope</th>
                            <th>Sector</th>
                            <th>2024 Base</th>
                            <th>CAGR (2025-30)</th>
                            <th>2030 Projected</th>
                            <th>APAC Share</th>
                            <th>License Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            // Pair USD Billion metrics with their APAC regional share
                            $paired_data = [];
                            foreach ($metrics as $m) {
                                if ($m['unit'] === 'USD Billion') {
                                    $paired_data[$m['report_id']] = $m;
                                }
                            }
                            foreach ($metrics as $m) {
                                if ($m['unit'] === 'Percent' && isset($paired_data[$m['report_id']])) {
                                    $paired_data[$m['report_id']]['apac_share'] = $m['metric_value'];
                                }
                            }

                            foreach ($paired_data as $row): 
                                $is_public = ($row['access_type'] === 'public_excerpt' || $row['report_id'] === 'R-102');
                        ?>
                            <tr class="metric-row">
                                <td class="fw-bold">
                                    <span class="badge bg-dark text-white px-2 py-1" style="font-family: 'JetBrains Mono', monospace;">
                                        <?php echo html_escape($row['report_id']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?php echo html_escape($row['title']); ?></div>
                                    <div class="text-muted small" style="font-size: 0.75rem;"><?php echo html_escape($row['market_scope']); ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border"><?php echo html_escape($row['sector']); ?></span>
                                </td>
                                <td class="fw-bold text-primary">
                                    $<?php echo number_format($row['metric_value'], 2); ?>B
                                </td>
                                <td>
                                    <?php if (!empty($row['cagr_percent'])): ?>
                                        <span class="cagr-badge">+<?php echo number_format($row['cagr_percent'], 1); ?>%</span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold text-success">
                                    <?php if (!empty($row['projected_2030'])): ?>
                                        $<?php echo number_format($row['projected_2030'], 2); ?>B
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['apac_share'])): ?>
                                        <span class="fw-semibold text-warning"><?php echo number_format($row['apac_share'], 1); ?>%</span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['is_entitled'] && !$is_public): ?>
                                        <span class="entitlement-badge-unlocked">
                                            <i class="bi bi-unlock-fill"></i> Licensed (Full)
                                        </span>
                                    <?php elseif ($is_public): ?>
                                        <span class="entitlement-badge-public">
                                            <i class="bi bi-file-earmark-text"></i> Public Excerpt
                                        </span>
                                    <?php else: ?>
                                        <span class="entitlement-badge-locked" title="Report not licensed for Client <?php echo html_escape($client_id); ?>">
                                            <i class="bi bi-lock-fill"></i> Upgrade Needed
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <form action="<?php echo site_url('chat/send'); ?>" method="POST" class="d-inline">
                                        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                                        <input type="hidden" name="question" value="What is the market sizing and 2030 growth projection in <?php echo html_escape($row['report_id']); ?>?">
                                        <button type="submit" class="btn btn-outline-primary btn-sm px-2 py-1" style="font-size: 0.75rem;" title="Ask Copilot about this report">
                                            <i class="bi bi-chat-quote-fill me-1"></i>Query
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- Chart.js Initializations & Data Binding -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Palette definitions
            const colorPrimary = '#2563EB';
            const colorSuccess = '#059669';
            const colorCyan = '#06B6D4';
            const colorPurple = '#8B5CF6';
            const colorWarning = '#F59E0B';
            const colorSlate = '#64748B';

            // Raw data from PHP
            const chartData = [
                <?php foreach ($paired_data as $m): ?>
                {
                    report_id: <?php echo json_encode($m['report_id']); ?>,
                    title: <?php echo json_encode($m['title']); ?>,
                    name: <?php echo json_encode($m['metric_name']); ?>,
                    sector: <?php echo json_encode($m['sector']); ?>,
                    base_2024: <?php echo (float)$m['metric_value']; ?>,
                    cagr: <?php echo !empty($m['cagr_percent']) ? (float)$m['cagr_percent'] : 0; ?>,
                    projected_2030: <?php echo !empty($m['projected_2030']) ? (float)$m['projected_2030'] : (float)$m['metric_value']; ?>,
                    apac_share: <?php echo !empty($m['apac_share']) ? (float)$m['apac_share'] : 0; ?>,
                    is_entitled: <?php echo $m['is_entitled'] ? 'true' : 'false'; ?>
                },
                <?php endforeach; ?>
            ];

            // 1. Plot 1: 2024 Base vs 2030 Forecast (Grouped Bar)
            const ctxMarket = document.getElementById('marketSizeChart').getContext('2d');
            new Chart(ctxMarket, {
                type: 'bar',
                data: {
                    labels: chartData.map(d => d.report_id + ' (' + d.title.split(' ')[0] + ' ' + (d.title.split(' ')[1] || '') + ')'),
                    datasets: [
                        {
                            label: '2024 Base Valuation ($B)',
                            data: chartData.map(d => d.base_2024),
                            backgroundColor: 'rgba(37, 99, 235, 0.85)',
                            borderColor: '#2563EB',
                            borderWidth: 1,
                            borderRadius: 6
                        },
                        {
                            label: '2030 Compounded Forecast ($B)',
                            data: chartData.map(d => d.projected_2030),
                            backgroundColor: 'rgba(6, 182, 212, 0.85)',
                            borderColor: '#06B6D4',
                            borderWidth: 1,
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` ${ctx.dataset.label}: USD ${ctx.parsed.y.toFixed(2)} Billion`
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'USD Billion ($)' },
                            grid: { color: 'rgba(226, 232, 240, 0.6)' }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });

            // 2. Plot 2: CAGR Velocity Ranking (Horizontal Bar)
            const sortedByCagr = [...chartData].filter(d => d.cagr > 0).sort((a, b) => b.cagr - a.cagr);
            const ctxCagr = document.getElementById('cagrRankingChart').getContext('2d');
            new Chart(ctxCagr, {
                type: 'bar',
                data: {
                    labels: sortedByCagr.map(d => d.report_id),
                    datasets: [{
                        label: 'CAGR % (2025-2030)',
                        data: sortedByCagr.map(d => d.cagr),
                        backgroundColor: sortedByCagr.map(d => d.cagr >= 12 ? 'rgba(5, 150, 105, 0.85)' : (d.cagr >= 9 ? 'rgba(6, 182, 212, 0.85)' : 'rgba(37, 99, 235, 0.85)')),
                        borderColor: sortedByCagr.map(d => d.cagr >= 12 ? '#059669' : (d.cagr >= 9 ? '#06B6D4' : '#2563EB')),
                        borderWidth: 1,
                        borderRadius: 6
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` CAGR: ${ctx.parsed.x.toFixed(1)}% per annum`
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: { display: true, text: 'Annual Growth Rate (%)' },
                            grid: { color: 'rgba(226, 232, 240, 0.6)' }
                        },
                        y: {
                            grid: { display: false }
                        }
                    }
                }
            });

            // 3. Plot 3: Multi-Year Growth Trajectory (Line Plot)
            const years = [2024, 2025, 2026, 2027, 2028, 2029, 2030];
            const trajectoryColors = ['#2563EB', '#059669', '#06B6D4', '#8B5CF6', '#F59E0B', '#DC2626', '#64748B'];

            const trajectoryDatasets = chartData.map((d, idx) => {
                const values = years.map(yr => {
                    const n = yr - 2024;
                    const r = d.cagr / 100;
                    return parseFloat((d.base_2024 * Math.pow(1 + r, n)).toFixed(2));
                });
                return {
                    label: d.report_id + ': ' + d.name.replace('Global ', '').replace(' Market Size 2024', ''),
                    data: values,
                    borderColor: trajectoryColors[idx % trajectoryColors.length],
                    backgroundColor: trajectoryColors[idx % trajectoryColors.length],
                    tension: 0.3,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: false
                };
            });

            const ctxLine = document.getElementById('trajectoryLineChart').getContext('2d');
            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: years,
                    datasets: trajectoryDatasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` ${ctx.dataset.label}: USD ${ctx.parsed.y.toFixed(2)}B`
                            }
                        }
                    },
                    scales: {
                        y: {
                            title: { display: true, text: 'Valuation ($B USD)' },
                            grid: { color: 'rgba(226, 232, 240, 0.6)' }
                        },
                        x: {
                            grid: { color: 'rgba(226, 232, 240, 0.4)' }
                        }
                    }
                }
            });

            // 4. Plot 4: APAC Regional Share Distribution (Doughnut)
            const apacFiltered = chartData.filter(d => d.apac_share > 0);
            const ctxApac = document.getElementById('apacShareChart').getContext('2d');
            new Chart(ctxApac, {
                type: 'doughnut',
                data: {
                    labels: apacFiltered.map(d => d.report_id + ' (' + d.apac_share + '%)'),
                    datasets: [{
                        data: apacFiltered.map(d => d.apac_share),
                        backgroundColor: [
                            'rgba(37, 99, 235, 0.85)',
                            'rgba(6, 182, 212, 0.85)',
                            'rgba(5, 150, 105, 0.85)',
                            'rgba(245, 158, 11, 0.85)',
                            'rgba(139, 92, 246, 0.85)',
                            'rgba(220, 38, 38, 0.85)'
                        ],
                        borderWidth: 2,
                        borderColor: '#FFFFFF'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` ${ctx.label}: APAC holds ${ctx.parsed}% share`
                            }
                        }
                    },
                    cutout: '62%'
                }
            });

            // Initialize playground calculation
            runLiveCalculation();
        });

        // Interactive Playground Calculation Engine
        function runLiveCalculation() {
            const selectEl = document.getElementById('calcSectorSelect');
            const selectedOpt = selectEl.options[selectEl.selectedIndex];
            const baseVal = parseFloat(selectedOpt.value) || 4.21;
            const defaultCagr = parseFloat(selectedOpt.dataset.cagr) || 11.2;

            const sliderCagr = parseFloat(document.getElementById('sliderCagr').value);
            const sliderYears = parseInt(document.getElementById('sliderYears').value);
            const shareRate = parseFloat(document.getElementById('calcShareSelect').value);

            document.getElementById('sliderCagrVal').innerText = sliderCagr.toFixed(1) + '%';
            document.getElementById('sliderYearsVal').innerText = sliderYears;

            const r = sliderCagr / 100;
            const globalCompounded = baseVal * Math.pow(1 + r, sliderYears);
            const regionalVal = globalCompounded * shareRate;

            document.getElementById('calcGlobalOutput').innerText = '$' + globalCompounded.toFixed(2) + ' Billion';
            document.getElementById('calcRegionalOutput').innerText = '$' + regionalVal.toFixed(2) + ' Billion';

            document.getElementById('calcFormulaStep').innerHTML = 
                `<code>Valuation = ${baseVal.toFixed(2)} × (1 + ${(r).toFixed(3)})^${sliderYears} = $${globalCompounded.toFixed(2)}B</code>` +
                (shareRate < 1.0 ? ` &nbsp;|&nbsp; <code>Regional (${(shareRate * 100).toFixed(0)}%) = $${regionalVal.toFixed(2)}B</code>` : '');
        }

        // Search/Filter table rows
        function filterMetricsTable() {
            const query = document.getElementById('tableSearch').value.toLowerCase();
            const rows = document.querySelectorAll('.metric-row');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
