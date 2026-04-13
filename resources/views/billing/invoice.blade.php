<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - #{{ $billing->invoice_no }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Montserrat', sans-serif; 
            background-color: #f3f4f6; 
            -webkit-print-color-adjust: exact !important; 
            print-color-adjust: exact !important; 
        }
        @media print {
            body { background-color: white !important; padding: 0 !important; }
            .no-print { display: none !important; }
            .print-shadow-none { box-shadow: none !important; margin: 0 !important; max-width: 100% !important; border-radius: 0 !important; }
            .page-break { page-break-inside: avoid; }
            * { -webkit-print-color-adjust: exact !important; color-adjust: exact !important; }
        }
        
        /* Custom Colors matched to the design */
        .text-dark { color: #2d3748; }
        .bg-dark { background-color: #333f50; }
        .bg-accent { background-color: #ffc107; }
        .border-accent { border-color: #ffc107; }

        /* Custom Stamp Effect for Status */
        .stamp {
            display: inline-block;
            padding: 0.25rem 1.5rem;
            text-transform: uppercase;
            border-radius: 0.5rem;
            font-family: 'Courier New', Courier, monospace;
            border: 4px solid;
            mask-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        }
    </style>
</head>
<body class="py-12 px-4 sm:px-6 antialiased text-gray-800">

    {{-- Floating Print Action --}}
    <div class="max-w-[800px] mx-auto flex justify-end mb-6 no-print">
        <button onclick="window.print()" class="px-6 py-2.5 bg-gray-900 hover:bg-gray-800 text-white text-xs font-bold uppercase tracking-widest rounded shadow-lg transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Download / Print
        </button>
    </div>

    {{-- Main Invoice Container (A4 Size approximation) --}}
    <div class="max-w-[800px] mx-auto bg-white shadow-2xl print-shadow-none relative pb-16 min-h-[1050px]">
        
        {{-- DYNAMIC WATERMARK SEAL --}}
        <div class="absolute top-[400px] left-1/2 -translate-x-1/2 opacity-10 transform -rotate-12 pointer-events-none select-none z-0">
            @if($billing->status === 'paid')
                <div class="stamp border-emerald-600 text-emerald-600 text-8xl font-black tracking-widest">PAID</div>
            @else
                <div class="stamp border-red-600 text-red-600 text-8xl font-black tracking-widest">DUE</div>
            @endif
        </div>

        {{-- 1. HEADER LOGO SECTION --}}
        <div class="px-10 pt-12 flex items-center gap-3 relative z-10">
            <div class="w-10 h-10 bg-dark text-white rounded flex items-center justify-center font-black text-xl shadow-md">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            <div class="leading-none">
                <h1 class="text-xl font-bold text-dark">eGeneration AI</h1>
                <p class="text-[7px] font-bold tracking-[0.25em] text-gray-500 uppercase mt-1">SaaS Directive Studio</p>
            </div>
        </div>

        {{-- 2. THE YELLOW BANNER & "INVOICE" TEXT --}}
        <div class="mt-8 flex items-center w-full relative z-10">
            <div class="h-10 bg-accent flex-1 rounded-r"></div>
            <div class="px-6 text-4xl sm:text-5xl font-black text-dark uppercase tracking-wide">
                INVOICE
            </div>
            <div class="h-10 bg-accent w-16 rounded-l"></div>
        </div>

        {{-- 3. BILLING INFO & META --}}
        <div class="mt-12 px-10 flex flex-col sm:flex-row justify-between items-start gap-8 relative z-10">
            {{-- Invoice To --}}
            <div>
                <h3 class="font-bold text-dark text-sm mb-2">Invoice to:</h3>
                <h2 class="text-lg font-bold text-dark">{{ $billing->user->name ?? 'Valued User' }}</h2>
                <p class="text-xs text-gray-500 font-medium leading-relaxed mt-1">
                    {{ $billing->user->email ?? '' }}<br>
                    Account ID: {{ substr(md5($billing->user_id), 0, 8) }}
                </p>
            </div>

            {{-- Invoice Details Grid (Dynamic Status) --}}
            <div class="bg-white">
                <table class="text-sm font-bold text-dark">
                    <tr>
                        <td class="pr-8 pb-3">Invoice#</td>
                        <td class="pb-3 text-right">{{ $billing->invoice_no }}</td>
                    </tr>
                    <tr>
                        <td class="pr-8 pb-3">Date</td>
                        <td class="pb-3 text-right">{{ $billing->created_at->format('d / m / Y') }}</td>
                    </tr>
                    <tr>
                        <td class="pr-8">Status</td>
                        <td class="text-right">
                            @if($billing->status === 'paid')
                                <span class="text-emerald-500 uppercase font-black tracking-widest">PAID</span>
                            @else
                                <span class="text-red-500 uppercase font-black tracking-widest">DUE</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- 4. INVOICE TABLE --}}
        <div class="mt-12 px-10 relative z-10">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-dark text-white text-[11px] font-bold uppercase tracking-wider">
                        <th class="py-3 px-4 w-16 text-center">SL.</th>
                        <th class="py-3 px-4">Item Description</th>
                        <th class="py-3 px-4 text-center">Price</th>
                        <th class="py-3 px-4 text-center">Qty.</th>
                        <th class="py-3 px-4 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="border-b border-l border-r border-gray-200 bg-white">
                    <tr class="border-b border-gray-100">
                        <td class="py-8 px-4 text-center font-bold text-dark text-sm">1</td>
                        <td class="py-8 px-4">
                            <p class="font-bold text-dark text-sm">{{ $billing->package->name ?? 'Custom' }} Package</p>
                            <p class="text-[11px] text-gray-500 font-medium mt-1">
                                {{ $billing->package->directive_allowance ?? 0 }} Prompts, 
                                {{ $billing->package->image_allowance ?? 0 }} Images, 
                                {{ $billing->package->video_allowance ?? 0 }} Videos.
                                <br>Billing Cycle: {{ ucfirst($billing->package->billing_cycle ?? 'One-time') }}
                            </p>
                        </td>
                        <td class="py-8 px-4 text-center font-bold text-dark text-sm">${{ number_format($billing->amount, 2) }}</td>
                        <td class="py-8 px-4 text-center font-bold text-dark text-sm">1</td>
                        <td class="py-8 px-4 text-right font-bold text-dark text-sm">${{ number_format($billing->amount, 2) }}</td>
                    </tr>
                    {{-- Empty spacer rows to give the table height --}}
                    <tr><td class="py-4 border-b border-gray-100"></td><td class="py-4 border-b border-gray-100"></td><td class="py-4 border-b border-gray-100"></td><td class="py-4 border-b border-gray-100"></td><td class="py-4 border-b border-gray-100"></td></tr>
                    <tr><td class="py-4"></td><td class="py-4"></td><td class="py-4"></td><td class="py-4"></td><td class="py-4"></td></tr>
                </tbody>
            </table>
        </div>

        {{-- 5. BOTTOM SECTION (Dynamic Terms vs Paid Info) --}}
        <div class="mt-10 px-10 flex flex-col sm:flex-row justify-between gap-8 relative z-10 page-break">
            
            {{-- Left: Terms & Payment Info --}}
            <div class="w-full sm:w-1/2 pr-0 sm:pr-8">
                <h4 class="font-bold text-dark text-sm mb-6">Thank you for your business</h4>
                
                @if($billing->status === 'paid')
                    {{-- UI WHEN PAID --}}
                    <div class="bg-emerald-50 border border-emerald-200 p-4 rounded text-emerald-800">
                        <h4 class="font-black text-xs uppercase tracking-widest mb-1 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            Payment Received
                        </h4>
                        <p class="text-[10px] font-bold">
                            Transaction completed successfully on {{ optional($billing->paid_at)->format('d / m / Y \a\t H:i') ?? 'the transaction date' }}. 
                            <br>Your neural generation credits are now fully active.
                        </p>
                    </div>
                @else
                    {{-- UI WHEN DUE --}}
                    <h4 class="font-bold text-dark text-xs mb-1">Terms & Conditions</h4>
                    <p class="text-[10px] font-medium text-gray-500 leading-relaxed mb-5">
                        Payment is due within 7 days of invoice date. Digital assets and generation credits remain locked until payment is verified by the administrator.
                    </p>

                    <h4 class="font-bold text-dark text-xs mb-2">Payment Info:</h4>
                    <table class="text-[10px] text-dark font-bold w-full">
                        <tr><td class="py-0.5 w-24 text-gray-500 font-medium">Account #:</td><td>1002 3994 5555</td></tr>
                        <tr><td class="py-0.5 w-24 text-gray-500 font-medium">A/C Name:</td><td>eGeneration Studio</td></tr>
                        <tr><td class="py-0.5 w-24 text-gray-500 font-medium">Bank Details:</td><td>Standard Corporate Bank</td></tr>
                    </table>
                @endif
            </div>

            {{-- Right: Totals & Signature --}}
            <div class="w-full sm:w-2/5 flex flex-col justify-start">
                <div class="flex justify-between text-sm font-bold text-dark mb-2 px-4">
                    <span>Sub Total:</span>
                    <span>${{ number_format($billing->amount, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm font-bold text-dark mb-4 px-4">
                    <span>Tax:</span>
                    <span>0.00%</span>
                </div>
                
                {{-- Highlighted Total Row --}}
                <div class="flex justify-between text-base font-black text-dark bg-accent py-3 px-4 rounded-l">
                    <span>Total:</span>
                    <span>${{ number_format($billing->amount, 2) }}</span>
                </div>
                
                {{-- Signature Line --}}
                <div class="mt-16 border-t-2 border-gray-300 mx-4 text-center pt-2">
                    <span class="text-[11px] font-bold text-dark">Authorised Sign</span>
                </div>
            </div>
        </div>

        {{-- 6. FOOTER LINE --}}
        <div class="absolute bottom-12 left-10 right-10">
            <div class="border-t-2 border-accent flex flex-wrap justify-center sm:justify-between items-center text-[10px] font-bold text-dark pt-4 gap-y-2">
                <span class="px-4">Phone: +880 123 456 789</span>
                <span class="hidden sm:inline text-accent">|</span>
                <span class="px-4">Address: Tech District, NY 10001</span>
                <span class="hidden sm:inline text-accent">|</span>
                <span class="px-4">Website: egeneration.co</span>
            </div>
        </div>

    </div>
</body>
</html>