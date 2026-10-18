<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Atlas — Market Research Report Copilot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --atlas-primary: #1e3a8a;
            --atlas-accent: #0284c7;
            --atlas-bg: #f8fafc;
            --atlas-chat-user: #e0f2fe;
            --atlas-chat-ai: #ffffff;
        }
        body {
            background-color: var(--atlas-bg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
        }
        .header-bar {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            color: #ffffff;
            padding: 1rem 1.5rem;
            border-bottom: 2px solid #38bdf8;
        }
        .chat-container {
            height: calc(100vh - 220px);
            overflow-y: auto;
            padding: 1.5rem;
            background: #ffffff;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .message-box {
            margin-bottom: 1.25rem;
            max-width: 85%;
        }
        .message-user {
            margin-left: auto;
            background-color: var(--atlas-chat-user);
            border: 1px solid #bae6fd;
            border-radius: 1rem 1rem 0.2rem 1rem;
            padding: 0.85rem 1.25rem;
        }
        .message-assistant {
            margin-right: auto;
            background-color: var(--atlas-chat-ai);
            border: 1px solid #e2e8f0;
            border-radius: 1rem 1rem 1rem 0.2rem;
            padding: 1rem 1.25rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .citation-badge {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-left: 3px solid #0284c7;
            padding: 0.4rem 0.75rem;
            border-radius: 0.35rem;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }
        .conflict-alert {
            background-color: #fffbeb;
            border: 1px solid #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 0.75rem;
            border-radius: 0.4rem;
            font-size: 0.88rem;
            margin-top: 0.75rem;
            color: #92400e;
        }
        .calc-card {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-left: 4px solid #16a34a;
            padding: 0.75rem;
            border-radius: 0.4rem;
            font-size: 0.88rem;
            margin-top: 0.75rem;
            color: #166534;
        }
        .escalation-badge {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-left: 4px solid #ef4444;
            padding: 0.75rem;
            border-radius: 0.4rem;
            font-size: 0.88rem;
            margin-top: 0.75rem;
            color: #991b1b;
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <header class="header-bar d-flex justify-content-between align-items-center shadow-sm">
        <div class="d-flex align-items-center gap-3">
            <span class="fs-4 fw-bold"><i class="bi bi-compass text-info me-2"></i>Project Atlas</span>
            <span class="badge bg-secondary">Market Research Copilot</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <form action="<?php echo site_url('chat/switch_client'); ?>" method="POST" class="d-flex align-items-center gap-2">
                <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                <label class="text-white-50 small mb-0 fw-semibold">Active Client:</label>
                <select name="client_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($clients as $cid => $cname): ?>
                        <option value="<?php echo html_escape($cid); ?>" <?php echo $cid === $client_id ? 'selected' : ''; ?>>
                            <?php echo html_escape($cid . ' — ' . $cname); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <a href="<?php echo site_url('chat/clear'); ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
        </div>
    </header>

    <div class="container-fluid py-3 px-4">
        <div class="row g-3">
            
            <!-- Chat Column -->
            <div class="col-lg-8">
                <div class="chat-container d-flex flex-column" id="chatStream">
                    <?php if (empty($messages)): ?>
                        <div class="text-center my-auto text-muted">
                            <i class="bi bi-chat-quote display-4 text-secondary mb-2"></i>
                            <h5 class="fw-semibold">Welcome to Project Atlas Copilot</h5>
                            <p class="small text-secondary" style="max-width: 550px; margin: 0 auto;">
                                Ask market research questions grounded in your licensed library reports. Try asking:
                            </p>
                            <div class="d-flex flex-wrap gap-2 justify-content-center mt-3">
                                <button class="btn btn-sm btn-outline-primary" onclick="setQuestion('What was the global smart water meter market size in 2024?')">Smart Water Meter 2024 Size</button>
                                <button class="btn btn-sm btn-outline-primary" onclick="setQuestion('Why do two reports provide different market-size figures?')">Compare R-101 vs R-102</button>
                                <button class="btn btn-sm btn-outline-primary" onclick="setQuestion('What might the APAC market be worth in 2030?')">APAC 2030 Projection</button>
                                <button class="btn btn-sm btn-outline-primary" onclick="setQuestion('Which sector has the most reports in the library?')">Sector Report Count</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="message-box <?php echo $msg['role'] === 'user' ? 'message-user' : 'message-assistant'; ?>">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold small text-muted">
                                        <?php if ($msg['role'] === 'user'): ?>
                                            <i class="bi bi-person-circle"></i> You (<?php echo html_escape($client_id); ?>)
                                        <?php else: ?>
                                            <i class="bi bi-robot text-primary"></i> Atlas Copilot
                                            <?php if (isset($msg['confidence'])): ?>
                                                <span class="badge bg-light text-dark border ms-2">Confidence: <?php echo round($msg['confidence'] * 100); ?>%</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </span>
                                    <span class="text-muted small"><?php echo html_escape($msg['time']); ?></span>
                                </div>

                                <div class="message-text">
                                    <?php echo nl2br(html_escape($msg['content'])); ?>
                                </div>

                                <!-- Source Conflicts -->
                                <?php if (!empty($msg['conflicts'])): ?>
                                    <div class="conflict-alert">
                                        <strong><i class="bi bi-exclamation-triangle-fill"></i> Source Discrepancy Detected:</strong>
                                        <ul class="mb-0 mt-1 ps-3">
                                            <?php foreach ($msg['conflicts'] as $conf): ?>
                                                <li><?php echo html_escape($conf); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <!-- Derived Calculations -->
                                <?php if (!empty($msg['derived_values'])): ?>
                                    <?php foreach ($msg['derived_values'] as $calc): ?>
                                        <div class="calc-card">
                                            <strong><i class="bi bi-calculator"></i> Derived Valuation: <?php echo html_escape($calc['metric']); ?></strong>
                                            <div class="small mt-1"><strong>Formula:</strong> <code><?php echo html_escape($calc['formula']); ?></code></div>
                                            <div class="small"><strong>Result:</strong> <b><?php echo html_escape($calc['result']); ?></b></div>
                                            <?php if (!empty($calc['assumptions'])): ?>
                                                <div class="small text-muted mt-1"><strong>Assumptions:</strong> <?php echo html_escape(implode(' | ', $calc['assumptions'])); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <!-- Escalation Status -->
                                <?php if (!empty($msg['escalated'])): ?>
                                    <div class="escalation-badge">
                                        <i class="bi bi-headset"></i> <strong>Escalated to Research Analyst:</strong> 
                                        Ticket ID: <code><?php echo html_escape($msg['ticket_id'] ?: 'ESC-PENDING'); ?></code>. An analyst is currently reviewing this query.
                                    </div>
                                <?php endif; ?>

                                <!-- Citations -->
                                <?php if (!empty($msg['citations'])): ?>
                                    <div class="mt-2 pt-2 border-top">
                                        <div class="small fw-bold text-secondary mb-1"><i class="bi bi-journal-bookmark-fill text-info"></i> Evidence Citations:</div>
                                        <div class="d-flex flex-column gap-1">
                                            <?php foreach ($msg['citations'] as $cit): ?>
                                                <div class="citation-badge">
                                                    <strong>[<?php echo html_escape($cit['report_id']); ?>, Page <?php echo (int)$cit['page']; ?>]</strong>
                                                    <span><?php echo html_escape($cit['claim']); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Thumbs Up / Down Feedback -->
                                <?php if ($msg['role'] === 'assistant'): ?>
                                    <?php $f_id = !empty($msg['id']) ? (int)$msg['id'] : 1; ?>
                                    <div class="feedback-actions mt-3 pt-2 border-top d-flex align-items-center gap-2" id="feedback-block-<?php echo $f_id; ?>">
                                        <span class="small text-muted fw-semibold me-1"><i class="bi bi-chat-heart text-primary"></i> Feedback:</span>
                                        <button type="button" class="btn btn-sm btn-outline-success px-3 py-1 fw-medium" onclick="submitFeedback(<?php echo $f_id; ?>, 1)">
                                            👍 Thumbs Up
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger px-3 py-1 fw-medium" onclick="submitFeedback(<?php echo $f_id; ?>, -1)">
                                            👎 Thumbs Down
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>

                <!-- Input Box -->
                <form action="<?php echo site_url('chat/send'); ?>" method="POST" class="mt-3">
                    <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                    <div class="input-group input-group-lg shadow-sm">
                        <input type="text" id="questionInput" name="question" class="form-control" placeholder="Ask a question about your entitled market research reports..." required autofocus autocomplete="off">
                        <button class="btn btn-primary px-4" type="submit"><i class="bi bi-send-fill me-1"></i> Ask</button>
                    </div>
                </form>
            </div>

            <!-- Sidebar Info Column -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-white fw-bold py-3 border-bottom">
                        <i class="bi bi-shield-check text-success me-2"></i>Entitlement Context
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-2">Authenticated Organization:</p>
                        <h6 class="fw-bold mb-1"><?php echo html_escape($client_id); ?></h6>
                        <div class="small mb-3">
                            <?php if ($client_id === 'C-01'): ?>
                                <span class="badge bg-success">Full Access: R-101 (Utilities)</span>
                                <span class="badge bg-success">Full Access: R-103 (Consumer Durables)</span>
                                <span class="badge bg-info text-dark">Public Excerpt: R-102</span>
                            <?php elseif ($client_id === 'C-02'): ?>
                                <span class="badge bg-danger">Restricted: R-101 (Not Entitled)</span>
                                <span class="badge bg-success">Full Access: R-103 (Consumer Durables)</span>
                                <span class="badge bg-info text-dark">Public Excerpt: R-102</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Public Excerpts Only</span>
                            <?php endif; ?>
                        </div>
                        <div class="alert alert-light border small text-secondary mb-0">
                            <strong>Security Policy:</strong> Strict pre-retrieval authorization prevents unauthorized report data from reaching the model context.
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold py-3 border-bottom d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-ticket-detailed text-warning me-2"></i>Analyst Tickets</span>
                        <span class="badge bg-warning text-dark"><?php echo count($escalations); ?></span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($escalations)): ?>
                            <div class="p-3 text-center text-muted small">No active escalations recorded for this client.</div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach (array_slice($escalations, 0, 5) as $esc): ?>
                                    <div class="list-group-item p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="badge bg-danger"><?php echo html_escape($esc['status']); ?></span>
                                            <small class="text-muted"><?php echo html_escape($esc['ticket_id']); ?></small>
                                        </div>
                                        <div class="small fw-semibold text-truncate mb-1"><?php echo html_escape($esc['question']); ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?php echo html_escape($esc['reason']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function setQuestion(text) {
            document.getElementById('questionInput').value = text;
            document.getElementById('questionInput').focus();
        }
        // Auto scroll chat to bottom
        const stream = document.getElementById('chatStream');
        if (stream) {
            stream.scrollTop = stream.scrollHeight;
        }

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
                    block.innerHTML = '<span class="badge bg-success text-white py-1 px-2 small"><i class="bi bi-check-circle me-1"></i> Feedback recorded</span>';
                }
            })
            .catch(err => {
                console.error('Feedback submission error:', err);
            });
        }
    </script>

</body>
</html>
