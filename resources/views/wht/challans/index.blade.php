@extends('layouts.app')
@section('title', 'Challans')
@section('page-title', 'Challans (PSID / CPR)')

@section('content')
@include('wht.partials.agent-bar')

<div class="d-flex justify-content-between align-items-center mb-3">
    <p style="color: #9ca3af; font-size: 0.85rem; margin: 0;">
        Every PSID referenced by a payment or salary record, with its tax total and documents.
    </p>
    <button class="btn btn-accent" onclick="openChallan()"><i class="bi bi-paperclip me-1"></i> Attach Documents</button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>PSID No.</th>
                    <th>CPR No.</th>
                    <th class="text-center">Entries</th>
                    <th class="text-end">Total Tax</th>
                    <th>PSID Document</th>
                    <th>CPR Document</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($challans as $c)
                <tr>
                    <td class="fw-semibold">{{ $c->psid_no }}</td>
                    <td>{{ $c->cpr_no ?: '—' }}</td>
                    <td class="text-center">{{ $c->entries }}</td>
                    <td class="text-end fw-semibold">{{ number_format($c->total_tax, 0) }}</td>
                    @foreach(['psid', 'cpr'] as $kind)
                    <td>
                        @if($c->files?->{"{$kind}_file"})
                            <a href="{{ route('wht.challans.download', ['challan' => $c->files->id, 'type' => $kind]) }}"
                               class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i> Download</a>
                            <form method="POST" action="{{ route('wht.challans.delete-file', ['challan' => $c->files->id, 'type' => $kind]) }}"
                                  class="d-inline" onsubmit="return confirm('Remove this document?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i></button>
                            </form>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    @endforeach
                    <td class="text-end text-nowrap">
                        <a href="{{ route('wht.challans.pdf', ['psid' => $c->psid_no]) }}" target="_blank"
                           class="btn btn-sm btn-outline-primary" title="Schedule of entries (PDF)">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                        <a href="{{ route('wht.challans.pdf', ['psid' => $c->psid_no, 'download' => 1]) }}"
                           class="btn btn-sm btn-outline-primary" title="Download schedule">
                            <i class="bi bi-download"></i>
                        </a>
                        <button class="btn btn-sm btn-outline-primary" title="Attach documents"
                                onclick='openChallan(@json($c->psid_no), @json($c->cpr_no))'>
                            <i class="bi bi-paperclip"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-5">
                    No PSIDs recorded yet. Add a PSID number to a payment or salary record and it will appear here.
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="challanModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('wht.challans.upload') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Challan Documents</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">PSID No. <span class="text-danger">*</span></label>
                        <input type="text" name="psid_no" id="c_psid" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">CPR No.</label>
                        <input type="text" name="cpr_no" id="c_cpr" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Paid On</label>
                        <input type="date" name="paid_on" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">PSID Document</label>
                        <input type="file" name="psid_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div class="col-12">
                        <label class="form-label">CPR Document</label>
                        <input type="file" name="cpr_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                </div>
                <p class="text-muted mt-3 mb-0" style="font-size: 0.8rem;">
                    PDF or image, up to 5 MB. Files are stored privately and served only to signed-in users with access.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const challanModal = new bootstrap.Modal(document.getElementById('challanModal'));

function openChallan(psid, cpr) {
    document.getElementById('c_psid').value = psid ?? '';
    document.getElementById('c_cpr').value = cpr ?? '';
    challanModal.show();
}
</script>
@endsection
