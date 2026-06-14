<x-app-layout>
    {{-- =======================================================
         MAIN WORKSPACE (Powered by Alpine.data script below)
         ======================================================= --}}
    <div class="p-4 sm:p-6 lg:p-8 max-w-4xl mx-auto space-y-6 antialiased selection:bg-pink-500/30" x-data="occasionStudio()">
        
        {{-- HEADER --}}
        <div class="flex items-center justify-between border-b border-white/10 pb-5">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-500 tracking-[0.2em] uppercase">
                    New Occasion Campaign
                </h1>
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1.5 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-pink-500 shadow-[0_0_8px_#ec4899] animate-pulse"></span>
                    Cinematic AI Pipeline
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" @click="openAutoFillModal = true" class="px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white rounded-lg text-[10px] font-black uppercase tracking-[0.15em] shadow-[0_0_15px_rgba(99,102,241,0.4)] transition-all flex items-center gap-2">
                    ✨ AI Auto-Fill
                </button>
                <a href="{{ route('occasions.index') }}" class="px-5 py-2.5 bg-white/5 hover:bg-white/10 text-white rounded-lg text-[10px] font-black uppercase tracking-[0.15em] transition-all border border-white/10">
                    Back
                </a>
            </div>
        </div>

        {{-- ERROR ALERTS --}}
        @if($errors->any())
            <div class="px-5 py-3 bg-red-500/10 border border-red-500/20 text-red-400 text-[10px] font-black uppercase tracking-widest rounded-lg">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(empty($canStartPipeline))
            <div class="px-5 py-3 bg-amber-500/10 border border-amber-500/20 text-amber-300 text-[10px] font-black uppercase tracking-widest rounded-lg">
                {{ $pipelineBlockMessage ?? 'Insufficient Prompt Credits. The masterpiece pipeline is unavailable until you refill credits or activate a plan.' }}
                @can('view_billing')
                    <a href="{{ route('billing.index') }}" class="block mt-2 text-pink-400 hover:text-pink-300 normal-case tracking-normal font-bold underline">View subscription &amp; refill credits →</a>
                @endcan
            </div>
        @endif

        {{-- FORM --}}
        <div class="bg-[#0a0a0a] border border-white/5 rounded-xl shadow-2xl relative">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-pink-600 to-purple-500 rounded-t-xl"></div>
            
            <div class="p-6 md:p-8">
                <form action="{{ route('occasions.store') }}" method="POST" class="space-y-7"
                    @submit="if (!canStartPipeline) { $event.preventDefault(); return; } isSubmitting = true">
                    @csrf
                    
                    {{-- MASTER PAYLOAD: Forces the Negative Space Protocol --}}
                    <input type="hidden" name="custom_text_payload" :value="generatePayloadText">
                    
                    {{-- 1. TIMELINE ROW --}}
                    <div class="grid grid-cols-2 gap-5 pb-5 border-b border-white/5">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Target Month</label>
                            <select name="target_month" x-model="month" class="w-full bg-[#111] border border-white/10 rounded-lg p-3.5 text-white text-xs focus:border-pink-500 outline-none font-bold cursor-pointer transition-colors hover:border-white/30">
                                @foreach(range(1, 12) as $m)
                                    <option value="{{ $m }}">{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Target Year</label>
                            <select name="target_year" x-model="year" class="w-full bg-[#111] border border-white/10 rounded-lg p-3.5 text-white text-xs focus:border-pink-500 outline-none font-bold cursor-pointer transition-colors hover:border-white/30">
                                <option value="{{ date('Y') }}">{{ date('Y') }}</option>
                                <option value="{{ date('Y') + 1 }}">{{ date('Y') + 1 }}</option>
                                <option value="{{ date('Y') + 2 }}">{{ date('Y') + 2 }}</option>
                            </select>
                        </div>
                    </div>

                    {{-- 2. OCCASION IDENTITY --}}
                    <div>
                        <div class="flex items-center justify-between mb-2 relative">
                            <label class="flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                Occasion Identity
                            </label>
                            
                            <div class="relative z-[120]">
                                <button type="button"
                                        @click="openOccasion = !openOccasion; openTheme = false; openText = false;"
                                        class="text-[9px] font-black uppercase tracking-widest text-pink-400 hover:text-pink-300 bg-pink-500/10 px-3 py-1.5 rounded border border-pink-500/20 transition-all flex items-center gap-1.5">
                                    💡 Load <span x-text="currentMonthName"></span> Events
                                    <svg class="w-3 h-3 transition-transform" :class="openOccasion ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                <div x-show="openOccasion" @click.away="openOccasion = false" x-cloak
                                     class="absolute right-0 mt-1 w-[min(20rem,calc(100vw-2rem))] bg-[#111] border border-white/10 rounded-lg shadow-2xl overflow-hidden max-h-72 overflow-y-auto custom-scrollbar">
                                    <template x-for="item in availableOccasions" :key="item">
                                        <button type="button" @click="occasion = item; openOccasion = false;" class="w-full text-left px-4 py-3 text-[10px] text-gray-300 hover:bg-pink-500/20 hover:text-white border-b border-white/5 transition-colors block">
                                            <span x-text="item"></span>
                                        </button>
                                    </template>
                                    <div x-show="availableOccasions.length === 0" class="p-3 text-[10px] text-gray-500 text-center italic">No presets for this month. Type below.</div>
                                </div>
                            </div>
                        </div>
                        <input type="text" name="occasion_identity" required x-model="occasion" :placeholder="placeholderOccasion" class="w-full bg-black border border-white/10 rounded-lg p-3.5 text-white text-xs focus:border-pink-500 outline-none transition-all placeholder-gray-700 font-bold">
                    </div>

                    {{-- 3. VISUAL DIRECTION (DIRECTOR'S CUT) --}}
                    <div :class="{'opacity-50 grayscale': !occasion && !visual_direction}" class="transition-all duration-300">
                        <div class="flex items-center justify-between mb-2 relative">
                            <label class="flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                Master Visual Direction (3 Themes)
                            </label>
                            
                            <div class="relative">
                                <button type="button" @click="openTheme = !openTheme; openOccasion = false; openText = false;" class="text-[9px] font-black uppercase tracking-widest text-emerald-400 hover:text-emerald-300 bg-emerald-500/10 px-3 py-1.5 rounded border border-emerald-500/20 transition-all flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed" :disabled="!occasion">
                                    🎬 View Themes
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                <div x-show="openTheme" @click.away="openTheme = false" x-cloak class="absolute right-0 mt-1 w-[400px] bg-[#111] border border-white/10 rounded-lg shadow-2xl z-50 overflow-hidden max-h-[450px] overflow-y-auto custom-scrollbar z-[100]">
                                    <template x-for="item in availableThemes" :key="item">
                                        <button type="button" @click="visual_direction = item; openTheme = false;" class="w-full text-left px-5 py-4 text-[10px] text-gray-300 hover:bg-emerald-500/20 hover:text-white border-b border-white/5 transition-colors block leading-relaxed">
                                            <span x-text="item"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <textarea name="visual_direction" required x-model="visual_direction" rows="7" :placeholder="placeholderTheme" class="w-full bg-black border border-white/10 rounded-lg p-4 text-white text-xs focus:border-emerald-500 outline-none transition-all placeholder-gray-700 leading-relaxed"></textarea>
                    </div>

                    {{-- 4. CUSTOM MARKETING TEXT (WILL BE APPLIED IN POST-PRODUCTION) --}}
                    <div :class="{'opacity-50 grayscale': !occasion && !custom_text}" class="transition-all duration-300">
                        <div class="flex items-center justify-between mb-2 relative">
                            <label class="flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                Poster Copy (Overlay Later) — Bangla & English
                                <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 rounded text-[8px] tracking-widest border border-blue-500/30">POST-RENDER</span>
                            </label>
                            
                            <div class="relative">
                                <button type="button" @click="openText = !openText; openOccasion = false; openTheme = false;" class="text-[9px] font-black uppercase tracking-widest text-blue-400 hover:text-blue-300 bg-blue-500/10 px-3 py-1.5 rounded border border-blue-500/20 transition-all flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed" :disabled="!occasion">
                                    📝 View Text Suggestions
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                <div x-show="openText" @click.away="openText = false" x-cloak class="absolute right-0 mt-1 w-72 bg-[#111] border border-white/10 rounded-lg shadow-2xl z-50 overflow-hidden max-h-64 overflow-y-auto custom-scrollbar z-[100]">
                                    <template x-for="item in availableTexts" :key="item">
                                        <button type="button" @click="applyTextSuggestion(item)" class="w-full text-left px-4 py-3 text-[10px] text-gray-300 hover:bg-blue-500/20 hover:text-white border-b border-white/5 transition-colors block">
                                            <span x-text="textSuggestionLabel(item)"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <textarea name="custom_text" x-model="custom_text" rows="3" :placeholder="placeholderText" class="w-full bg-black border border-white/10 rounded-lg p-3.5 text-white text-xs focus:border-blue-500 outline-none transition-all placeholder-gray-700 font-bold leading-relaxed resize-y min-h-[4.5rem]"></textarea>
                    </div>

                    {{-- SUBMIT BUTTON --}}
                    <div class="pt-4 border-t border-white/5">
                        <button type="submit" :disabled="isSubmitting || !canStartPipeline"
                            class="w-full py-4 bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-500 hover:to-purple-500 disabled:opacity-60 disabled:cursor-not-allowed text-white text-xs font-black rounded-lg uppercase tracking-[0.2em] shadow-[0_0_20px_rgba(236,72,153,0.3)] transition-all">
                            <span x-show="!isSubmitting && canStartPipeline">Generate Masterpiece Pipeline</span>
                            <span x-show="!isSubmitting && !canStartPipeline" x-cloak>No Prompt Credits</span>
                            <span x-show="isSubmitting" x-cloak>Starting pipeline…</span>
                        </button>
                        
                        <p class="text-center text-[9px] text-gray-500 uppercase tracking-widest font-bold mt-3">
                            @if(isset($wallet->is_admin) && $wallet->is_admin)
                                <span class="text-emerald-400">Admin Mode Active: Consumes 0 Credits</span>
                            @elseif(!empty($canStartPipeline))
                                Consumes 1 Prompt Credit
                            @else
                                <span class="text-amber-400">Masterpiece pipeline unavailable — 0 prompt credits</span>
                            @endif
                        </p>
                    </div>
                </form>
            </div>
        </div>
        
        {{-- AI AUTO-FILL MODAL (fixed overlay — no x-teleport for deploy compatibility) --}}
            <div x-show="openAutoFillModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/95 backdrop-blur-md" x-cloak>
                <div class="bg-[#0f0f0f] border border-white/10 w-full max-w-lg rounded-xl shadow-2xl relative" @click.away="!isAutoFilling ? openAutoFillModal = false : null">
                    
                    {{-- Header --}}
                    <div class="px-6 py-4 flex justify-between items-center border-b border-white/5">
                        <h3 class="text-[11px] font-black text-indigo-400 uppercase tracking-[0.25em] flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            AI Auto-Fill Engine
                        </h3>
                        <button @click="openAutoFillModal = false" :disabled="isAutoFilling" class="text-gray-500 hover:text-white transition-colors disabled:opacity-50">✕</button>
                    </div>

                    {{-- Body --}}
                    <div class="p-6">
                        <p class="text-xs text-gray-400 mb-4 font-bold">150+ presets — Bangladeshi national days, South Asian festivals, global holidays, Islamic occasions, pharma, retail &amp; corporate campaigns. Pick one or type a custom occasion.</p>
                        
                        {{-- Searchable / Writeable Combobox Input field --}}
                        <div class="relative" @click.away="dropdownOpen = false">
                            <div class="flex items-center bg-black border border-white/10 rounded-lg overflow-hidden group focus-within:border-indigo-500 transition-all mb-1">
                                <input type="text" x-model="autoFillInput" @input="dropdownOpen = true" @click="dropdownOpen = true" @keydown.enter.prevent="triggerAutoFill()" :disabled="isAutoFilling" placeholder="Search preset or type custom occasion..." class="w-full bg-transparent border-none p-4 text-white text-sm focus:ring-0 placeholder-gray-700 font-bold disabled:opacity-50">
                                <button type="button" @click="dropdownOpen = !dropdownOpen" :disabled="isAutoFilling" class="p-4 text-gray-500 hover:text-gray-300 transition-colors border-l border-white/5">
                                    <svg class="w-4 h-4 transform transition-transform" :class="{'rotate-180': dropdownOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                            </div>

                            {{-- Expanded Dynamic Combobox List --}}
                            <div x-show="dropdownOpen && filteredFestivals.length > 0" x-transition class="absolute left-0 right-0 mt-1 bg-[#121212] border border-white/10 rounded-lg shadow-2xl max-h-48 overflow-y-auto custom-scrollbar z-[1000]" x-cloak>
                                <template x-for="fest in filteredFestivals" :key="fest">
                                    <button type="button" @click="autoFillInput = fest; dropdownOpen = false;" class="w-full text-left px-4 py-3 text-xs text-gray-300 hover:bg-indigo-500/20 hover:text-white border-b border-white/[0.03] transition-colors block font-semibold">
                                        <span x-text="fest"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        
                        <div x-show="autoFillError" class="text-red-400 text-[10px] font-black uppercase tracking-widest mt-3 mb-1" x-text="autoFillError" x-cloak></div>

                        <button @click="triggerAutoFill()" :disabled="isAutoFilling || !autoFillInput" class="w-full py-3.5 mt-5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black rounded-lg uppercase tracking-[0.2em] shadow-[0_0_20px_rgba(79,70,229,0.3)] transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <template x-if="isAutoFilling">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </template>
                            <template x-if="!isAutoFilling">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                            </template>
                            <span x-text="isAutoFilling ? 'Synthesizing DNA...' : 'Generate Magic'"></span>
                        </button>
                    </div>
                </div>
            </div>

    </div>

    {{-- =======================================================
         ALPINE.JS DATA SCRIPT (THE ROBUST BRAIN)
         ======================================================= --}}
    <script>
        (function () {
            const registerOccasionStudio = () => {
            Alpine.data('occasionStudio', () => ({
                
                month: @js(old('target_month', date('n'))),
                year: @js(old('target_year', date('Y'))),
                occasion: @js(old('occasion_identity', '')),
                visual_direction: @js(old('visual_direction', '')),
                custom_text: @js(old('custom_text', '')),
                
                openOccasion: false,
                openTheme: false,
                openText: false,
                
                // --- AUTO-FILL COMBINED STATE ---
                openAutoFillModal: false,
                autoFillInput: '',
                isAutoFilling: false,
                isSubmitting: false,
                autoFillError: '',
                dropdownOpen: false,
                canStartPipeline: @js($canStartPipeline ?? false),
                pipelineBlockMessage: @js($pipelineBlockMessage ?? ''),

                monthsMap: {
                    1: 'January', 2: 'February', 3: 'March', 4: 'April', 
                    5: 'May', 6: 'June', 7: 'July', 8: 'August', 
                    9: 'September', 10: 'October', 11: 'November', 12: 'December'
                },

                masterFestivals: @js($masterFestivals ?? []),
                occasionsMap: @js($occasionsMap ?? []),

                themesMap: {
                    /* ================= JANUARY ================= */
                    'Happy New Year': [
                        '[REALISTIC] Cinematic IMAX Masterpiece: Directed like a high-budget sci-fi epic. A futuristic metropolis at midnight. Dazzling neon gold and magenta fireworks reflecting off towering obsidian glass skyscrapers. Shot on ARRI Alexa 65. Volumetric atmospheric fog.',
                        '[GRAPHICAL] High-End Swiss Vector: Award-winning graphic design poster. Minimalist, sharp silhouettes toasting champagne under mathematically perfect, stylized geometric fireworks. Bold midnight blue and hyper-vibrant neon pink gradients.',
                        '[3D STYLIZED] Unreal Engine 5 Neon Pop: A breathtaking commercial asset. Elegantly proportioned, hyper-glossy inflated balloons floating in a pristine cyber-pop studio. Illuminated by multi-colored neon rim lights and golden confetti.'
                    ],
                    'Makar Sankranti': [
                        '[REALISTIC] Golden Harvest Cinema: Breathtaking golden-hour photography. Hundreds of colorful, intricate paper kites soaring in a brilliant azure sky over a lush, vibrant green mustard field. Sunbeams catching the fine strings. Flawless 8K resolution.',
                        '[GRAPHICAL] Vibrant Geometric Kites: A sleek, highly dynamic flat vector illustration. Stylized, overlapping diamond-shaped kites in explosive yellows, reds, and oranges against a clean, minimal sky-blue background.',
                        '[3D STYLIZED] Isometric Sweet Studio: A cute, ultra-premium 3D render. Elegantly scaled traditional sesame and jaggery sweets (Til Ladoo) arranged around a stylized golden kite on a warm, soft-orange matte pedestal. Flawless global illumination.'
                    ],
                    'Chinese New Year': [
                        '[REALISTIC] Majestic Red Cinema: Hyper-realistic 8K photography. Intricate, glowing red paper lanterns hanging in a grand, ancient wooden temple. Soft snow falling gently outside. Cinematic red ambient lighting and golden reflections.',
                        '[GRAPHICAL] Dynamic Dragon Vector: A world-class flat graphic poster. A highly stylized, flowing golden dragon on a rich, crimson red background. Minimalist cloud motifs, clean layout, exuding premium cultural luxury.',
                        '[3D STYLIZED] Glossy Gold Studio: A high-fashion 3D render. A hyper-glossy, adorable 3D zodiac animal figurine sitting among incredibly realistic 3D gold coins and red silk envelopes. Crisp studio box lighting.'
                    ],

                    /* ================= FEBRUARY ================= */
                    'Valentine\'s Day': [
                        '[REALISTIC] Cinematic Macro Romance: Directed with extreme emotional intimacy. A hyper-realistic close-up of a flawless red Baccara rose resting on crushed black velvet. Warm, flickering candlelight casts romantic shadows. 100mm macro lens.',
                        '[GRAPHICAL] Lo-Fi Digital Masterpiece: A heartwarming, flawlessly illustrated digital painting. A beautifully drawn coffee cup with latte art in the shape of a heart, resting on a pastel desk. Soft pastel pink and warm orange color palette.',
                        '[3D STYLIZED] Premium Abstract Geometry: A world-class 3D motion-design still. Matte-finished pastel pink and deep red hearts floating gracefully around a central pristine white podium. Soft box studio lighting.'
                    ],
                    'International Mother Language Day': [
                        '[POSTER] Language Martyrs tribute: Shaheed Minar centered at dawn/dusk, moody sky, marigolds on steps optional. Lower third dark empty band for Bengali verse overlay. NO text, NO logos, NO alphabet glyphs in image.',
                        '[REALISTIC] Epic Dawn Tribute: Cinematic wide-shot of Shaheed Minar, soft purple sky, marigolds on steps. Volumetric god-rays. Clean lower edge for typography.',
                        '[GRAPHICAL] Constructivist Heritage Vector: Stark Shaheed Minar perspective, red sun accent. Flawless negative space lower third for text.',
                        '[3D STYLIZED] Deep Silhouette Studio: Layered silhouettes merging into Shaheed Minar. Deep blue gradients. NO rendered script in image—empty zones for post-production Bengali.'
                    ],
                    'Spring Festival (Bosonto)': [
                        '[REALISTIC] Vibrant Cultural Portrait: High-end lifestyle photography. A breathtaking close-up of beautiful, fresh yellow and orange marigold garlands resting on vibrant yellow traditional silk fabric. Bright, cheerful morning sunlight.',
                        '[GRAPHICAL] Festive Flat Illustration: A dynamic, highly colorful digital artwork. Stylized flying birds and blooming spring flowers in vibrant shades of yellow, orange, and red. Clean lines, joyful mood.',
                        '[3D STYLIZED] Golden Floral Studio: A hyper-polished 3D commercial render. Giant, abstract, perfectly smooth 3D marigold petals floating around a glowing central sphere. Lit with warm, soft-box studio lighting.'
                    ],

                    /* ================= MARCH ================= */
                    'Independence Day': [
                        '[POSTER] Monument night tribute: National Martyrs\' Memorial centered on deep blue starry sky, soft cyan rim glow on concrete tiers, ground lights at base. Lower third (30%) smooth dark gradient band completely empty for Bengali typography. Landscape 16:9. NO text, NO logos.',
                        '[REALISTIC] Glorious Cinematic Sunrise: A breathtaking shot of the National Martyrs\' Monument at dawn. Blazing golden sunlight pierces through thick mist, creating intense volumetric rays over a lush green landscape. Lower edge kept dark and uncluttered for text overlay.',
                        '[GRAPHICAL] Dynamic Patriotism: A highly energetic, modern vector illustration. Sweeping green and red fluid strokes. Heroic aesthetic with clean empty lower third for post-production text. NO rendered typography.',
                        '[3D STYLIZED] Ultra-Polished Studio Still: A high-end 3D glossy commercial render. Red and green silk ribbon on dark studio pedestal. Maximum negative space below for overlay text.'
                    ],
                    'International Women\'s Day': [
                        '[REALISTIC] Empowering Portraiture: A highly professional, cinematic photography shot. A subtle, elegant arrangement of purple tulips resting on a clean marble desk next to a sleek modern laptop. Bright, optimistic natural lighting.',
                        '[GRAPHICAL] Diverse Harmony Illustration: A beautiful, modern minimalist flat-art graphic. Abstract, flowing silhouettes of diverse women standing together in unity. A gorgeous color palette of soft purples, blush pinks, and gold.',
                        '[3D STYLIZED] Abstract Strength: A world-class 3D abstract concept. Smooth, interwoven glossy 3D ribbons in various shades of violet and magenta, forming a continuous infinity loop on a soft white pedestal.'
                    ],
                    'Holi Festival': [
                        '[REALISTIC] High-Speed Pigment Splash: Award-winning action photography. An explosive cloud of ultra-vibrant magenta, cyan, and yellow Gulal powder frozen in mid-air in front of a dark, dramatic background. Extreme 8K clarity.',
                        '[GRAPHICAL] Minimalist Color Burst: A premium vector illustration. A pristine white canvas with incredibly clean, dynamic paint splatters in vivid primary colors. High contrast, modern.',
                        '[3D STYLIZED] Liquid Paint Studio: A flawless 3D fluid simulation still. Glossy, smooth ribbons of brightly colored 3D liquid paint wrapping around a sleek, glowing white sphere. Perfectly lit, visually bursting with energy.'
                    ],
                    'Ramadan Kareem': [
                        '[REALISTIC] Architectural Serenity: An ultra-realistic cinematic interior. A glowing, carved brass lantern sits on a rich hand-woven Persian rug. Soft, cool moonlight filters through a grand arched window, casting geometric shadows.',
                        '[GRAPHICAL] Modern Premium Arabesque: A beautiful, sleek flat-vector graphic. Elegant minarets and a crisp crescent moon against a deep, starry indigo night sky. Accented with subtle golden geometric mandala patterns.',
                        '[3D STYLIZED] Magical Golden Illumination: A masterful 3D studio scene. An intelligently scaled, softly glowing golden crescent moon hovering above a pristine, dark emerald marble pedestal. Subtle floating particles of light.'
                    ],

                    /* ================= APRIL ================= */
                    'Pohela Boishakh': [
                        '[REALISTIC] Hyper-Vibrant Cultural Macro: An incredibly colorful, extreme high-resolution shot. A traditional painted clay pot overflowing with fresh yellow marigolds. Intricate, wet white Alpona paint on a terracotta floor. Joyful morning sunlight.',
                        '[GRAPHICAL] Festive Parade Masterpiece: A flawless, flat-color digital illustration. Highly stylized, bold, dynamic depictions of traditional Mangal Shobhajatra owl and tiger masks. Colorful triangular bunting flags hang above. Pristine, off-white background leaving massive negative space in the center.',
                        '[3D STYLIZED] Isometric Festival Diorama: A highly attractive stylized 3D isometric scene. A vibrant rural Bengali courtyard featuring a lush Banyan tree, clay pots, and colorful festive decorations. Soft clay-like textures.'
                    ],
                    'Eid ul-Fitr': [
                        '[POSTER] Eid night greeting scene: Deep indigo sky, large glowing crescent and stars, warm fairy lights on trees, soft city silhouette. Top 30% and bottom 18% kept clean for Bengali headline and logo. Mid-ground festive mood only. NO Bengali text, NO URLs, NO logos.',
                        '[REALISTIC] Luxurious Sunset Mosques: Silhouetted mosque domes against golden-orange sunset and radiant crescent. Flawless negative space top-center for headline overlay.',
                        '[GRAPHICAL] Sleek Minimalist Twilight: Geometric night sky, stylized glowing crescent. Deep teal and gold. Clean zones for post-production typography.',
                        '[3D STYLIZED] Elegant Teal & Gold Studio: Golden crescent with teal cut-outs, silk and lantern on soft beige. Uncluttered lower corner for branding.'
                    ],
                    'Easter Sunday': [
                        '[REALISTIC] Springtime Elegance: Cinematic lifestyle photography. A beautiful woven wicker basket filled with delicately painted, pastel-colored eggs resting on fresh, morning-dew covered green grass. Soft, divine sunlight piercing the background.',
                        '[GRAPHICAL] Clean Pastel Minimalist: A world-class flat vector illustration. A single, perfectly smooth, stylized egg featuring elegant geometric patterns, centered on a soft mint-green background. Maximum negative space.',
                        '[3D STYLIZED] Glossy Chocolate Studio: A mouth-watering 3D macro render. A hyper-realistic, glossy 3D chocolate bunny wrapped partially in luxurious gold foil. Soft studio lighting highlighting the rich, delicious textures.'
                    ],
                    'Earth Day': [
                        '[REALISTIC] Breathtaking Nature Cinema: National Geographic style landscape photography. A solitary, ancient, vibrant green tree growing out of perfectly clear, mirror-like water, reflecting an epic, majestic cloudy sky. Infinite depth.',
                        '[GRAPHICAL] Eco-Minimalist Vector: A highly impactful, clean environmental poster. A stylized globe formed entirely from interwoven, lush green leaves and vines. Sharp, crisp lines on an off-white recycled-paper textured background.',
                        '[3D STYLIZED] Floating Terrarium: A beautiful 3D concept render. A pristine, glowing glass sphere containing a miniature, hyper-detailed luxury ecosystem. Floating against a clean dark studio background.'
                    ],

                    /* ================= MAY ================= */
                    'Labour Day': [
                        '[REALISTIC] Gritty Heroic Cinema: An incredibly dynamic, hyper-detailed cinematic shot of a metalworker\'s grinder. Brilliant, glowing golden sparks fly in super slow-motion against a pitch-black, moody industrial background.',
                        '[GRAPHICAL] Bold Constructivist Poster: An award-winning graphic illustration inspired by vintage labor movements. Strong, aggressive geometric shapes, featuring a stylized silhouette of a worker holding a wrench. Red, yellow, and black.',
                        '[3D STYLIZED] Flawless Industrial Render: A sleek, macro 3D masterpiece of interlocking, brushed steel gears perfectly catching a cool, blue studio light. Hyper-realistic metallic textures. Rendered in Unreal Engine 5.'
                    ],
                    'Mother\'s Day': [
                        '[REALISTIC] Tender Cinematic Morning: A breathtaking, emotionally resonant photography shot. Soft, heavenly morning light streams through sheer white curtains, illuminating a crystal vase filled with dew-covered pink peonies.',
                        '[GRAPHICAL] Expressive Digital Painting: A beautiful, emotionally charged digital art poster. A soft-focus, stylized portrait of a mother embracing her child, surrounded by floating, delicate floral elements. Warm golden-hour colors.',
                        '[3D STYLIZED] Masterful Floral Typography: A stunning, premium 3D graphic design. Beautifully proportioned, lush, hyper-vibrant 3D flowers and green vines framing a clean, bright pastel central area.'
                    ],
                    'Buddha Purnima': [
                        '[REALISTIC] Divine Monastic Peace: A hyper-realistic cinematic shot. Hundreds of glowing oil lamps floating gently on a calm, dark river at twilight. The soft, warm orange glow reflects flawlessly on the ripples. Deep spiritual tranquility.',
                        '[GRAPHICAL] Golden Lotus Vector: A highly sophisticated, minimalist digital artwork. A beautifully symmetrical, stylized golden lotus flower blooming beneath a massive, glowing full moon. Deep indigo background, peaceful geometry.',
                        '[3D STYLIZED] Zen Garden Render: A premium 3D architectural render. A perfectly smooth, stylized stone Buddha statue sitting beside an elegant 3D bonsai tree and raked zen sand. Soft, diffused overhead skylight.'
                    ],

                    /* ================= JUNE ================= */
                    'Father\'s Day': [
                        '[REALISTIC] Warm Lifestyle Cinema: A deeply heartwarming cinematic photograph. A sturdy, vintage leather armchair sitting next to a softly glowing reading lamp. A classic analog watch rests on the side table. Rich amber tones.',
                        '[GRAPHICAL] Heroic Minimalist Art: A highly stylized, clean graphic illustration. A large silhouette of a father walking and holding the hand of a small child, set against a massive, warm golden setting sun.',
                        '[3D STYLIZED] Premium Workspace Render: A sleek 3D studio design. A flawlessly rendered classic fountain pen resting on a dark wooden desk beside a beautiful cup of black coffee. Sharp, dramatic studio lighting.'
                    ],
                    'Eid ul-Adha': [
                        '[POSTER] Eid ul-Adha illustration poster: Night blue sky, crescent moon and stars, warm string lights on trees, distant lit city skyline. Foreground: stylized families in traditional dress with white goats (flat, clean commercial illustration). Top-center and bottom-right empty for headline and logo. NO Bengali script, NO URLs, NO watermarks.',
                        '[REALISTIC] Divine Elegance Cinema: Golden crescent and lantern on deep maroon velvet. Cinematic god-rays. Upper area clean for typography.',
                        '[GRAPHICAL] Premium Minimalist Line Art: Gold line-art crescent and mosque dome on emerald green. Generous negative space for overlay text.',
                        '[3D STYLIZED] Premium Pastel Studio: Stylized ram, lanterns and stars on warm beige. Clay textures. Corner kept empty for branding.'
                    ],
                    'World Environment Day': [
                        '[REALISTIC] Pristine Rainforest Macro: High-end BBC nature documentary style. Extreme macro shot of a single, flawless green fern leaf covered in sparkling morning dew. Dark, moody, lush jungle background out of focus.',
                        '[GRAPHICAL] Hand-Drawn Ecology: A beautiful, modern corporate vector design. Clean, stylized illustrations of wind turbines, green leaves, and solar panels integrated into a single cohesive, bright geometric badge.',
                        '[3D STYLIZED] Miniature Eco-City: An incredible 3D isometric masterpiece. A highly detailed miniature futuristic city where skyscrapers are overgrown with lush, stylized 3D foliage and glowing blue rivers flow between them.'
                    ],

                    /* ================= JULY ================= */
                    'Monsoon Festival': [
                        '[REALISTIC] Cinematic Rain Drop Macro: A breathtaking National Geographic macro shot. A perfect, crystal-clear raindrop splashing onto a vibrant green leaf in super slow-motion, creating a flawless water crown. Deep blue and green tones.',
                        '[GRAPHICAL] Trending Lo-Fi Rain: A cozy, beautifully illustrated digital 2D poster. Looking out a rain-streaked window at a lush green landscape, with a steaming cup of tea on the windowsill. Soft, muted, nostalgic colors.',
                        '[3D STYLIZED] Explosive Splash Art: A highly dynamic 3D stylized render. Bright yellow and red umbrellas floating in mid-air amidst stylized, glossy, frozen blue water splashes. Playful, bursting with colorful energy.'
                    ],
                    'Summer Vibes': [
                        '[REALISTIC] Tropical Beach Cinema: An ultra-premium travel photography shot. Crystal clear, shimmering turquoise ocean waves gently lapping onto pristine white sand. A luxury woven sun hat resting on the beach under bright mid-day sunlight.',
                        '[GRAPHICAL] Retro Wave Illustration: A bold, trending 80s-inspired retro vector graphic. Bright neon pinks, deep purples, and vibrant cyan colors. A stylized setting sun over digital palm trees. Highly energetic.',
                        '[3D STYLIZED] Juicy Fruit Studio: A mouth-watering 3D commercial render. Slices of hyper-realistic, glowing watermelon and citrus fruits splashing through crystal-clear water in extreme slow motion. Perfect studio lighting.'
                    ],
                    'Islamic New Year': [
                        '[REALISTIC] Deep Desert Twilight: A breathtaking, highly realistic cinematic wide shot. A massive, glowing crescent moon rising over an endless, perfectly smooth sand dune in the desert at twilight. Incredible textures and deep indigo sky.',
                        '[GRAPHICAL] Elegant Geometric Star: A flawless, premium vector graphic. Complex, highly detailed Islamic star geometry woven into a minimalist, clean, flat-color background in shades of deep blue, white, and soft gold.',
                        '[3D STYLIZED] Glowing Archway: A masterful 3D architectural visualization. A stylized, perfectly symmetrical Moorish archway made of white glowing marble, floating over a mirrored floor reflecting a starry night sky.'
                    ],

                    /* ================= AUGUST ================= */
                    'National Mourning Day': [
                        '[REALISTIC] Somber Cinematic Masterpiece: An incredibly powerful black-and-white photography shot. A single, hyper-detailed, drooping black ribbon draped over a stark, pure white marble pillar. Intense, high-contrast chiaroscuro lighting.',
                        '[GRAPHICAL] Stark Silhouette Design: A deeply respectful, minimalist digital poster. A stark, dark silhouette of the Bangabandhu portrait against a softly glowing, muted grey and black gradient background.',
                        '[3D STYLIZED] Eternal Flame Render: A highly atmospheric, premium 3D render. A solitary, bright eternal flame burning intensely inside a dark, monumental stone memorial. The fire casts long, dancing orange shadows.'
                    ],
                    'Friendship Day': [
                        '[REALISTIC] Golden Hour Nostalgia: A beautiful, cinematic lifestyle photograph. Two vintage Polaroid cameras resting on a wooden picnic table, bathed in intense, warm golden-hour sunset light. Deeply nostalgic, emotional.',
                        '[GRAPHICAL] Pop-Art Connection: A fun, highly energetic flat vector illustration. Two stylized hands giving a high-five, surrounded by colorful pop-art burst lines and stars. Vibrant primary colors (red, blue, yellow).',
                        '[3D STYLIZED] Cute Infinity Loop: A flawless, premium 3D abstract render. Two interlocking, glossy rings—one pastel pink, one pastel blue—floating gracefully in a clean white studio. Perfect soft-box lighting.'
                    ],
                    'Janmashtami': [
                        '[REALISTIC] Flute & Feather Macro: A breathtaking, hyper-realistic close-up. A highly polished, antique bamboo flute resting beside a vibrant, iridescent peacock feather. A beautifully decorated, glowing clay pot of butter in the background.',
                        '[GRAPHICAL] Divine Silhouette Vector: A clean, vibrant digital art poster. A highly stylized silhouette of Lord Krishna playing the flute under a massive, glowing full moon. Deep indigo sky, minimal aesthetic, premium finish.',
                        '[3D STYLIZED] Glowing Matki Studio: A world-class 3D render. An intelligently scaled, perfectly smooth 3D clay pot wrapped in colorful glowing neon ropes, floating in a dark studio. Vibrant, colorful, and hyper-modern.'
                    ],

                    /* ================= SEPTEMBER ================= */
                    'Autumn Festival (Kashful)': [
                        '[REALISTIC] Epic Landscape Cinema: A breathtaking, serene landscape photography masterpiece. Endless fields of tall, fluffy white Kashful swaying gently. Soft, warm golden afternoon sunlight. Pure, natural, poetic beauty. Clean landscape.',
                        '[GRAPHICAL] Dreamy Watercolor Poster: A beautiful, highly artistic watercolor illustration. Masterful, soft blending strokes depicting white autumn clouds and falling golden leaves in a pure sky. Dreamy, highly aesthetic.',
                        '[3D STYLIZED] Soft Pastel Clouds: A highly aesthetic, soft-rendered 3D promotional masterpiece. Fluffy, stylized 3D white autumn clouds floating elegantly against a pristine, soft blue backdrop. Flawless global illumination.'
                    ],
                    'World Tourism Day': [
                        '[REALISTIC] Aerial Horizon: A breathtaking, award-winning drone photograph. A solitary, tiny red hot air balloon drifting over an endless, hyper-detailed, lush green mountain valley wrapped in morning mist. Inspiring wanderlust.',
                        '[GRAPHICAL] Minimalist Vintage Passport: A highly trendy, flat-vector travel poster. Clean, stylized passport stamps, an airplane window outline, and soft pastel colors evoking a chic, modern travel agency advertisement.',
                        '[3D STYLIZED] Tiny Planet Render: A highly creative 3D diorama. A perfectly spherical, miniature 3D globe featuring tiny stylized famous landmarks popping out of it. Highly detailed, colorful.'
                    ],
                    'Teacher\'s Day': [
                        '[REALISTIC] Cinematic Vintage Desk: A highly emotive, nostalgic photography shot. A beautifully worn, antique globe resting next to a stack of classic hardbound books. A single, perfect red apple sits on top. Warm, soft cinematic lighting.',
                        '[GRAPHICAL] Academic Line Art: A clean, modern minimalist flat illustration. Continuous line art of an open book transitioning into a flying bird. Deep emerald green background with crisp white lines. Elegant and intellectual.',
                        '[3D STYLIZED] Glowing Chalkboard Studio: A creative 3D render. A hyper-realistic floating piece of chalk drawing glowing, neon geometric light-trails in mid-air in front of a dark, pristine slate background.'
                    ],

                    /* ================= OCTOBER ================= */
                    'Durga Puja': [
                        '[REALISTIC] Majestic Divine Cinema: An awe-inspiring, hyper-realistic photography shot of Goddess Durga\'s idol. Glowing with a divine, soft golden radiance against a dramatic, dark cinematic backdrop. Intricate jewelry details.',
                        '[GRAPHICAL] Explosive Festival Flat Art: A highly dynamic, colorful vector illustration. Stylized depictions of Dhak drums, burning Dhunuchi, and glowing diyas. Bold, explosive festive colors like bright crimson, deep orange, and gold.',
                        '[3D STYLIZED] Modern Low-Poly Pandal: A highly creative, modern 3D low-poly art style. A beautifully geometric, colorful Puja Pandal illuminated by soft, glowing virtual fairy lights. A fun, highly engaging visual masterpiece.'
                    ],
                    'Halloween': [
                        '[REALISTIC] Spooky Hollywood Cinema: A thrilling, hyper-realistic horror-aesthetic commercial shot. A menacingly carved, glowing Jack-o\'-lantern sitting on an aged wooden porch, enveloped by thick, rolling cinematic fog.',
                        '[GRAPHICAL] Playful Vector Magic: A playful, highly attractive flat illustration. Cute, stylized friendly ghosts, bats, and colorful candies floating on a vibrant purple background. Perfect graphic design for premium sale promotions.',
                        '[3D STYLIZED] Glossy Neon Cauldron: A flawless 3D rendered animation still. Glowing, translucent green potions bubbling out of an intelligently scaled witch\'s cauldron. Vibrant purple and neon green studio lighting. Octane Render.'
                    ],
                    'Diwali Festival': [
                        '[REALISTIC] Infinite Diyas: A jaw-dropping cinematic wide shot. Thousands of small, beautifully lit clay diyas (oil lamps) glowing warmly, arranged in perfect geometric symmetry across a vast, dark temple courtyard. 8K detail.',
                        '[GRAPHICAL] Neon Rangoli Vector: A vibrant, hyper-modern digital poster. A highly intricate, glowing neon mandala/rangoli pattern overlapping a deep black background. Explosive colors, perfectly symmetric, visually loud.',
                        '[3D STYLIZED] Sparkler Studio: An ultra-premium 3D motion render. A 3D golden sparkler suspended in mid-air, throwing off physically accurate, glowing 3D light sparks. Dark studio background, incredible physics and lighting.'
                    ],

                    /* ================= NOVEMBER ================= */
                    'Black Friday': [
                        '[REALISTIC] Luxury Stealth Cinema: A sleek, ultra-luxurious dark mode photography shot. Matte black geometric surfaces with sharp, glowing, metallic rose-gold and silver accents. Dramatic, highly focused studio lighting.',
                        '[GRAPHICAL] High-Impact Typographic Vector: A visually explosive, modern typography-driven vector illustration. Dynamic, skewed abstract geometric shapes in bold yellow and black. Fast-paced, impossible to scroll past.',
                        '[3D STYLIZED] Shattered Impact Render: A bold, hyper-realistic abstract 3D render. Obsidian glass shattering in super slow-motion, revealing a blindingly bright, glowing golden core underneath. Flawless physics simulation.'
                    ],
                    'Thanksgiving': [
                        '[REALISTIC] Warm Harvest Feast: An ultra-realistic, deeply comforting culinary photography shot. A beautifully arranged rustic wooden table featuring a glowing bounty of autumn harvest—pumpkins, corn, and warm candlelight. 50mm lens.',
                        '[GRAPHICAL] Autumn Leaf Flat Art: A highly aesthetic, clean graphic illustration. Stylized, perfectly symmetrical autumn leaves in rich oranges, deep reds, and gold falling against a creamy beige background. Warm, inviting.',
                        '[3D STYLIZED] Cozy Knit Studio: A macro 3D masterpiece. Incredibly realistic 3D textures of a chunky, warm orange knitted blanket draped beautifully beside a glowing, stylized 3D pumpkin. Soft, comforting global illumination.'
                    ],
                    'Singles Day (11.11)': [
                        '[REALISTIC] Cyberpunk Shopping: A high-fashion, neon-drenched photography shot. A futuristic, transparent shopping bag filled with glowing, high-tech luxury items against a blurred, rainy neon city backdrop. Blade Runner aesthetic.',
                        '[GRAPHICAL] Glitch Art Mega Sale: A highly trendy, youth-focused vector design. Bold 11.11 numbers rendered in colorful, modern digital glitch art, overlapping bright neon pink and cyan blocks. Supreme e-commerce style.',
                        '[3D STYLIZED] Floating Gift Boxes: An immaculate 3D studio render. Hundreds of sleek, matte-black gift boxes with glowing red ribbons floating in an anti-gravity chamber. Perfect studio reflections, visually hypnotic.'
                    ],

                    /* ================= DECEMBER ================= */
                    'Victory Day': [
                        '[POSTER] Victory Day night tribute: Jatiyo Smriti Soudho (National Martyrs\' Memorial) centered and dominant on deep blue starry night sky, bright cyan outline glow on monument edges, small white spherical lights at base, soft horizon glow. Lower third (30%) solid dark navy-black gradient completely empty for Bengali greeting lines. Landscape 16:9. NO text, NO logos, NO typography.',
                        '[REALISTIC] Glorious Architectural Cinema: National Memorial at blazing sunrise with volumetric golden rays. Dark uncluttered foreground band for text overlay.',
                        '[GRAPHICAL] Dynamic Patriotic Vector: Triumphant flag silhouettes on green and red sunburst. Clean lower third reserved for post-production Bengali copy.',
                        '[3D STYLIZED] Glowing Map Tribute: Polished metallic national map on dark pedestal with warm spotlighting. Space below for footer text.'
                    ],
                    'Merry Christmas': [
                        '[REALISTIC] Cozy Winter Cinema: A deeply comforting, hyper-realistic magical photography shot. A snow-frosted pine tree heavily decorated with glowing, warm fairy lights inside a luxurious wooden cabin. The ultimate cozy holiday.',
                        '[GRAPHICAL] Festive Flat Village: A beautiful, modern flat vector art scene. A stylized, geometric snowy village with cute reindeer and a red sleigh flying across a starry night sky. Clean, cheerful, highly commercial.',
                        '[3D STYLIZED] Premium Glossy Ornaments: A high-fashion 3D studio render. Extreme macro shot of pristine, sparkling, perfectly smooth glossy red and gold Christmas baubles resting on pure white marble. Ultra-luxurious.'
                    ],
                    'New Year\'s Eve': [
                        '[REALISTIC] Luxury Champagne Macro: A stunning, high-end commercial shot. A crystal-clear champagne flute overflowing with golden bubbles, dramatically backlit by out-of-focus, glittering bokeh party lights. Extreme 8K clarity.',
                        '[GRAPHICAL] Retro Disco Ball: A fun, energetic flat vector poster. A stylized, glowing silver disco ball casting dynamic, colorful beams of light across a dark, starry background. Bold, retro 70s party aesthetic.',
                        '[3D STYLIZED] Golden Clock Strike: An immaculate 3D concept render. A massive, floating, hyper-detailed 3D golden clock face, with its gears exposed, dramatically illuminated as the hands strike exactly midnight. Flawless metallic textures.'
                    ],

                    /* ================= FALLBACK ================= */
                    'Default Custom Event': [
                        '[REALISTIC] Sleek Minimalist Cinema: An ultra-premium, sleek minimalist photography shot. Clean, polished white marble surfaces bathed in soft, diffused, natural studio daylight. Flawless reflections, sharp 8K details.',
                        '[GRAPHICAL] Modern Corporate Vector: A highly attractive, modern flat corporate illustration. Stylized, diverse abstract elements floating with bright, optimistic pastel colors. Clean, professional, visually engaging layout.',
                        '[3D STYLIZED] Abstract Geometry Render: An incredibly vibrant, dynamic 3D promotional masterpiece. Soft, glossy 3D geometric shapes (spheres, cubes, waves) floating effortlessly against a clean, infinite pastel background.'
                    ]
                },

                posterTypeA: @js(config('occasion_presets.posterTypeA')),
                masterPosterRaw: @json(config('occasion_presets.masterPosterRaw')),
                aspectRatioDirective: @json(config('occasion_presets.aspectRatioDirective')),
                semanticPosterFramework: @json(config('occasion_presets.semanticPosterFramework')),
                semanticCategoryTemplates: @js(config('occasion_presets.semanticCategoryTemplates')),
                semanticCategoryByOccasion: @js(config('occasion_presets.semanticCategoryByOccasion')),
                semanticOccasionDetail: @js(config('occasion_presets.semanticOccasionDetail')),
                graphicDesignerBrief: @json(config('occasion_presets.graphicDesignerBrief')),
                typographyMatchingBrief: @json(config('occasion_presets.typographyMatchingBrief')),
                promptExcellenceLock: @json(config('occasion_presets.promptExcellenceLock')),
                typographyByStyleFamily: @js(config('occasion_presets.typographyByStyleFamily')),
                posterLayoutRuleA: @json(config('occasion_presets.compositionTypeA')),
                posterLayoutRuleB: @json(config('occasion_presets.compositionTypeB')),
                posterStyleTemplates: @js(config('occasion_presets.posterStyleTemplates')),
                posterStyleByOccasion: @js(config('occasion_presets.posterStyleByOccasion')),
                posterOccasionFocus: @js(config('occasion_presets.posterOccasionFocus')),
                posterVisualHints: @js(config('occasion_presets.posterVisualHints')),
                textsMap: @js(config('occasion_presets.textsMap')),

                get currentMonthName() { return this.monthsMap[parseInt(this.month, 10)] || ''; },
                get availableOccasions() {
                    const key = parseInt(this.month, 10);
                    const list = this.occasionsMap?.[key] ?? this.occasionsMap?.[String(key)];
                    return Array.isArray(list) ? list : [];
                },

                get availableThemes() { return this.themesMap[this.occasion] || this.themesMap['Default Custom Event']; },
                get availableTexts() { return this.textsMap[this.occasion] || this.textsMap['Default Custom Event']; },

                get placeholderOccasion() { return `Select from dropdown or type a custom event for ${this.currentMonthName}...`; },
                get placeholderTheme() {
                    if (!this.occasion) return 'Please select an Occasion above first...';
                    return `Describe the visual atmosphere, lighting, and style for ${this.occasion}...`;
                },
                get placeholderText() {
                    if (!this.occasion) return 'Enter the 2D overlay text...';
                    return `Bangla or English headline lines for ${this.occasion} — use presets or type your own...`;
                },

                // --- DYNAMIC LIVE FILTERED SEARCH FOR THE AUTO-FILL COMBOBOX ---
                get filteredFestivals() {
                    if (!this.autoFillInput.trim()) {
                        return this.masterFestivals; 
                    }
                    return this.masterFestivals.filter(fest => 
                        fest.toLowerCase().includes(this.autoFillInput.toLowerCase())
                    );
                },

                posterLayoutFor(occasionName) {
                    if (!occasionName) return this.posterLayoutRuleB + ' ' + this.masterPosterRaw;
                    const isA = this.isPosterTypeA(occasionName);
                    return (isA ? this.posterLayoutRuleA : this.posterLayoutRuleB) + ' ' + this.masterPosterRaw;
                },

                isPosterTypeA(occasionName) {
                    return this.posterTypeA.some(k => k.toLowerCase() === String(occasionName || '').toLowerCase());
                },

                buildPosterTheme(occasionName) {
                    const key = occasionName || 'Default Custom Event';
                    const familyKey = this.posterStyleByOccasion[key] || 'modern_celebration';
                    const template = this.posterStyleTemplates[familyKey] || this.posterStyleTemplates.modern_celebration;
                    const focus = this.posterOccasionFocus[key]
                        || this.posterVisualHints[key]
                        || this.posterVisualHints['Default Custom Event'];
                    const layoutNote = this.isPosterTypeA(key)
                        ? 'Apply Layout Type A (tribute band).'
                        : 'Apply Layout Type B (greeting headline zone).';
                    return `[POSTER] Style family: ${familyKey}. ${template} Occasion focus for ${key}: ${focus}. Semantic design: ${this.semanticBriefFor(key)} ${layoutNote} ${this.masterPosterRaw}`;
                },

                overlaySpaceNote(occasionName) {
                    return this.isPosterTypeA(occasionName)
                        ? 'Lower third (30%) smooth dark gradient empty for extraordinary Bengali/English typography matched to palette. Landscape 16:9.'
                        : 'Left or lower ~25% clean for headline stack—typography colors echo image accents. Landscape 16:9.';
                },

                typographyMoodFor(occasionName) {
                    const key = occasionName || 'Default Custom Event';
                    const familyKey = this.posterStyleByOccasion[key] || 'modern_celebration';
                    return this.typographyByStyleFamily[familyKey]
                        || this.typographyByStyleFamily.modern_celebration
                        || '';
                },

                semanticBriefFor(occasionName) {
                    const key = occasionName || 'Default Custom Event';
                    if (this.semanticOccasionDetail[key]) {
                        return this.semanticOccasionDetail[key];
                    }
                    const category = this.semanticCategoryByOccasion[key] || 'modern_celebration';
                    const template = this.semanticCategoryTemplates[category]
                        || this.semanticCategoryTemplates.modern_celebration
                        || '';
                    return `Occasion "${key}": ${template} Every visual layer and text zone must mean something for ${key}—no filler decoration.`;
                },

                buildRealisticTheme(occasionName) {
                    const key = occasionName || 'Default Custom Event';
                    const subject = this.posterVisualHints[key]
                        || this.posterVisualHints['Default Custom Event']
                        || `${key} celebration`;
                    return `[REALISTIC] Cinematic IMAX 8K photography for ${key}: ${subject}. Shot on ARRI Alexa 65, volumetric god-rays, shallow depth of field, premium commercial grade. ${this.overlaySpaceNote(key)} NO text, NO logos, NO typography in image.`;
                },

                buildGraphicalTheme(occasionName) {
                    const key = occasionName || 'Default Custom Event';
                    const subject = this.posterVisualHints[key]
                        || this.posterVisualHints['Default Custom Event']
                        || `${key} celebration`;
                    return `[GRAPHICAL] Award-winning flat vector / digital illustration for ${key}: ${subject}. Bold clean shapes, premium Swiss-poster composition, vibrant cohesive palette. ${this.overlaySpaceNote(key)} NO text, NO logos, NO alphabet glyphs in image.`;
                },

                ensurePosterThemesForAll() {
                    const keys = new Set([
                        ...Object.keys(this.themesMap),
                        ...this.masterFestivals,
                        'Default Custom Event'
                    ]);
                    keys.forEach(key => {
                        const existing = Array.isArray(this.themesMap[key]) ? [...this.themesMap[key]] : [];
                        const poster = this.buildPosterTheme(key);
                        const realistic = existing.find(t => String(t).startsWith('[REALISTIC]')) || this.buildRealisticTheme(key);
                        const graphical = existing.find(t => String(t).startsWith('[GRAPHICAL]')) || this.buildGraphicalTheme(key);
                        this.themesMap[key] = [poster, realistic, graphical];
                    });
                },

                buildDefaultTexts(occasionName) {
                    const isA = this.isPosterTypeA(occasionName);
                    if (isA) {
                        return [
                            `সবাইকে|শুভ ${occasionName}|সবার জন্য শুভেচ্ছা`,
                            `Everyone|Happy ${occasionName}|Warm Wishes`
                        ];
                    }
                    return [
                        `শুভেচ্ছা|${occasionName}|সবাইকে`,
                        `Happy ${occasionName}|Best Wishes|Celebrate With Us`
                    ];
                },

                ensureTextsForAllOccasions() {
                    this.masterFestivals.forEach(name => {
                        if (!this.textsMap[name]) {
                            this.textsMap[name] = this.buildDefaultTexts(name);
                        }
                    });
                },

                applyPosterDefaults(occasionName) {
                    if (!occasionName) return;
                    const themes = this.themesMap[occasionName] || this.themesMap['Default Custom Event'];
                    const posterTheme = themes.find(t => String(t).startsWith('[POSTER]'));
                    if (posterTheme) {
                        this.visual_direction = posterTheme;
                    }
                },

                textSuggestionLabel(item) {
                    const parts = String(item).includes('|') ? String(item).split('|') : [item];
                    const first = parts[0].trim();
                    return parts.length > 1 ? `${first} …` : first;
                },

                applyTextSuggestion(item) {
                    this.custom_text = String(item).includes('|')
                        ? String(item).split('|').map(s => s.trim()).filter(Boolean).join('\n')
                        : item;
                    this.openText = false;
                },

                // --- MASTER PAYLOAD GENERATOR (ART DIRECTION + TYPOGRAPHY + COMPOSITION) ---
                get generatePayloadText() {
                    const occasionKey = this.occasion || 'Default Custom Event';
                    const layout = this.posterLayoutFor(occasionKey);
                    const lines = String(this.custom_text || '').replace(/\n/g, ' | ');
                    const vd = this.visual_direction || this.buildPosterTheme(occasionKey);
                    const isPoster = String(vd).startsWith('[POSTER]');
                    const styleLock = isPoster
                        ? this.promptExcellenceLock
                        : 'Follow the selected [REALISTIC] or [GRAPHICAL] style in visual_direction exactly—senior art-director composition with typography zone reserved.';
                    const typographyMood = this.typographyMoodFor(occasionKey);
                    return [
                        `Visual style: ${vd}`,
                        `ASPECT RATIO: ${this.aspectRatioDirective}`,
                        `SEMANTIC POSTER: ${this.semanticPosterFramework}`,
                        `SEMANTIC OCCASION: ${this.semanticBriefFor(occasionKey)}`,
                        `Intended overlay copy (DO NOT RENDER in image): "${lines}"—each line must map to a meaningful zone (title, symbol pocket, factual footer).`,
                        `GRAPHIC DESIGNER BRIEF: ${this.graphicDesignerBrief}`,
                        `TYPOGRAPHY MATCH: ${this.typographyMatchingBrief} ${typographyMood}`,
                        `Composition directive: ${layout}`,
                        `STRICT AI DIRECTIVE: ${styleLock}`,
                        'Do NOT draw text, letters, Bengali script, fonts, URLs, or logos in the image. NO watermarks. NO floating UI clutter.',
                        'Post-production typography must feel extraordinary and perfectly harmonized with the picture palette and mood.'
                    ].join(' ');
                },

                // --- 🚨 FULL CONTEXT AI AUTO-FILL LOGIC (SENDS ALL EXISTING DATA & FIXES $WATCH) 🚨 ---
                async triggerAutoFill() {
                    const inputName = this.autoFillInput.trim();
                    if (!inputName) return;
                    
                    this.isAutoFilling = true;
                    this.autoFillError = '';

                    // 1. Check if the typed occasion is in our registered master list
                    const isRegistered = this.masterFestivals.some(
                        fest => fest.toLowerCase() === inputName.toLowerCase()
                    );

                    // 2. AUTO-SELECT MONTH & SMART YEAR LOGIC
                    if (isRegistered) {
                        for (const [mString, festivals] of Object.entries(this.occasionsMap)) {
                            if (festivals.some(f => f.toLowerCase() === inputName.toLowerCase())) {
                                // Instantly update the UI Dropdown for Month
                                this.month = parseInt(mString); 
                                
                                // Smart Year logic: Bump to next year if the festival already passed this year
                                const currentRealMonth = new Date().getMonth() + 1;
                                const currentRealYear = new Date().getFullYear();
                                
                                if (this.month < currentRealMonth) {
                                    this.year = currentRealYear + 1;
                                } else {
                                    this.year = currentRealYear;
                                }
                                break;
                            }
                        }
                    }

                    // 3. Safely grab existing themes/texts
                    const passedThemes = this.themesMap[inputName] || this.themesMap['Default Custom Event'];
                    const passedTexts = this.textsMap[inputName] || this.textsMap['Default Custom Event'];

                    // 4. Build the payload
                    const posterLayout = this.posterLayoutFor(inputName);
                    const payload = {
                        occasion_name: inputName,
                        target_month_number: this.month,
                        target_month_name: this.currentMonthName,
                        target_year: this.year,
                        is_registered: isRegistered,
                        existing_themes: passedThemes,
                        existing_texts: passedTexts,
                        poster_layout_directive: posterLayout,
                        ai_instruction: (isRegistered
                            ? "Registered festival. existing_themes[0] is [POSTER] with a UNIQUE style per occasion (folk/Pohela, patriotic night Victory, patriotic graphic Independence, pharma premium, etc.)—copy that diversity, never one generic night template for all. Also [REALISTIC] and [GRAPHICAL] available. existing_texts use pipe (|) for Bengali—marketing_text with newlines."
                            : "Custom occasion. [POSTER] uses modern_celebration family unless themes specify otherwise. Multi-line marketing_text (newlines OK).")
                            + " ASPECT RATIO: " + this.aspectRatioDirective
                            + " SEMANTIC POSTER: " + this.semanticPosterFramework
                            + " SEMANTIC OCCASION: " + this.semanticBriefFor(inputName)
                            + " GRAPHIC DESIGNER BRIEF: " + this.graphicDesignerBrief
                            + " TYPOGRAPHY: " + this.typographyMatchingBrief + " " + this.typographyMoodFor(inputName)
                            + " Composition: " + posterLayout
                            + " EXCELLENCE LOCK: " + this.promptExcellenceLock
                    };

                    try {
                        const response = await fetch('{{ route("occasions.autoFill") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.occasion = inputName;
                            this.visual_direction = data.visual_direction;
                            const mt = data.marketing_text || '';
                            this.custom_text = mt.includes('|')
                                ? mt.split('|').map(s => s.trim()).filter(Boolean).join('\n')
                                : mt;
                            
                            // Close modal & reset combobox toggle strings
                            this.openAutoFillModal = false;
                            this.autoFillInput = '';
                            this.dropdownOpen = false;
                        } else {
                            this.autoFillError = data.message || 'Unknown Error from n8n.';
                        }
                    } catch (error) {
                        this.autoFillError = 'Network error. Could not reach server.';
                    } finally {
                        // Allow Alpine to resume normal watching after a short delay
                        setTimeout(() => { this.isAutoFilling = false; }, 100);
                    }
                },

                init() {
                    this.ensurePosterThemesForAll();
                    this.ensureTextsForAllOccasions();

                    this.$watch('month', (value) => {
                        if (!this.isAutoFilling) {
                            this.occasion = '';
                            this.visual_direction = '';
                            this.custom_text = '';
                        }
                    });
                    this.$watch('occasion', (value) => {
                        if (!this.isAutoFilling) {
                            this.custom_text = '';
                            this.applyPosterDefaults(value);
                        }
                    });

                    if (this.occasion && (!this.visual_direction || !String(this.visual_direction).startsWith('[POSTER]'))) {
                        this.applyPosterDefaults(this.occasion);
                    }
                }
            }));
            };

            document.addEventListener('alpine:init', registerOccasionStudio);
        })();
    </script>

    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #ec4899; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #be185d; }
    </style>
</x-app-layout>