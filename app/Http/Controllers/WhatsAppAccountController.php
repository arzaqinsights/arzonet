<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\MetaEmbeddedSignupService;
use Illuminate\Support\Facades\Auth;
use Exception;

class WhatsAppAccountController extends Controller
{
    protected MetaEmbeddedSignupService $signupService;

    public function __construct(MetaEmbeddedSignupService $signupService)
    {
        $this->signupService = $signupService;
    }

    public function index()
    {
        $accounts = Auth::user()->whatsappAccounts()->latest()->get();
        return view('admin.whatsapp.accounts.index', compact('accounts'));
    }

    /**
     * Handle the callback from Meta Embedded Signup.
     * Expected: { code: "..." }
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        try {
            $account = $this->signupService->completeOnboarding($request->code, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp account connected successfully!',
                'account' => $account
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Onboarding failed: ' . $e->getMessage()
            ], 422);
        }
    }

    public function destroy(WhatsAppAccount $whatsappAccount)
    {
        $this->authorize('delete', $whatsappAccount);
        $whatsappAccount->delete();
        return back()->with('success', 'Account disconnected.');
    }
}
