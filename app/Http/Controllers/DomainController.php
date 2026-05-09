<?php

namespace App\Http\Controllers;

use App\Models\VerifiedDomain;
use App\Services\SendGridDomainService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DomainController extends Controller
{
    protected $domainService;

    public function __construct(SendGridDomainService $domainService)
    {
        $this->domainService = $domainService;
    }

    public function index()
    {
        $domains = VerifiedDomain::where('user_id', Auth::id())->latest()->get();
        return view('domains.index', compact('domains'));
    }

    public function create()
    {
        return view('domains.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'domain' => [
                'required',
                'string',
                'unique:verified_domains,domain',
                function ($attribute, $value, $fail) {
                    $publicProviders = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'icloud.com'];
                    if (in_array(strtolower($value), $publicProviders)) {
                        $fail('Public email providers cannot be authenticated. Please use a custom business domain.');
                    }
                },
            ],
        ]);

        try {
            $response = $this->domainService->authenticateDomain($request->domain);

            $domain = VerifiedDomain::create([
                'user_id' => Auth::id(),
                'domain' => $request->domain,
                'sendgrid_domain_id' => $response['id'],
                'dns_records' => $response['dns'],
                'status' => 'pending',
            ]);

            return redirect()->route('admin.domains.show', $domain)->with('success', 'Domain added! Please configure your DNS records.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function show(VerifiedDomain $domain)
    {
        $this->authorizeOwner($domain);
        return view('domains.show', compact('domain'));
    }

    public function verify(VerifiedDomain $domain)
    {
        $this->authorizeOwner($domain);

        try {
            $response = $this->domainService->validateDomain($domain->sendgrid_domain_id);

            if ($response['valid']) {
                $domain->update([
                    'status' => 'verified',
                    'verified_at' => now(),
                ]);
                return back()->with('success', 'Domain verified successfully!');
            }

            return back()->with('error', 'DNS records not yet propagated. Please wait and try again.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(VerifiedDomain $domain)
    {
        $this->authorizeOwner($domain);
        $domain->delete();
        return redirect()->route('admin.domains.index')->with('success', 'Domain removed.');
    }

    protected function authorizeOwner(VerifiedDomain $domain)
    {
        if ($domain->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
