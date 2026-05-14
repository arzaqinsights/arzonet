<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Models\Sender;
use Illuminate\Http\Request;
use App\Services\PersonalizationService;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = Template::latest()->paginate(12);
        return view('templates.index', compact('templates'));
    }

    public function create()
    {
        return view('templates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'html_content' => 'required|string',
            'json_design'  => 'nullable|string',
        ]);

        $template = Template::create([
            'name'         => $request->name,
            'html_content' => $request->html_content,
            'json_design'  => $request->json_design,
        ]);

        return redirect()
            ->route('admin.templates.index')
            ->with('success', 'Template created successfully.');
    }

    public function edit(Template $template)
    {
        return view('templates.edit', compact('template'));
    }

    public function update(Request $request, Template $template)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'html_content' => 'required|string',
            'json_design'  => 'nullable|string',
        ]);

        $template->update($request->only('name', 'html_content', 'json_design'));

        return redirect()
            ->route('admin.templates.index')
            ->with('success', 'Template updated successfully.');
    }

    public function preview(Template $template, PersonalizationService $personalizer)
    {
        $previewData = null;
        if (request()->has('list_id')) {
            $list = \App\Models\EmailList::find(request('list_id'));
            if ($list) {
                $contact = $list->emails()->valid()->first();
                if ($contact) {
                    $previewData = $contact->toArray();
                }
            }
        }

        $previewHtml = $personalizer->preview($template->html_content, $previewData);
        
        if (request()->has('raw')) {
            return $previewHtml;
        }

        $senders = Sender::verified()->get();
        return view('templates.preview', compact('template', 'previewHtml', 'senders'));
    }

    public function destroy(Template $template)
    {
        $template->delete();

        return redirect()
            ->route('admin.templates.index')
            ->with('success', 'Template deleted successfully.');
    }

    public function sendTest(Request $request, Template $template)
    {
        $request->validate([
            'test_email' => 'required|email',
            'test_name'  => 'nullable|string|max:255',
            'sender_id'  => 'required|exists:senders,id',
        ]);

        // Verify the sender is verified
        $sender = Sender::findOrFail($request->sender_id);
        if ($sender->status !== 'verified') {
            return back()->with('error', 'Selected sender is not verified.');
        }

        \App\Jobs\SendTestEmailJob::dispatch(
            $request->test_email,
            $template->id,
            $sender->id
        );

        return back()->with('success', 'Test email queued for sending via SES.');
    }
}
