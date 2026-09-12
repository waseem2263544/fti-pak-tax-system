{{-- The lines under one head, and the movement working behind each. --}}
@foreach($group as $line)
    @php $moves = $line->movementsFor($taxYear); $worked = $moves->isNotEmpty(); @endphp
    <div class="st-r st-l">
        <div class="st-sr"></div>
        <div class="st-desc">
            <span class="st-name">{{ $line->description }}</span>
            @if($line->balancing)<span class="st-tag st-tag-b">balancing</span>@endif
            @if($worked)<span class="st-tag">worked</span>@endif
            @if($line->attributeSummary())<span class="st-attrs">{{ $line->attributeSummary() }}</span>@endif
            <span class="st-tools">
                <button type="button" class="st-tool" title="Additions and disposals" onclick="toggleMoves({{ $line->id }})">
                    <i class="bi bi-list-ul"></i>
                </button>
                @unless($line->balancing)
                    <button type="button" class="st-tool" title="Make this the balancing figure" onclick="setBalancing({{ $line->id }})">
                        <i class="bi bi-calculator"></i>
                    </button>
                @endunless
                <button type="button" class="st-tool st-tool-x" title="Remove this line"
                        onclick="removeLine({{ $line->id }}, @json($line->description))"><i class="bi bi-trash"></i></button>
            </span>
        </div>
        <div class="st-num">
            @if($line->balancing || $worked)
                <span class="st-derived" title="{{ $line->balancing ? 'Worked out so the statement reconciles' : 'Worked out from the movements below' }}">{{ $n($line->amountFor($taxYear)) }}</span>
            @else
                <input type="number" step="0.01" class="st-in" name="amounts[{{ $line->id }}]"
                       value="{{ $line->amountFor($taxYear) }}" aria-label="{{ $line->description }}">
            @endif
        </div>
        <div class="st-num st-prior">{{ $line->amountFor($taxYear - 1) === null ? '—' : $n($line->amountFor($taxYear - 1)) }}</div>
    </div>

    <div class="st-mv-box @if($worked) open @endif" id="mv-{{ $line->id }}">
        <div class="st-mv-t">Movements during {{ $taxYear }}</div>
        <div class="st-mv st-mv-edge">
            <div>Opening, 30 June {{ $taxYear - 1 }}</div>
            <div class="st-num">{{ $n($line->values->firstWhere('tax_year', $taxYear - 1)?->amount ?? 0) }}</div>
            <div></div>
        </div>
        @foreach($moves as $m)
            <div class="st-mv">
                <div>
                    {{ $m->note ?: \App\Models\WealthMovement::KINDS[$m->kind] }}
                    <span class="st-attrs">{{ \App\Models\WealthMovement::KINDS[$m->kind] }}@if($m->occurred_on) · {{ $m->occurred_on->format('d M Y') }}@endif</span>
                </div>
                <div class="st-num {{ $m->kind === 'disposal' ? 'st-out' : 'st-in-amt' }}">
                    {{ $m->kind === 'disposal' ? '(' . $n($m->amount) . ')' : $n($m->amount) }}
                </div>
                <div><button form="mvdel{{ $m->id }}" class="st-tool st-tool-x" title="Remove"><i class="bi bi-x-lg"></i></button></div>
            </div>
        @endforeach
        <div class="st-mv st-mv-edge">
            <div>Closing, 30 June {{ $taxYear }}</div>
            <div class="st-num">{{ $n($line->amountFor($taxYear)) }}</div>
            <div></div>
        </div>
        {{-- The controls belong to a form declared outside the statement: a form
             inside a form is dropped by the browser. --}}
        <div class="st-mv-add">
            <select form="mvadd{{ $line->id }}" name="kind" class="form-select form-select-sm" aria-label="Kind">
                @foreach(\App\Models\WealthMovement::KINDS as $k => $label)
                    <option value="{{ $k }}">{{ $label }}</option>
                @endforeach
            </select>
            <input form="mvadd{{ $line->id }}" type="text" name="note" class="form-control form-control-sm"
                   placeholder="What happened — e.g. construction of ground floor" aria-label="What happened">
            <input form="mvadd{{ $line->id }}" type="date" name="occurred_on" class="form-control form-control-sm" aria-label="Date">
            <input form="mvadd{{ $line->id }}" type="number" step="0.01" name="amount"
                   class="form-control form-control-sm st-num" placeholder="Amount" required aria-label="Amount">
            <button form="mvadd{{ $line->id }}" class="btn btn-accent btn-sm">Add</button>
        </div>
    </div>
@endforeach
