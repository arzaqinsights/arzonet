<?php

namespace App\Http\Controllers;

use App\Models\EmailList;
use App\Services\FileParserService;
use App\Jobs\ProcessEmailListJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmailListController extends Controller
{
    public function index()
    {
        $lists = EmailList::latest()->paginate(15);
        return view('email-lists.index', compact('lists'));
    }

    public function create()
    {
        return view('email-lists.create');
    }

    public function store(Request $request, FileParserService $parser)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'import_type' => 'required|in:upload,manual,paste',
        ]);

        // 1. Handling File Upload
        if ($request->import_type === 'upload') {
            $request->validate([
                'file' => 'required|file|max:' . config('emailplatform.upload.max_file_size') . '|mimes:csv,xlsx,txt',
            ]);

            $file = $request->file('file');
            $path = $file->store('email-lists', 'local');

            $parsed = $parser->parse($file);
            $headers = $parsed['headers'];
            $sampleRows = array_slice($parsed['rows'], 0, 5);
            $suggestedEmail = $parser->autoDetectEmailColumn($headers, $sampleRows);
            $suggestedName = $parser->autoDetectNameColumn($headers);
            $autoSuggestions = $parser->autoDetectMappings($headers, $sampleRows);

            $emailList = EmailList::create([
                'name'              => $request->name,
                'signup_source'     => $request->signup_source,
                'segment_name'      => $request->segment_name,
                'file_path'         => $path,
                'original_filename' => $file->getClientOriginalName(),
                'status'            => 'pending',
            ]);

            return view('email-lists.mapping', compact('emailList','headers','sampleRows','suggestedEmail','suggestedName', 'autoSuggestions'));
        }

        // 2. Handling Bulk Paste
        if ($request->import_type === 'paste') {
            $request->validate(['emails_text' => 'required|string']);
            
            $emailList = EmailList::create([
                'name' => $request->name,
                'signup_source' => $request->signup_source ?? 'Bulk Paste',
                'segment_name' => $request->segment_name,
                'status' => 'processing',
            ]);

            // Simple line-by-line parsing
            $lines = explode("\n", $request->emails_text);
            foreach ($lines as $line) {
                $email = trim($line);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $emailList->emails()->create([
                        'email' => $email,
                        'status' => 'valid',
                        'subscription_status' => 'subscribed'
                    ]);
                    $emailList->increment('valid_count');
                    $emailList->increment('total_records');
                }
            }

            $emailList->update(['status' => 'completed']);
            return redirect()->route('email-lists.show', $emailList)->with('success', 'List imported from paste.');
        }

        // 3. Handling Manual Add (Redirect to show with modal open or just store)
        if ($request->import_type === 'manual') {
            $emailList = EmailList::create([
                'name' => $request->name,
                'signup_source' => $request->signup_source ?? 'Manual Entry',
                'segment_name' => $request->segment_name,
                'status' => 'completed',
            ]);
            return redirect()->route('email-lists.show', $emailList)->with('success', 'Empty list created. Add contacts manually.');
        }
    }

    public function storeMapping(Request $request, int $id)
    {
        $request->validate([
            'mapping' => 'required|array',
        ]);

        $emailList = EmailList::findOrFail($id);

        // Filter and reverse the mapping: we want SystemField => ExcelColumn
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

        // Mandatory Performance settings (Full verification enforced)
        $finalMapping['_settings'] = [
            'skip_dns' => false,
        ];

        $emailList->update([
            'column_mapping' => $finalMapping,
            'status' => 'processing'
        ]);

        // Dispatch processing job
        ProcessEmailListJob::dispatch($emailList->id);

        return redirect()
            ->route('email-lists.show', $emailList)
            ->with('success', 'CRM Import started. Contacts will appear shortly.');
    }

    public function show(EmailList $emailList)
    {
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

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $emails = $query->paginate(50);

        return response()->json([
            'html'  => view('email-lists.partials.email-table-rows', [
                'emails' => $emails,
                'emailList' => $emailList
            ])->render(),
            'total' => $emails->total(),
            'links' => $emails->links()->toHtml(),
        ]);
    }

    public function destroyEmail(EmailList $emailList, int $emailId)
    {
        $email = $emailList->emails()->findOrFail($emailId);
        $status = $email->status;
        $email->delete();

        // Update counters
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
        // Delete stored file
        if ($emailList->file_path) {
            Storage::disk('local')->delete($emailList->file_path);
        }

        $emailList->delete();

        return redirect()
            ->route('email-lists.index')
            ->with('success', 'Email list deleted successfully.');
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
        $email = $emailList->emails()->findOrFail($emailId);
        return response()->json($email);
    }

    public function updateEmail(Request $request, EmailList $emailList, int $emailId)
    {
        $email = $emailList->emails()->findOrFail($emailId);
        
        $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string|max:255',
        ]);

        $data = $request->only('email', 'name', 'segment_name', 'signup_source', 'subscription_status');
        
        // Handle custom meta fields if sent
        if ($request->has('meta')) {
            $data['meta'] = array_merge($email->meta ?? [], $request->meta);
        }

        $email->update($data);

        return response()->json(['success' => true]);
    }

    public function addContact(Request $request, EmailList $emailList)
    {
        $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string|max:255',
        ]);

        // Check if exists in list
        $exists = $emailList->emails()->where('email', $request->email)->exists();
        
        $email = $emailList->emails()->create([
            'email' => $request->email,
            'name' => $request->name,
            'segment_name' => $request->segment_name,
            'signup_source' => $request->signup_source ?? 'Manual',
            'status' => $exists ? 'duplicate' : 'valid',
            'subscription_status' => 'subscribed'
        ]);

        // Update counters
        $emailList->increment('total_records');
        if ($exists) {
            $emailList->increment('duplicate_count');
        } else {
            $emailList->increment('valid_count');
        }

        return response()->json(['success' => true]);
    }

    /**
     * Import more contacts into an existing list.
     */
    public function importMore(Request $request, EmailList $emailList, FileParserService $parser)
    {
        $request->validate([
            'import_type' => 'required|in:upload,paste',
        ]);

        // 1. Handling File Upload
        if ($request->import_type === 'upload') {
            $request->validate([
                'file' => 'required|file|max:' . config('emailplatform.upload.max_file_size') . '|mimes:csv,xlsx,txt',
            ]);

            $file = $request->file('file');
            $path = $file->store('email-lists', 'local');

            $parsed = $parser->parse($file);
            $headers = $parsed['headers'];
            $sampleRows = array_slice($parsed['rows'], 0, 5);
            $autoSuggestions = $parser->autoDetectMappings($headers, $sampleRows);

            // Update list metadata (new file path, segment if provided)
            $emailList->update([
                'file_path' => $path,
                'segment_name' => $request->segment_name ?? $emailList->segment_name,
                'signup_source' => $request->signup_source ?? $emailList->signup_source,
                'status' => 'pending',
            ]);

            return view('email-lists.mapping', compact('emailList','headers','sampleRows','autoSuggestions'));
        }

        // 2. Handling Bulk Paste
        if ($request->import_type === 'paste') {
            $request->validate(['emails_text' => 'required|string']);
            
            $emailList->update([
                'status' => 'processing',
                'segment_name' => $request->segment_name ?? $emailList->segment_name,
                'signup_source' => $request->signup_source ?? $emailList->signup_source,
            ]);

            // Simple line-by-line parsing (could be improved with service)
            $lines = explode("\n", $request->emails_text);
            $validEmails = [];
            foreach ($lines as $line) {
                $email = strtolower(trim($line));
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $validEmails[] = ['email' => $email];
                }
            }

            if (!empty($validEmails)) {
                // Dispatch background job for validation and import
                ProcessEmailListJob::dispatch($emailList->id);
            } else {
                $emailList->update(['status' => 'completed']);
            }

            return redirect()->route('email-lists.show', $emailList)->with('success', 'Bulk import started in background.');
        }
    }
}
