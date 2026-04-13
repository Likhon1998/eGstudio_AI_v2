<x-app-layout>
    <div class="max-w-6xl mx-auto pt-6 pb-8 px-6 space-y-6 antialiased">
        
        {{-- Header Section --}}
        <div class="flex items-center justify-between border-b border-white/10 pb-6">
            <div>
                <h1 class="text-base font-bold tracking-tight text-white uppercase">
                    System <span class="text-purple-500">Roles</span>
                </h1>
                <p class="text-[10px] text-gray-500 font-medium uppercase tracking-widest mt-1">
                    Manage Access Groups & Clearances
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-white/5 hover:bg-white/10 text-gray-400 border border-white/10 rounded text-[9px] font-bold uppercase tracking-widest transition-all">
                    Back to Console
                </a>
                
                {{-- THE ADD ROLE BUTTON --}}
                <a href="{{ route('admin.roles.create') }}" class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded text-[9px] font-bold uppercase tracking-widest shadow-lg shadow-purple-600/20 transition-all flex items-center gap-2">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                    Initialize Role
                </a>
            </div>
        </div>

        {{-- Roles Table --}}
        <div class="bg-[#0d0d0d] border border-white/10 rounded-lg overflow-hidden shadow-2xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white/[0.02] border-b border-white/10 text-[9px] uppercase tracking-[0.2em] text-gray-500 font-bold">
                        <th class="px-6 py-4">Role Designation</th>
                        <th class="px-6 py-4">Attached Clearances (Permissions)</th>
                        <th class="px-6 py-4 text-right">Created</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.03]">
                    @forelse($roles as $role)
                    <tr class="hover:bg-white/[0.01] transition-colors group">
                        <td class="px-6 py-4">
                            <span class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                {{ $role->name }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1.5">
                                @forelse($role->permissions as $permission)
                                    <span class="px-1.5 py-0.5 bg-purple-500/5 border border-purple-500/20 text-purple-400/80 rounded text-[8px] font-mono uppercase">
                                        {{ str_replace('_', ' ', $permission->name) }}
                                    </span>
                                @empty
                                    <span class="text-[9px] text-gray-600 italic">No clearances attached</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-[10px] font-mono text-gray-500">{{ $role->created_at->format('Y-m-d') }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.roles.edit', $role->id) }}" class="inline-block px-3 py-1.5 bg-amber-500/10 text-amber-500 hover:bg-amber-500/20 border border-amber-500/20 rounded text-[9px] font-bold uppercase tracking-widest transition-all">
                                Edit
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center">
                            <p class="text-[10px] text-gray-500 uppercase font-bold tracking-widest">No System Roles Found</p>
                            <p class="text-[9px] text-gray-600 mt-1">Click 'Initialize Role' to create your first access group.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>