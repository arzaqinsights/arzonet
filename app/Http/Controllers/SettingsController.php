<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'ses_access_key'     => config('emailplatform.ses.access_key'),
            'ses_secret_key'     => config('emailplatform.ses.secret_key') ? '••••••••' : '',
            'ses_region'         => config('emailplatform.ses.region'),
            'ses_from_email'     => config('emailplatform.ses.from_email'),
            'emails_per_minute'  => config('emailplatform.limits.emails_per_minute'),
            'daily_limit'        => config('emailplatform.limits.daily'),
            'weekly_limit'       => config('emailplatform.limits.weekly'),
            'monthly_limit'      => config('emailplatform.limits.monthly'),
            'batch_size'         => config('emailplatform.batch_size'),
            'cost_per_email'     => config('emailplatform.cost_per_email'),
        ];

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'ses_region'         => 'nullable|string|max:50',
            'ses_from_email'     => 'nullable|email|max:255',
            'emails_per_minute'  => 'required|integer|min:1|max:1000',
            'daily_limit'        => 'required|integer|min:100',
            'weekly_limit'       => 'required|integer|min:100',
            'monthly_limit'      => 'required|integer|min:100',
            'batch_size'         => 'required|integer|min:10|max:500',
            'cost_per_email'     => 'required|numeric|min:0',
        ]);

        // Update .env values
        $this->setEnv('SES_REGION', $request->ses_region);
        $this->setEnv('SES_FROM_EMAIL', $request->ses_from_email);
        $this->setEnv('EMAILS_PER_MINUTE', $request->emails_per_minute);
        $this->setEnv('DAILY_EMAIL_LIMIT', $request->daily_limit);
        $this->setEnv('WEEKLY_EMAIL_LIMIT', $request->weekly_limit);
        $this->setEnv('MONTHLY_EMAIL_LIMIT', $request->monthly_limit);
        $this->setEnv('BATCH_SIZE', $request->batch_size);
        $this->setEnv('SES_COST_PER_EMAIL', $request->cost_per_email);

        // Clear config cache
        Artisan::call('config:clear');

        return back()->with('success', 'Settings updated successfully.');
    }

    /**
     * Update a .env variable value.
     */
    protected function setEnv(string $key, ?string $value): void
    {
        $path = app()->environmentFilePath();
        $content = file_get_contents($path);

        $value = $value ?? '';

        if (str_contains($content, "{$key}=")) {
            $content = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $content
            );
        } else {
            $content .= "\n{$key}={$value}";
        }

        file_put_contents($path, $content);
    }
}
