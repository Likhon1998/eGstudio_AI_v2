<?php

/**
 * Shared art-direction & typography intelligence for every Occasion Studio campaign.
 * Consumed by create.blade.php (custom_text_payload) and OccasionController (n8n webhooks).
 */
return [
    'aspectRatio' => '16:9',

    'aspectRatioDirective' => 'MANDATORY landscape widescreen 16:9 aspect ratio (cinematic 1920×1080 frame). Never portrait, square, or 4:5.',

    'graphicDesignerBrief' => 'Think like a Cannes-lion art director at a premium Bengali FMCG/pharma agency: every frame is a campaign hero in landscape 16:9 widescreen where EVERY element has meaning—background tells the story, hero icon is the emotional spine, graphic shapes encode year or symbol, colors carry cultural emotion, empty zones await purposeful Bangla/English lines (never decorative filler). Museum-quality composition, golden-ratio focal placement, cinematic depth, photo+flat graphic+3D hero layering. STRICT: render ZERO text, Bengali/English letters, numbers, URLs, logos, or watermarks inside the image.',

    'typographyMatchingBrief' => 'Typography must be extraordinary and feel born from the artwork—not pasted on. Bangla headline: bold authoritative display (Li Sarifity / similar); English subline: crisp geometric sans (Montserrat/Poppins). Hierarchy: headline 2–3× subline weight. Colors sampled directly from the image palette (festive gold for Eid, crimson-green for patriotic, pastel blush for spring, clinical teal-white for pharma)—never clash with the hero. Legibility: soft outer glow or subtle drop shadow only on busy areas; generous line spacing; 2–4 overlay lines max. Alignment follows layout type (centered tribute vs. left-stack greeting). Copy is FOR POST-PRODUCTION OVERLAY ONLY—never render glyphs in the image.',

    'posterDirective' => 'Unique poster look per occasion (folk bright, patriotic night glow, patriotic graphic, pharma premium, etc.) with shared rules: landscape 16:9 widescreen ONLY, deliberate negative space for multi-line Bengali headline + English sub-copy added in post only, extraordinary typography matched to palette and mood, NO text or logos baked into the image.',

    'promptExcellenceLock' => 'Follow visual_direction style family exactly. Each occasion has its own visual DNA—do NOT default every event to the same Eid night city or generic festival template. Match emotional tone, palette, and hero symbols to the specific occasion identity.',

    'compositionTypeA' => 'Layout Type A (tribute, 16:9): Hero monument/symbol dominates center frame; lower ~30% smooth dark empty gradient band engineered for bold Bengali headline stack and English subline—typography colors echo image accents.',

    'compositionTypeB' => 'Layout Type B (greeting, 16:9): Occasion artwork in center frame; left or lower ~25% clean zone for headline stack; opposite edge relatively uncluttered for footer line—typography hierarchy mirrors festive or corporate mood.',

    'negativePromptBase' => 'text, letters, words, typography, Bengali script, English alphabet, numbers, watermark, logo, brand mark, URL, QR code, caption, subtitle, UI overlay, floating text, misspelled text, amateur layout, clipart, stock watermark, crowded composition, illegible glyphs',

    'typographyByStyleFamily' => [
        'folk_bright'           => 'Bold folkloric Bangla display; warm red-green-gold from mask pigments; playful rhythmic line breaks.',
        'patriotic_night_glow'  => 'Solemn white + crimson headline on dark navy band; dignified condensed sans subline; respectful spacing.',
        'patriotic_graphic'     => 'Clean white Bangla on blue gradient; green-red accent on key word; modern patriotic sans hierarchy.',
        'monument_dawn'         => 'Soft marigold-gold Bangla on purple dawn band; poetic verse spacing; reverent not loud.',
        'festive_night_vector'  => 'Warm gold Bangla on navy; friendly rounded sans English; fairy-light sparkle echoed in text glow.',
        'cultural_burst'        => 'Explosive magenta-cyan-yellow headline sampled from gulal/diya palette; energetic stacked lines.',
        'spring_pastel'         => 'Airy blush-yellow Bangla; light sans subline; generous whitespace; spring optimism.',
        'islamic_serenity'      => 'Elegant gold-cream Arabic-influenced geometry in layout; peaceful indigo band; refined serif-sans pairing.',
        'neon_celebration'      => 'Neon gold-magenta headline with subtle glow; midnight party energy; tight modern sans footer.',
        'sports_broadcast'      => 'Bold white block headline like premium TV sports graphics; gold accent echoes trophy; footer hosts+dates in clean sans—official broadcast hierarchy.',
        'warm_family'           => 'Soft rose-amber headline; intimate script-feel subline; emotional warmth without clutter.',
        'nature_eco'            => 'Fresh emerald-green headline on clean white/cream band; hopeful sans; eco-trust clarity.',
        'monsoon_cozy'          => 'Cool blue-green muted headline; nostalgic calm; soft shadow for rain-textured backgrounds.',
        'beach_summer'          => 'Turquoise-sand gradient text zone; travel-magazine bold headline; sun-kissed accent.',
        'labour_bold'           => 'Constructivist red-yellow-black headline; strong industrial sans; angular alignment.',
        'tourism_vintage'       => 'Vintage passport-stamp aesthetic subline; pastel landmark palette; chic wanderlust hierarchy.',
        'academic_nostalgic'    => 'Deep emerald intellectual headline; classic bookish serif-sans pairing; calm academic dignity.',
        'halloween_playful'     => 'Playful purple-orange display; cute not horror; rounded friendly letterforms.',
        'sale_impact'           => 'High-contrast yellow-black or neon offer headline; urgent retail skew; maximum legibility.',
        'harvest_warm'          => 'Rustic amber-cream headline; harvest warmth; cozy serif accent optional.',
        'christmas_cozy'        => 'Snow-white + crimson-green festive headline; warm fairy-light glow on text zone.',
        'mourning_solemn'       => 'Muted grey-white restrained headline; zero celebration colors; solemn spacing.',
        'divine_indigo'         => 'Luminous gold-cream on indigo; spiritual symmetry; peaceful devotional hierarchy.',
        'women_empower'         => 'Empowering purple-blush headline; modern minimal sans; unity and strength.',
        'modern_celebration'    => 'Occasion-specific palette headline; balanced Swiss-grid typography; premium commercial polish.',
        'pharma_premium'        => 'Clinical teal-white band; trustworthy navy headline; Swiss-grid pharma luxury; Fortune-500 polish.',
        'pharma_trust'          => 'Healing emerald-navy headline; caring professional sans; warm credible tone.',
        'pharma_wellness'       => 'Optimistic morning-green headline; family-wellness warmth; magazine-cover clarity.',
        'pharma_awareness'      => 'Cause-ribbon color accent in headline; dignified emotional sans; spotlight clarity.',
        'pharma_lab'            => 'Futuristic teal-gold on dark navy; science-credible geometric sans.',
        'corporate_summit'      => 'Executive navy-gold headline; wide bilingual CEO message zone; glass-tower prestige.',
        'industrial_power'      => 'Safety orange-steel headline; bold industrial sans; momentum and strength.',
        'retail_mega'           => 'Explosive offer headline; brand magenta-cyan or gold-black; retail urgency.',
        'real_estate_luxe'      => 'Prestige deep-blue gold headline; aerial skyline echo; project-name lower band.',
        'telecom_future'        => 'Electric blue-magenta tech headline; 5G sleek sans; connectivity energy.',
        'textile_craft'         => 'Heritage maroon-gold jamdani-inspired headline; artisan luxury spacing.',
        'agro_harvest'          => 'Sunrise green-gold headline; farmer optimism; field-toned palette match.',
    ],
];
