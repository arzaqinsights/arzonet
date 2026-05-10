<?php

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;

// This script manually syncs the latest pending invoice
$invoice = Invoice::where('status', 'pending')->latest()->first();

if (!$invoice) {
    echo "No pending invoice found.\n";
    exit;
}

echo "Found pending invoice #{$invoice->invoice_number} for user {$invoice->user->email}\n";

// Mark as paid
$invoice->update(['status' => 'paid']);

// Update or Create Subscription
$details = $invoice->plan_details;
Subscription::updateOrCreate(
    ['user_id' => $invoice->user_id],
    [
        'plan_name' => 'Power Plan',
        'contacts_limit' => $details['contacts_limit'],
        'emails_limit' => $details['emails_limit'],
        'status' => 'active',
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
    ]
);

echo "SUCCESS: Account activated with {$details['contacts_limit']} contacts and {$details['emails_limit']} emails.\n";
