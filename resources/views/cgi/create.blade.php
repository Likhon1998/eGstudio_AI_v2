<x-app-layout>
    <div class="max-w-6xl mx-auto space-y-8 py-8 px-4 sm:px-6 lg:px-8">

        {{-- Header Section --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 mb-10">
            <div>
                <h2 class="text-3xl font-extrabold text-white tracking-tight">CGI Studio Director</h2>
                <p class="text-gray-400 text-sm mt-2 font-medium">Build your commercial. Use the <span
                        class="text-blue-400 font-bold">ⓘ</span> icons to understand each step.</p>
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                <a href="{{ route('cgi.index') }}"
                    class="flex-1 sm:flex-none px-6 py-2.5 bg-gray-800/80 border border-gray-700 text-gray-300 rounded-xl hover:bg-gray-700 hover:text-white transition-all duration-200 font-semibold text-sm shadow-lg text-center backdrop-blur-sm">
                    Directory
                </a>
                <a href="{{ route('dashboard') }}"
                    class="flex-1 sm:flex-none px-6 py-2.5 bg-gray-800/80 border border-gray-700 text-gray-300 rounded-xl hover:bg-gray-700 hover:text-white transition-all duration-200 font-semibold text-sm shadow-lg text-center backdrop-blur-sm">
                    Exit
                </a>
            </div>
        </div>

        <div
            class="bg-gray-900/60 backdrop-blur-2xl p-8 sm:p-10 rounded-3xl text-white shadow-2xl border border-gray-800/60 relative overflow-hidden">
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl pointer-events-none">
            </div>

            <form action="{{ route('cgi.store') }}" method="POST" class="space-y-12 relative z-10"
                x-data="{ isSubmitting: false }" @submit="isSubmitting = true">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-14">

                    {{-- 01. The Product --}}
                    <div class="md:col-span-2" x-data="{ val: '' }">
                        <div class="flex items-center gap-2 mb-3 relative group w-fit">
                            <label class="block text-blue-400 text-xs font-bold tracking-[0.2em] uppercase">01. What are
                                we selling?</label>
                            <div class="cursor-help text-gray-500 hover:text-blue-400 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div
                                class="absolute left-0 bottom-full mb-2 hidden group-hover:block w-72 p-3.5 bg-gray-800 border border-gray-700 text-[11px] text-gray-300 rounded-xl shadow-2xl z-20 leading-relaxed">
                                <strong class="text-white block mb-1">Guide:</strong> Mention the specific product name.
                                Adding the material (e.g., Titanium, Glass) helps the AI create realistic textures.
                            </div>
                        </div>
                        <input type="text" name="product_name" x-model="val" placeholder="E.g. Luxury Leather Watch..."
                            required
                            class="w-full bg-black/40 border border-gray-700/80 rounded-2xl text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 p-4.5 outline-none transition-all text-lg shadow-inner placeholder-gray-600">
                        <div class="flex flex-wrap gap-2 mt-4">
                            <button type="button" @click="val = 'Luxury Gold & Leather Watch'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">⌚
                                Gold Watch</button>
                            <button type="button" @click="val = 'Nitro Mesh Running Shoe'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">👟
                                Running Shoe</button>
                            <button type="button" @click="val = 'Frosted Glass Perfume Bottle'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">✨
                                Perfume</button>
                            <button type="button" @click="val = 'Matte Carbon Fiber Headphones'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">🎧
                                Headphones</button>
                            <button type="button" @click="val = 'Obsidian Mirror Smartphone'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">📱
                                Smartphone</button>
                            <button type="button" @click="val = 'Organic Ceramic Skincare Jar'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">🧴
                                Skincare</button>
                            <button type="button" @click="val = 'Brushed Titanium Water Bottle'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">🍶
                                Water Bottle</button>
                            <button type="button" @click="val = 'Polished Chrome Espresso Machine'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">☕
                                Espresso</button>
                        </div>
                    </div>

                    {{-- 02. The Feeling --}}
                    <div x-data="{ 
                        val: '',
                        toggle(word) {
                            let items = this.val ? this.val.split(', ').filter(i => i) : [];
                            if (items.includes(word)) {
                                items = items.filter(i => i !== word);
                            } else {
                                items.push(word);
                            }
                            this.val = items.join(', ');
                        }
                    }">
                        <div class="flex items-center gap-2 mb-3 relative group w-fit">
                            <label class="block text-blue-400 text-xs font-bold tracking-[0.2em] uppercase">02. What
                                product benefits do you want to see highlighted in the picture?</label>
                            <div class="cursor-help text-gray-500 hover:text-blue-400 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div
                                class="absolute left-0 bottom-full mb-2 hidden group-hover:block w-72 p-3.5 bg-gray-800 border border-gray-700 text-[11px] text-gray-300 rounded-xl shadow-2xl z-20 leading-relaxed">
                                <strong class="text-white block mb-1">Guide:</strong> Click multiple buttons to select
                                several benefits. These will appear as bold 3D text in your video and will pulse with
                                light!
                            </div>
                        </div>
                        <input type="text" name="marketing_angle" x-model="val" placeholder="Select multiple below..."
                            required
                            class="w-full bg-black/40 border border-gray-700/80 rounded-2xl text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 p-4 outline-none transition-all shadow-inner placeholder-gray-600">
                        <div class="flex flex-wrap gap-2 mt-4">
                            <button type="button" @click="toggle('ENERGY SAVING')"
                                :class="val.includes('ENERGY') ? 'bg-blue-600 text-white' : 'bg-gray-800/40 text-gray-400'"
                                class="px-3 py-1.5 border border-gray-700 rounded-lg text-[10px] transition-all font-bold">⚡
                                Energy</button>
                            <button type="button" @click="toggle('PURE LUXURY')"
                                :class="val.includes('LUXURY') ? 'bg-blue-600 text-white' : 'bg-gray-800/40 text-gray-400'"
                                class="px-3 py-1.5 border border-gray-700 rounded-lg text-[10px] transition-all font-bold">💎
                                Luxury</button>
                            <button type="button" @click="toggle('ULTRA DURABLE')"
                                :class="val.includes('DURABLE') ? 'bg-blue-600 text-white' : 'bg-gray-800/40 text-gray-400'"
                                class="px-3 py-1.5 border border-gray-700 rounded-lg text-[10px] transition-all font-bold">🔨
                                Durable</button>
                            <button type="button" @click="toggle('HYPER SPEED')"
                                :class="val.includes('SPEED') ? 'bg-blue-600 text-white' : 'bg-gray-800/40 text-gray-400'"
                                class="px-3 py-1.5 border border-gray-700 rounded-lg text-[10px] transition-all font-bold">🚀
                                Speed</button>
                            <button type="button" @click="toggle('ICE COLD')"
                                :class="val.includes('COLD') ? 'bg-blue-600 text-white' : 'bg-gray-800/40 text-gray-400'"
                                class="px-3 py-1.5 border border-gray-700 rounded-lg text-[10px] transition-all font-bold">❄️
                                Fresh</button>
                            <button type="button" @click="toggle('SILENT POWER')"
                                :class="val.includes('SILENT') ? 'bg-blue-600 text-white' : 'bg-gray-800/40 text-gray-400'"
                                class="px-3 py-1.5 border border-gray-700 rounded-lg text-[10px] transition-all font-bold">🤫
                                Silent</button>
                            <button type="button" @click="toggle('ADVANCED AI')"
                                :class="val.includes('AI') ? 'bg-blue-600 text-white' : 'bg-gray-800/40 text-gray-400'"
                                class="px-3 py-1.5 border border-gray-700 rounded-lg text-[10px] transition-all font-bold">🧠
                                AI Tech</button>
                            <button type="button" @click="toggle('WATER PROOF')"
                                :class="val.includes('WATER') ? 'bg-blue-600 text-white' : 'bg-gray-800/40 text-gray-400'"
                                class="px-3 py-1.5 border border-gray-700 rounded-lg text-[10px] transition-all font-bold">🌊
                                Waterproof</button>
                        </div>
                    </div>

                    {{-- 03. Decoration --}}
                    <div x-data="{ 
    selectedProps: [], 
    customInput: '',
    addProp(item) {
        if (item.trim() !== '' && !this.selectedProps.includes(item)) {
            this.selectedProps.push(item.trim());
        }
        this.customInput = ''; // Clear input after adding
    },
    removeProp(item) {
        this.selectedProps = this.selectedProps.filter(i => i !== item);
    },
    toggleProp(item) {
        if (this.selectedProps.includes(item)) {
            this.removeProp(item);
        } else {
            this.addProp(item);
        }
    }
}">
    {{-- Hidden input to send the final string to your Laravel Controller --}}
    <input type="hidden" name="visual_prop" :value="selectedProps.join(', ')">

    <div class="flex items-center gap-2 mb-3 relative group w-fit">
        <label class="block text-blue-400 text-xs font-bold tracking-[0.2em] uppercase">03. Objects next to product</label>
        <div class="cursor-help text-gray-500 hover:text-blue-400 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div class="absolute left-0 bottom-full mb-2 hidden group-hover:block w-72 p-3.5 bg-gray-800 border border-gray-700 text-[11px] text-gray-300 rounded-xl shadow-2xl z-20 leading-relaxed">
            <strong class="text-white block mb-1">Guide:</strong> Combine multiple objects! Type a custom one and press <b>Enter</b>, or click the presets below.
        </div>
    </div>

    {{-- Interactive Multi-Input Tray --}}
    <div class="w-full bg-black/40 border border-gray-700/80 rounded-2xl p-2 flex flex-wrap gap-2 transition-all focus-within:ring-2 focus-within:ring-blue-500/50 shadow-inner">
        {{-- Selected Tags --}}
        <template x-for="prop in selectedProps" :key="prop">
            <div class="flex items-center gap-2 bg-blue-600/20 border border-blue-500/40 text-blue-300 px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider animate-in fade-in zoom-in duration-200">
                <span x-text="prop"></span>
                <button type="button" @click="removeProp(prop)" class="hover:text-white text-blue-500/50 transition-colors">✕</button>
            </div>
        </template>
        
        {{-- Custom Writing Area --}}
        <input type="text" 
               x-model="customInput" 
               @keydown.enter.prevent="addProp(customInput)"
               @keydown.comma.prevent="addProp(customInput)"
               @blur="addProp(customInput)"
               placeholder="Type custom prop & Enter..." 
               class="flex-1 min-w-[150px] bg-transparent border-none text-white text-sm p-2 outline-none focus:ring-0 placeholder-gray-600">
    </div>

    {{-- Quick-Selection Presets --}}
    <div class="flex flex-wrap gap-2 mt-4">
        <template x-for="item in [
            {id: 'Marble', val: 'Polished Marble Slab', icon: '🪨'},
            {id: 'Water', val: 'Splashing Water Wave', icon: '💧'},
            {id: 'Chrome', val: 'Floating Chrome Spheres', icon: '🟠'},
            {id: 'Leaves', val: 'Tropical Palm Leaves', icon: '🍃'},
            {id: 'Rock', val: 'Jagged Volcanic Rocks', icon: '🌋'},
            {id: 'Energy', val: 'Abstract Glowing Fibers', icon: '⚡'},
            {id: 'Glass', val: 'Crystal Glass Shards', icon: '💎'},
            {id: 'Silk', val: 'Silky Red Fabric', icon: '🧤'}
        ]" :key="item.id">
            <button type="button" 
                @click="toggleProp(item.val)" 
                :class="selectedProps.includes(item.val) ? 'bg-blue-600 border-blue-400 text-white' : 'bg-gray-800/40 border-gray-700 text-gray-400 hover:text-white hover:border-blue-500/50'"
                class="px-3 py-1.5 border rounded-lg text-[10px] font-bold uppercase tracking-widest transition-all flex items-center gap-2">
                <span x-text="item.icon"></span>
                <span x-text="item.id"></span>
            </button>
        </template>
    </div>
</div>

                    {{-- 04. Background --}}
                    <div x-data="{ val: '' }">
                        <div class="flex items-center gap-2 mb-3 relative group w-fit">
                            <label class="block text-blue-400 text-xs font-bold tracking-[0.2em] uppercase">04. Scene
                                Background</label>
                            <div class="cursor-help text-gray-500 hover:text-blue-400 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div
                                class="absolute left-0 bottom-full mb-2 hidden group-hover:block w-72 p-3.5 bg-gray-800 border border-gray-700 text-[11px] text-gray-300 rounded-xl shadow-2xl z-20 leading-relaxed">
                                <strong class="text-white block mb-1">Guide:</strong> This defines the entire
                                environment. Our AI is "Global," so you can choose Nature, City, or high-tech Studio
                                looks.
                            </div>
                        </div>
                        <input type="text" name="atmosphere" x-model="val" placeholder="E.g. Sunny mountain beach..."
                            required
                            class="w-full bg-black/40 border border-gray-700/80 rounded-2xl text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 p-4 outline-none transition-all shadow-inner placeholder-gray-600">
                        <div class="flex flex-wrap gap-2 mt-4">
                            <button type="button" @click="val = 'Misty Morning Forest'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">🌲
                                Forest</button>
                            <button type="button" @click="val = 'Luxury Penthouse Skyline'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">🏙️
                                City</button>
                            <button type="button" @click="val = 'Underwater Sunbeams'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">🌊
                                Ocean</button>
                            <button type="button" @click="val = 'Neon Cyberpunk Tokyo'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">🏮
                                Tokyo</button>
                            <button type="button" @click="val = 'Deep Space Station'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">🚀
                                Space</button>
                            <button type="button" @click="val = 'Minimal High-Tech Lab'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">🔬
                                Lab</button>
                            <button type="button" @click="val = 'Golden Desert Dunes'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">🏜️
                                Desert</button>
                            <button type="button" @click="val = 'Cozy Minimalist Studio'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">🏠
                                Studio</button>
                        </div>
                    </div>

                    {{-- 05. Movement --}}
                    <div x-data="{ val: '' }">
                        <div class="flex items-center gap-2 mb-3 relative group w-fit">
                            <label class="block text-blue-400 text-xs font-bold tracking-[0.2em] uppercase">05. Video
                                Camera Style</label>
                            <div class="cursor-help text-gray-500 hover:text-blue-400 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div
                                class="absolute left-0 bottom-full mb-2 hidden group-hover:block w-72 p-3.5 bg-gray-800 border border-gray-700 text-[11px] text-gray-300 rounded-xl shadow-2xl z-20 leading-relaxed">
                                <strong class="text-white block mb-1">Guide:</strong> This sets how the camera moves in
                                the final video. Orbit is great for 360 views; Zoom is great for dramatic reveals.
                            </div>
                        </div>
                        <input type="text" name="camera_motion" x-model="val"
                            placeholder="E.g. Slow circle around product..." required
                            class="w-full bg-black/40 border border-gray-700/80 rounded-2xl text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 p-4 outline-none transition-all shadow-inner placeholder-gray-600">
                        <div class="flex flex-wrap gap-2 mt-4">
                            <button type="button" @click="val = 'Elegant 360 degree slow orbit'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">🔄
                                Orbit</button>
                            <button type="button" @click="val = 'Extreme Fast Zoom'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">🔍
                                Zoom</button>
                            <button type="button" @click="val = 'Vertical Bottom-Up Sweep'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">⬆️
                                Up</button>
                            <button type="button" @click="val = 'Cinematic Macro Slide'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">🎞️
                                Macro</button>
                            <button type="button" @click="val = 'Spinning Glitch Zoom'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">🌀
                                Spin</button>
                            <button type="button" @click="val = 'Dolly Push Reveal'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all">🚶
                                Push</button>
                            <button type="button" @click="val = 'Drone Landscape Sweep'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30">🚁
                                Drone</button>
                            <button type="button" @click="val = 'Handheld Shaky Motion'"
                                class="px-3 py-1.5 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-400 hover:text-white hover:bg-blue-600/30">📹
                                Shaky</button>
                        </div>
                    </div>

                    {{-- 06. Layout --}}
                    <div class="md:col-span-2" x-data="{ comp: '' }">
                        <div class="flex items-center gap-2 mb-3 relative group w-fit">
                            <label class="block text-blue-400 text-xs font-bold tracking-[0.2em] uppercase">06. Where to
                                put the product?</label>
                            <div class="cursor-help text-gray-500 hover:text-blue-400 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div
                                class="absolute left-0 bottom-full mb-2 hidden group-hover:block w-72 p-3.5 bg-gray-800 border border-gray-700 text-[11px] text-gray-300 rounded-xl shadow-2xl z-20 leading-relaxed">
                                <strong class="text-white block mb-1">Guide:</strong> Choose the layout. If you pick
                                "Left Side," it leaves perfect open space on the right for the big benefit text.
                            </div>
                        </div>
                        <input type="text" name="composition" x-model="comp" placeholder="Select a position..." required
                            class="w-full bg-black border border-blue-500/30 rounded-2xl text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 p-4 outline-none transition-all mb-6 shadow-inner text-sm">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div @click="comp = 'Product on far left side. Negative space on right.'"
                                class="p-4 border border-gray-700/80 bg-black/30 rounded-xl cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <h4 class="font-bold text-white text-[10px] uppercase">Far Left</h4>
                            </div>
                            <div @click="comp = 'Product on the right side. Space on left for text.'"
                                class="p-4 border border-gray-700/80 bg-black/30 rounded-xl cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <h4 class="font-bold text-white text-[10px] uppercase">Far Right</h4>
                            </div>
                            <div @click="comp = 'Symmetrical centered product. Perfectly balanced.'"
                                class="p-4 border border-gray-700/80 bg-black/30 rounded-xl cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <h4 class="font-bold text-white text-[10px] uppercase">Centered</h4>
                            </div>
                            <div @click="comp = 'Product at bottom center looking up.'"
                                class="p-4 border border-gray-700/80 bg-black/30 rounded-xl cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <h4 class="font-bold text-white text-[10px] uppercase">Hero Bottom</h4>
                            </div>
                            <div @click="comp = 'Extreme close up of product corner.'"
                                class="p-4 border border-gray-700/80 bg-black/30 rounded-xl cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <h4 class="font-bold text-white text-[10px] uppercase">Macro Angle</h4>
                            </div>
                            <div @click="comp = 'Top-down flat lay view of product.'"
                                class="p-4 border border-gray-700/80 bg-black/30 rounded-xl cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <h4 class="font-bold text-white text-[10px] uppercase">Top Down</h4>
                            </div>
                            <div @click="comp = 'Product floating at a 45 degree angle.'"
                                class="p-4 border border-gray-700/80 bg-black/30 rounded-xl cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <h4 class="font-bold text-white text-[10px] uppercase">Dynamic Tilt</h4>
                            </div>
                            <div @click="comp = 'Product in foreground, decoration in background.'"
                                class="p-4 border border-gray-700/80 bg-black/30 rounded-xl cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <h4 class="font-bold text-white text-[10px] uppercase">Depth Mix</h4>
                            </div>
                        </div>
                    </div>

                    {{-- 07. Lighting --}}
                    <div class="md:col-span-2" x-data="{ light: '' }">
                        <div class="flex items-center gap-2 mb-3 relative group w-fit">
                            <label class="block text-blue-400 text-xs font-bold tracking-[0.2em] uppercase">07. Lighting
                                & Colors</label>
                            <div class="cursor-help text-gray-500 hover:text-blue-400 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div
                                class="absolute left-0 bottom-full mb-2 hidden group-hover:block w-72 p-3.5 bg-gray-800 border border-gray-700 text-[11px] text-gray-300 rounded-xl shadow-2xl z-20 leading-relaxed">
                                <strong class="text-white block mb-1">Guide:</strong> This sets the "Color Grade." Movie
                                Style is high-end; Cyber Neon is for tech; Warm Sunset is for lifestyle.
                            </div>
                        </div>
                        <input type="text" name="lighting_style" x-model="light" placeholder="Select a mood..." required
                            class="w-full bg-black border border-blue-500/30 rounded-2xl text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 p-4 outline-none transition-all mb-6 shadow-inner text-sm">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div @click="light = 'Movie Style: High contrast, cinematic glow'"
                                class="p-5 border border-gray-700/80 bg-black/30 rounded-2xl cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-2xl block mb-2">🎬</span>
                                <h4 class="text-[10px] font-bold uppercase">Movie</h4>
                            </div>
                            <div @click="light = 'Warm Sunset: Golden hour glow'"
                                class="p-5 border border-gray-700/80 bg-black/30 rounded-2xl cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-2xl block mb-2">🌅</span>
                                <h4 class="text-[10px] font-bold uppercase">Sunset</h4>
                            </div>
                            <div @click="light = 'Clean Studio: White softbox lighting'"
                                class="p-5 border border-gray-700/80 bg-black/30 rounded-2xl cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-2xl block mb-2">💡</span>
                                <h4 class="text-[10px] font-bold uppercase">Clean</h4>
                            </div>
                            <div @click="light = 'Cyber Neon: Pink & Blue glow'"
                                class="p-5 border border-gray-700/80 bg-black/30 rounded-2xl cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-2xl block mb-2">🟣</span>
                                <h4 class="text-[10px] font-bold uppercase">Neon</h4>
                            </div>
                            <div @click="light = 'Midnight Blue: Cold moonlight shadows'"
                                class="p-5 border border-gray-700/80 bg-black/30 rounded-2xl cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-2xl block mb-2">🌙</span>
                                <h4 class="text-[10px] font-bold uppercase">Moon</h4>
                            </div>
                            <div @click="light = 'Earth Tones: Natural browns and greens'"
                                class="p-5 border border-gray-700/80 bg-black/30 rounded-2xl cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-2xl block mb-2">🍂</span>
                                <h4 class="text-[10px] font-bold uppercase">Earth</h4>
                            </div>
                            <div @click="light = 'Noir: High contrast black and white'"
                                class="p-5 border border-gray-700/80 bg-black/30 rounded-2xl cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-2xl block mb-2">🌑</span>
                                <h4 class="text-[10px] font-bold uppercase">Noir</h4>
                            </div>
                            <div @click="light = 'Dreamy Glow: Soft hazy highlights'"
                                class="p-5 border border-gray-700/80 bg-black/30 rounded-2xl cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-2xl block mb-2">☁️</span>
                                <h4 class="text-[10px] font-bold uppercase">Soft</h4>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Action Section --}}
                <div class="pt-10 border-t border-gray-800/80 mt-10 flex flex-col items-center space-y-8">

                    {{-- Small Redesigned Button --}}
                    <button type="submit" :disabled="isSubmitting"
                        class="relative w-full max-w-lg group overflow-hidden py-4 rounded-2xl transition-all duration-500 border border-zinc-700/50 bg-zinc-950 hover:border-blue-500/50 hover:shadow-[0_0_30px_rgba(37,99,235,0.15)] disabled:opacity-50 disabled:cursor-not-allowed">
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-transparent via-blue-500/5 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000">
                        </div>

                        <div class="relative flex items-center justify-center gap-4">
                            <div
                                class="p-2 bg-zinc-900 border border-zinc-800 rounded-lg group-hover:border-blue-500/50 transition-colors">
                                <svg x-show="!isSubmitting" class="w-4 h-4 text-blue-500" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <svg x-show="isSubmitting" x-cloak class="w-4 h-4 animate-spin text-blue-400"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </div>

                            <div class="text-left">
                                <span
                                    class="block text-[9px] font-bold text-gray-500 tracking-[0.3em] uppercase group-hover:text-blue-400 transition-colors">System
                                    Ready</span>
                                <span
                                    x-text="isSubmitting ? 'Processing Visualization...' : 'Launch Rendering Pipeline'"
                                    class="block text-white font-black tracking-widest uppercase text-sm"></span>
                            </div>
                        </div>
                    </button>

                    {{-- Updated Powered By Branding --}}
                    <div class="flex items-center gap-4 w-full max-w-xs">
                        <div class="h-px flex-1 bg-gradient-to-r from-transparent to-gray-800"></div>
                        <p class="text-[11px] tracking-[0.4em] uppercase text-gray-500 font-medium">
                            Powered by <span class="text-white font-black">eGeneration</span>
                        </p>
                        <div class="h-px flex-1 bg-gradient-to-l from-transparent to-gray-800"></div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>