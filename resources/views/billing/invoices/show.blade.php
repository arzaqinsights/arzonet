<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }} — Arzonet</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Outfit:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #0a0a0a; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        .invoice-page { width: 210mm; min-height: 297mm; margin: 0 auto; background: #fff; position: relative; overflow: hidden; }
        .invoice-content { padding: 30px 40px 20px; position: relative; z-index: 2; }

        .watermark { position: absolute; bottom: 10px; left: 0; width: 100%; display: flex; justify-content: center; z-index: 1; pointer-events: none; }
        .watermark img { width: 400px; opacity: 0.035; }

        /* Header */
        .inv-header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 18px; border-bottom: 2px solid #0a0a0a; margin-bottom: 20px; }
        .inv-header img { height: 32px; margin-bottom: 6px; }
        .inv-header .tag { font-size: 9px; color: #64748b; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; }
        .inv-header .right { text-align: right; }
        .inv-header h1 { font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 900; }
        .inv-header .num { font-size: 12px; font-weight: 700; color: #ff6b00; margin-top: 3px; }
        .inv-header .badge { display: inline-block; margin-top: 6px; padding: 3px 12px; font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; border-radius: 2px; }
        .s-paid { background: #dcfce7; color: #166534; }
        .s-pending { background: #fef9c3; color: #854d0e; }
        .s-failed { background: #fee2e2; color: #991b1b; }

        /* Info Grid */
        .info-row { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; margin-bottom: 18px; }
        .info-row h4 { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #94a3b8; margin-bottom: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; }
        .info-row .nm { font-size: 14px; font-weight: 800; margin-bottom: 3px; }
        .info-row .dt { font-size: 11px; color: #475569; line-height: 1.7; }
        .info-row .gs { display: inline-block; margin-top: 6px; padding: 3px 10px; background: #f1f5f9; font-size: 10px; font-weight: 700; border: 1px solid #e2e8f0; }

        /* Meta */
        .meta-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 18px; padding: 14px 16px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .meta-row label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; display: block; margin-bottom: 3px; }
        .meta-row span { font-size: 12px; font-weight: 700; }

        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        thead th { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; text-align: left; padding: 8px 12px; border-bottom: 2px solid #0a0a0a; }
        thead th:last-child, tbody td:last-child { text-align: right; }
        tbody td { padding: 12px 12px; font-size: 12px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .i-name { font-weight: 700; font-size: 12px; }
        .i-desc { font-size: 10px; color: #64748b; margin-top: 2px; }
        .i-hsn { font-size: 9px; color: #94a3b8; margin-top: 2px; }

        /* Totals */
        .totals { display: flex; justify-content: flex-end; margin-bottom: 16px; }
        .totals-box { width: 260px; }
        .t-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 12px; }
        .t-row .lb { color: #64748b; font-weight: 500; }
        .t-row .vl { font-weight: 700; }
        .t-grand { border-top: 2px solid #0a0a0a; margin-top: 8px; padding-top: 10px; display: flex; justify-content: space-between; align-items: center; }
        .t-grand .lb { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .t-grand .vl { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 900; color: #ff6b00; }

        /* Payment */
        .pay-row { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 14px; padding: 12px 14px; background: #fafafa; border: 1px solid #e2e8f0; margin-bottom: 16px; }
        .pay-row .pl { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 3px; }
        .pay-row .pv { font-size: 11px; font-weight: 600; }

        /* Footer */
        .inv-footer { padding-top: 14px; border-top: 1px solid #e2e8f0; }
        .inv-footer p { font-size: 9px; color: #94a3b8; line-height: 1.8; }

        @media print { body { background: #fff; } .no-print { display: none !important; } .invoice-page { margin: 0; box-shadow: none; } }
        @media screen { .invoice-page { box-shadow: 0 0 30px rgba(0,0,0,0.06); margin-top: 20px; margin-bottom: 40px; } }

        .action-bar { max-width: 210mm; margin: 16px auto 0; display: flex; justify-content: space-between; align-items: center; }
        .action-bar a { font-size: 12px; font-weight: 700; color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 6px; }
        .action-bar a:hover { color: #0a0a0a; }
        .action-bar .bp { padding: 8px 20px; background: #0a0a0a; color: #fff; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; border: none; cursor: pointer; display: flex; align-items: center; gap: 6px; }
        .action-bar .bp:hover { opacity: 0.85; }
    </style>
</head>
<body>

    <div class="action-bar no-print">
        <a href="{{ route('admin.billing.invoices.index') }}"><i class="fa-solid fa-arrow-left"></i> Back to Billing</a>
        <div style="display: flex; gap: 10px;">
            <button onclick="downloadInvoice()" class="bp" style="background: #ff6b00;"><i class="fa-solid fa-download"></i> Download PDF</button>
            <button onclick="window.print()" class="bp"><i class="fa-solid fa-print"></i> Print Invoice</button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadInvoice() {
            const element = document.querySelector('.invoice-page');
            const opt = {
                margin: 0,
                filename: 'Invoice-{{ $invoice->invoice_number }}.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>

    @php
        $details = $invoice->plan_details ?? [];
        $statusClass = match($invoice->status) { 'paid' => 's-paid', 'pending' => 's-pending', default => 's-failed' };
        
        // Check if new simplified format or old module-based format
        if (isset($details['plan']) && isset($details['limits'])) {
            // NEW FORMAT: simplified plan with limits
            $planKey = $details['plan'];
            $limits = $details['limits'];
            $rates = config('plans.rates', []);
            
            $items = [];
            
            if ($planKey !== 'custom') {
                // Fixed plan — single line item
                $plan = config("plans.plans.{$planKey}");
                $items[] = [
                    'name' => ucfirst($planKey) . ' Plan',
                    'desc' => 'CRM + Email Marketing + WhatsApp — all included',
                    'qty' => 1,
                    'amount' => $plan['price'] ?? 0
                ];
            } else {
                // Custom plan — per-unit line items
                if (!empty($limits['crm_users'])) {
                    $items[] = [
                        'name' => 'CRM Team Members',
                        'desc' => '₹' . ($rates['crm_per_user'] ?? 100) . ' per user/month',
                        'qty' => $limits['crm_users'],
                        'amount' => $limits['crm_users'] * ($rates['crm_per_user'] ?? 100)
                    ];
                }
                if (!empty($limits['crm_contacts'])) {
                    $items[] = [
                        'name' => 'CRM Contacts',
                        'desc' => '₹' . ($rates['crm_per_1k_contacts'] ?? 10) . ' per 1,000 contacts/month',
                        'qty' => $limits['crm_contacts'],
                        'amount' => ($limits['crm_contacts'] / 1000) * ($rates['crm_per_1k_contacts'] ?? 10)
                    ];
                }
                if (!empty($limits['emails_per_month'])) {
                    $items[] = [
                        'name' => 'Email Volume',
                        'desc' => '₹' . ($rates['email_per_1k'] ?? 100) . ' per 1,000 emails/month',
                        'qty' => $limits['emails_per_month'],
                        'amount' => ($limits['emails_per_month'] / 1000) * ($rates['email_per_1k'] ?? 100)
                    ];
                }
                if (!empty($limits['whatsapp_numbers'])) {
                    $items[] = [
                        'name' => 'WhatsApp Numbers',
                        'desc' => '₹' . ($rates['whatsapp_per_number'] ?? 500) . ' per number/month',
                        'qty' => $limits['whatsapp_numbers'],
                        'amount' => $limits['whatsapp_numbers'] * ($rates['whatsapp_per_number'] ?? 500)
                    ];
                }
                if (!empty($limits['whatsapp_messages'])) {
                    $items[] = [
                        'name' => 'WhatsApp Messages',
                        'desc' => '₹' . ($rates['whatsapp_per_message'] ?? 0.80) . ' per message/month',
                        'qty' => $limits['whatsapp_messages'],
                        'amount' => $limits['whatsapp_messages'] * ($rates['whatsapp_per_message'] ?? 0.80)
                    ];
                }
            }
            
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += $item['amount'];
            }
            $bundleDiscount = 0;
            
            $taxableAmount = $invoice->amount / 1.18;
            $taxAmount = $invoice->amount - $taxableAmount;
            
        } elseif (isset($details['plan_level'])) {
            // OLD FORMAT: module-based pricing (backward compatibility for old invoices)
            $planLevel = $details['plan_level'];
            $selectedModules = $details['selected_modules'] ?? [];
            $contacts = $details['contacts_limit'] ?? 0;
            $emails = $details['emails_limit'] ?? 0;
            $whatsapp = $details['whatsapp_limit'] ?? 0;
            $team = $details['team_limit'] ?? 0;
            
            $items = [];
            $items[] = [
                'name' => ucfirst($planLevel) . ' Plan License',
                'desc' => 'Modules: ' . implode(', ', array_map('ucfirst', $selectedModules)),
                'qty' => 1,
                'amount' => $invoice->amount / 1.18 // approximate base from total
            ];
            
            $subtotal = $invoice->amount / 1.18;
            $bundleDiscount = 0;
            
            $taxableAmount = $invoice->amount / 1.18;
            $taxAmount = $invoice->amount - $taxableAmount;
            
        } else {
            $contacts = $details['contacts_limit'] ?? 0;
            $emails = $details['emails_limit'] ?? 0;
            $contactsBasePrice = 200;
            $emailsBasePrice = 100;
            $contactsAmount = ($contacts / 1000) * $contactsBasePrice;
            $emailsAmount = ($emails / 1000) * $emailsBasePrice;
            $subtotal = $contactsAmount + $emailsAmount;
            $taxableAmount = $invoice->amount / 1.18;
            $taxAmount = $invoice->amount - $taxableAmount;
            
            $items = [
                [
                    'name' => 'Contact Storage Plan',
                    'desc' => 'Manage, filter & export contacts with dedup, bounce tracking & segmentation',
                    'qty' => $contacts,
                    'amount' => $contactsAmount
                ],
                [
                    'name' => 'Email Sending Volume',
                    'desc' => 'Bulk campaigns, transactional & service emails with tracking',
                    'qty' => $emails,
                    'amount' => $emailsAmount
                ],
                [
                    'name' => 'Platform License',
                    'desc' => '',
                    'qty' => 1,
                    'amount' => 0
                ]
            ];
        }
    @endphp

    <div class="invoice-page">
        <div class="invoice-content">

            <div class="inv-header">
                <div>
                    <img src="{{ asset('images/logo/logo.png') }}" alt="Arzonet" onerror="this.style.display='none'">
                    <div class="tag">Tax Invoice</div>
                </div>
                <div class="right">
                    <h1>INVOICE</h1>
                    <div class="num">{{ $invoice->invoice_number }}</div>
                    <div class="badge {{ $statusClass }}">{{ strtoupper($invoice->status) }}</div>
                </div>
            </div>

            <div class="info-row">
                <div>
                    <h4>From</h4>
                    <div class="nm">Arzonet</div>
                    <div class="dt">10/208, Mandai, Sandila, Hardoi, UP 241204<br>help@arzonet.com | www.arzonet.com</div>
                    <div class="gs">GSTIN: 09GIPPM8686H1Z5</div>
                </div>
                <div>
                    <h4>Bill To</h4>
                    <div class="nm">{{ $invoice->user->name }}</div>
                    <div class="dt">
                        @if($invoice->user->company_name){{ $invoice->user->company_name }}<br>@endif
                        {{ $invoice->user->email }}@if($invoice->user->phone_number) | {{ $invoice->user->phone_number }}@endif
                        @if($invoice->user->address_street)<br>{{ $invoice->user->address_street }}, {{ $invoice->user->address_city ?? '' }} {{ $invoice->user->address_zip ?? '' }}@endif
                    </div>
                    @if($invoice->user->gstin)<div class="gs">GSTIN: {{ $invoice->user->gstin }}</div>@endif
                </div>
            </div>

            <div class="meta-row">
                <div><label>Invoice Date</label><span>{{ $invoice->created_at->format('d M Y') }}</span></div>
                <div><label>Payment Date</label><span>{{ $invoice->status === 'paid' ? $invoice->updated_at->format('d M Y') : '—' }}</span></div>
                <div><label>Order ID</label><span>{{ $invoice->payment_id }}</span></div>
                <div><label>Currency</label><span>INR (₹)</span></div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width:6%">#</th>
                        <th style="width:60%">Description</th>
                        <th style="width:14%">Qty</th>
                        <th style="width:20%">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div class="i-name">{{ $item['name'] }}</div>
                            @if(!empty($item['desc']))
                            <div class="i-desc">{{ $item['desc'] }}</div>
                            @endif
                            <div class="i-hsn">SAC: 998315</div>
                        </td>
                        <td>{{ is_numeric($item['qty']) ? number_format($item['qty']) : $item['qty'] }}</td>
                        <td>{{ $item['amount'] > 0 ? '₹' . number_format($item['amount'], 2) : 'FREE' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="totals">
                <div class="totals-box">
                    <div class="t-row"><span class="lb">Subtotal</span><span class="vl">₹{{ number_format($subtotal, 2) }}</span></div>
                    @if($subtotal > $taxableAmount)
                    <div class="t-row"><span class="lb">Discount</span><span class="vl" style="color:#16a34a">- ₹{{ number_format($subtotal - $taxableAmount, 2) }}</span></div>
                    @endif
                    <div class="t-row"><span class="lb">Taxable Amount</span><span class="vl">₹{{ number_format($taxableAmount, 2) }}</span></div>
                    <div class="t-row"><span class="lb">CGST (9%)</span><span class="vl">₹{{ number_format($taxAmount / 2, 2) }}</span></div>
                    <div class="t-row"><span class="lb">SGST (9%)</span><span class="vl">₹{{ number_format($taxAmount / 2, 2) }}</span></div>
                    <div class="t-grand"><span class="lb">Total Amount</span><span class="vl">₹{{ number_format($invoice->amount, 2) }}</span></div>
                </div>
            </div>

            <div class="pay-row">
                <div><div class="pl">Payment Gateway</div><div class="pv">Cashfree Payments</div></div>
                <div><div class="pl">Transaction ID</div><div class="pv">{{ $invoice->payment_id }}</div></div>
                <div><div class="pl">Payment Method</div><div class="pv">Online (UPI/Card/NetBanking)</div></div>
                <div><div class="pl">Payment Status</div><div class="pv" style="color:{{ $invoice->status === 'paid' ? '#16a34a' : '#dc2626' }};font-weight:800">{{ strtoupper($invoice->status) }}</div></div>
            </div>

            <div class="inv-footer">
                <p>
                    Computer-generated invoice — no signature required. For queries: <strong>help@arzonet.com</strong><br>
                    <strong>Arzonet</strong> — 10/208, Mandai, Sandila, Hardoi, Uttar Pradesh 241204 | GSTIN: 09GIPPM8686H1Z5
                </p>
            </div>
        </div>

        <div class="watermark">
            <img src="{{ asset('images/logo/logo.png') }}" alt="" onerror="this.parentElement.style.display='none'">
        </div>
    </div>

</body>
</html>
