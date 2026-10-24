<section class="completion-certificate-card reveal" aria-labelledby="completion-certificate-title">
    <header class="completion-certificate-header">
        <div>
            <p class="certificate-eyebrow">MANGNO · READINESS MILESTONE</p>
            <h2 id="completion-certificate-title">CERTIFICATE OF COMPLETION</h2>
            <p class="certificate-summary">
                {{ $certificate['completed_scenarios'] }} / {{ $certificate['total_scenarios'] }} scenarios
                <span aria-hidden="true">·</span> {{ collect($certificate['achievements'])->where('unlocked', true)->count() }} / 5 badges
                <span aria-hidden="true">·</span> {{ $certificate['total_stars'] }} / 12 stars
            </p>
        </div>
        <span class="certificate-status {{ $certificate['eligible'] ? 'ready' : 'locked' }}">
            {{ ! $certificate['eligible'] ? 'LOCKED' : ($certificate['certificate_id'] ? 'ISSUED' : 'READY TO CLAIM') }}
        </span>
    </header>

    <div class="certificate-requirement-grid">
        <div class="certificate-requirement-group">
            <h3>Disaster scenarios</h3>
            <ul>
                @foreach ($certificate['scenarios'] as $scenario)
                    <li class="{{ $scenario['completed'] ? 'complete' : 'pending' }}">
                        <span aria-hidden="true">{{ $scenario['completed'] ? '✓' : '○' }}</span>
                        <span>{{ $scenario['name'] }}</span>
                        <strong>{{ $scenario['completed'] ? $scenario['stars'].' / 3 stars' : 'Not complete' }}</strong>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="certificate-requirement-group">
            <h3>Achievement badges</h3>
            <ul>
                @foreach ($certificate['achievements'] as $achievement)
                    <li class="{{ $achievement['unlocked'] ? 'complete' : 'pending' }}">
                        <span aria-hidden="true">{{ $achievement['unlocked'] ? '✓' : '○' }}</span>
                        <span>{{ $achievement['name'] }}</span>
                        <strong>{{ $achievement['unlocked'] ? 'Unlocked' : 'Locked' }}</strong>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    @if (! $certificate['eligible'])
        <div class="certificate-missing" role="status">
            <h3>Requirements still missing</h3>
            <ul>
                @foreach ($certificate['missing_requirements'] as $requirement)
                    <li><span aria-hidden="true">▶</span> {{ $requirement }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="certificate-actions">
        @if ($certificate['eligible'] && $certificate['certificate_id'])
            <a class="btn btn-primary" href="{{ route('dashboard.certificate') }}">VIEW CERTIFICATE</a>
            <a class="btn btn-secondary" href="{{ route('dashboard.certificate.pdf') }}">DOWNLOAD PDF</a>
        @elseif ($certificate['eligible'])
            <form method="POST" action="{{ route('dashboard.certificate.issue') }}">
                @csrf
                <button class="btn btn-primary" type="submit">VIEW CERTIFICATE</button>
            </form>
            <span class="certificate-claim-note">Your certificate is ready. Claim it once to save its issue date and unique ID.</span>
        @else
            <button class="btn btn-secondary" type="button" disabled aria-disabled="true">CERTIFICATE LOCKED</button>
            <span class="certificate-claim-note">Finish all four scenarios and unlock all five achievements to continue.</span>
        @endif
    </div>
</section>
