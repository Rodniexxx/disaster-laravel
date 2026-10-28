<dialog id="player-profile-dialog" class="player-profile-dialog" aria-labelledby="player-profile-title" data-ui>
    <div class="player-profile-shell">
        <header class="player-profile-heading">
            <h2 id="player-profile-title">Player profile</h2>
            <button type="button" class="player-profile-close" data-profile-close aria-label="Close player profile" autofocus>
                <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
            </button>
        </header>
        <div class="player-profile-body" data-profile-body aria-busy="false" tabindex="0" role="region" aria-label="Player profile details">
            <p class="player-profile-message" data-profile-message role="status" aria-live="polite"></p>
            <button type="button" class="btn btn-secondary" data-profile-retry hidden>Try again</button>
            <div data-profile-content hidden>
                <div class="player-profile-identity">
                    <x-player-avatar :size="72" data-profile-avatar />
                    <div>
                        <h3 data-profile-name></h3>
                        <p class="player-profile-meta"><span data-profile-rank></span><span data-profile-joined></span></p>
                    </div>
                </div>
                <dl class="player-profile-totals">
                    <div><dt>Total readiness score</dt><dd data-profile-score></dd></div>
                    <div><dt>Stars earned</dt><dd data-profile-stars></dd></div>
                </dl>
                <section class="player-profile-section" aria-labelledby="player-profile-progress-title">
                    <div class="player-profile-section-heading">
                        <h3 id="player-profile-progress-title">Game progress</h3>
                        <span data-profile-progress-label></span>
                    </div>
                    <progress data-profile-progress max="100" value="0" aria-label="Overall scenario completion"></progress>
                </section>
                <section class="player-profile-section" aria-labelledby="player-profile-scenarios-title">
                    <h3 id="player-profile-scenarios-title">Disaster scenarios</h3>
                    <p class="player-profile-help">Best completed readiness scores, out of 100.</p>
                    <ul class="player-profile-scenarios" data-profile-scenarios></ul>
                </section>
                <section class="player-profile-section" aria-labelledby="player-profile-achievements-title">
                    <h3 id="player-profile-achievements-title">Achievements</h3>
                    <p class="player-profile-help" data-profile-perfect></p>
                    <ul class="player-profile-achievements" data-profile-achievements></ul>
                    <p class="player-profile-help" data-profile-no-achievements hidden>No achievements earned yet.</p>
                    <p class="player-profile-certificate">Completion certificate <strong data-profile-certificate></strong></p>
                </section>
            </div>
        </div>
    </div>
</dialog>
