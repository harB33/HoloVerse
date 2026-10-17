<section class="flash-telemetry" aria-labelledby="flash-telemetry-title">
    <div class="flash-telemetry-heading">
        <h2 id="flash-telemetry-title"><span aria-hidden="true"></span> Active queue &amp; anti-snipe telemetry engine</h2>
        <span>System clock: <?= date('Y-m-d\TH:i:sP') ?></span>
    </div>
    <div class="flash-telemetry-grid">
        <article class="flash-telemetry-card">
            <div class="flash-telemetry-label"><span>Gatekeeper pass</span><span aria-hidden="true">♢</span></div>
            <strong>Passed / verified</strong>
            <p>Hardware attestation cleared. Zero captcha required during lock.</p>
            <span class="flash-telemetry-badge">Hardware encrypted</span>
        </article>

        <article class="flash-telemetry-card">
            <div class="flash-telemetry-label"><span>Your cart lock monitor</span><span class="flash-telemetry-icon-red" aria-hidden="true">◷</span></div>
            <strong class="flash-telemetry-countdown">01:59 <small>remaining</small></strong>
            <p>Active cart reservation lock protects against out-of-stock slips.</p>
            <div class="flash-telemetry-progress" role="progressbar" aria-label="Cart lock time remaining" aria-valuenow="66" aria-valuemin="0" aria-valuemax="100"><span></span></div>
        </article>

        <article class="flash-telemetry-card">
            <div class="flash-telemetry-label"><span>Standby queue position</span><span aria-hidden="true">♙</span></div>
            <strong>#14 <small>est. wait: 12 sec</small></strong>
            <p>Dynamic pool reallocates unsold slots in real time.</p>
            <span class="flash-telemetry-footnote"><i aria-hidden="true"></i> 42 releases in past 60s</span>
        </article>

        <article class="flash-telemetry-card">
            <div class="flash-telemetry-label"><span>Fulfillment SLA</span><span aria-hidden="true">♢</span></div>
            <strong>100% secured</strong>
            <p>Allocation locked once button clicked. No oversell guarantee.</p>
            <span class="flash-telemetry-badge">ISO 27001 vault</span>
        </article>
    </div>
</section>
