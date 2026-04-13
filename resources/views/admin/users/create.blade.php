<x-app-layout>
    {{-- Main Container --}}
    <div class="max-w-4xl mx-auto pt-6 pb-8 px-6 space-y-6 antialiased">
        
        {{-- Header Section --}}
        <div class="flex items-center justify-between border-b border-white/10 pb-6">
            <div>
                <h1 class="text-base font-bold tracking-tight text-white uppercase">
                    Provision <span class="text-blue-500">New Agent</span>
                </h1>
                <p class="text-[10px] text-gray-500 font-medium uppercase tracking-widest mt-1">
                    Assign Credentials & System Clearance Levels
                </p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="text-[9px] font-bold text-gray-500 hover:text-white uppercase tracking-widest transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to Roster
            </a>
        </div>

        {{-- User Creation Form --}}
        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                {{-- Left Side: Identity & Credentials --}}
                <div class="md:col-span-2 space-y-4">
                    <div class="bg-[#0d0d0d] border border-white/10 rounded-lg p-6 space-y-5 shadow-xl">
                        <h3 class="text-[10px] font-black text-blue-500 uppercase tracking-[0.2em] border-b border-white/5 pb-2">Primary Identity</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-2">Agent Full Name</label>
                                <input type="text" name="name" value="{{ old('name') }}" required 
                                    class="w-full bg-black border border-white/10 rounded px-4 py-2.5 text-xs text-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                    placeholder="e.g. Shahidul Islam">
                                @error('name') <p class="text-[9px] text-red-500 mt-1 uppercase font-bold">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-2">System Email (Login ID)</label>
                                <input type="email" name="email" value="{{ old('email') }}" required 
                                    class="w-full bg-black border border-white/10 rounded px-4 py-2.5 text-xs text-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                    placeholder="agent@egeneration.co">
                                @error('email') <p class="text-[9px] text-red-500 mt-1 uppercase font-bold">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-2">Access Key (Password)</label>
                                <input type="password" name="password" required 
                                    class="w-full bg-black border border-white/10 rounded px-4 py-2.5 text-xs text-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                    placeholder="••••••••">
                                @error('password') <p class="text-[9px] text-red-500 mt-1 uppercase font-bold">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right Side: Role Selection --}}
                <div class="md:col-span-1 space-y-4">
                    <div class="bg-[#0d0d0d] border border-white/10 rounded-lg p-6 shadow-xl h-full flex flex-col justify-between">
                        <div>
                            <h3 class="text-[10px] font-black text-purple-500 uppercase tracking-[0.2em] border-b border-white/5 pb-2 mb-4">Clearance Level</h3>
                            
                            {{-- Changed label text to reflect it's no longer a Spatie role --}}
                            <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-2">Assigned System Role</label>
                            
                            <select name="role_name" required 
                                class="w-full bg-black border border-white/10 rounded px-4 py-2.5 text-xs text-white focus:ring-1 focus:ring-purple-500 focus:border-purple-500 outline-none cursor-pointer uppercase font-mono tracking-tighter">
                                <option value="" disabled selected>-- Select Role --</option>
                                
                                {{-- Updated variable from $roles to $userRoles and removed ->name property --}}
                                @foreach($userRoles as $role)
                                    <option value="{{ $role }}" class="bg-[#0d0d0d]">{{ strtoupper($role) }}</option>
                                @endforeach
                                
                            </select>
                            
                            <div class="mt-4 p-3 bg-white/[0.02] border border-white/5 rounded">
                                <p class="text-[9px] text-gray-600 leading-relaxed italic">
                                    Selecting a role will assign the respective clearance level to this access group.
                                </p>
                            </div>
                            @error('role_name') <p class="text-[9px] text-red-500 mt-1 uppercase font-bold">{{ $message }}</p> @enderror
                        </div>

                        <div class="pt-6">
                            <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-500 text-white rounded text-[10px] font-bold uppercase tracking-widest shadow-lg shadow-blue-600/20 transition-all">
                                Deploy Agent
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </form>

    </div>
</x-app-layout>