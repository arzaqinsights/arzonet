<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsAppAccount;
use App\Services\WhatsApp\MetaEmbeddedSignupService;
use Illuminate\Support\Facades\Auth;
use Exception;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class WhatsAppAccountController extends Controller
{
    use AuthorizesRequests;

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
            'waba_id' => 'nullable|string',
            'phone_number_id' => 'nullable|string',
        ]);

        try {
            \Log::info('WhatsApp Onboarding started', [
                'user_id' => Auth::id(),
                'waba_id_from_client' => $request->waba_id,
                'phone_number_id_from_client' => $request->phone_number_id,
            ]);
            $account = $this->signupService->completeOnboarding(
                $request->code,
                Auth::id(),
                $request->waba_id,
                $request->phone_number_id
            );
            \Log::info('WhatsApp Account saved successfully', ['account_id' => $account->id]);

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp account connected successfully!',
                'account' => $account
            ]);

        } catch (Exception $e) {
            \Log::error('WhatsApp Onboarding Failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Onboarding failed: ' . $e->getMessage()
            ], 422);
        }
    }

    public function register(WhatsAppAccount $account, \App\Services\WhatsApp\MetaApiService $api)
    {
        $this->authorize('update', $account);

        try {
            $token = Crypt::decryptString($account->access_token);
            $success = $api->registerPhoneNumber($account->phone_number_id, $token);

            if ($success) {
                return back()->with('success', 'Phone number registered and activated on Cloud API.');
            } else {
                return back()->with('error', 'Registration failed. Check Meta dashboard.');
            }
        } catch (Exception $e) {
            return back()->with('error', 'Registration error: ' . $e->getMessage());
        }
    }

    public function destroy(WhatsAppAccount $whatsappAccount)
    {
        $this->authorize('delete', $whatsappAccount);
        $whatsappAccount->delete();
        return back()->with('success', 'Account disconnected.');
    }
}
