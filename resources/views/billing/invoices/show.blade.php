<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arzonet Receipt {{ $invoice->invoice_number }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #ffffff; 
            color: #1a1a1a;
            -webkit-print-color-adjust: exact;
        }
        
        @media print {
            .no-print { display: none !important; }
            body { padding: 0 !important; }
            .invoice-shell { border: none !important; padding: 0 !important; max-width: 100% !important; }
        }

        .hsn-text { font-size: 10px; color: #666; margin-top: 2px; }
    </style>
</head>
<body class="p-4 md:p-12">

    <!-- Action Bar -->
    <div class="max-w-4xl mx-auto no-print mb-8 flex justify-between items-center border-b pb-6">
        <a href="{{ route('admin.billing.invoices.index') }}" class="text-sm font-bold text-gray-500 hover:text-black flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Billing History
        </a>
        <button onclick="window.print()" class="px-6 py-2.5 bg-black text-white font-black text-xs rounded-sm uppercase tracking-widest hover:opacity-90 transition-all shadow-xl shadow-black/10">
            <i class="fa-solid fa-download mr-2"></i> Download Receipt
        </button>
    </div>

    <div class="max-w-4xl mx-auto invoice-shell">
        
        <!-- Top Branding -->
        <div class="mb-8">
            <p class="text-[11px] font-bold text-gray-400 mb-4">Arzonet SaaS Platform</p>
            <h1 class="text-4xl font-extrabold tracking-tight text-black mb-12">Arzonet Receipt {{ $invoice->invoice_number }}</h1>
        </div>

        <!-- Three Column Header -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-12 mb-16">
            <!-- Issued To -->
            <div>
                <h3 class="text-base font-black mb-4">Issued to</h3>
                <p class="text-sm font-medium text-gray-900">{{ $invoice->user->email }}</p>
                <p class="text-sm text-gray-500 leading-relaxed mt-1">
                    {{ $invoice->user->name }}<br>
                    @if($invoice->user->company_name) {{ $invoice->user->company_name }}<br> @endif
                    @if($invoice->user->address_street) {{ $invoice->user->address_street }}, {{ $invoice->user->address_city }}<br> @endif
                    @if($invoice->user->gstin) <span class="font-bold text-black">Tax ID: {{ $invoice->user->gstin }}</span> @endif
                </p>
            </div>

            <!-- Issued By -->
            <div>
                <h3 class="text-base font-black mb-4">Issued by</h3>
                <p class="text-sm font-medium text-gray-900">Arzonet</p>
                <p class="text-sm text-gray-500 leading-relaxed mt-1">
                    Arzaq Insights Pvt Ltd<br>
                    123, Okhla Phase III<br>
                    New Delhi, India 110020<br>
                    <a href="https://arzonet.com" class="text-brand font-bold underline">www.arzonet.com</a><br>
                    <span class="font-bold text-black">Tax ID: 07AAGTM3462J2ZB</span>
                </p>
            </div>

            <!-- Details -->
            <div>
                <h3 class="text-base font-black mb-4">Details</h3>
                <div class="space-y-1">
                    <p class="text-sm text-gray-500"><span class="font-bold text-black">Order#</span> {{ substr($invoice->payment_id, -8) }}</p>
                    <p class="text-sm text-gray-500"><span class="font-bold text-black">Date Paid:</span> {{ $invoice->created_at->format('M d, Y h:i a') }}</p>
                </div>
            </div>
        </div>

        <!-- Billing Statement -->
        <div class="mb-12">
            <h2 class="text-xl font-black mb-8 border-b-2 border-black pb-4">Billing statement</h2>
            
            @php
                $details = $invoice->plan_details;
                $contacts = $details['contacts_limit'] ?? 0;
                $emails = $details['emails_limit'] ?? 0;
                $taxableAmount = $invoice->amount / 1.18;
                $taxAmount = $invoice->amount - $taxableAmount;
            @endphp

            <div class="space-y-10">
                <!-- Contacts Line -->
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="text-base font-bold mb-1">Standard Contact Plan</h4>
                        <p class="text-sm text-gray-500">{{ number_format($contacts) }} contacts</p>
                        <p class="hsn-text">HSN Code: 998361</p>
                    </div>
                    <div class="text-right">
                        <span class="text-base font-bold">₹{{ number_format(($contacts/1000) * 200, 2) }}</span>
                    </div>
                </div>

                <!-- Emails Line -->
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="text-base font-bold mb-1">Email Volume Add-on</h4>
                        <p class="text-sm text-gray-500">{{ number_format($emails) }} emails/month</p>
                        <p class="hsn-text">HSN Code: 998315</p>
                    </div>
                    <div class="text-right">
                        <span class="text-base font-bold">₹{{ number_format(($emails/1000) * 100, 2) }}</span>
                    </div>
                </div>

                <!-- Taxable Amount -->
                <div class="pt-8 border-t border-gray-200 flex justify-between items-center">
                    <h4 class="text-base font-bold">Taxable amount</h4>
                    <span class="text-base font-bold">₹{{ number_format($taxableAmount, 2) }}</span>
                </div>

                <!-- GST -->
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 italic">GST (18%)</h4>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-bold">₹{{ number_format($taxAmount, 2) }}</span>
                    </div>
                </div>

                <!-- Paid Via -->
                <div class="pt-8 border-t border-gray-200 flex justify-between items-start">
                    <div class="text-gray-500 text-xs italic leading-relaxed">
                        Paid via Cashfree Payment Gateway<br>
                        Transaction ID: {{ $invoice->payment_id }}<br>
                        on {{ $invoice->created_at->format('M d, Y') }}
                    </div>
                    <div class="text-right">
                        <span class="text-xl font-black">₹{{ number_format($invoice->amount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Balance Box -->
        <div class="border-2 border-black p-6 flex justify-between items-center mb-16">
            <h3 class="text-lg font-black uppercase tracking-widest">Balance as of {{ $invoice->created_at->format('M d, Y') }}</h3>
            <span class="text-2xl font-black">₹0.00</span>
        </div>

        <!-- Fine Print -->
        <div class="text-xs text-gray-400 leading-relaxed space-y-4">
            <p>If a refund is required, it will be issued in the purchase currency for the amount of the original charge.</p>
            <p>This is a computer-generated receipt and does not require a physical signature. For any billing queries, contact support@arzonet.com.</p>
        </div>

        <!-- Logo Footer -->
        <div class="mt-20 pt-10 border-t border-gray-100">
            <img src="{{ asset('images/logo/logo.png') }}" class="h-8 grayscale opacity-50">
        </div>
    </div>

</body>
</html>
