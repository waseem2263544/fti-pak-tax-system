{{-- One partner or witness. $kind = partners|witnesses, $i = index, $p = values --}}
@php $isPartner = $kind === 'partners'; $n = "{$kind}[{$i}]"; @endphp
<div class="person-row mb-4 pb-3" style="border-bottom: 1px solid var(--n-150, #eee);">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <strong class="person-no" style="color: var(--primary);">{{ $isPartner ? 'Partner' : 'Witness No. ' . ((int) $i + 1) }}</strong>
        @if($removable)
            <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removePartner(this)"><i class="bi bi-trash"></i> Remove</button>
        @endif
    </div>
    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label small mb-1">Name <span class="text-danger">*</span></label>
            <input type="text" name="{{ $n }}[name]" class="form-control form-control-sm" value="{{ $p['name'] ?? '' }}" required>
        </div>
        <div class="col-md-1">
            <label class="form-label small mb-1">&nbsp;</label>
            <select name="{{ $n }}[relation]" class="form-select form-select-sm">
                @foreach(\App\Models\FirmRegistration::RELATIONS as $r)
                    <option @selected(($p['relation'] ?? 'S/O') === $r)>{{ $r }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1">Father's / husband's name <span class="text-danger">*</span></label>
            <input type="text" name="{{ $n }}[father_name]" class="form-control form-control-sm" value="{{ $p['father_name'] ?? '' }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">CNIC @if($isPartner)<span class="text-danger">*</span>@endif</label>
            <input type="text" name="{{ $n }}[cnic]" class="form-control form-control-sm cnic" value="{{ $p['cnic'] ?? '' }}" placeholder="12345-1234567-1"
                   pattern="\d{5}-\d{7}-\d" @if($isPartner) required @endif
                   oninput="let d=this.value.replace(/\D/g,'').slice(0,13); this.value=d.length>12?d.slice(0,5)+'-'+d.slice(5,12)+'-'+d.slice(12):d.length>5?d.slice(0,5)+'-'+d.slice(5):d;">
        </div>
        <div class="col-md-{{ $isPartner ? 6 : 9 }}">
            <label class="form-label small mb-1">Permanent address @if($isPartner)<span class="text-danger">*</span>@endif</label>
            <input type="text" name="{{ $n }}[address]" class="form-control form-control-sm" value="{{ $p['address'] ?? '' }}" @if($isPartner) required @endif>
        </div>
        <div class="col-md-{{ $isPartner ? 2 : 3 }}">
            <label class="form-label small mb-1">Cell no.</label>
            <input type="text" name="{{ $n }}[cell]" class="form-control form-control-sm" value="{{ $p['cell'] ?? '' }}" placeholder="03XXXXXXXXX">
        </div>
        @if($isPartner)
        <div class="col-md-2">
            <label class="form-label small mb-1">Capital share % <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0" max="100" name="{{ $n }}[capital_share]" class="form-control form-control-sm" value="{{ $p['capital_share'] ?? '' }}" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Profit &amp; loss % <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0" max="100" name="{{ $n }}[profit_share]" class="form-control form-control-sm" value="{{ $p['profit_share'] ?? '' }}" required>
        </div>
        @endif
    </div>
</div>
