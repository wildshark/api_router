<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Administrative Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --primary:    #FF6B6B;
            --primary-d:  #dd4d51;
            --accent:     #00FFFF;
            --accent-d:   #00999b;
            --text:       #FFFFFF;
            --text-m:     #b0b8c1;
            --bg:         #0b0d12;
            --bg-card:    #141720;
            --bg-elem:    #1e2130;
            --border:     rgba(255,255,255,0.07);
            --glow-cyan:  0 0 18px rgba(0,255,255,0.25);
            --glow-red:   0 0 18px rgba(255,107,107,0.25);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family:'Inter',sans-serif; background:var(--bg); color:var(--text); overflow-x:hidden; }

        #preloader { position:fixed; inset:0; background:var(--bg); z-index:9999; display:flex; align-items:center; justify-content:center; transition:opacity .5s; }
        .spinner-ring { width:58px; height:58px; border:3px solid var(--bg-elem); border-top-color:var(--accent); border-radius:50%; animation:spin 1s linear infinite; }
        @keyframes spin { to { transform:rotate(360deg); } }

        #refresh-bar { height:2px; background:var(--accent); position:fixed; top:0; left:0; right:0; z-index:9998; transform-origin:left; }

        .sidebar { width:240px; min-height:100vh; background:var(--bg-card); border-right:1px solid var(--border); position:fixed; top:0; left:0; display:flex; flex-direction:column; z-index:200; }
        .sidebar-brand { padding:24px 20px 20px; font-size:1.1rem; font-weight:700; color:var(--primary); border-bottom:1px solid var(--border); display:flex; align-items:center; gap:10px; }
        .sidebar-brand small { display:block; font-size:.62rem; font-weight:400; color:var(--text-m); letter-spacing:.06em; text-transform:uppercase; }
        .nav-lnk { display:flex; align-items:center; gap:12px; padding:13px 20px; color:var(--text-m); font-weight:500; font-size:.88rem; border-left:3px solid transparent; transition:all .2s; cursor:pointer; text-decoration:none; }
        .nav-lnk:hover { color:var(--text); background:var(--bg-elem); }
        .nav-lnk.active { color:var(--accent); border-left-color:var(--accent); background:var(--bg-elem); }
        .nav-lnk i { width:18px; text-align:center; }
        .sidebar-footer { margin-top:auto; padding:16px 20px; border-top:1px solid var(--border); }

        .live-badge { display:inline-flex; align-items:center; gap:6px; background:rgba(0,255,120,.08); border:1px solid rgba(0,255,120,.2); color:#00ff78; font-size:.7rem; font-weight:600; padding:4px 10px; border-radius:20px; letter-spacing:.06em; text-transform:uppercase; }
        .live-dot { width:7px; height:7px; background:#00ff78; border-radius:50%; animation:pulse-dot 1.4s ease infinite; }
        @keyframes pulse-dot { 0%,100%{box-shadow:0 0 0 0 rgba(0,255,120,.5);} 50%{box-shadow:0 0 0 5px rgba(0,255,120,0);} }

        .main-content { margin-left:240px; padding:28px 32px; min-height:100vh; }
        .topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:28px; }
        .topbar h1 { font-size:1.4rem; font-weight:700; }
        .topbar h1 span { color:var(--primary); }

        .stats-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:18px; margin-bottom:28px; }
        .stat-card { background:var(--bg-card); border:1px solid var(--border); border-radius:14px; padding:20px 22px; position:relative; overflow:hidden; transition:transform .2s,box-shadow .2s; }
        .stat-card:hover { transform:translateY(-3px); }
        .stat-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; border-radius:14px 14px 0 0; }
        .stat-card.cyan::before  { background:var(--accent); }
        .stat-card.red::before   { background:var(--primary); }
        .stat-card.grn::before   { background:#00d68f; }
        .stat-card.pur::before   { background:#a855f7; }
        .stat-card.org::before   { background:#f97316; }
        .stat-label { font-size:.7rem; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:var(--text-m); margin-bottom:10px; }
        .stat-value { font-size:1.85rem; font-weight:700; line-height:1; margin-bottom:6px; }
        .cv { color:var(--accent); } .rv { color:var(--primary); } .gv { color:#00d68f; } .pv { color:#a855f7; } .ov { color:#f97316; }
        .stat-icon { position:absolute; right:18px; top:50%; transform:translateY(-50%); font-size:2rem; opacity:.12; }
        .stat-sub { font-size:.7rem; color:var(--text-m); }
        .stat-endpoint { font-size:.95rem; font-weight:600; font-family:monospace; color:var(--accent); word-break:break-all; }

        .panel { background:var(--bg-card); border:1px solid var(--border); border-radius:14px; margin-bottom:24px; overflow:hidden; }
        .panel-header { padding:15px 22px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; font-weight:600; font-size:.88rem; color:var(--text-m); }
        .panel-header span { color:var(--text); }
        .panel-body { padding:22px; }
        .chart-wrap { position:relative; height:270px; }

        .data-table { width:100%; border-collapse:collapse; font-size:.83rem; }
        .data-table th { padding:10px 16px; text-align:left; font-weight:600; font-size:.7rem; text-transform:uppercase; letter-spacing:.07em; color:var(--text-m); border-bottom:1px solid var(--border); }
        .data-table td { padding:10px 16px; border-bottom:1px solid rgba(255,255,255,.04); color:var(--text-m); }
        .data-table tr:last-child td { border-bottom:none; }
        .data-table tr:hover td { background:var(--bg-elem); }
        .mbadge { display:inline-block; padding:2px 8px; border-radius:4px; font-size:.68rem; font-weight:700; letter-spacing:.05em; }
        .GET    { background:rgba(0,214,143,.15);  color:#00d68f; }
        .POST   { background:rgba(168,85,247,.15);  color:#a855f7; }
        .PUT    { background:rgba(249,115,22,.15);   color:#f97316; }
        .DELETE { background:rgba(255,107,107,.15);  color:#FF6B6B; }
        .PATCH  { background:rgba(0,255,255,.15);    color:#00FFFF; }

        .section-view { display:none; }
        .section-view.active { display:block; }

        @keyframes fadeUp { from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);} }
        .fade-up { animation:fadeUp .4s ease forwards; }
        .d1{animation-delay:.05s;opacity:0;} .d2{animation-delay:.12s;opacity:0;} .d3{animation-delay:.19s;opacity:0;}

        @media(max-width:768px){.sidebar{width:100%;min-height:auto;position:relative;}.main-content{margin-left:0;padding:16px;}}
    </style>
</head>
<body>

<div id="preloader"><div class="spinner-ring"></div></div>
<div id="refresh-bar"></div>

<nav class="sidebar">
    <div class="sidebar-brand">
        <i class="fa-solid fa-hexagon-nodes"></i>
        <div>API Admin <small>Gateway Dashboard</small></div>
    </div>
    <ul class="nav flex-column mt-2" style="list-style:none;padding:0;">
        <li><a class="nav-lnk active" href="#" onclick="switchTab('metrics',this);return false;"><i class="fa-solid fa-chart-line"></i> Live Metrics</a></li>
        <li><a class="nav-lnk" href="#" onclick="switchTab('application',this);return false;"><i class="fa-solid fa-network-wired"></i> Applications</a></li>
        <li><a class="nav-lnk" href="#" onclick="switchTab('routes',this);return false;"><i class="fa-solid fa-network-wired"></i> API Routes</a></li>
        <li><a class="nav-lnk" href="#" onclick="switchTab('logs',this);return false;"><i class="fa-solid fa-list-ul"></i> Request Logs</a></li>
        <li><a class="nav-lnk" href="#" onclick="switchTab('tokens',this);return false;"><i class="fa-solid fa-key"></i> Auth Tokens</a></li>
    </ul>
    <div class="sidebar-footer">
        <a href="index.php?action=logout" class="nav-lnk" style="border-left:none;"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<main class="main-content">

    <div class="topbar">
        <h1 id="pageTitle"><span>Live</span> Metrics</h1>
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="live-badge"><div class="live-dot"></div> Live</div>
            <span id="last-updated" style="font-size:.74rem;color:var(--text-m);">Loading…</span>
        </div>
    </div>

    <!-- METRICS -->
    <div id="metrics" class="section-view active">

        <div class="stats-grid fade-up d1">
            <div class="stat-card cyan">
                <div class="stat-label">Avg Latency</div>
                <div class="stat-value cv" id="stat-avg">—</div>
                <div class="stat-sub">per request</div>
                <div class="stat-icon" style="color:var(--accent);"><i class="fa-solid fa-bolt"></i></div>
            </div>
            <div class="stat-card grn">
                <div class="stat-label">Min Latency</div>
                <div class="stat-value gv" id="stat-min">—</div>
                <div class="stat-sub">fastest recorded</div>
                <div class="stat-icon" style="color:#00d68f;"><i class="fa-solid fa-gauge-high"></i></div>
            </div>
            <div class="stat-card red">
                <div class="stat-label">Max Latency</div>
                <div class="stat-value rv" id="stat-max">—</div>
                <div class="stat-sub">slowest recorded</div>
                <div class="stat-icon" style="color:var(--primary);"><i class="fa-solid fa-triangle-exclamation"></i></div>
            </div>
            <div class="stat-card pur">
                <div class="stat-label">Hits This Week</div>
                <div class="stat-value pv" id="stat-week">—</div>
                <div class="stat-sub">last 7 days</div>
                <div class="stat-icon" style="color:#a855f7;"><i class="fa-solid fa-calendar-week"></i></div>
            </div>
            <div class="stat-card org">
                <div class="stat-label">Current Endpoint</div>
                <div class="stat-endpoint" id="stat-endpoint">—</div>
                <div class="stat-sub" id="stat-endpoint-method" style="margin-top:5px;">—</div>
                <div class="stat-icon" style="color:#f97316;"><i class="fa-solid fa-arrow-pointer"></i></div>
            </div>
            <div class="stat-card cyan">
                <div class="stat-label">Total Logged</div>
                <div class="stat-value cv" id="stat-total">—</div>
                <div class="stat-sub">all-time requests</div>
                <div class="stat-icon" style="color:var(--accent);"><i class="fa-solid fa-globe"></i></div>
            </div>
            <div class="stat-card grn">
                <div class="stat-label">Total Applications</div>
                <div class="stat-value gv" id="stat-apps">—</div>
                <div class="stat-sub">registered apps</div>
                <div class="stat-icon" style="color:#00d68f;"><i class="fa-solid fa-layer-group"></i></div>
            </div>
            <div class="stat-card cyan">
                <div class="stat-label">Active Tokens</div>
                <div class="stat-value cv" id="stat-tokens-active">—</div>
                <div class="stat-sub">currently valid</div>
                <div class="stat-icon" style="color:var(--accent);"><i class="fa-solid fa-key"></i></div>
            </div>
            <div class="stat-card" style="border-left: 3px solid #ff4d4f;">
                <div class="stat-label">Blocked Tokens</div>
                <div class="stat-value" id="stat-tokens-blocked" style="color:#ff4d4f">—</div>
                <div class="stat-sub">revoked access</div>
                <div class="stat-icon" style="color:#ff4d4f;"><i class="fa-solid fa-lock"></i></div>
            </div>
            <div class="stat-card red">
                <div class="stat-label">Rate Limited Clients</div>
                <div class="stat-value rv" id="stat-blocks">—</div>
                <div class="stat-sub">currently blocked</div>
                <div class="stat-icon" style="color:var(--primary);"><i class="fa-solid fa-ban"></i></div>
            </div>
        </div>

        <div class="panel fade-up d2">
            <div class="panel-header">
                <span>Requests per Hour &mdash; Last 24h</span>
                <div style="display:flex;gap:14px;font-size:.76rem;">
                    <span style="color:#00d68f;"><i class="fa-solid fa-minus"></i> GET</span>
                    <span style="color:#a855f7;"><i class="fa-solid fa-minus"></i> POST</span>
                    <span style="color:#f97316;"><i class="fa-solid fa-minus"></i> PUT</span>
                    <span style="color:#FF6B6B;"><i class="fa-solid fa-minus"></i> DELETE</span>
                    <span style="color:#00FFFF;"><i class="fa-solid fa-minus"></i> PATCH</span>
                </div>
            </div>
            <div class="panel-body">
                <div class="chart-wrap"><canvas id="hourlyChart"></canvas></div>
            </div>
        </div>

        <div class="panel fade-up d3">
            <div class="panel-header"><span>Avg Latency by Endpoint</span></div>
            <table class="data-table">
                <thead><tr><th>Path</th><th>Avg Latency</th><th>Requests</th></tr></thead>
                <tbody id="path-tbody"><tr><td colspan="3" style="text-align:center;padding:20px;">Loading…</td></tr></tbody>
            </table>
        </div>

    </div>

     <div id="application" class="section-view">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="panel">
                    <div class="panel-header"><span id="app-form-title">Register Application</span></div>
                    <div class="panel-body">
                        <form action="index.php" method="POST" id="app-form">
                            <input type="hidden" name="action" id="app-form-action" value="add_application">
                            <input type="hidden" name="id" id="app-form-id" value="">
                            <div class="mb-3"><label class="form-label" style="color:var(--text-m);font-size:.82rem;">Tenant / Client ID</label>
                                <input type="text" name="tenant_id" value="<?php echo sprintf('%04x%04x-%04x-4%03x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff), mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)); ?>" class="form-control" placeholder="client123" required style="background:var(--bg-elem);border-color:var(--border);color:var(--text);">
                            </div>
                            <div class="mb-3"><label class="form-label" style="color:var(--text-m);font-size:.82rem;">Application Name</label>
                                <input type="text" name="application-name" class="form-control" placeholder="/users" required style="background:var(--bg-elem);border-color:var(--border);color:var(--text);">
                            </div>
                            <div class="mb-4"><label class="form-label" style="color:var(--text-m);font-size:.82rem;">Description</label>
                                <input type="text" name="description" class="form-control" placeholder="" required style="background:var(--bg-elem);border-color:var(--border);color:var(--text);">
                            </div>
                            <div class="mb-4">
                                <label class="form-label" style="color:var(--text-m);font-size:.82rem;display:block;">App Status</label>
                                <div class="form-check form-check-inline">
                                  <input class="form-check-input" type="radio" name="status" id="appStatusActive" value="active" checked>
                                  <label class="form-check-label" for="appStatusActive" style="color:var(--text);">Active</label>
                                </div>
                                <div class="form-check form-check-inline">
                                  <input class="form-check-input" type="radio" name="status" id="appStatusBlocked" value="blocked">
                                  <label class="form-check-label" for="appStatusBlocked" style="color:var(--text);">Blocked</label>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" id="app-form-submit" class="btn w-100" style="background:var(--accent);color:#000;font-weight:700;"><i class="fa-solid fa-plus me-2"></i>Register Application</button>
                                <button type="button" id="app-form-cancel" class="btn w-100" style="background:var(--bg-elem);color:var(--text);border:1px solid var(--border);display:none;" onclick="cancelAppEdit()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="panel">
                    <div class="panel-header">
                        <span>Registered Applications</span>
                        <button onclick="loadApplications()" class="btn btn-sm" style="background:var(--bg-elem);color:var(--text-m);font-size:.75rem;border:1px solid var(--border);"><i class="fa-solid fa-rotate-right"></i> Refresh</button>
                    </div>
                    <table class="data-table">
                        <thead><tr><th>Tenant</th><th>Application</th><th>Status</th><th style="text-align:right;">Action</th></tr></thead>
                        <tbody id="application-tbody"><tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-m);">Loading…</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ROUTES -->
    <div id="routes" class="section-view">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="panel">
                    <div class="panel-header"><span>Register New Route</span></div>
                    <div class="panel-body">
                        <form action="index.php" method="POST" id="add-route-form">
                            <input type="hidden" name="action" value="add_route">
                            <div class="mb-3"><label class="form-label" style="color:var(--text-m);font-size:.82rem;">Tenant / Client ID</label>
                                <input type="text" name="tenant_id" class="form-control" placeholder="client123" required style="background:var(--bg-elem);border-color:var(--border);color:var(--text);">
                            </div>
                            <div class="mb-3"><label class="form-label" style="color:var(--text-m);font-size:.82rem;">Endpoint Path</label>
                                <input type="text" name="path" class="form-control" placeholder="/users" required style="background:var(--bg-elem);border-color:var(--border);color:var(--text);">
                            </div>
                            <div class="mb-4"><label class="form-label" style="color:var(--text-m);font-size:.82rem;">Target URL</label>
                                <input type="text" name="target_url" class="form-control" placeholder="http://backend/path" required style="background:var(--bg-elem);border-color:var(--border);color:var(--text);">
                            </div>
                            <button type="submit" class="btn w-100" style="background:var(--accent);color:#000;font-weight:700;"><i class="fa-solid fa-plus me-2"></i>Save Route</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="panel">
                    <div class="panel-header">
                        <span>Registered Routes</span>
                        <button onclick="loadRoutes()" class="btn btn-sm" style="background:var(--bg-elem);color:var(--text-m);font-size:.75rem;border:1px solid var(--border);"><i class="fa-solid fa-rotate-right"></i> Refresh</button>
                    </div>
                    <table class="data-table">
                        <thead><tr><th>Tenant</th><th>Path</th><th>Target URL</th><th style="text-align:right;">Action</th></tr></thead>
                        <tbody id="routes-tbody"><tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-m);">Loading…</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- LOGS -->
    <div id="logs" class="section-view">
        <div class="panel">
            <div class="panel-header"><span>Recent Requests</span><small style="color:var(--text-m);">Last 100</small></div>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead><tr><th>Timestamp</th><th>Client</th><th>Method</th><th>Path</th><th>Latency</th></tr></thead>
                    <tbody id="logs-tbody"><tr><td colspan="5" style="text-align:center;padding:20px;">Loading…</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TOKENS -->
    <div id="tokens" class="section-view">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="panel">
                    <div class="panel-header"><span id="token-form-title">Generate Token</span></div>
                    <div class="panel-body">
                        <form action="index.php" method="POST" id="token-form">
                            <input type="hidden" name="action" id="token-form-action" value="generate_token">
                            <input type="hidden" name="id" id="token-form-id" value="">
                            <div class="mb-3"><label class="form-label" style="color:var(--text-m);font-size:.82rem;">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="user@example.com" required style="background:var(--bg-elem);border-color:var(--border);color:var(--text);">
                            </div>
                            <div class="mb-4"><label class="form-label" style="color:var(--text-m);font-size:.82rem;">Expiry Date</label>
                                <input type="date" name="expiry" class="form-control" style="background:var(--bg-elem);border-color:var(--border);color:var(--text);">
                            </div>
                            <div class="mb-4">
                                <label class="form-label" style="color:var(--text-m);font-size:.82rem;display:block;">Token Status</label>
                                <div class="form-check form-check-inline">
                                  <input class="form-check-input" type="radio" name="status" id="statusActive" value="active" checked>
                                  <label class="form-check-label" for="statusActive" style="color:var(--text);">Active</label>
                                </div>
                                <div class="form-check form-check-inline">
                                  <input class="form-check-input" type="radio" name="status" id="statusBlocked" value="blocked">
                                  <label class="form-check-label" for="statusBlocked" style="color:var(--text);">Blocked</label>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" id="token-form-submit" class="btn w-100" style="background:var(--accent);color:#000;font-weight:700;">Generate Key</button>
                                <button type="button" id="token-form-cancel" class="btn w-100" style="background:var(--bg-elem);color:var(--text);border:1px solid var(--border);display:none;" onclick="cancelTokenEdit()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="panel">
                    <div class="panel-header">
                        <span>Managed Tokens</span>
                        <button onclick="loadTokens()" class="btn btn-sm" style="background:var(--bg-elem);color:var(--text-m);font-size:.75rem;border:1px solid var(--border);"><i class="fa-solid fa-rotate-right"></i> Refresh</button>
                    </div>
                    <table class="data-table">
                        <thead><tr><th>Email</th><th>Token Key</th><th>Status</th><th>Expiry</th><th style="text-align:right;">Action</th></tr></thead>
                        <tbody id="tokens-tbody"><tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-m);">Loading…</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const METRICS_URL   = 'http://localhost/api_router/index.php/metrics';
const REFRESH_MS    = 5000;
const METHODS       = ['GET','POST','PUT','DELETE','PATCH'];
const METHOD_COLORS = { GET:'#00d68f', POST:'#a855f7', PUT:'#f97316', DELETE:'#FF6B6B', PATCH:'#00FFFF' };

/* Utility functions */
function copyToClip(text, btn) {
    navigator.clipboard.writeText(text);
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-check" style="color:#00ff78;"></i>';
    setTimeout(() => btn.innerHTML = orig, 1500);
}
/* Chart */
const HOURS = Array.from({length:24},(_,i)=>String(i).padStart(2,'0')+':00');
const hourlyChart = new Chart(document.getElementById('hourlyChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: HOURS,
        datasets: METHODS.map(m => ({
            label: m,
            data: new Array(24).fill(0),
            borderColor: METHOD_COLORS[m],
            backgroundColor: METHOD_COLORS[m] + '18',
            borderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 6,
            tension: 0.4,
            fill: true,
        }))
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        interaction: { mode:'index', intersect:false },
        plugins: {
            legend: { display: false },
            tooltip: { backgroundColor:'#1e2130', borderColor:'rgba(255,255,255,.08)', borderWidth:1, titleColor:'#fff', bodyColor:'#b0b8c1', padding:12 }
        },
        scales: {
            x: { grid:{color:'rgba(255,255,255,.05)'}, ticks:{color:'#b0b8c1',font:{size:11},maxTicksLimit:12} },
            y: { grid:{color:'rgba(255,255,255,.05)'}, ticks:{color:'#b0b8c1',font:{size:11},stepSize:1}, beginAtZero:true }
        }
    }
});

/* Helpers */
function fmt(sec) {
    if (!sec || isNaN(sec)) return '—';
    if (sec < 0.001) return (sec*1e6).toFixed(1)+' µs';
    if (sec < 1)     return (sec*1000).toFixed(2)+' ms';
    return sec.toFixed(4)+' s';
}
function badge(m) { return `<span class="mbadge ${m||'GET'}">${m||'GET'}</span>`; }
function esc(s)   { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function set(id,v){ const e=document.getElementById(id);if(e)e.textContent=v; }
function html(id,v){ const e=document.getElementById(id);if(e)e.innerHTML=v; }

/* Fetch */
async function fetchMetrics() {
    try {
        const r = await fetch(METRICS_URL + '?_='+Date.now());
        const d = await r.json();
        const L = d.latency || {};

        set('stat-avg',   fmt(L.avg_seconds));
        set('stat-min',   fmt(L.min_seconds));
        set('stat-max',   fmt(L.max_seconds));
        set('stat-week',  (d.week_hits??'—').toLocaleString());
        set('stat-total', (L.total_logged??'—').toLocaleString());

        set('stat-apps',  (d.total_apps??'0').toLocaleString());
        set('stat-tokens-active', (d.active_tokens??'0').toLocaleString());
        set('stat-tokens-blocked', (d.blocked_tokens??'0').toLocaleString());
        set('stat-blocks',(d.total_blocks??'0').toLocaleString());

        const ep = d.current_endpoint;
        if (ep) {
            set('stat-endpoint', ep.path||'—');
            html('stat-endpoint-method', badge(ep.method));
        }

        /* Update chart */
        hourlyChart.data.datasets.forEach(ds => ds.data.fill(0));
        (d.requests_per_hour||[]).forEach(row => {
            const hi = parseInt(row.hour,10);
            const mi = METHODS.indexOf(row.method);
            if (mi !== -1 && hi >= 0 && hi < 24)
                hourlyChart.data.datasets[mi].data[hi] = parseInt(row.count,10);
        });
        hourlyChart.update('none');

        /* Path table */
        const paths = L.by_path || [];
        html('path-tbody', paths.length
            ? paths.map(p=>`<tr>
                <td style="font-family:monospace;color:var(--accent);">${esc(p.path)}</td>
                <td>${fmt(parseFloat(p.avg_latency))}</td>
                <td>${p.requests}</td></tr>`).join('')
            : '<tr><td colspan="3" style="text-align:center;padding:20px;">No data yet</td></tr>');

        /* Logs table */
        const logs = d.logs || [];
        html('logs-tbody', logs.length
            ? logs.map(l=>`<tr>
                <td style="font-size:.76rem;">${esc(l.created_at)}</td>
                <td style="font-size:.76rem;">${esc(l.client_id)}</td>
                <td>${badge(l.method)}</td>
                <td style="font-family:monospace;color:var(--accent);">${esc(l.path)}</td>
                <td>${fmt(parseFloat(l.latency))}</td></tr>`).join('')
            : '<tr><td colspan="5" style="text-align:center;padding:20px;">No logs yet</td></tr>');

        document.getElementById('last-updated').textContent = 'Updated '+new Date().toLocaleTimeString();
    } catch(e) {
        document.getElementById('last-updated').textContent = 'Error: '+e.message;
    }
}

/* Refresh bar */
function animateBar() {
    const b = document.getElementById('refresh-bar');
    b.style.transition = 'none';
    b.style.transform  = 'scaleX(0)';
    setTimeout(() => {
        b.style.transition = `transform ${REFRESH_MS}ms linear`;
        b.style.transform  = 'scaleX(1)';
    }, 30);
}

/* Tab switch */
function switchTab(id, el) {
    document.querySelectorAll('.nav-lnk').forEach(l=>l.classList.remove('active'));
    el.classList.add('active');
    document.querySelectorAll('.section-view').forEach(s=>s.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    const T = { metrics:'<span>Live</span> Metrics', application:'<span>Registered</span> Applications', routes:'<span>API</span> Routes', logs:'<span>Request</span> Logs', tokens:'<span>Auth</span> Tokens' };
    html('pageTitle', T[id]||id);
    if (id === 'routes') loadRoutes();
    if (id === 'application') loadApplications();
    if (id === 'tokens') loadTokens();
}

/* Tokens table */
async function loadTokens() {
    html('tokens-tbody', '<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-m);">Loading…</td></tr>');
    try {
        const r = await fetch('index.php?action=get_tokens&_='+Date.now());
        const rows = await r.json();
        if (!rows.length) {
            html('tokens-tbody', '<tr><td colspan="5" style="text-align:center;padding:20px;color:var(--text-m);">No tokens configured yet.</td></tr>');
            return;
        }
        html('tokens-tbody', rows.map(row => {
            const st = row.status === 'blocked' 
                ? '<span style="background:rgba(255,77,79,.15);color:#ff4d4f;padding:2px 8px;border-radius:4px;font-size:0.75rem;">Blocked</span>'
                : '<span style="background:rgba(0,214,143,.15);color:#00d68f;padding:2px 8px;border-radius:4px;font-size:0.75rem;">Active</span>';
            return `
            <tr>
                <td style="font-weight:600;color:var(--text);">${esc(row.email)}</td>
                <td style="font-family:monospace;color:var(--accent);">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span>${esc(row.token)}</span>
                        <button type="button" class="btn btn-sm" style="background:transparent;color:var(--text-m);border:none;padding:0;" title="Copy to clipboard" onclick="copyToClip('${esc(row.token)}', this)">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                    </div>
                </td>
                <td>${st}</td>
                <td style="font-size:.78rem;color:var(--text-m);">${row.expiry ? esc(row.expiry) : 'Never'}</td>
                <td style="text-align:right;">
                    <button type="button" class="btn btn-sm" style="background:rgba(168,85,247,.12);color:#a855f7;border:1px solid rgba(168,85,247,.2);margin-right:4px;" title="Edit Token" onclick="editToken(${row.id}, '${esc(row.email).replace(/'/g,"\\'").replace(/"/g,"&quot;")}', '${esc(row.status)}', '${row.expiry||''}')">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <form method="POST" action="index.php" style="display:inline;" onsubmit="return confirm('Revoke this token?')">
                        <input type="hidden" name="action" value="delete_token">
                        <input type="hidden" name="id" value="${row.id}">
                        <button type="submit" class="btn btn-sm" style="background:rgba(255,107,107,.12);color:#FF6B6B;border:1px solid rgba(255,107,107,.2);">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>`;
        }).join(''));
    } catch(e) {
        html('tokens-tbody', `<tr><td colspan="5" style="text-align:center;padding:20px;color:#FF6B6B;">Error: ${esc(e.message)}</td></tr>`);
    }
}

/* Edit Token form handler */
function editToken(id, email, status, expiry) {
    document.getElementById('token-form-title').textContent = 'Edit Token';
    document.getElementById('token-form-action').value = 'update_token';
    document.getElementById('token-form-id').value = id;
    document.querySelector('#token-form [name="email"]').value = email;
    document.querySelector('#token-form [name="expiry"]').value = expiry;
    if (status === 'blocked') document.getElementById('statusBlocked').checked = true;
    else document.getElementById('statusActive').checked = true;
    document.getElementById('token-form-submit').textContent = 'Update Key';
    document.getElementById('token-form-cancel').style.display = 'block';
    window.scrollTo({top: 0, behavior: 'smooth'});
}
function cancelTokenEdit() {
    document.getElementById('token-form').reset();
    document.getElementById('token-form-title').textContent = 'Generate Token';
    document.getElementById('token-form-action').value = 'generate_token';
    document.getElementById('token-form-id').value = '';
    document.getElementById('token-form-submit').textContent = 'Generate Key';
    document.getElementById('token-form-cancel').style.display = 'none';
}

/* Applications table */
async function loadApplications() {
    html('application-tbody', '<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-m);">Loading…</td></tr>');
    try {
        const r = await fetch('index.php?action=get_applications&_='+Date.now());
        const rows = await r.json();
        if (!rows.length) {
            html('application-tbody', '<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-m);">No applications registered</td></tr>');
            return;
        }
        html('application-tbody', rows.map(row => {
            const st = row.status === 'blocked' 
                ? '<span style="background:rgba(255,77,79,.15);color:#ff4d4f;padding:2px 8px;border-radius:4px;font-size:0.75rem;">Blocked</span>'
                : '<span style="background:rgba(0,214,143,.15);color:#00d68f;padding:2px 8px;border-radius:4px;font-size:0.75rem;">Active</span>';
            return `
            <tr>
                <td style="font-family:monospace;color:#a855f7;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span>${esc(row.tenant_id)}</span>
                        <button type="button" class="btn btn-sm" style="background:transparent;color:var(--text-m);border:none;padding:0;" title="Copy to clipboard" onclick="copyToClip('${esc(row.tenant_id)}', this)">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                    </div>
                </td>
                <td style="font-weight:600;color:var(--text);">${esc(row.app_name)}</td>
                <td>${st}</td>
                <td style="text-align:right;">
                    <button type="button" class="btn btn-sm" style="background:rgba(168,85,247,.12);color:#a855f7;border:1px solid rgba(168,85,247,.2);margin-right:4px;" title="Edit App" onclick="editApp(${row.id}, '${esc(row.tenant_id)}', '${esc(row.app_name).replace(/'/g,"\\'").replace(/"/g,"&quot;")}', '${esc(row.description).replace(/'/g,"\\'").replace(/"/g,"&quot;")}', '${esc(row.status)}')">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <form method="POST" action="index.php" style="display:inline;" onsubmit="return confirm('Delete this application?')">
                        <input type="hidden" name="action" value="delete_application">
                        <input type="hidden" name="id" value="${row.id}">
                        <button type="submit" class="btn btn-sm" style="background:rgba(255,107,107,.12);color:#FF6B6B;border:1px solid rgba(255,107,107,.2);">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>`;
        }).join(''));
    } catch(e) {
        html('application-tbody', `<tr><td colspan="4" style="text-align:center;padding:20px;color:#FF6B6B;">Error: ${esc(e.message)}</td></tr>`);
    }
}

/* Edit App form handler */
function editApp(id, tenant_id, app_name, description, status) {
    document.getElementById('app-form-title').textContent = 'Edit Application';
    document.getElementById('app-form-action').value = 'update_application';
    document.getElementById('app-form-id').value = id;
    document.querySelector('#app-form [name="tenant_id"]').value = tenant_id;
    document.querySelector('#app-form [name="application-name"]').value = app_name;
    document.querySelector('#app-form [name="description"]').value = description;
    if (status === 'blocked') document.getElementById('appStatusBlocked').checked = true;
    else document.getElementById('appStatusActive').checked = true;
    document.getElementById('app-form-submit').innerHTML = '<i class="fa-solid fa-save me-2"></i>Update App';
    document.getElementById('app-form-cancel').style.display = 'block';
    window.scrollTo({top: 0, behavior: 'smooth'});
}
function cancelAppEdit() {
    document.getElementById('app-form').reset();
    document.getElementById('app-form-title').textContent = 'Register Application';
    document.getElementById('app-form-action').value = 'add_application';
    document.getElementById('app-form-id').value = '';
    document.getElementById('app-form-submit').innerHTML = '<i class="fa-solid fa-plus me-2"></i>Register Application';
    document.getElementById('app-form-cancel').style.display = 'none';
}

/* Routes table */
async function loadRoutes() {
    html('routes-tbody', '<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-m);">Loading…</td></tr>');
    try {
        const r = await fetch('index.php?action=get_routes&_='+Date.now());
        const rows = await r.json();
        if (!rows.length) {
            html('routes-tbody', '<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-m);">No routes registered</td></tr>');
            return;
        }
        html('routes-tbody', rows.map(row => `
            <tr>
                <td style="font-family:monospace;color:#a855f7;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span>${esc(row.tenant_id)}</span>
                        <button type="button" class="btn btn-sm" style="background:transparent;color:var(--text-m);border:none;padding:0;" title="Copy to clipboard" onclick="copyToClip('${esc(row.tenant_id)}', this)">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                    </div>
                </td>
                <td style="font-family:monospace;color:var(--accent);">${esc(row.path)}</td>
                <td style="font-size:.78rem;color:var(--text-m);word-break:break-all;">${esc(row.target_url)}</td>
                <td style="text-align:right;">
                    <form method="POST" action="index.php" style="display:inline;" onsubmit="return confirm('Delete this route?')">
                        <input type="hidden" name="action" value="delete_route">
                        <input type="hidden" name="id" value="${row.id}">
                        <button type="submit" class="btn btn-sm" style="background:rgba(255,107,107,.12);color:#FF6B6B;border:1px solid rgba(255,107,107,.2);">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>`).join(''));
    } catch(e) {
        html('routes-tbody', `<tr><td colspan="4" style="text-align:center;padding:20px;color:#FF6B6B;">Error: ${esc(e.message)}</td></tr>`);
    }
}

/* Boot */
window.addEventListener('load', () => {
    const pre = document.getElementById('preloader');
    setTimeout(()=>{ pre.style.opacity='0'; setTimeout(()=>pre.remove(),500); }, 600);
    fetchMetrics();
    animateBar();
    setInterval(()=>{ fetchMetrics(); animateBar(); }, REFRESH_MS);

    // Auto-switch tab if hash is present
    const hash = window.location.hash.substring(1);
    if (hash && ['metrics', 'application', 'routes', 'logs', 'tokens'].includes(hash)) {
        const link = document.querySelector(`a[onclick*="switchTab('${hash}'"]`);
        if (link) switchTab(hash, link);
    }
});
</script>
</body>
</html>