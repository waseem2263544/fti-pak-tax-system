<?php

namespace App\Http\Controllers;

use App\Models\Process;
use App\Models\Task;
use App\Models\Notification;
use App\Models\Client;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;

class ProcessController extends Controller
{
    public function index(Request $request)
    {
        $query = Process::with('client', 'service', 'assignedTo');

        if ($request->stage) {
            $query->where('stage', $request->stage);
        }

        $processes = $query->orderBy('due_date')->paginate(15);

        $stats = [
            'intake' => Process::where('stage', 'intake')->count(),
            'in_progress' => Process::where('stage', 'in_progress')->count(),
            'review' => Process::where('stage', 'review')->count(),
            'completed' => Process::where('stage', 'completed')->count(),
        ];

        return view('processes.index', compact('processes', 'stats'));
    }

    public function create()
    {
        $clients = Client::orderBy('name')->get();
        $services = Service::orderBy('display_name')->get();
        $users = User::orderBy('name')->get();
        return view('processes.create', compact('clients', 'services', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'service_id' => 'nullable|exists:services,id',
            'assigned_to' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'stage' => 'nullable|in:intake,in_progress,review,completed',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        // Sensible defaults for fields no longer collected on the form
        $validated['stage'] = $validated['stage'] ?? 'intake';
        $validated['start_date'] = $validated['start_date'] ?? now()->toDateString();
        $validated['service_id'] = $validated['service_id'] ?? optional(Service::first())->id;

        // Save template and metadata
        $validated['template'] = $request->input('template');

        $metadataFields = [
            'bench', 'appellant_name', 'ntn_cnic', 'appellant_address', 'appellant_phone', 'appellant_email', 'tax_year',
            'section', 'assessment_order_no', 'assessment_order_date', 'order_date',
            'cira_order_no', 'cira_order_date', 'cira_appeal_no',
            'respondent_1', 'respondent_2', 'respondent_name', 'respondent_address',
            'recovery_notice_no', 'recovery_notice_date',
            'reference_no',
            'demand_amount', 'amount_paid', 'balance_demand',
            'grounds', 'prayer', 'stay_reasons',
            'type_of_appeal',
            'ir_office_assessment', 'ir_office_location',
            'communication_date', 'filing_date',
            'verifier_name', 'verifier_designation',
            'bank_accounts_attached',
            'commissioner_appeals',
        ];
        $metadata = [];
        foreach ($metadataFields as $field) {
            if ($request->filled($field)) {
                $metadata[$field] = $request->input($field);
            }
        }
        if (!empty($metadata)) {
            $validated['metadata'] = $metadata;
        }

        $process = Process::create($validated);
        $this->handleStTribunalStayUploads($request, $process);
        $this->createTaskForAssignment($process);
        return redirect()->route('processes.show', $process)->with('success', 'Process created successfully');
    }

    /**
     * Save uploaded Order in Appeal / Order in Original / Recovery Notice files
     * for st-tribunal-stay processes into public/uploads/processes/{id}/
     * and merge their public-relative paths into the process metadata.
     */
    private function handleStTribunalStayUploads(Request $request, Process $process)
    {
        $fields = ['order_in_appeal_file', 'order_in_original_file', 'recovery_notice_file'];
        $metadata = $process->metadata ?? [];
        $changed = false;
        foreach ($fields as $field) {
            if (!$request->hasFile($field)) continue;
            $file = $request->file($field);
            if (!$file->isValid()) continue;

            $dir = public_path('uploads/processes/' . $process->id);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            // Delete old file if present
            if (!empty($metadata[$field])) {
                $oldAbs = public_path($metadata[$field]);
                if (is_file($oldAbs)) @unlink($oldAbs);
            }

            $ext = $file->getClientOriginalExtension() ?: 'bin';
            $filename = $field . '-' . time() . '.' . $ext;
            $file->move($dir, $filename);
            $metadata[$field] = 'uploads/processes/' . $process->id . '/' . $filename;
            $metadata[$field . '_pages'] = $this->countAttachmentPages($dir . '/' . $filename);
            $changed = true;
        }
        if ($changed) {
            $process->update(['metadata' => $metadata]);
        }
    }

    /**
     * Count physical pages in an uploaded attachment.
     *  - Images = 1
     *  - PDF: regex against the raw bytes (looks for /Type /Pages /Count, falls back to counting /Type /Page objects)
     *  - Anything else / unparseable = 1
     */
    private function countAttachmentPages(string $filePath): int
    {
        if (!is_file($filePath)) return 1;
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tif', 'tiff'])) {
            return 1;
        }

        if ($ext === 'pdf') {
            $content = @file_get_contents($filePath);
            if (!$content) return 1;
            if (preg_match('/\/Type\s*\/Pages\b[^>]*?\/Count\s+(\d+)/s', $content, $m)) {
                return max(1, (int) $m[1]);
            }
            if (preg_match('/\/Count\s+(\d+)[^>]*?\/Type\s*\/Pages\b/s', $content, $m)) {
                return max(1, (int) $m[1]);
            }
            $count = preg_match_all('/\/Type\s*\/Page(?!\w)/i', $content, $matches);
            if ($count > 0) return $count;
        }

        return 1;
    }

    public function show(Process $process)
    {
        $process->load('client', 'service', 'assignedTo');
        return view('processes.show', compact('process'));
    }

    public function edit(Process $process)
    {
        $clients = Client::orderBy('name')->get();
        $services = Service::orderBy('display_name')->get();
        $users = User::orderBy('name')->get();
        return view('processes.edit', compact('process', 'clients', 'services', 'users'));
    }

    public function update(Request $request, Process $process)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'service_id' => 'nullable|exists:services,id',
            'assigned_to' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'stage' => 'nullable|in:intake,in_progress,review,completed',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'completed_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        // Drop fields the form no longer submits so update() doesn't blank them out
        foreach (['service_id', 'stage', 'assigned_to', 'start_date', 'due_date', 'completed_date', 'description'] as $f) {
            if (!$request->has($f)) {
                unset($validated[$f]);
            }
        }

        // Update metadata
        $metadataFields = [
            'bench', 'appellant_name', 'ntn_cnic', 'appellant_address', 'appellant_phone', 'appellant_email', 'tax_year',
            'section', 'assessment_order_no', 'assessment_order_date', 'order_date',
            'cira_order_no', 'cira_order_date', 'cira_appeal_no',
            'respondent_1', 'respondent_2', 'respondent_name', 'respondent_address',
            'recovery_notice_no', 'recovery_notice_date',
            'reference_no',
            'demand_amount', 'amount_paid', 'balance_demand',
            'grounds', 'prayer', 'stay_reasons',
            'type_of_appeal',
            'ir_office_assessment', 'ir_office_location',
            'communication_date', 'filing_date',
            'verifier_name', 'verifier_designation',
            'bank_accounts_attached',
            'commissioner_appeals',
        ];
        $metadata = $process->metadata ?? [];
        foreach ($metadataFields as $field) {
            if ($request->has($field)) {
                $metadata[$field] = $request->input($field);
            }
        }
        $validated['metadata'] = $metadata;

        $oldAssignedTo = $process->assigned_to;
        $process->update($validated);
        $this->handleStTribunalStayUploads($request, $process->fresh());

        $newAssignedTo = $validated['assigned_to'] ?? $process->assigned_to;
        if ($newAssignedTo && $newAssignedTo != $oldAssignedTo) {
            $this->createTaskForAssignment($process);
        }

        return redirect()->route('processes.show', $process)->with('success', 'Process updated successfully');
    }

    public function destroy(Process $process)
    {
        $process->delete();
        return redirect()->route('processes.index')->with('success', 'Process deleted successfully');
    }

    private function createTaskForAssignment($process)
    {
        if (!$process->assigned_to) return;

        $process->load('client', 'service');
        $task = Task::create([
            'title' => "[Process] {$process->title}",
            'description' => "Process assigned to you.\nService: {$process->service->display_name}\nClient: {$process->client->name}\nStage: " . ucfirst(str_replace('_', ' ', $process->stage)),
            'client_id' => $process->client_id,
            'created_by' => auth()->id(),
            'status' => 'pending',
            'due_date' => $process->due_date,
            'priority' => 1,
        ]);

        $task->assignedUsers()->attach($process->assigned_to);

        Notification::create([
            'user_id' => $process->assigned_to,
            'client_id' => $process->client_id,
            'title' => 'New Process Assigned',
            'message' => "{$process->title} - {$process->service->display_name}",
            'type' => 'task',
            'priority' => 'medium',
            'related_task_id' => $task->id,
        ]);
    }

    public function updateStage(Request $request, Process $process)
    {
        $process->update(['stage' => $request->stage]);
        if ($request->stage === 'completed') {
            $process->update(['completed_date' => now()]);
        }
        return response()->json(['success' => true]);
    }
}
