<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoicesController extends Controller
{
    public function index()
    {
        $invoices = auth()->user()->invoices()->latest()->paginate(15);
        return view('billing.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        // Security check
        if ($invoice->user_id !== auth()->id()) {
            abort(403);
        }

        return view('billing.invoices.show', compact('invoice'));
    }
}
