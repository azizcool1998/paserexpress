<h2>📄 Log Analyzer PRO</h2>

<select id="log-type">
    <option value="nginx_main">Nginx Main Error</option>
    <option value="nginx_site">Nginx Website Error</option>
    <option value="php_fpm">PHP-FPM</option>
    <option value="mariadb">MariaDB</option>
    <option value="syslog">System Log</option>
</select>

<button onclick="loadLog()">Load Log</button>
<button onclick="analyzeLog()">Analyze</button>
<button onclick="fixCommon()">Fix Common Issues</button>
<button onclick="clearLog()">Clear Log</button>

<pre id="log-view"
     style="background:black;color:#0f0;padding:15px;height:300px;overflow:auto;margin-top:20px">
Please load a log file.
</pre>

<h3>🔍 Analysis Result</h3>
<div id="analysis-box" style="background:#111;color:#fff;padding:15px;border-radius:10px"></div>

<script>
async function loadLog() {
    const type = document.getElementById("log-type").value;
    const res = await fetch("?page=log_read&type="+type);
    const j = await res.json();

    document.getElementById("log-view").innerText =
        j.success ? j.log : "Error: " + j.error;
}

async function analyzeLog() {
    const res = await fetch("?page=log_analyze");
    const j = await res.json();

    document.getElementById("analysis-box").innerHTML =
        "<pre>"+ j.result +"</pre>";
}

async function fixCommon() {
    if (!confirm("Lanjutkan perbaikan otomatis?")) return;

    const res = await fetch("?page=log_fix");
    const j = await res.json();
    alert("Fix result:\n" + j.fix_output);
}

async function clearLog() {
    const type = document.getElementById("log-type").value;
    await fetch("?page=log_clear&type="+type);
    alert("Log cleared.");
    loadLog();
}
</script>
