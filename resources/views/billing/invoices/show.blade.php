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

        .invoice-page { width: 210mm; max-height: 297mm; margin: 0 auto; background: #fff; position: relative; overflow: hidden; }
        .invoice-content { padding: 28px 36px 20px; position: relative; z-index: 2; }

        .watermark { position: absolute; bottom: 10px; left: 0; width: 100%; display: flex; justify-content: center; z-index: 1; pointer-events: none; }
        .watermark img { width: 400px; opacity: 0.035; }

        /* Header */
        .inv-header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 14px; border-bottom: 2px solid #0a0a0a; margin-bottom: 16px; }
        .inv-header img { height: 28px; margin-bottom: 4px; }
        .inv-header .tag { font-size: 9px; color: #64748b; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; }
        .inv-header .right { text-align: right; }
        .inv-header h1 { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 900; }
        .inv-header .num { font-size: 11px; font-weight: 700; color: #ff6b00; margin-top: 2px; }
        .inv-header .badge { display: inline-block; margin-top: 4px; padding: 2px 10px; font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; border-radius: 2px; }
        .s-paid { background: #dcfce7; color: #166534; }
        .s-pending { background: #fef9c3; color: #854d0e; }
        .s-failed { background: #fee2e2; color: #991b1b; }

        /* Info Grid */
        .info-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 14px; }
        .info-row h4 { font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #94a3b8; margin-bottom: 6px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        .info-row .nm { font-size: 13px; font-weight: 800; margin-bottom: 2px; }
        .info-row .dt { font-size: 10px; color: #475569; line-height: 1.6; }
        .info-row .gs { display: inline-block; margin-top: 4px; padding: 2px 8px; background: #f1f5f9; font-size: 9px; font-weight: 700; border: 1px solid #e2e8f0; }

        /* Meta */
        .meta-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 14px; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .meta-row label { font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; display: block; margin-bottom: 2px; }
        .meta-row span { font-size: 11px; font-weight: 700; }

        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        thead th { font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; text-align: left; padding: 6px 10px; border-bottom: 2px solid #0a0a0a; }
        thead th:last-child, tbody td:last-child { text-align: right; }
        tbody td { padding: 8px 10px; font-size: 11px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .i-name { font-weight: 700; font-size: 11px; }
        .i-desc { font-size: 9px; color: #64748b; margin-top: 1px; }
        .i-hsn { font-size: 8px; color: #94a3b8; margin-top: 1px; }

        /* Totals */
        .totals { display: flex; justify-content: flex-end; margin-bottom: 12px; }
        .totals-box { width: 240px; }
        .t-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 11px; }
        .t-row .lb { color: #64748b; font-weight: 500; }
        .t-row .vl { font-weight: 700; }
        .t-grand { border-top: 2px solid #0a0a0a; margin-top: 6px; padding-top: 8px; display: flex; justify-content: space-between; align-items: center; }
        .t-grand .lb { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .t-grand .vl { font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 900; color: #ff6b00; }

        /* Payment */
        .pay-row { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 12px; padding: 10px 14px; background: #fafafa; border: 1px solid #e2e8f0; margin-bottom: 14px; }
        .pay-row .pl { font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 2px; }
        .pay-row .pv { font-size: 10px; font-weight: 600; }

        /* Footer */
        .inv-footer { padding-top: 10px; border-top: 1px solid #e2e8f0; }
        .inv-footer p { font-size: 8px; color: #94a3b8; line-height: 1.7; }

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
        <button onclick="window.print()" class="bp"><i class="fa-solid fa-print"></i> Print Invoice</button>
    </div>

    @php
        $details = $invoice->plan_details ?? [];
        $contacts = $details['contacts_limit'] ?? 0;
        $emails = $details['emails_limit'] ?? 0;
        $contactsBasePrice = 200;
        $emailsBasePrice = 100;
        $contactsAmount = ($contacts / 1000) * $contactsBasePrice;
        $emailsAmount = ($emails / 1000) * $emailsBasePrice;
        $subtotal = $contactsAmount + $emailsAmount;
        $taxableAmount = $invoice->amount / 1.18;
        $taxAmount = $invoice->amount - $taxableAmount;
        $statusClass = match($invoice->status) { 'paid' => 's-paid', 'pending' => 's-pending', default => 's-failed' };
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
                    <tr>
                        <td>1</td>
                        <td>
                            <div class="i-name">Contact Storage Plan</div>
                            <div class="i-desc">Manage, filter & export contacts with dedup, bounce tracking & segmentation</div>
                            <div class="i-hsn">SAC: 998361</div>
                        </td>
                        <td>{{ number_format($contacts) }}</td>
                        <td>₹{{ number_format($contactsAmount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>
                            <div class="i-name">Email Sending Volume</div>
                            <div class="i-desc">Bulk campaigns, transactional & service emails with tracking</div>
                            <div class="i-hsn">SAC: 998315</div>
                        </td>
                        <td>{{ number_format($emails) }}/mo</td>
                        <td>₹{{ number_format($emailsAmount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td><div class="i-name">Platform License</div></td>
                        <td>1</td>
                        <td>FREE</td>
                    </tr>
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
