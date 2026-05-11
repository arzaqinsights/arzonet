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
    <div class="loader" id="loader">
        <div class="spinner"></div>
        <h2>Initializing Secure Payment</h2>
        <p>Connecting to Cashfree Payment Gateway...</p>
        <p style="font-size: 11px; margin-top: 20px; color: #94a3b8;">Session: {{ substr($sessionId, 0, 10) }}... | Mode: {{ config('cashfree.mode') }}</p>
    </div>

    <div id="error-container" style="display: none; text-align: center; max-width: 400px; padding: 20px;">
        <div style="color: #ef4444; font-size: 48px; mb-4;"><i class="fa-solid fa-circle-exclamation"></i></div>
        <h2 style="color: #ef4444;">Payment Initialization Failed</h2>
        <p id="error-message">We couldn't open the payment gateway. This might be due to a configuration issue or network problem.</p>
        <button onclick="initializeCheckout()" style="margin-top: 20px; padding: 12px 24px; bg-brand; border: none; border-radius: 4px; color: white; cursor: pointer; font-weight: 700; background: #ff6b00;">TRY AGAIN</button>
        <a href="{{ route('admin.billing.plans') }}" style="display: block; margin-top: 15px; color: #64748b; text-decoration: none; font-size: 14px;">Return to Plans</a>
    </div>

    <script>
        const mode = "{{ config('cashfree.mode') }}";
        console.log("Initializing Cashfree in " + mode + " mode");

        let cashfree;
        
        function tryInitialize() {
            if (typeof Cashfree === 'undefined') {
                console.error("Cashfree SDK not loaded yet.");
                return false;
            }
            
            try {
                cashfree = Cashfree({
                    mode: mode
                });
                return true;
            } catch (e) {
                console.error("Failed to initialize Cashfree SDK:", e);
                showError("SDK Initialization Error: " + e.message);
                return false;
            }
        }

        const checkoutOptions = {
            paymentSessionId: "{{ $sessionId }}",
            redirectTarget: "_self"
        };

        function initializeCheckout() {
            if (!cashfree && !tryInitialize()) {
                showError("Cashfree SDK not loaded. Please check your internet connection and ensure the script is not blocked.");
                return;
            }

            console.log("Opening Checkout...");
            cashfree.checkout(checkoutOptions).then((result) => {
                if (result.error) {
                    console.error("Cashfree Checkout Error:", result.error);
                    showError(result.error.message);
                }
            }).catch((err) => {
                console.error("Checkout Promise Error:", err);
                showError("Unexpected error during checkout.");
            });
        }

        function showError(msg) {
            document.getElementById('loader').style.display = 'none';
            document.getElementById('error-container').style.display = 'block';
            document.getElementById('error-message').innerText = msg;
        }

        // Initialize on load
        window.onload = () => {
            setTimeout(initializeCheckout, 1500);
        };
    </script>
</body>
</html>
