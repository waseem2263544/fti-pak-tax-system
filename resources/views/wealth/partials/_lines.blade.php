{{-- The lines under one head. Figures are read-only here: a line's value is the
     running total of what has been recorded against it, and changes are made
     through the additions and disposals dialog. --}}
@foreach($group as $line)
    @php $moves = $line->movementsFor($taxYear); @endphp
    <div class="st-r st-l">
        <div class="st-sr"></div>
        <div class="st-desc">
            <span class="st-name">{{ $line->description }}</span>
            @if($line->balancing)<span class="st-tag st-tag-b">balancing</span>@endif
            @if($moves->isNotEmpty())<span class="st-tag">{{ $moves->count() }} {{ Str::plural('movement', $moves->count()) }}</span>@endif
            @if($line->attributeSummary())<span class="st-attrs">{{ $line->attributeSummary() }}</span>@endif
            <span class="st-tools">
                <button type="button" class="st-tool" title="Additions and disposals"
                        data-bs-toggle="modal" data-bs-target="#mv{{ $line->id }}">
                    <i class="bi bi-plus-slash-minus"></i>
                </button>
                @unless($line->balancing)
                    <button type="button" class="st-tool" title="Make this the balancing figure"
                            onclick="setBalancing({{ $line->id }})"><i class="bi bi-calculator"></i></button>
                @endunless
                <button type="button" class="st-tool st-tool-x" title="Remove this line"
                        onclick="removeLine({{ $line->id }}, @json($line->description))"><i class="bi bi-trash"></i></button>
            </span>
        </div>
        <div class="st-num">
            <span class="@if($line->balancing) st-derived @endif"
                  @if($line->balancing) title="Worked out so the statement reconciles" @endif>{{ $n($line->amountFor($taxYear)) }}</span>
        </div>
        <div class="st-num st-prior">{{ $line->amountFor($taxYear - 1) === null ? '—' : $n($line->amountFor($taxYear - 1)) }}</div>
    </div>

    {{-- Additions and disposals for this line, in a dialog. --}}
    <div class="modal fade" id="mv{{ $line->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">{{ $line->description }}</h5>
                        <p class="ws-sub mb-0">Additions and disposals during {{ $taxYear }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="st-mv st-mv-edge">
                        <div>Opening, 30 June {{ $taxYear - 1 }}</div>
                        <div class="st-num">{{ $n($line->openingFor($taxYear)) }}</div>
                        <div></div>
                    </div>
                    @forelse($moves as $m)
                        <div class="st-mv">
                            <div>
                                {{ $m->note ?: \App\Models\WealthMovement::KINDS[$m->kind] }}
                                <span class="st-attrs">{{ \App\Models\WealthMovement::KINDS[$m->kind] }}@if($m->occurred_on) · {{ $m->occurred_on->format('d M Y') }}@endif</span>
                            </div>
                            <div class="st-num {{ $m->kind === 'disposal' ? 'st-out' : 'st-in-amt' }}">
                                {{ $m->kind === 'disposal' ? '(' . $n($m->amount) . ')' : $n($m->amount) }}
                            </div>
                            <div>
                                <form method="POST" action="{{ route('wealth.movements.destroy', [$client, $line, $m]) }}"
                                      onsubmit="return confirm('Remove this movement?')">
                                    @csrf @method('DELETE')
                                    <button class="st-tool st-tool-x" title="Remove"><i class="bi bi-x-lg"></i></button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="st-mv"><div class="text-muted">Nothing recorded for {{ $taxYear }}.</div><div></div><div></div></div>
                    @endforelse
                    <div class="st-mv st-mv-edge">
                        <div>Closing, 30 June {{ $taxYear }}</div>
                        <div class="st-num">{{ $n($line->amountFor($taxYear)) }}</div>
                        <div></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-accent"
                            data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#mvadd{{ $line->id }}">
                        <i class="bi bi-plus-lg me-1"></i> Record an addition or disposal
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Recording one is a form of its own, so the working above stays a
         working and is not half list, half data entry. --}}
    <div class="modal fade" id="mvadd{{ $line->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('wealth.movements.store', [$client, $line]) }}">
                @csrf
                <input type="hidden" name="tax_year" value="{{ $taxYear }}">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Addition or disposal</h5>
                        <p class="ws-sub mb-0">{{ $line->description }} · {{ $taxYear }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label" for="mk{{ $line->id }}">Kind</label>
                            <select name="kind" id="mk{{ $line->id }}" class="form-select form-select-sm">
                                @foreach(\App\Models\WealthMovement::KINDS as $k => $label)
                                    <option value="{{ $k }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="md{{ $line->id }}">Date</label>
                            <input type="date" name="occurred_on" id="md{{ $line->id }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="mn{{ $line->id }}">What happened</label>
                            <input type="text" name="note" id="mn{{ $line->id }}" class="form-control form-control-sm"
                                   placeholder="e.g. Construction of ground floor · Second instalment · Part sale of 4 marla">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="ma{{ $line->id }}">Amount</label>
                            <input type="number" step="1" name="amount" id="ma{{ $line->id }}"
                                   class="form-control form-control-sm" style="text-align: right;" required>
                        </div>
                    </div>
                    <p class="ws-sub mt-3 mb-0">
                        An addition raises the line's cost; a disposal reduces it. Cash in hand moves by the
                        same amount, so the reconciliation stays at nil.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-accent">Record</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
