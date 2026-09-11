<?php

namespace App\Http\Controllers\Mcp;

use App\Http\Controllers\Controller;
use App\Models\WhtCompany;
use App\Models\WhtParty;
use App\Models\WhtPurchase;
use App\Models\WhtSalary;
use App\Services\Wht\WhtPsidBatcher;
use App\Services\Wht\WhtTransactionImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * MCP connector, so Claude in the claude.ai app can work with the WHT module
 * directly instead of passing spreadsheets back and forth.
 *
 * Speaks JSON-RPC 2.0 over a single POST endpoint, which is what the Streamable
 * HTTP transport needs for a server that never initiates messages of its own.
 *
 * Two deliberate constraints:
 *
 *  - Claude sends ROWS, never tax figures. Every rate and amount is recomputed
 *    here by WhtCalculator, exactly as the import screen does. A tax figure in
 *    the client's sheet is carried through only so the discrepancy is reported.
 *
 *  - Writing is a two-step. preview_import analyses and returns a token;
 *    commit_import needs that token back. An LLM misreading a merged header
 *    should not be able to create fifty wrong entries in one call.
 */
class WhtMcpController extends Controller
{
    private const PROTOCOL_VERSIONS = ['2025-06-18', '2025-03-26', '2024-11-05'];
    private const CACHE_MINUTES = 30;

    public function __construct(
        private WhtTransactionImporter $importer,
        private WhtPsidBatcher $batcher,
    ) {
    }

    public function handle(Request $request, string $secret)
    {
        $expected = (string) config('services.wht_mcp.secret', '');

        // Unset means the connector is off. 404 rather than 401, so probing
        // cannot confirm the endpoint exists.
        if ($expected === '' || !hash_equals($expected, $secret)) {
            abort(404);
        }

        $body = $request->json()->all();

        // A batch arrives as a list of messages.
        if (array_is_list($body) && $body !== []) {
            $responses = array_values(array_filter(array_map(fn($m) => $this->dispatch($m), $body)));

            return $responses ? response()->json($responses) : response()->noContent(202);
        }

        $response = $this->dispatch($body);

        // Notifications get no reply.
        return $response ? response()->json($response) : response()->noContent(202);
    }

    private function dispatch(array $message): ?array
    {
        $id = $message['id'] ?? null;
        $method = $message['method'] ?? '';
        $params = $message['params'] ?? [];

        // No id means a notification: act, but stay silent.
        if ($id === null) {
            return null;
        }

        try {
            $result = match ($method) {
                'initialize' => $this->initialize($params),
                'ping'       => (object) [],
                'tools/list' => ['tools' => $this->tools()],
                'tools/call' => $this->callTool($params),
                default      => throw new \RuntimeException("Unknown method: {$method}", -32601),
            };

            return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
        } catch (\Throwable $e) {
            $code = $e->getCode();

            return [
                'jsonrpc' => '2.0',
                'id'      => $id,
                'error'   => [
                    'code'    => is_int($code) && $code < 0 ? $code : -32603,
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }

    private function initialize(array $params): array
    {
        $asked = $params['protocolVersion'] ?? null;

        return [
            'protocolVersion' => in_array($asked, self::PROTOCOL_VERSIONS, true)
                ? $asked
                : self::PROTOCOL_VERSIONS[0],
            'capabilities' => ['tools' => (object) []],
            'serverInfo'   => ['name' => 'fti-pak-wht', 'version' => '1.0.0'],
            'instructions' => implode(' ', [
                'Withholding tax module for FTI Pak.',
                'Never calculate tax yourself — send the payment rows and this server computes',
                'every rate and amount from its own rate matrix.',
                'Importing is two steps: preview_import returns a token and a summary; show that',
                'summary to the user and call commit_import with the token only after they agree.',
            ]),
        ];
    }

    private function tools(): array
    {
        $row = [
            'type' => 'object',
            'properties' => [
                'payee_name'     => ['type' => 'string', 'description' => 'Vendor, supplier or contractor name as written in the client sheet. Do not tidy it.'],
                'payee_cnic_ntn' => ['type' => 'string', 'description' => 'CNIC or NTN, digits only. Blank if absent — never invent one.'],
                'payment_date'   => ['type' => 'string', 'description' => 'Date the payment was made, as d/m/Y or Y-m-d.'],
                'period_month'   => ['type' => 'string', 'description' => 'Tax period as YYYY-MM. Omit unless the sheet states a period different from the payment month.'],
                'section'        => ['type' => 'string', 'description' => 'Section such as 153(1)(a)/9, or an FBR payment code such as 64060009. Omit if the sheet does not say — never guess; the payee default is used.'],
                'amount'         => ['type' => 'number', 'description' => 'Payment amount, plain number with no separators.'],
                'amount_basis'   => ['type' => 'string', 'enum' => ['gross', 'net'], 'description' => 'Whether amount is before or after tax. Omit if unclear.'],
                'tax_withheld'   => ['type' => 'number', 'description' => 'Tax the client states they deducted, if the sheet says. Used only as a cross-check — the server recomputes and reports any difference. Never supply your own calculation.'],
                'remarks'        => ['type' => 'string', 'description' => 'Invoice number, cost centre or other context worth keeping.'],
            ],
            'required' => ['payment_date', 'amount'],
        ];

        return [
            [
                'name' => 'list_agents',
                'description' => 'List the withholding agents (client companies) in the system, with their ids and NTNs. Call this first when the user names an agent, to confirm which one they mean.',
                'inputSchema' => ['type' => 'object', 'properties' => (object) []],
            ],
            [
                'name' => 'deposit_status',
                'description' => 'For one tax period, show every agent\'s vendor and salary batches: how many entries, total tax, and whether a PSID has been raised or a CPR recorded. Use for questions like "which agents have not deposited June?".',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'month' => ['type' => 'string', 'description' => 'Tax period as YYYY-MM. Defaults to last month.'],
                    ],
                ],
            ],
            [
                'name' => 'find_parties',
                'description' => 'Search an agent\'s vendors and employees by name or CNIC/NTN. Use before creating a party, to check whether a differently-spelled one already exists.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'agent'  => ['type' => 'string', 'description' => 'Withholding agent id or part of its name.'],
                        'search' => ['type' => 'string', 'description' => 'Part of a name, or a CNIC/NTN.'],
                    ],
                    'required' => ['agent'],
                ],
            ],
            [
                'name' => 'create_party',
                'description' => implode(' ', [
                    'Add a vendor or employee to an agent, so their payments can be imported.',
                    'category and atl_status are REQUIRED and have no default because both change the tax rate:',
                    'getting them wrong produces wrong tax.',
                    'Do not infer them from a name. Take them from the client sheet if stated, otherwise ASK THE USER.',
                    'Check find_parties first — the payee may already exist under a different spelling.',
                ]),
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'agent'      => ['type' => 'string', 'description' => 'Withholding agent id or part of its name.'],
                        'name'       => ['type' => 'string', 'description' => 'Party name as it should appear.'],
                        'cnic_ntn'   => ['type' => 'string', 'description' => 'CNIC or NTN, digits only. Strongly recommended — it is how payments are matched.'],
                        'type'       => ['type' => 'string', 'enum' => ['vendor', 'employee', 'both'], 'description' => 'Defaults to vendor.'],
                        'category'   => ['type' => 'string', 'enum' => ['company', 'individual', 'aop'], 'description' => 'REQUIRED. Ask the user if the sheet does not say.'],
                        'atl_status' => ['type' => 'string', 'enum' => ['filer', 'non-filer'], 'description' => 'REQUIRED. Whether they are on the Active Taxpayers List. Ask the user if the sheet does not say.'],
                        'default_section' => ['type' => 'string', 'description' => 'Optional section to assume for this party when a sheet does not state one.'],
                        'address'    => ['type' => 'string'],
                        'city'       => ['type' => 'string'],
                    ],
                    'required' => ['agent', 'name', 'category', 'atl_status'],
                ],
            ],
            [
                'name' => 'preview_import',
                'description' => implode(' ', [
                    'Analyse payment rows parsed from a client spreadsheet WITHOUT writing anything.',
                    'The server matches each payee, resolves the section, looks up the rate for the tax period and computes the tax.',
                    'Returns a per-row verdict (ok / warning / blocked) and a token.',
                    'Show the summary to the user — especially blocked rows and unknown payees — and only then call commit_import.',
                ]),
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'agent' => ['type' => 'string', 'description' => 'Withholding agent id or part of its name.'],
                        'kind'  => ['type' => 'string', 'enum' => ['purchases', 'salaries'], 'description' => "purchases for vendor, supplier and contractor payments (tax from the section rate matrix). salaries for employee pay (tax from the year's salary slabs). Defaults to purchases."],
                        'rows'  => ['type' => 'array', 'items' => $row, 'description' => 'One entry per payment. For salaries, put the salary month in period_month and the total salary (or take-home, with amount_basis net) in amount.'],
                    ],
                    'required' => ['agent', 'rows'],
                ],
            ],
            [
                'name' => 'commit_import',
                'description' => 'Write the previewed payments into the system. Requires the token from preview_import, which is single-use and expires after 30 minutes. Call this only after the user has seen the preview and agreed.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'token'            => ['type' => 'string', 'description' => 'Token returned by preview_import.'],
                        'include_warnings' => ['type' => 'boolean', 'description' => 'Import rows flagged with a warning as well as clean ones. Default true.'],
                    ],
                    'required' => ['token'],
                ],
            ],
        ];
    }

    private function callTool(array $params): array
    {
        $name = $params['name'] ?? '';
        $args = $params['arguments'] ?? [];

        $payload = match ($name) {
            'list_agents'    => $this->listAgents(),
            'find_parties'   => $this->findParties($args),
            'create_party'   => $this->createParty($args),
            'deposit_status' => $this->depositStatus($args),
            'preview_import' => $this->previewImport($args),
            'commit_import'  => $this->commitImport($args),
            default          => throw new \RuntimeException("Unknown tool: {$name}", -32602),
        };

        return [
            'content' => [[
                'type' => 'text',
                'text' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]],
        ];
    }

    // ── tools ────────────────────────────────────────────────────────────────

    private function listAgents(): array
    {
        return [
            'agents' => WhtCompany::orderBy('name')
                ->get(['id', 'name', 'ntn_cnic', 'is_active'])
                ->toArray(),
        ];
    }

    private function depositStatus(array $args): array
    {
        $month = $this->month($args['month'] ?? null);
        $out = [];

        foreach (WhtCompany::where('is_active', true)->orderBy('name')->get() as $agent) {
            foreach (WhtPsidBatcher::KINDS as $kind) {
                $b = $this->batcher->batch($agent, $month, $kind);

                if ($b['count'] === 0) {
                    continue;
                }

                $out[] = [
                    'agent'   => $agent->name,
                    'kind'    => $kind,
                    'entries' => $b['count'],
                    'tax'     => round($b['tax'], 2),
                    'status'  => $b['status'],
                    'psid_no' => $b['psid_no'],
                    'cpr_no'  => $b['cpr_no'],
                ];
            }
        }

        return ['period' => $month->format('Y-m'), 'batches' => $out];
    }

    private function findParties(array $args): array
    {
        $agent = $this->agent($args['agent'] ?? '');
        $search = trim((string) ($args['search'] ?? ''));

        $q = $agent->parties();

        if ($search !== '') {
            $digits = preg_replace('/[^0-9]/', '', $search);
            $q->where(function ($w) use ($search, $digits) {
                $w->where('name', 'like', "%{$search}%");
                if ($digits !== '') {
                    $w->orWhere('cnic_ntn', 'like', "%{$digits}%");
                }
            });
        }

        return [
            'agent'   => $agent->name,
            'parties' => $q->orderBy('name')->limit(50)
                ->get(['id', 'name', 'cnic_ntn', 'type', 'category', 'atl_status', 'is_active', 'default_section'])
                ->toArray(),
        ];
    }

    private function createParty(array $args): array
    {
        $agent = $this->agent($args['agent'] ?? '');

        $name = trim((string) ($args['name'] ?? ''));
        $category = strtolower(trim((string) ($args['category'] ?? '')));
        $atl = strtolower(trim((string) ($args['atl_status'] ?? '')));

        if ($name === '') {
            throw new \RuntimeException('A party name is required.', -32602);
        }

        // No defaults on purpose: both of these change the tax rate.
        if (!in_array($category, ['company', 'individual', 'aop'], true)) {
            throw new \RuntimeException('category must be company, individual or aop. Ask the user — do not infer it, it changes the tax rate.', -32602);
        }

        if (!in_array($atl, ['filer', 'non-filer'], true)) {
            throw new \RuntimeException('atl_status must be filer or non-filer. Ask the user — do not infer it, it changes the tax rate.', -32602);
        }

        $cnic = preg_replace('/[^0-9]/', '', (string) ($args['cnic_ntn'] ?? ''));

        if ($cnic !== '') {
            $existing = $agent->parties()->where('cnic_ntn', $cnic)->first();

            if ($existing) {
                return [
                    'created' => false,
                    'party'   => $existing->only(['id', 'name', 'cnic_ntn', 'type', 'category', 'atl_status']),
                    'message' => "A party with that CNIC/NTN already exists as '{$existing->name}'. Using it rather than creating a duplicate.",
                ];
            }
        }

        $type = strtolower(trim((string) ($args['type'] ?? 'vendor')));
        $type = in_array($type, ['vendor', 'employee', 'both'], true) ? $type : 'vendor';

        $party = WhtParty::create([
            'wht_company_id'  => $agent->id,
            'name'            => $name,
            'cnic_ntn'        => $cnic ?: null,
            'type'            => $type,
            'category'        => $category,
            'atl_status'      => $atl,
            'default_section' => trim((string) ($args['default_section'] ?? '')) ?: null,
            'address'         => trim((string) ($args['address'] ?? '')) ?: null,
            'city'            => trim((string) ($args['city'] ?? '')) ?: null,
        ]);

        return [
            'created' => true,
            'party'   => $party->only(['id', 'name', 'cnic_ntn', 'type', 'category', 'atl_status']),
            'message' => "Created {$party->name} as a {$category} {$type}, {$atl}. Re-run preview_import to pick up their rows.",
        ];
    }

    private function previewImport(array $args): array
    {
        $agent = $this->agent($args['agent'] ?? '');
        $kind = ($args['kind'] ?? 'purchases') === 'salaries' ? 'salaries' : 'purchases';
        $rows = $args['rows'] ?? [];

        if (!is_array($rows) || $rows === []) {
            throw new \RuntimeException('No rows supplied.', -32602);
        }

        if (count($rows) > 2000) {
            throw new \RuntimeException('Too many rows in one call — split into batches of 2000 or fewer.', -32602);
        }

        $analysis = $this->importer->analyseRows($agent, $rows, $kind);
        $summary = $analysis['summary'];

        $token = Str::uuid()->toString();

        Cache::put("wht_mcp_import:{$token}", [
            'agent_id' => $agent->id,
            'kind'     => $kind,
            'rows'     => $analysis['rows']->toArray(),
        ], now()->addMinutes(self::CACHE_MINUTES));

        return [
            'token'   => $token,
            'agent'   => $agent->name,
            'kind'    => $kind,
            'summary' => [
                'total'          => $summary['total'],
                'will_import'    => $summary['ok'] + $summary['warning'],
                'clean'          => $summary['ok'],
                'with_warnings'  => $summary['warning'],
                'blocked'        => $summary['blocked'],
                'gross_total'    => round($summary['gross'], 2),
                'tax_total'      => round($summary['tax'], 2),
                'unknown_payees' => $summary['unknown'],
            ],
            'rows' => $analysis['rows']->map(fn($r) => [
                'row'          => $r['line'],
                'status'       => $r['status'],
                'payee'        => $r['party_name'] ?? $r['raw_payee'],
                'section'      => $r['section'],
                'period_month' => $r['period_month'],
                'gross_amount' => $r['gross_amount'],
                'tax_rate'     => $r['tax_rate'],
                'tax_year'     => $r['tax_year'] ?? null,
                'taxable_salary' => $r['taxable_salary'] ?? null,
                'exempt_amount'  => $r['exempt_amount'] ?? null,
                'tax_withheld' => $r['tax_withheld'],
                'problems'     => $r['problems'],
                'warnings'     => $r['warnings'],
            ])->values(),
            'next_step' => 'Show this summary to the user. Call commit_import with the token only once they agree. Blocked rows will be skipped; unknown payees must be added in the app first.',
        ];
    }

    private function commitImport(array $args): array
    {
        $token = $args['token'] ?? '';
        $cached = $token ? Cache::get("wht_mcp_import:{$token}") : null;

        if (!$cached) {
            throw new \RuntimeException('That preview has expired or was already used. Run preview_import again.', -32602);
        }

        // Single-use: drop it before writing, so a retry cannot double-import.
        Cache::forget("wht_mcp_import:{$token}");

        $agent = WhtCompany::findOrFail($cached['agent_id']);
        $includeWarnings = $args['include_warnings'] ?? true;

        $importable = collect($cached['rows'])->filter(function ($r) use ($includeWarnings) {
            if ($r['status'] === WhtTransactionImporter::STATUS_BLOCKED) {
                return false;
            }

            return $includeWarnings || $r['status'] === WhtTransactionImporter::STATUS_OK;
        });

        if ($importable->isEmpty()) {
            return ['created' => 0, 'message' => 'Nothing to import with those options.'];
        }

        $kind = $cached['kind'] ?? 'purchases';
        $created = 0;

        DB::transaction(function () use ($importable, $agent, $kind, &$created) {
            foreach ($importable as $r) {
                if ($kind === 'salaries') {
                    WhtSalary::create([
                        'wht_company_id'    => $agent->id,
                        'employee_id'       => $r['party_id'],
                        'salary_month'      => $r['period_month'] . '-01',
                        'payment_date'      => $r['payment_date'],
                        'section'           => $r['section'],
                        'calc_mode'         => $r['calc_mode'],
                        'input_amount'      => $r['input_amount'],
                        'taxable_salary'    => $r['taxable_salary'],
                        'exempt_amount'     => $r['exempt_amount'],
                        'exempt_rate'       => $r['exempt_rate'],
                        'total_salary'      => $r['gross_amount'],
                        'tax_deducted'      => $r['tax_withheld'],
                        'final_net_payment' => $r['net_payment'],
                        'tax_year'          => $r['tax_year'],
                    ]);
                } else {
                    WhtPurchase::create([
                        'wht_company_id' => $agent->id,
                        'party_id'       => $r['party_id'],
                        'period_month'   => $r['period_month'] . '-01',
                        'payment_date'   => $r['payment_date'],
                        'section'        => $r['section'],
                        'calc_mode'      => $r['calc_mode'],
                        'gross_amount'   => $r['gross_amount'],
                        'tax_rate'       => $r['tax_rate'],
                        'tax_rate_id'    => $r['tax_rate_id'],
                        'rate_source'    => $r['rate_source'] ?? 'matrix',
                        'tax_withheld'   => $r['tax_withheld'],
                        'net_payment'    => $r['net_payment'],
                        'remarks'        => $r['remarks'],
                    ]);
                }
                $created++;
            }
        });

        $where = $kind === 'salaries' ? 'WHT → Salaries' : 'WHT → Payments';

        return [
            'created'   => $created,
            'agent'     => $agent->name,
            'kind'      => $kind,
            'tax_total' => round($importable->sum('tax_withheld'), 2),
            'message'   => "Imported {$created} " . ($kind === 'salaries' ? 'salary records' : 'payments')
                           . " into {$agent->name}. Review them in the app under {$where}.",
        ];
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function agent(string $key): WhtCompany
    {
        $key = trim($key);

        $agent = ctype_digit($key)
            ? WhtCompany::find($key)
            : WhtCompany::where('name', 'like', '%' . $key . '%')->first();

        if (!$agent) {
            throw new \RuntimeException("No withholding agent matches '{$key}'. Call list_agents to see the options.", -32602);
        }

        return $agent;
    }

    private function month(?string $v): Carbon
    {
        $v = $v ?: now()->subMonth()->format('Y-m');

        try {
            return Carbon::createFromFormat('Y-m-d', $v . '-01')->startOfMonth();
        } catch (\Throwable) {
            throw new \RuntimeException("Could not read '{$v}' as a tax period. Use YYYY-MM.", -32602);
        }
    }
}
