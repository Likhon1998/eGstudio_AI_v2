<x-app-layout>
    {{-- Main Container --}}
    <div class="max-w-4xl mx-auto pt-6 pb-8 px-6 space-y-6 antialiased">
        
        {{-- Header Section --}}
        <div class="flex items-center justify-between border-b border-white/10 pb-6">
            <div>
                <h1 class="text-base font-bold tracking-tight text-white uppercase">
                    Initialize <span class="text-purple-500">System Role</span>
                </h1>
                <p class="text-[10px] text-gray-500 font-medium uppercase tracking-widest mt-1">
                    Define Access Groups & Permission Clusters
                </p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-[9px] font-bold text-gray-500 hover:text-white uppercase tracking-widest transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to Console
            </a>
        </div>

        {{-- Role Creation Form --}}
        <form action="{{ route('admin.roles.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                {{-- Left Side: Role Identity --}}
                <div class="md:col-span-1 space-y-4">
                    <div class="bg-[#0d0d0d] border border-white/10 rounded-lg p-5 shadow-xl">
                        <h3 class="text-[10px] font-black text-purple-400 uppercase tracking-[0.2em] border-b border-white/5 pb-2 mb-4">Role Identity</h3>
                        
                        <div>
                            <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-2">Role Designation</label>
                            <input type="text" name="name" required 
                                class="w-full bg-black border border-white/10 rounded px-4 py-2.5 text-xs text-white focus:ring-1 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all"
                                placeholder="e.g. Content_Manager">
                            @error('name')
                                <p class="text-[9px] text-red-500 mt-1 uppercase font-bold">{{ $message }}</p>
                            @enderror
                        </div>

                        <p class="text-[9px] text-gray-600 leading-relaxed mt-4 italic">
                            Roles act as permission containers. Once defined, you can assign these to any agent node.
                        </p>
                    </div>

                    <button type="submit" class="w-full py-3 bg-purple-600 hover:bg-purple-500 text-white rounded text-[10px] font-bold uppercase tracking-widest shadow-lg shadow-purple-600/20 transition-all">
                        Deploy New Role
                    </button>
                </div>

                {{-- Right Side: Permission Grid --}}
                <div class="md:col-span-2">
                    <div class="bg-[#0d0d0d] border border-white/10 rounded-lg p-5 shadow-xl min-h-[300px]">
                        <h3 class="text-[10px] font-black text-blue-400 uppercase tracking-[0.2em] border-b border-white/5 pb-2 mb-4">Attached Clearances</h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @forelse($permissions as $permission)
                                <label class="flex items-center gap-3 p-3 rounded border border-white/5 hover:bg-white/[0.02] cursor-pointer transition-all group">
                                    <div class="relative flex items-center justify-center">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" 
                                            class="peer appearance-none w-4 h-4 border border-gray-600 rounded bg-transparent checked:bg-purple-600 checked:border-purple-600 cursor-pointer transition-all">
                                        <svg class="absolute w-2.5 h-2.5 text-white opacity-0 peer-checked:opacity-100 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <span class="text-[10px] font-bold text-gray-400 group-hover:text-gray-200 uppercase tracking-wider transition-colors">
                                        {{ str_replace('_', ' ', $permission->name) }}
                                    </span>
                                </label>
                            @empty
                                <div class="col-span-2 py-10 text-center">
                                    <p class="text-[10px] text-gray-600 uppercase font-bold tracking-widest">No permissions found in database.</p>
                                    <p class="text-[9px] text-gray-700 mt-1">Run your permission seeders to initialize the system.</p>
                                </div>
                            @endforelse
                        </div>
                        
                        @error('permissions')
                            <p class="text-[9px] text-red-500 mt-4 uppercase font-bold text-center border-t border-red-500/20 pt-2">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

            </div>
        </form>

    </div>

    {{-- Global Styles --}}
    <style>
        body { 
            background-color: #050505; 
            color: #e5e7eb;
        }
    </style>
</x-app-layout>