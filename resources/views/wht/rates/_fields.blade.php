<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Section <span class="text-danger">*</span></label>
        <select name="section" class="form-select @error('section') is-invalid @enderror" required>
            <option value="">Select section…</option>
            @foreach($sections as $s)
                <option value="{{ $s->section }}" {{ old('section', $rate?->section) == $s->section ? 'selected' : '' }}>
                    {{ $s->section }} — {{ $s->payment_nature }}
                </option>
            @endforeach
        </select>
        @error('section')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Goods / Service Type</label>
        <input type="text" name="goods_type" class="form-control" value="{{ old('goods_type', $rate?->goods_type) }}">
        <div class="form-text">Leave blank for the general rule. A row with a value here wins over the blank one.</div>
    </div>

    <div class="col-md-3">
        <label class="form-label">Category <span class="text-danger">*</span></label>
        <select name="category" class="form-select" required>
            @foreach(['company' => 'Company', 'individual' => 'Individual', 'aop' => 'AOP'] as $v => $l)
                <option value="{{ $v }}" {{ old('category', $rate?->category) == $v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">ATL Status <span class="text-danger">*</span></label>
        <select name="atl_status" class="form-select" required>
            <option value="filer" {{ old('atl_status', $rate?->atl_status) == 'filer' ? 'selected' : '' }}>Filer</option>
            <option value="non-filer" {{ old('atl_status', $rate?->atl_status) == 'non-filer' ? 'selected' : '' }}>Non-filer</option>
        </select>
    </div>

    <div class="col-md-2">
        <label class="form-label">Rate (%) <span class="text-danger">*</span></label>
        <input type="number" step="0.001" min="0" max="100" name="rate" class="form-control"
               value="{{ old('rate', $rate?->rate) }}" required>
    </div>

    <div class="col-md-2">
        <label class="form-label">From Month <span class="text-danger">*</span></label>
        <input type="month" name="effective_from" class="form-control"
               value="{{ old('effective_from', $rate?->effective_from?->format('Y-m') ?? now()->format('Y-m')) }}" required>
    </div>

    <div class="col-md-2">
        <label class="form-label">To Month</label>
        <input type="month" name="effective_to" class="form-control"
               value="{{ old('effective_to', $rate?->effective_to?->format('Y-m')) }}">
        <div class="form-text">Blank = still current.</div>
    </div>

    <div class="col-12">
        <label class="form-label">Notes</label>
        <input type="text" name="notes" class="form-control" value="{{ old('notes', $rate?->notes) }}"
               placeholder="e.g. Finance Act 2026">
    </div>
</div>
