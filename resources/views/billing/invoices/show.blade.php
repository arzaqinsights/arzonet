<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }} — Arzonet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Outfit:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --brand: #ff6b00;
            --ink: #0a0a0a;
            --muted: #64748b;
            --light: #f1f5f9;
            --border: #e2e8f0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #f8fafc;
            color: var(--ink);
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .invoice-page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #fff;
            position: relative;
            overflow: hidden;
        }

        .invoice-content {
            padding: 40px 48px;
            position: relative;
            z-index: 2;
        }

        /* Watermark Logo */
        .watermark {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
            pointer-events: none;
        }
        .watermark img {
            width: 100%;
            max-width: 600px;
            opacity: 0.04;
        }

        /* Header */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 24px;
            border-bottom: 3px solid var(--ink);
        }
        .invoice-header .logo-area img {
            height: 36px;
            margin-bottom: 8px;
        }
        .invoice-header .logo-area p {
            font-size: 10px;
            color: var(--muted);
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 32px;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: var(--ink);
        }
        .invoice-title .inv-number {
            font-size: 13px;
            font-weight: 700;
            color: var(--brand);
            margin-top: 4px;
        }
        .invoice-title .inv-status {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 14px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border-radius: 2px;
        }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef9c3; color: #854d0e; }
        .status-failed { background: #fee2e2; color: #991b1b; }

        /* Two Column Info */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 36px;
            margin-bottom: 36px;
        }
        .info-block h3 {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--muted);
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 1px solid var(--border);
        }
        .info-block .name {
            font-size: 15px;
            font-weight: 800;
            color: var(--ink);
            margin-bottom: 4px;
        }
        .info-block .detail {
            font-size: 12px;
            color: #475569;
            line-height: 1.7;
        }
        .info-block .gstin {
            display: inline-block;
            margin-top: 8px;
            padding: 3px 10px;
            background: var(--light);
            font-size: 11px;
            font-weight: 700;
            color: var(--ink);
            border: 1px solid var(--border);
            letter-spacing: 0.5px;
        }

        /* Details Row */
        .details-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 36px;
            padding: 16px 20px;
            background: var(--light);
            border: 1px solid var(--border);
        }
        .detail-item label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--muted);
            display: block;
            margin-bottom: 4px;
        }
        .detail-item span {
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
        }

        /* Table */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }
        .invoice-table thead th {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--muted);
            text-align: left;
            padding: 10px 16px;
            border-bottom: 2px solid var(--ink);
        }
        .invoice-table thead th:last-child,
        .invoice-table tbody td:last-child {
            text-align: right;
        }
        .invoice-table tbody td {
            padding: 14px 16px;
            font-size: 13px;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }
        .invoice-table .item-name {
            font-weight: 700;
            color: var(--ink);
        }
        .invoice-table .item-desc {
            font-size: 11px;
            color: var(--muted);
            margin-top: 2px;
        }
        .invoice-table .item-hsn {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 2px;
        }

        /* Totals */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 36px;
        }
        .totals-box {
            width: 280px;
        }
        .totals-box .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 13px;
        }
        .totals-box .total-row .label {
            color: var(--muted);
            font-weight: 500;
        }
        .totals-box .total-row .value {
            font-weight: 700;
            color: var(--ink);
        }
        .totals-box .grand-total {
            border-top: 2px solid var(--ink);
            margin-top: 8px;
            padding-top: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .totals-box .grand-total .label {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--ink);
        }
        .totals-box .grand-total .value {
            font-family: 'Outfit', sans-serif;
            font-size: 26px;
            font-weight: 900;
            color: var(--brand);
        }

        /* Payment Info */
        .payment-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            padding: 20px 24px;
            background: #fafafa;
            border: 1px solid var(--border);
            margin-bottom: 36px;
        }
        .payment-info .pi-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--muted);
            margin-bottom: 4px;
        }
        .payment-info .pi-value {
            font-size: 12px;
            font-weight: 600;
            color: var(--ink);
        }

        /* Footer */
        .invoice-footer {
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }
        .invoice-footer p {
            font-size: 10px;
            color: #94a3b8;
            line-height: 1.8;
        }

        /* Print Styles */
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .invoice-page {
                margin: 0;
                box-shadow: none;
                border: none;
            }
            .action-bar { display: none !important; }
        }

        @media screen {
            .invoice-page {
                box-shadow: 0 0 40px rgba(0,0,0,0.08);
                margin-top: 24px;
                margin-bottom: 60px;
            }
        }

        /* Action Bar */
        .action-bar {
            max-width: 210mm;
            margin: 24px auto 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 12px;
        }
        .action-bar a {
            font-size: 13px;
            font-weight: 700;
            color: var(--muted);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.2s;
        }
        .action-bar a:hover { color: var(--ink); }
        .action-bar .btn-print {
            padding: 10px 24px;
            background: var(--ink);
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .action-bar .btn-print:hover { opacity: 0.85; }
    </style>
</head>
<body>

    <!-- Action Bar (Hidden on Print) -->
    <div class="action-bar no-print">
        <a href="{{ route('admin.billing.invoices.index') }}">
            <i class="fa-solid fa-arrow-left"></i> Back to Billing
        </a>
        <button onclick="window.print()" class="btn-print">
            <i class="fa-solid fa-print"></i> Print Invoice
        </button>
    </div>

    @php
        $details = $invoice->plan_details ?? [];
        $contacts = $details['contacts_limit'] ?? 0;
        $emails = $details['emails_limit'] ?? 0;

        // Pricing config
        $contactsBasePrice = 200; // per 1000
        $emailsBasePrice = 100;   // per 1000
        $taxPercent = 18;

        $contactsAmount = ($contacts / 1000) * $contactsBasePrice;
        $emailsAmount = ($emails / 1000) * $emailsBasePrice;
        $subtotal = $contactsAmount + $emailsAmount;
        $taxableAmount = $invoice->amount / (1 + ($taxPercent / 100));
        $taxAmount = $invoice->amount - $taxableAmount;

        $statusClass = match($invoice->status) {
            'paid' => 'status-paid',
            'pending' => 'status-pending',
            default => 'status-failed',
        };
    @endphp

    <!-- Invoice Page (A4) -->
    <div class="invoice-page">
        <div class="invoice-content">

            <!-- Header -->
            <div class="invoice-header">
                <div class="logo-area">
                    <img src="{{ asset('images/logo/logo.png') }}" alt="Arzonet" onerror="this.style.display='none'">
                    <p>Tax Invoice</p>
                </div>
                <div class="invoice-title">
                    <h1>INVOICE</h1>
                    <div class="inv-number">{{ $invoice->invoice_number }}</div>
                    <div class="inv-status {{ $statusClass }}">{{ strtoupper($invoice->status) }}</div>
                </div>
            </div>

            <!-- Company & Customer Info -->
            <div class="info-grid">
                <!-- From: Arzonet -->
                <div class="info-block">
                    <h3>From</h3>
                    <div class="name">Arzonet</div>
                    <div class="detail">
                        10/208, Mandai, Sandila<br>
                        Hardoi, Uttar Pradesh 241204<br>
                        India<br>
                        <strong>Email:</strong> help@arzonet.com<br>
                        <strong>Web:</strong> www.arzonet.com
                    </div>
                    <div class="gstin">GSTIN: 09GIPPM8686H1Z5</div>
                </div>

                <!-- To: Customer -->
                <div class="info-block">
                    <h3>Bill To</h3>
                    <div class="name">{{ $invoice->user->name }}</div>
                    <div class="detail">
                        @if($invoice->user->company_name){{ $invoice->user->company_name }}<br>@endif
                        {{ $invoice->user->email }}<br>
                        @if($invoice->user->phone_number){{ $invoice->user->phone_number }}<br>@endif
                        @if($invoice->user->address_street)
                            {{ $invoice->user->address_street }}<br>
                            {{ $invoice->user->address_city ?? '' }}{{ $invoice->user->address_state ? ', ' . $invoice->user->address_state : '' }}
                            {{ $invoice->user->address_zip ?? '' }}<br>
                            {{ $invoice->user->address_country ?? 'India' }}
                        @endif
                    </div>
                    @if($invoice->user->gstin)
                        <div class="gstin">GSTIN: {{ $invoice->user->gstin }}</div>
                    @endif
                </div>
            </div>

            <!-- Invoice Meta Details -->
            <div class="details-row">
                <div class="detail-item">
                    <label>Invoice Date</label>
                    <span>{{ $invoice->created_at->format('d M Y') }}</span>
                </div>
                <div class="detail-item">
                    <label>Payment Date</label>
                    <span>{{ $invoice->status === 'paid' ? $invoice->updated_at->format('d M Y') : '—' }}</span>
                </div>
                <div class="detail-item">
                    <label>Order ID</label>
                    <span>{{ $invoice->payment_id }}</span>
                </div>
                <div class="detail-item">
                    <label>Currency</label>
                    <span>{{ $invoice->currency ?? 'INR' }} (₹)</span>
                </div>
            </div>

            <!-- Line Items Table -->
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th style="width:10%">#</th>
                        <th style="width:50%">Description</th>
                        <th style="width:15%">Qty</th>
                        <th style="width:10%">Rate</th>
                        <th style="width:15%">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>
                            <div class="item-name">Contact Storage Plan</div>
                            <div class="item-desc">Store, manage, filter & export contacts with duplicate removal, typo fixing, bounce tracking, and advanced segmentation.</div>
                            <div class="item-hsn">SAC: 998361</div>
                        </td>
                        <td>{{ number_format($contacts) }}</td>
                        <td>₹{{ $contactsBasePrice }}/1K</td>
                        <td>₹{{ number_format($contactsAmount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>
                            <div class="item-name">Email Sending Volume</div>
                            <div class="item-desc">Bulk campaigns, transactional alerts & service notifications with full personalization, open/click tracking, and unsubscribe management.</div>
                            <div class="item-hsn">SAC: 998315</div>
                        </td>
                        <td>{{ number_format($emails) }}/mo</td>
                        <td>₹{{ $emailsBasePrice }}/1K</td>
                        <td>₹{{ number_format($emailsAmount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>
                            <div class="item-name">Platform License</div>
                            <div class="item-desc">Dashboard, analytics, campaign builder, template designer, domain verification, sender management & team access.</div>
                        </td>
                        <td>1</td>
                        <td>—</td>
                        <td>FREE</td>
                    </tr>
                </tbody>
            </table>

            <!-- Totals -->
            <div class="totals-section">
                <div class="totals-box">
                    <div class="total-row">
                        <span class="label">Subtotal</span>
                        <span class="value">₹{{ number_format($subtotal, 2) }}</span>
                    </div>
                    @if($subtotal > $taxableAmount)
                    <div class="total-row">
                        <span class="label">Discount</span>
                        <span class="value" style="color: #16a34a;">- ₹{{ number_format($subtotal - $taxableAmount, 2) }}</span>
                    </div>
                    @endif
                    <div class="total-row">
                        <span class="label">Taxable Amount</span>
                        <span class="value">₹{{ number_format($taxableAmount, 2) }}</span>
                    </div>
                    <div class="total-row">
                        <span class="label">CGST (9%)</span>
                        <span class="value">₹{{ number_format($taxAmount / 2, 2) }}</span>
                    </div>
                    <div class="total-row">
                        <span class="label">SGST (9%)</span>
                        <span class="value">₹{{ number_format($taxAmount / 2, 2) }}</span>
                    </div>
                    <div class="grand-total">
                        <span class="label">Total Amount</span>
                        <span class="value">₹{{ number_format($invoice->amount, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="payment-info">
                <div>
                    <div class="pi-label">Payment Gateway</div>
                    <div class="pi-value">Cashfree Payments</div>
                </div>
                <div>
                    <div class="pi-label">Transaction ID</div>
                    <div class="pi-value">{{ $invoice->payment_id }}</div>
                </div>
                <div>
                    <div class="pi-label">Payment Method</div>
                    <div class="pi-value">Online (UPI / Card / NetBanking)</div>
                </div>
                <div>
                    <div class="pi-label">Payment Status</div>
                    <div class="pi-value" style="color: {{ $invoice->status === 'paid' ? '#16a34a' : '#dc2626' }}; font-weight: 800;">
                        {{ strtoupper($invoice->status) }}
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="invoice-footer">
                <p>
                    This is a computer-generated invoice and does not require a physical signature.<br>
                    For billing queries, contact <strong>help@arzonet.com</strong>.<br>
                    Refund policy: If eligible, refunds will be processed in the original payment currency within 5-7 business days.<br>
                    <strong>Arzonet</strong> — 10/208, Mandai, Sandila, Hardoi, Uttar Pradesh 241204, India | GSTIN: 09GIPPM8686H1Z5
                </p>
            </div>
        </div>

        <!-- Full-Width Watermark Logo at Bottom -->
        <div class="watermark">
            <img src="{{ asset('images/logo/logo.png') }}" alt="" onerror="this.parentElement.style.display='none'">
        </div>
    </div>

</body>
</html>
