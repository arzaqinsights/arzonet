<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Securing Payment — Arzonet</title>
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    <style>
        body { 
            font-family: system-ui, -apple-system, sans-serif; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            margin: 0;
            background: #f8fafc;
        }
        .loader {
            text-align: center;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e2e8f0;
            border-top: 4px solid #ff6b00;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        h2 { color: #0f172a; margin: 0; font-size: 18px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        p { color: #64748b; font-size: 14px; margin-top: 8px; font-weight: 500; }
    </style>
</head>
<body>
    <div class="loader">
        <div class="spinner"></div>
        <h2>Initializing Secure Payment</h2>
        <p>Please do not refresh or close this window...</p>
    </div>

    <script>
        const cashfree = Cashfree({
            mode: "{{ app()->environment('production') ? 'production' : 'sandbox' }}"
        });

        const checkoutOptions = {
            paymentSessionId: "{{ $sessionId }}",
            redirectTarget: "_self"
        };

        // Initialize Checkout
        setTimeout(() => {
            cashfree.checkout(checkoutOptions);
        }, 1000);
    </script>
</body>
</html>
