export function initPlayerProfiles() {
    const dialog = document.getElementById('player-profile-dialog');
    if (!dialog) return;

    const find = (name) => dialog.querySelector(`[data-profile-${name}]`);
    const content = find('content');
    const message = find('message');
    const retry = find('retry');
    const body = find('body');
    let trigger;
    let pending;
    let closeTimer;
    let endpoint;

    const text = (name, value) => { find(name).textContent = value; };
    const element = (tag, className, value) => {
        const node = document.createElement(tag);
        node.className = className;
        if (value !== undefined) node.textContent = value;
        return node;
    };

    const render = (player) => {
        text('name', player.username);
        text('rank', player.rank === null ? 'Unranked overall' : `Overall rank #${player.rank}`);
        text('joined', player.joined ? `Joined ${player.joined}` : '');
        const avatar = find('avatar');
        avatar.src = player.avatar_url || avatar.dataset.fallback;
        text('score', `${player.total_score} / ${player.max_score}`);
        text('stars', `${player.stars} / ${player.max_stars}`);
        text('progress-label', `${player.completed_scenarios} of ${player.total_scenarios} completed · ${player.progress_percent}%`);
        find('progress').value = player.progress_percent;

        const scenarios = player.scenarios.map((scenario) => {
            const row = element('li', 'player-profile-scenario');
            const identity = element('div', 'player-profile-scenario-identity');
            identity.append(element('h4', '', scenario.name), element('span', 'player-profile-meta', scenario.status_label));
            const result = element('div', 'player-profile-scenario-result');
            result.append(element('strong', '', scenario.best_score === null ? 'No score yet' : `${scenario.best_score} / 100`));
            const stars = element('span', 'player-profile-star-line');
            stars.setAttribute('role', 'img');
            stars.setAttribute('aria-label', `${scenario.stars} of 3 stars earned`);
            for (let i = 0; i < 3; i++) {
                const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                svg.setAttribute('viewBox', '0 0 24 24');
                svg.setAttribute('aria-hidden', 'true');
                svg.classList.add(i < scenario.stars ? 'is-earned' : 'is-unearned');
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', 'm12 3 2.8 5.7 6.3.9-4.6 4.4 1.1 6.3-5.6-3-5.6 3 1.1-6.3L3 9.6l6.2-.9Z');
                svg.append(path);
                stars.append(svg);
            }
            result.append(stars);
            row.append(identity, result);
            return row;
        });
        find('scenarios').replaceChildren(...scenarios);
        text('perfect', `${player.perfect_scores} of ${player.total_scenarios} scenarios with a perfect 100 / 100.`);
        find('achievements').replaceChildren(...player.achievements.map((name) => element('li', 'player-profile-achievement', name)));
        find('no-achievements').hidden = player.achievements.length > 0;
        text('certificate', player.certificate_status);
    };

    const load = async () => {
        pending?.abort();
        const request = new AbortController();
        pending = request;
        content.hidden = true;
        retry.hidden = true;
        message.hidden = false;
        message.textContent = 'Loading player profile…';
        body.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(endpoint, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: request.signal,
            });
            if (!response.ok) throw new Error('Profile unavailable');
            const player = await response.json();
            if (request.signal.aborted || !dialog.open) return;
            render(player);
            content.hidden = false;
            message.textContent = `Player profile for ${player.username} loaded.`;
            message.classList.add('sr-only');
        } catch (error) {
            if (request.signal.aborted) return;
            message.classList.remove('sr-only');
            message.textContent = 'This player profile could not be loaded. Please try again.';
            retry.hidden = false;
        } finally {
            if (pending === request) body.setAttribute('aria-busy', 'false');
        }
    };

    const close = () => {
        if (!dialog.open || closeTimer) return;
        pending?.abort();
        dialog.classList.add('is-closing');
        const duration = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 140;
        closeTimer = window.setTimeout(() => {
            dialog.close();
            closeTimer = undefined;
        }, duration);
    };

    document.querySelectorAll('[data-player-profile]').forEach((button) => {
        button.addEventListener('click', () => {
            trigger = button;
            endpoint = button.dataset.playerProfile;
            message.classList.remove('sr-only');
            dialog.classList.remove('is-closing');
            // Native modal dialogs make the rest of the page inert and contain focus.
            dialog.showModal();
            document.body.classList.add('player-profile-open');
            body.scrollTop = 0;
            find('close').focus();
            load();
        });
    });
    find('close').addEventListener('click', close);
    retry.addEventListener('click', load);
    dialog.addEventListener('cancel', (event) => { event.preventDefault(); close(); });
    let backdropPress = false;
    const outside = (event) => {
        const bounds = dialog.getBoundingClientRect();
        return event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom;
    };
    dialog.addEventListener('pointerdown', (event) => { backdropPress = event.target === dialog && outside(event); });
    dialog.addEventListener('click', (event) => {
        if (backdropPress && event.target === dialog && outside(event)) close();
        backdropPress = false;
    });
    dialog.addEventListener('close', () => {
        pending?.abort();
        document.body.classList.remove('player-profile-open');
        dialog.classList.remove('is-closing');
        trigger?.focus({ preventScroll: true });
    });
}
