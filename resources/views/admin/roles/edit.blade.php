<x-app-layout>
    {{-- Main Container --}}
    <div class="max-w-4xl mx-auto pt-6 pb-8 px-6 space-y-6 antialiased">
        
        {{-- Header Section --}}
        <div class="flex items-center justify-between border-b border-white/10 pb-6">
            <div>
                <h1 class="text-base font-bold tracking-tight text-white uppercase">
                    Reconfigure <span class="text-amber-500">System Role</span>
                </h1>
                <p class="text-[10px] text-gray-500 font-medium uppercase tracking-widest mt-1">
                    Modify Access Groups & Permission Clusters
                </p>
            </div>
            <a href="{{ route('admin.roles.index') }}" class="text-[9px] font-bold text-gray-500 hover:text-white uppercase tracking-widest transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to Roles Hub
            </a>
        </div>

        {{-- Role Edit Form (Notice the PUT method) --}}
        <form action="{{ route('admin.roles.update', $role->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                {{-- Left Side: Role Identity --}}
                <div class="md:col-span-1 space-y-4">
                    <div class="bg-[#0d0d0d] border border-white/10 rounded-lg p-5 shadow-xl">
                        <h3 class="text-[10px] font-black text-amber-500 uppercase tracking-[0.2em] border-b border-white/5 pb-2 mb-4">Role Identity</h3>
                        
                        <div>
                            <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-2">Role Designation</label>
                            {{-- We pre-fill the input with the current role name --}}
                            <input type="text" name="name" value="{{ old('name', $role->name) }}" required 
                                class="w-full bg-black border border-white/10 rounded px-4 py-2.5 text-xs text-white focus:ring-1 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all">
                            @error('name')
                                <p class="text-[9px] text-red-500 mt-1 uppercase font-bold">{{ $message }}</p>
                            @enderror
                        </div>

                        <p class="text-[9px] text-gray-600 leading-relaxed mt-4 italic">
                            Updating this role will instantly apply these changes to all connected agents.
                        </p>
                    </div>

                    <button type="submit" class="w-full py-3 bg-amber-600 hover:bg-amber-500 text-white rounded text-[10px] font-bold uppercase tracking-widest shadow-lg shadow-amber-600/20 transition-all">
                        Update Clearances
                    </button>
                </div>

                {{-- Right Side: Permission Grid --}}
                <div class="md:col-span-2">
                    <div class="bg-[#0d0d0d] border border-white/10 rounded-lg p-5 shadow-xl min-h-[300px]">
                        <h3 class="text-[10px] font-black text-blue-400 uppercase tracking-[0.2em] border-b border-white/5 pb-2 mb-4">Modify Clearances</h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @forelse($permissions as $permission)
                                <label class="flex items-center gap-3 p-3 rounded border border-white/5 hover:bg-white/[0.02] cursor-pointer transition-all group">
                                    <div class="relative flex items-center justify-center">
                                        
                                        {{-- We use $role->hasPermissionTo() to automatically check the box if they already have it --}}
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" 
                                            {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}
                                            class="peer appearance-none w-4 h-4 border border-gray-600 rounded bg-transparent checked:bg-amber-500 checked:border-amber-500 cursor-pointer transition-all">
                                        
                                        <svg class="absolute w-2.5 h-2.5 text-black opacity-0 peer-checked:opacity-100 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <span class="text-[10px] font-bold text-gray-400 group-hover:text-gray-200 uppercase tracking-wider transition-colors">
                                        {{ str_replace('_', ' ', $permission->name) }}
                                    </span>
                                </label>
                            @empty
                                <div class="col-span-2 py-10 text-center">
                                    <p class="text-[10px] text-gray-600 uppercase font-bold tracking-widest">No permissions found.</p>
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