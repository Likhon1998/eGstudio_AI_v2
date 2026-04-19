<x-app-layout>
    <div class="max-w-7xl mx-auto py-10 px-6 antialiased">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 border-b border-white/10 pb-6 mb-8">
            <div>
                <h1 class="text-3xl font-black text-white uppercase tracking-widest">Manual Top-Up Ledger</h1>
                <p class="text-xs text-gray-500 font-bold uppercase mt-2">Audit trail of all manual credit injections</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="px-5 py-2.5 bg-white/5 hover:bg-white/10 text-white text-[10px] font-black uppercase tracking-widest rounded-lg transition-all border border-white/10">
                Back to Users
            </a>
        </div>

        <div class="bg-[#0a0a0a] border border-white/5 rounded-2xl overflow-hidden shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead class="bg-white/[0.02] border-b border-white/5">
                        <tr class="text-[9px] uppercase tracking-[0.2em] text-gray-500 font-bold">
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4">Client Name</th>
                            <th class="px-6 py-4">Asset Type</th>
                            <th class="px-6 py-4 text-center">Amount</th>
                            <th class="px-6 py-4">Billing Note</th>
                            <th class="px-6 py-4 text-right">Authorized By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.03]">
                        @forelse($logs as $log)
                            <tr class="hover:bg-white/[0.01] transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs text-gray-400 font-mono">{{ $log->created_at->format('M d, Y') }}</span>
                                    <span class="block text-[9px] text-gray-600 mt-0.5 uppercase tracking-widest">{{ $log->created_at->format('h:i A') }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-xs font-black text-white uppercase tracking-wider">{{ $log->user->name ?? 'Unknown Client' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 bg-blue-500/10 border border-blue-500/20 text-blue-400 text-[9px] font-black uppercase tracking-widest rounded">
                                        {{ str_replace('_', ' ', $log->credit_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="text-sm font-black text-emerald-400">+{{ $log->amount }}</span>
                                </td>
                                <td class="px-6 py-4 max-w-[250px] truncate">
                                    <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider" title="{{ $log->billing_note }}">
                                        {{ $log->billing_note ?: '—' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-[10px] text-gray-500 font-black uppercase tracking-widest">{{ $log->admin->name ?? 'System' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest">No manual top-ups recorded yet.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>