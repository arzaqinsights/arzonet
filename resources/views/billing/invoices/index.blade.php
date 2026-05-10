@extends('layouts.app')

@section('content')
<div class="p-6 md:p-10 max-w-5xl mx-auto">
    <div class="flex justify-between items-end mb-10">
        <div>
            <h1 class="text-3xl font-black text-black mb-2 font-['Outfit'] italic underline decoration-brand decoration-4 underline-offset-8">Billing History</h1>
            <p class="text-surface-500 font-medium">View and download your past transaction receipts.</p>
        </div>
        <a href="{{ route('admin.billing.plans') }}" class="px-6 py-3 bg-black text-white text-xs font-black rounded-sm uppercase tracking-widest hover:bg-surface-800 transition-all shadow-lg shadow-black/10">
            Change Plan
        </a>
    </div>

    <div class="bg-white border border-surface-200 rounded-sm shadow-sm overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-surface-50 border-b border-surface-200">
                    <th class="px-6 py-4 text-[10px] font-black text-surface-400 uppercase tracking-widest">Invoice #</th>
                    <th class="px-6 py-4 text-[10px] font-black text-surface-400 uppercase tracking-widest">Date</th>
                    <th class="px-6 py-4 text-[10px] font-black text-surface-400 uppercase tracking-widest">Amount</th>
                    <th class="px-6 py-4 text-[10px] font-black text-surface-400 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-4 text-[10px] font-black text-surface-400 uppercase tracking-widest text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100">
                @forelse($invoices as $invoice)
                <tr class="hover:bg-surface-50 transition-colors">
                    <td class="px-6 py-4">
                        <span class="text-sm font-black text-black">{{ $invoice->invoice_number }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-xs font-bold text-surface-500">{{ $invoice->created_at->format('M d, Y') }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-black text-black">₹{{ number_format($invoice->amount, 2) }}</span>
                    </td>
                    <td class="px-6 py-4">
                        @if($invoice->status === 'paid')
                            <span class="px-2.5 py-1 bg-green-50 text-green-600 text-[10px] font-black uppercase tracking-widest rounded-sm border border-green-100">Paid</span>
                        @else
                            <span class="px-2.5 py-1 bg-amber-50 text-amber-600 text-[10px] font-black uppercase tracking-widest rounded-sm border border-amber-100">Pending</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('admin.billing.invoices.show', $invoice) }}" class="inline-flex items-center gap-2 text-[10px] font-black text-brand uppercase tracking-widest hover:underline">
                            <i class="fa-solid fa-file-invoice"></i> View Receipt
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-20 text-center">
                        <div class="flex flex-col items-center gap-4">
                            <div class="w-16 h-16 bg-surface-50 rounded-full flex items-center justify-center text-surface-200">
                                <i class="fa-solid fa-receipt text-3xl"></i>
                            </div>
                            <p class="text-surface-400 font-bold">No transactions found yet.</p>
                            <a href="{{ route('admin.billing.plans') }}" class="text-xs font-black text-brand uppercase tracking-widest hover:underline">Purchase a plan to get started</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $invoices->links() }}
    </div>
</div>
@endsection
