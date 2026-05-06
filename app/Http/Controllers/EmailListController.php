<?php

namespace App\Http\Controllers;

use App\Models\EmailList;
use App\Services\FileParserService;
use App\Jobs\ProcessEmailListJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmailListController extends Controller
{
    public function index()
    {
        $lists = EmailList::latest()->paginate(15);
        
        $globalStats = [
            'total'        => \App\Models\Email::count(),
            'subscribed'   => \App\Models\Email::where('subscription_status', 'subscribed')->count(),
            'unsubscribed' => \App\Models\Email::whereNotNull('unsubscribed_at')->count(),
            'bounced'      => \App\Models\EmailLog::where('status', 'bounced')->count(),
            'invalid'      => \App\Models\Email::where('status', 'invalid')->count(),
            'duplicate'    => \App\Models\Email::where('status', 'duplicate')->count(),
        ];

        return view('email-lists.index', compact('lists', 'globalStats'));
    }

    public function create()
    {
        return view('email-lists.create');
    }

    /**
     * Store a new email list.
     * FLOW: Upload -> Store File -> Return Mapping Preview
     */
    public function store(Request $request, FileParserService $parser)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'import_type' => 'required|in:upload,manual,paste',
        ]);

        $emailList = EmailList::create([
            'name'              => $request->name,
            'signup_source'     => $request->signup_source,
            'segment_name'      => $request->segment_name,
            'status'            => 'pending',
        ]);

        // 1. Handling File Upload
        if ($request->import_type === 'upload') {
            $request->validate([
                'file' => 'required|file|max:' . config('emailplatform.upload.max_file_size', 10240) . '|mimes:csv,xlsx,txt',
            ]);

            $file = $request->file('file');
            $path = $file->store('email-lists', 'local');
            
            $emailList->update([
                'file_path'         => $path,
                'original_filename' => $file->getClientOriginalName(),
            ]);

            return $this->showMappingView($emailList, $parser, $file);
        }

        // 2. Handling Bulk Paste (ASYNC REFACTOR)
        if ($request->import_type === 'paste') {
            $request->validate(['emails_text' => 'required|string']);
            
            // Convert paste to a temporary CSV file for async processing
            $filename = 'paste_' . Str::random(10) . '.csv';
            $path = 'email-lists/' . $filename;
            
            // Create CSV content with a header
            $content = "email\n" . $request->emails_text;
            Storage::disk('local')->put($path, $content);

            $emailList->update([
                'file_path'         => $path,
                'original_filename' => 'bulk_paste.csv',
                'signup_source'     => $request->signup_source ?? 'Bulk Paste',
                'column_mapping'    => ['email' => 'email', '_settings' => ['skip_dns' => false]],
                'status'            => 'processing'
            ]);

            ProcessEmailListJob::dispatch($emailList->id);

            return redirect()->route('admin.email-lists.show', $emailList)->with('success', 'Bulk import started in background.');
        }

        // 3. Handling Manual Add
        if ($request->import_type === 'manual') {
            $emailList->update([
                'signup_source' => $request->signup_source ?? 'Manual Entry',
                'status' => 'completed',
            ]);
            return redirect()->route('admin.email-lists.show', $emailList)->with('success', 'Empty list created. Add contacts manually.');
        }
    }

    protected function showMappingView(EmailList $emailList, FileParserService $parser, $file)
    {
        // Preview only - uses streaming to avoid OOM even here
        $parsed = $parser->parse($file); 
        $headers = $parsed['headers'];
        $sampleRows = array_slice($parsed['rows'], 0, 5);
        $suggestedEmail = $parser->autoDetectEmailColumn($headers, $sampleRows);
        $suggestedName = $parser->autoDetectNameColumn($headers);
        $autoSuggestions = $parser->autoDetectMappings($headers, $sampleRows);

        return view('email-lists.mapping', compact('emailList','headers','sampleRows','suggestedEmail','suggestedName', 'autoSuggestions'));
    }

    public function storeMapping(Request $request, int $id)
    {
        $request->validate([
            'mapping' => 'required|array',
        ]);

        $emailList = EmailList::findOrFail($id);
        $rawMapping = $request->input('mapping', []);
        $finalMapping = [];

        foreach ($rawMapping as $excelColumn => $systemField) {
            if (!empty($systemField)) {
                $finalMapping[$systemField] = $excelColumn;
            }
        }

        if (!isset($finalMapping['email'])) {
            return back()->withErrors(['mapping' => 'You must map the Email Address column.']);
        }

        $finalMapping['_settings'] = ['skip_dns' => false];

        $emailList->update([
            'column_mapping' => $finalMapping,
            'status' => 'processing'
        ]);

        ProcessEmailListJob::dispatch($emailList->id);

        return redirect()
            ->route('admin.email-lists.show', $emailList)
            ->with('success', 'CRM Import started. Contacts will appear shortly.');
    }

    public function show(EmailList $emailList)
    {
        $emailList->recalculateStats();
        $emailList->load('emails');
        $stats = [
            'total'     => $emailList->total_records,
            'valid'     => $emailList->valid_count,
            'invalid'   => $emailList->invalid_count,
            'duplicate' => $emailList->duplicate_count,
        ];
        $emails = $emailList->emails()->paginate(50);
        return view('email-lists.show', compact('emailList', 'stats', 'emails'));
    }

    public function filterEmails(Request $request, EmailList $emailList)
    {
        $query = $emailList->emails();
        if ($request->status && $request->status !== 'all') $query->where('status', $request->status);
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%");
            });
        }
        $emails = $query->paginate(50);
        return response()->json([
            'html'  => view('email-lists.partials.email-table-rows', ['emails' => $emails, 'emailList' => $emailList])->render(),
            'total' => $emails->total(),
            'links' => $emails->links()->toHtml(),
        ]);
    }

    public function destroyEmail(EmailList $emailList, int $emailId)
    {
        $email = $emailList->emails()->findOrFail($emailId);
        $status = $email->status;
        $email->delete();
        $emailList->decrement('total_records');
        match ($status) {
            'valid'     => $emailList->decrement('valid_count'),
            'invalid'   => $emailList->decrement('invalid_count'),
            'duplicate' => $emailList->decrement('duplicate_count'),
        };
        return response()->json(['success' => true]);
    }

    public function destroy(EmailList $emailList)
    {
        if ($emailList->file_path) Storage::disk('local')->delete($emailList->file_path);
        $emailList->delete();
        return redirect()->route('admin.email-lists.index')->with('success', 'Email list deleted successfully.');
    }

    public function checkStatus(EmailList $emailList)
    {
        return response()->json([
            'status'          => $emailList->status,
            'total_records'   => $emailList->total_records,
            'valid_count'     => $emailList->valid_count,
            'invalid_count'   => $emailList->invalid_count,
            'duplicate_count' => $emailList->duplicate_count,
        ]);
    }

    public function getEmail(EmailList $emailList, int $emailId)
    {
        return response()->json($emailList->emails()->findOrFail($emailId));
    }

    public function updateEmail(Request $request, EmailList $emailList, int $emailId)
    {
        $email = $emailList->emails()->findOrFail($emailId);
        $request->validate(['email' => 'required|email', 'name' => 'nullable|string|max:255']);
        $data = $request->only('email', 'name', 'segment_name', 'signup_source', 'subscription_status');
        if ($request->has('meta')) $data['meta'] = array_merge($email->meta ?? [], $request->meta);
        $email->update($data);
        return response()->json(['success' => true]);
    }

    public function addContact(Request $request, EmailList $emailList)
    {
        $request->validate(['email' => 'required|email', 'name' => 'nullable|string|max:255']);
        $exists = $emailList->emails()->where('email', $request->email)->exists();
        $email = $emailList->emails()->create([
            'email' => $request->email,
            'name' => $request->name,
            'segment_name' => $request->segment_name,
            'signup_source' => $request->signup_source ?? 'Manual',
            'status' => $exists ? 'duplicate' : 'valid',
            'subscription_status' => 'subscribed'
        ]);
        $emailList->increment('total_records');
        if ($exists) $emailList->increment('duplicate_count');
        else $emailList->increment('valid_count');
        return response()->json(['success' => true]);
    }

    /**
     * Import more contacts into an existing list (ASYNC).
     */
    public function importMore(Request $request, EmailList $emailList, FileParserService $parser)
    {
        $request->validate(['import_type' => 'required|in:upload,paste']);

        if ($request->import_type === 'upload') {
            $request->validate(['file' => 'required|file|max:' . config('emailplatform.upload.max_file_size', 10240) . '|mimes:csv,xlsx,txt']);
            $file = $request->file('file');
            $path = $file->store('email-lists', 'local');
            $emailList->update([
                'file_path' => $path,
                'segment_name' => $request->segment_name ?? $emailList->segment_name,
                'signup_source' => $request->signup_source ?? $emailList->signup_source,
                'status' => 'pending',
            ]);
            return $this->showMappingView($emailList, $parser, $file);
        }

        if ($request->import_type === 'paste') {
            $request->validate(['emails_text' => 'required|string']);
            
            $filename = 'paste_' . Str::random(10) . '.csv';
            $path = 'email-lists/' . $filename;
            Storage::disk('local')->put($path, "email\n" . $request->emails_text);

            $emailList->update([
                'status' => 'processing',
                'file_path' => $path,
                'segment_name' => $request->segment_name ?? $emailList->segment_name,
                'signup_source' => $request->signup_source ?? $emailList->signup_source,
                'column_mapping' => ['email' => 'email', '_settings' => ['skip_dns' => false]],
            ]);

            ProcessEmailListJob::dispatch($emailList->id);

            return redirect()->route('admin.email-lists.show', $emailList)->with('success', 'Bulk import started in background.');
        }
    }
}
