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
                        data-bs-toggle="modal" data-bs-target="#rm{{ $line->id }}"><i class="bi bi-trash"></i></button>
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

    {{-- Taking an asset off the statement is three different events, and each
         posts somewhere different. --}}
    <div class="modal fade" id="rm{{ $line->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('wealth.lines.dispose', [$client, $line]) }}">
                @csrf
                <input type="hidden" name="tax_year" value="{{ $taxYear }}">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Remove from the statement</h5>
                        <p class="ws-sub mb-0">{{ $line->description }} · held at {{ $n($line->amountFor($taxYear)) }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="rm-choice">
                        <label><input type="radio" name="mode" value="error" checked onchange="rmMode({{ $line->id }})">
                            <span><strong>Entered in error</strong><em>Just remove it. Nothing is posted anywhere else.</em></span></label>
                        <label><input type="radio" name="mode" value="gift" onchange="rmMode({{ $line->id }})">
                            <span><strong>Gifted out</strong><em>To a relative it is an outflow; to anyone else it is a disposal at fair market value.</em></span></label>
                        <label><input type="radio" name="mode" value="sale" onchange="rmMode({{ $line->id }})">
                            <span><strong>Sold</strong><em>Goes to capital gains, against what the asset cost.</em></span></label>
                    </div>

                    <div class="rm-fields" data-for="gift sale" id="rmcommon{{ $line->id }}" hidden>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="rd{{ $line->id }}">Date</label>
                                <input type="date" name="occurred_on" id="rd{{ $line->id }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="rs{{ $line->id }}">Share disposed (%)</label>
                                <input type="number" step="0.01" min="0.01" max="100" name="share" id="rs{{ $line->id }}"
                                       class="form-control form-control-sm" value="100" style="text-align: right;">
                                <div class="form-text">Less than 100 leaves the rest on the statement.</div>
                            </div>
                        </div>
                    </div>

                    <div class="rm-fields" data-for="sale" id="rmsale{{ $line->id }}" hidden>
                        <div class="row g-3 mt-0">
                            <div class="col-md-6">
                                <label class="form-label" for="rc{{ $line->id }}">Sale consideration</label>
                                <input type="number" step="1" name="consideration" id="rc{{ $line->id }}"
                                       class="form-control form-control-sm" style="text-align: right;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="rsc{{ $line->id }}">Expenses of sale</label>
                                <input type="number" step="1" name="selling_cost" id="rsc{{ $line->id }}"
                                       class="form-control form-control-sm" style="text-align: right;">
                                <div class="form-text">Added to the cost of the asset, not netted off the price.</div>
                            </div>
                        </div>
                    </div>

                    <div class="rm-fields" data-for="gift" id="rmgift{{ $line->id }}" hidden>
                        <div class="row g-3 mt-0">
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="relative" value="1"
                                           id="rr{{ $line->id }}" checked onchange="rmMode({{ $line->id }})">
                                    <label class="form-check-label" for="rr{{ $line->id }}">The recipient is a relative</label>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label" for="rn{{ $line->id }}">Recipient's name</label>
                                <input type="text" name="recipient" id="rn{{ $line->id }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="ri{{ $line->id }}">CNIC / NTN</label>
                                <input type="text" name="recipient_id" id="ri{{ $line->id }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6 rm-fmv" id="rmfmv{{ $line->id }}" hidden>
                                <label class="form-label" for="rf{{ $line->id }}">Fair market value</label>
                                <input type="number" step="1" name="fair_value" id="rf{{ $line->id }}"
                                       class="form-control form-control-sm" style="text-align: right;">
                                <div class="form-text">A gift to someone who is not a relative is taxed on this.</div>
                            </div>
                        </div>
                    </div>

                    <p class="ws-sub mt-3 mb-0" id="rmnote{{ $line->id }}">
                        The line will simply be removed.
                    </p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger">Remove</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
