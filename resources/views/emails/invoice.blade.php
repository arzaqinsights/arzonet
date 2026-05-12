<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 2px solid #0a0a0a; }
        .header h1 { font-family: 'Outfit', sans-serif; margin: 0; color: #ff6b00; font-size: 28px; }
        .content { font-size: 14px; color: #333; line-height: 1.6; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 20px; }
        .table th, .table td { padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        .table th { color: #64748b; font-size: 12px; text-transform: uppercase; }
        .table td { font-weight: bold; }
        .button { display: inline-block; padding: 10px 20px; background-color: #0a0a0a; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px; margin-top: 20px; }
        .footer { text-align: center; font-size: 12px; color: #94a3b8; margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ARZONET</h1>
            <p style="margin:5px 0 0; color:#64748b; font-weight:bold; font-size: 12px;">PAYMENT SUCCESSFUL</p>
        </div>
        
        <div class="content">
            <p>Hi {{ $invoice->user->name }},</p>
            <p>Thank you for your purchase! Your payment has been successfully processed and your subscription is now active.</p>
            
            <h3 style="margin-top: 30px; margin-bottom: 10px;">Order Overview</h3>
            <table class="table">
                <tr>
                    <th>Invoice Number</th>
                    <td>{{ $invoice->invoice_number }}</td>
                </tr>
                <tr>
                    <th>Order ID</th>
                    <td>{{ $invoice->payment_id }}</td>
                </tr>
                <tr>
                    <th>Date</th>
                    <td>{{ $invoice->updated_at->format('d M Y') }}</td>
                </tr>
                <tr>
                    <th>Total Paid</th>
                    <td style="color:#16a34a; font-size: 16px;">₹{{ number_format($invoice->amount, 2) }}</td>
                </tr>
            </table>

            <p>You can view and download your full tax invoice directly from your billing dashboard.</p>
            
            <div style="text-align: center;">
                <a href="{{ route('admin.billing.invoices.show', $invoice->id) }}" class="button">View Official Invoice</a>
            </div>
        </div>

        <div class="footer">
            <p>Arzonet — 10/208, Mandai, Sandila, Hardoi, Uttar Pradesh 241204</p>
            <p>If you have any questions, contact us at <a href="mailto:billing@arzonet.com">billing@arzonet.com</a></p>
        </div>
    </div>
</body>
</html>
