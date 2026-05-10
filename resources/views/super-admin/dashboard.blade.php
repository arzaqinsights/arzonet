@extends('layouts.super-admin')

@section('content')
<div class="p-6 md:p-10">
    <div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-black mb-2 font-['Outfit']">Super Admin Command Center</h1>
            <p class="text-surface-500 font-medium">Real-time platform metrics and global controls.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.super.users') }}" class="px-5 py-2.5 bg-black text-white text-sm font-bold rounded-sm hover:bg-surface-800 transition-all flex items-center gap-2">
                <i class="fa-solid fa-users"></i> Manage Users
            </a>
            <a href="{{ route('admin.super.settings') }}" class="px-5 py-2.5 bg-brand text-white text-sm font-bold rounded-sm hover:bg-brand/90 transition-all flex items-center gap-2">
                <i class="fa-solid fa-gear"></i> Pricing Rules
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
        <div class="bg-white p-6 border border-surface-200 rounded-sm">
            <p class="text-xs font-bold text-surface-400 uppercase tracking-wider mb-2">Total Users</p>
            <h3 class="text-3xl font-black text-black">{{ number_format($stats['total_users']) }}</h3>
        </div>
        <div class="bg-white p-6 border border-surface-200 rounded-sm">
            <p class="text-xs font-bold text-surface-400 uppercase tracking-wider mb-2">Global Contacts</p>
            <h3 class="text-3xl font-black text-black">{{ number_format($stats['total_contacts']) }}</h3>
        </div>
        <div class="bg-white p-6 border border-surface-200 rounded-sm">
            <p class="text-xs font-bold text-surface-400 uppercase tracking-wider mb-2">Total Emails Sent</p>
            <h3 class="text-3xl font-black text-black">{{ number_format($stats['total_emails_sent']) }}</h3>
        </div>
        <div class="bg-white p-6 border border-surface-200 rounded-sm">
            <p class="text-xs font-bold text-surface-400 uppercase tracking-wider mb-2">Active Subs</p>
            <h3 class="text-3xl font-black text-black">{{ number_format($stats['active_subscriptions']) }}</h3>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <!-- Recent Activity/Invoices -->
        <div class="lg:col-span-2">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-black text-black font-['Outfit']">Recent Transactions</h2>
                <span class="text-xs font-bold text-surface-400 uppercase">Last 10 Payments</span>
            </div>
            
            <div class="bg-white border border-surface-200 rounded-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-surface-50 border-b border-surface-200">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black text-surface-400 uppercase tracking-widest">Invoice</th>
                            <th class="px-6 py-4 text-[10px] font-black text-surface-400 uppercase tracking-widest">User</th>
                            <th class="px-6 py-4 text-[10px] font-black text-surface-400 uppercase tracking-widest">Amount</th>
                            <th class="px-6 py-4 text-[10px] font-black text-surface-400 uppercase tracking-widest">Status</th>
                            <th class="px-6 py-4 text-[10px] font-black text-surface-400 uppercase tracking-widest">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100">
                        @forelse($recentInvoices as $invoice)
                        <tr class="hover:bg-surface-50/50 transition-colors">
                            <td class="px-6 py-4 text-xs font-bold text-black">#{{ $invoice->invoice_number }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-black">{{ $invoice->user->name }}</span>
                                    <span class="text-[10px] text-surface-400">{{ $invoice->user->email }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs font-black text-black">₹{{ number_format($invoice->amount) }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-[10px] font-black uppercase {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-600' : 'bg-amber-100 text-amber-600' }}">
                                    {{ $invoice->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-surface-500 font-medium">{{ $invoice->created_at->format('M d, Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-surface-400 italic text-sm">No transactions yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- System Health / Quick Links -->
        <div>
            <h2 class="text-xl font-black text-black font-['Outfit'] mb-6">System Health</h2>
            <div class="space-y-4">
                <div class="bg-surface-50 p-5 rounded-sm border border-surface-100">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-black text-surface-500 uppercase tracking-wider">Queue Workers</span>
                        <span class="w-2 h-2 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.6)]"></span>
                    </div>
                    <p class="text-xs text-surface-600 leading-relaxed font-medium">All background workers are active and processing high, bulk, and default queues.</p>
                </div>

                <div class="bg-surface-50 p-5 rounded-sm border border-surface-100">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-black text-surface-500 uppercase tracking-wider">Payment Gateway</span>
                        <span class="text-[10px] font-black text-brand uppercase">Cashfree Active</span>
                    </div>
                    <p class="text-xs text-surface-600 leading-relaxed font-medium">API connection stable. Webhooks are configured and ready for automated activation.</p>
                </div>
                
                <div class="bg-black p-6 rounded-sm text-white">
                    <h4 class="text-sm font-black mb-2 font-['Outfit']">Need Help?</h4>
                    <p class="text-[11px] text-white/70 mb-4 font-medium">As super admin, you have full access to database records. Use with caution.</p>
                    <a href="https://github.com/arzaqinsights/arzonet/wiki" class="text-[10px] font-black uppercase text-brand hover:underline">Documentation &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
