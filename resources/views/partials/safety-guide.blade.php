@php
    $accessDate = 'October 14, 2026';

    $safetyGuides = [
        [
            'title' => 'Earthquake',
            'agency' => 'DOST-PHIVOLCS',
            'before' => [
                'Know your building\'s safe spots and prepare a "go bag" with water, food, a flashlight, a first-aid kit, and essential medicines.',
                'Identify open evacuation areas and practice the "Duck, Cover, and Hold" drill regularly.',
                'Secure heavy furniture and cabinets to the walls and repair structural defects in your home.',
            ],
            'during' => [
                'If indoors: Duck under a sturdy desk or table, cover your head and neck, and hold on until the shaking stops. Stay away from windows, mirrors, and cabinets.',
                'If outdoors: move to an open area away from posts, walls, and power lines.',
                'If driving: pull to the side of the road and stop. Do not cross bridges or overpasses.',
                'If you are near the shore and the shaking is strong, move quickly to higher ground — a tsunami may follow.',
            ],
            'after' => [
                'Check yourself and others for injuries. Do not use candles or open flames.',
                'Expect aftershocks and keep performing Duck, Cover, and Hold.',
                'Inspect your home for damage and evacuate if it is unsafe. Listen to official advisories.',
            ],
            'source' => 'Adapted from DOST-PHIVOLCS Earthquake Preparedness.',
        ],
        [
            'title' => 'Volcanic Eruption',
            'agency' => 'DOST-PHIVOLCS',
            'before' => [
                'Monitor the volcano\'s alert level and advisories from PHIVOLCS, your LGU, and barangay.',
                'Prepare a go bag with goggles, a thick face mask, water, and medicines. Know the danger zones and evacuation routes.',
                'Be ready to evacuate immediately when advised, and plan ahead for pets and livestock.',
            ],
            'during' => [
                'Stay calm and stay indoors. Close all doors, windows, and vents, and listen to the radio for updates.',
                'Cover your nose and mouth with a mask or damp cloth and wear goggles or protective eyewear.',
                'If driving during heavy ashfall, pull over and stop.',
                'Avoid low-lying areas and river valleys (possible lahar flows). Scrape heavy ash off roofs to prevent collapse.',
            ],
            'after' => [
                'Wear a mask and goggles when cleaning. Remove ash from roofs, gutters, and surroundings.',
                'Wash vegetables, fruits, and utensils before use. Cover water and food to avoid contamination.',
                'Wait for official announcements before leaving the evacuation center or returning home.',
            ],
            'source' => 'Adapted from DOST-PHIVOLCS Volcano Preparedness.',
        ],
        [
            'title' => 'Typhoon',
            'agency' => 'DOST-PAGASA',
            'before' => [
                'Monitor PAGASA weather bulletins and wind signals, and know the signal level for your area.',
                'Secure or repair roofs, trim trees, clear drainage, and store food, water, batteries, and medicines.',
                'Prepare a family emergency plan and know your evacuation center and route.',
            ],
            'during' => [
                'Stay indoors and away from windows. Keep calm and keep monitoring PAGASA updates over radio or TV.',
                'If told to evacuate, do so early and follow the designated routes.',
                'Turn off main electricity, gas, and water if flooding is imminent. Avoid wading through floodwater.',
            ],
            'after' => [
                'Avoid damaged buildings, downed power lines, and flooded areas. Report hazards to authorities.',
                'Boil drinking water or use safe water, eat only well-cooked food, and wash your hands.',
                'Help others and cooperate with local officials. Watch for post-typhoon advisories such as flooding and storm surge.',
            ],
            'source' => 'Adapted from DOST-PAGASA weather bulletins and advisories.',
        ],
        [
            'title' => 'Fire',
            'agency' => 'BFP',
            'before' => [
                'Unplug appliances when not in use, replace frayed cords, and avoid overloading outlets.',
                'Install smoke alarms and keep a fire extinguisher handy. Learn how to use it (T.P.A.S.S.).',
                'Plan and practice escape routes. Never leave cooking unattended and keep candles away from flammable objects.',
            ],
            'during' => [
                'Stay low and go under the smoke. If your clothes catch fire, "Stop, Drop, and Roll."',
                'Evacuate immediately using the nearest safe exit. Do not use elevators.',
                'Call 911 or the BFP. If it is safe, use a fire extinguisher following T.P.A.S.S.',
            ],
            'after' => [
                'Do not re-enter the building until fire personnel declare it safe.',
                'Seek medical help for injuries or burns and watch out for hotspots or re-ignition.',
                'Report the incident and cooperate with the BFP. Review and improve your fire safety plan.',
            ],
            'source' => 'Adapted from BFP fire safety education materials.',
        ],
    ];

    $emergencyContacts = [
        ['agency' => 'National Emergency Hotline', 'role' => 'Police • Fire • Medical', 'number' => '911', 'url' => 'https://e911.gov.ph/'],
        ['agency' => 'NDRRMC', 'role' => 'Disaster response and coordination', 'number' => '(02) 8911-5061 to 64', 'url' => 'https://ndrrmc.gov.ph/'],
        ['agency' => 'DOST-PHIVOLCS', 'role' => 'Earthquake and volcano monitoring', 'number' => '(02) 8426-1468 to 79', 'url' => 'https://www.phivolcs.dost.gov.ph/contact-us/'],
        ['agency' => 'DOST-PAGASA', 'role' => 'Weather and typhoon warnings', 'number' => '(02) 8284-0800', 'url' => 'https://www.pagasa.dost.gov.ph/contact-us'],
        ['agency' => 'BFP', 'role' => 'Fire emergencies', 'number' => '(02) 8426-0246 / (02) 8426-0219', 'url' => 'https://bfp.gov.ph/contact-us/'],
    ];

    $references = [
        ['credit' => 'DOST-PHIVOLCS. "Earthquake Preparedness." Philippine Institute of Volcanology and Seismology.', 'url' => 'https://www.phivolcs.dost.gov.ph/earthquake-preparedness/'],
        ['credit' => 'DOST-PHIVOLCS. "Volcano Preparedness." Philippine Institute of Volcanology and Seismology.', 'url' => 'https://www.phivolcs.dost.gov.ph/volcano-preparedness/'],
        ['credit' => 'DOST-PAGASA. "Products and Services / Severe Weather Bulletins." Philippine Atmospheric, Geophysical and Astronomical Services Administration.', 'url' => 'https://www.pagasa.dost.gov.ph/'],
        ['credit' => 'Bureau of Fire Protection (BFP). "Standardized Public Fire Education Manual" and "Fire Safety Guidelines."', 'url' => 'https://bfp.gov.ph/'],
        ['credit' => 'Office of Civil Defense / NDRRMC. "Disaster Preparedness and First Aid Handbook" (compiled with PHIVOLCS, PAGASA, BFP, DepEd, DOH, and MGB).', 'url' => 'https://climate.gov.ph/files/Disaster_Preparedness_First_Aid_Handbook.pdf'],
        ['credit' => 'NDRRMC. "NDRRMC Hotlines." National Disaster Risk Reduction and Management Council.', 'url' => 'https://ndrrmc.gov.ph/'],
        ['credit' => 'Emergency 911 National Office. "Emergency Hotline Numbers."', 'url' => 'https://e911.gov.ph/'],
    ];
@endphp

<section class="section container scenario-section" id="safety-guide">
    <div class="section-heading reveal-item">
        <p class="eyebrow">Safety Guide</p>
        <h2>What to Do Before, During &amp; After</h2>
    </div>

    <p class="safety-intro reveal-item">
        Step-by-step safety actions for the most common hazards in the Philippines, organized by the national
        agency responsible for each one.
    </p>
    <p class="safety-note reveal-item">
        Guidance below is summarized and adapted from official Philippine government sources. Refer to the
        citations at the bottom of this section for the original references.
    </p>

    <div class="safety-grid">
        @foreach ($safetyGuides as $guide)
            <article class="safety-card reveal-item">
                <div class="safety-card-head">
                    
                    <h3>{{ $guide['title'] }}</h3>
                </div>

                <span class="agency-badge">Guidance by {{ $guide['agency'] }}</span>

                <div class="phase-grid">
                    <div class="phase before">
                        <span class="phase-label">Before</span>
                        <ul>
                            @foreach ($guide['before'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="phase during">
                        <span class="phase-label">During</span>
                        <ul>
                            @foreach ($guide['during'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="phase after">
                        <span class="phase-label">After</span>
                        <ul>
                            @foreach ($guide['after'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <p class="safety-source">{{ $guide['source'] }}</p>
            </article>
        @endforeach
    </div>

    <div class="section-heading reveal-item" id="emergency-info" style="margin-top: 56px;">
        <p class="eyebrow">Emergency Info</p>
        <h2>Who to Call</h2>
    </div>

    <div class="contact-grid">
        @foreach ($emergencyContacts as $contact)
            <div class="contact-card reveal-item">
                <span class="contact-agency">{{ $contact['agency'] }}</span>
                <span class="contact-role">{{ $contact['role'] }}</span>
                <span class="contact-number">{{ $contact['number'] }}</span>
                <a class="contact-link" href="{{ $contact['url'] }}" target="_blank" rel="noopener noreferrer">Official website</a>
            </div>
        @endforeach
    </div>

    <p class="safety-note reveal-item" style="margin-top: 22px;">
        Hotline numbers may change over time. Please verify the latest contact details with the respective agency.
    </p>

    <div class="references reveal-item">
        <h3>Acknowledgment &amp; References</h3>
        <p>
            The safety guidance and emergency information above are adapted from the following official sources.
            Full credit belongs to the respective Philippine government agencies. Sources accessed {{ $accessDate }}.
        </p>
        <ol class="reference-list">
            @foreach ($references as $reference)
                <li>
                    {{ $reference['credit'] }}
                    <a href="{{ $reference['url'] }}" target="_blank" rel="noopener noreferrer">{{ $reference['url'] }}</a>
                </li>
            @endforeach
        </ol>
    </div>
</section>
