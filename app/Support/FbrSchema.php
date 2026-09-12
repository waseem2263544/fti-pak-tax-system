<?php

namespace App\Support;

/**
 * The wealth statement and return, as FBR defines them.
 *
 * Source: SRO 1160(I)/2019, "Instructions for Filling in Return Form & Wealth
 * Statement", cross-checked against IRIS. The codes here are the ones IRIS uses
 * internally, so every figure stored maps one-to-one onto a field on the form.
 *
 * A Finance Act change to the form should be a change in this file and nowhere
 * else.
 */
class FbrSchema
{
    public const PROPERTY_TYPES = ['Constructed Property', 'Farm House', 'Flat', 'Open Plot', 'Shop', 'Plaza', 'Factory', 'Workshop'];

    public const AREA_UNITS = ['Acre', 'Kanal', 'Marla', 'Square foot', 'Square meter', 'Square yards'];

    public const PROVINCES = ['Punjab', 'Sindh', 'Khyber Pakhtunkhwa', 'Balochistan', 'ICT', 'AJK', 'FATA', 'Gilgit Baltistan'];

    /** Fields shared by the two property heads. */
    private const PROPERTY_FIELDS = [
        ['key' => 'form',        'label' => 'Type',                'type' => 'select', 'options' => self::PROPERTY_TYPES],
        ['key' => 'acquired_on', 'label' => 'Date of acquisition', 'type' => 'date'],
        ['key' => 'area',        'label' => 'Land area',           'type' => 'number'],
        ['key' => 'area_unit',   'label' => 'Unit',                'type' => 'select', 'options' => self::AREA_UNITS],
        ['key' => 'share',       'label' => 'Share %',             'type' => 'number'],
        ['key' => 'address',     'label' => 'Unit / street / block / locality', 'type' => 'text', 'wide' => true],
        ['key' => 'city',        'label' => 'City',                'type' => 'text'],
        ['key' => 'district',    'label' => 'District',            'type' => 'text'],
        ['key' => 'province',    'label' => 'Province',            'type' => 'select', 'options' => self::PROVINCES],
    ];

    /**
     * Asset heads, in wealth statement order.
     *
     * 'fields' are the attributes IRIS asks for on that head; they are stored
     * as details on the line rather than as columns, because they differ
     * completely from one head to the next.
     */
    public static function assetHeads(): array
    {
        return [
            '7001' => [
                'sr' => 1, 'label' => 'Agricultural Property',
                'fields' => array_merge([
                    ['key' => 'form',    'label' => 'Form', 'type' => 'select', 'options' => ['Irrigated', 'Unirrigated', 'Uncultivable']],
                    ['key' => 'mauza',   'label' => 'Mauza / Village / Chak No.', 'type' => 'text'],
                    ['key' => 'tehsil',  'label' => 'Tehsil', 'type' => 'text'],
                ], array_slice(self::PROPERTY_FIELDS, 1)),
            ],
            '7002' => [
                'sr' => 2, 'label' => 'Commercial, Industrial, Residential Property (Non-Business)',
                'fields' => self::PROPERTY_FIELDS,
            ],
            '7003' => [
                'sr' => 3, 'label' => 'Business Capital',
                'fields' => [
                    ['key' => 'form',  'label' => 'Form',  'type' => 'select', 'options' => ['Sole Proprietorship', 'AOP']],
                    ['key' => 'ntn',   'label' => 'NTN of the business / AOP', 'type' => 'text'],
                    ['key' => 'share', 'label' => 'Share %', 'type' => 'number'],
                ],
            ],
            '7004' => [
                'sr' => 4, 'label' => 'Equipment (Non-Business)',
                'hint' => 'Generator, tubewell, tractor, trolley, harvester…',
                'fields' => [['key' => 'acquired_on', 'label' => 'Date of acquisition', 'type' => 'date']],
            ],
            '7005' => [
                'sr' => 5, 'label' => 'Animal (Non-Business)',
                'fields' => [['key' => 'form', 'label' => 'Form', 'type' => 'select', 'options' => ['Livestock', 'Pet', 'Unspecified']]],
            ],
            '7006' => [
                'sr' => 6, 'label' => 'Investment (Non-Business)',
                'fields' => [
                    ['key' => 'form',        'label' => 'Form', 'type' => 'select', 'options' => [
                        'Account — Current', 'Account — Saving', 'Account — Fixed Deposit', 'Account — PLS',
                        'Annuity', 'Bond', 'Certificate', 'Debenture', 'Deposit', 'Term Deposit', 'Fund',
                        'Instrument', 'Insurance Policy', 'Security', 'Stock / Share', 'Unit', 'Others']],
                    ['key' => 'account_no',  'label' => 'Account / instrument no.', 'type' => 'text'],
                    ['key' => 'institution', 'label' => 'Institution name or CNIC', 'type' => 'text', 'wide' => true],
                    ['key' => 'share',       'label' => 'Share %', 'type' => 'number'],
                ],
            ],
            '7007' => [
                'sr' => 7, 'label' => 'Debt (Non-Business) — receivable',
                'fields' => [
                    ['key' => 'form',        'label' => 'Form', 'type' => 'select', 'options' => ['Advance', 'Debt', 'Deposit', 'Prepayment', 'Receivable', 'Security', 'Others']],
                    ['key' => 'reference',   'label' => 'Reference no.', 'type' => 'text'],
                    ['key' => 'institution', 'label' => 'Institution name or CNIC', 'type' => 'text', 'wide' => true],
                    ['key' => 'share',       'label' => 'Share %', 'type' => 'number'],
                ],
            ],
            '7008' => [
                'sr' => 8, 'label' => 'Motor Vehicle (Non-Business)',
                'fields' => [
                    ['key' => 'form',            'label' => 'Form', 'type' => 'select', 'options' => ['Car', 'Jeep', 'Motor Cycle', 'Scooter', 'Van']],
                    ['key' => 'registration_no', 'label' => 'E&TD registration no.', 'type' => 'text'],
                    ['key' => 'maker',           'label' => 'Maker', 'type' => 'text'],
                    ['key' => 'engine_capacity', 'label' => 'Engine capacity (cc)', 'type' => 'number'],
                    ['key' => 'acquired_on',     'label' => 'Date of acquisition', 'type' => 'date'],
                ],
            ],
            '7009' => [
                'sr' => 9, 'label' => 'Precious Possession',
                'fields' => [
                    ['key' => 'form',   'label' => 'Form', 'type' => 'select', 'options' => ['Antique / Artifact', 'Jewellery / Ornament / Metal / Stone', 'Others']],
                    ['key' => 'weight', 'label' => 'Weight / quantity', 'type' => 'text'],
                ],
            ],
            '7010' => ['sr' => 10, 'label' => 'Household Effect', 'fields' => []],
            '7011' => ['sr' => 11, 'label' => 'Personal Item',    'fields' => []],
            '7012' => ['sr' => 12, 'label' => 'Cash (Non-Business)', 'hint' => 'Notes and coins in hand', 'fields' => []],
            '7013' => ['sr' => 13, 'label' => 'Any Other Asset',  'fields' => []],
            '7014' => [
                'sr' => 14, 'label' => "Assets in Others' Name (Benami)",
                'hint' => 'Declared only where acquired with your own funds',
                'fields' => [
                    ['key' => 'held_by',      'label' => 'Held in the name of', 'type' => 'text'],
                    ['key' => 'held_by_cnic', 'label' => 'Their CNIC', 'type' => 'text'],
                ],
            ],
            '7016' => [
                'sr' => 16, 'label' => 'Assets held outside Pakistan',
                'hint' => 'At cost, converted to PKR',
                'fields' => [
                    ['key' => 'country',   'label' => 'Country', 'type' => 'text'],
                    ['key' => 'currency',  'label' => 'Currency', 'type' => 'text'],
                    ['key' => 'fx_amount', 'label' => 'Amount in that currency', 'type' => 'number'],
                    ['key' => 'fx_rate',   'label' => 'FX rate used', 'type' => 'number'],
                    ['key' => 'fx_date',   'label' => 'Rate date', 'type' => 'date'],
                ],
            ],
        ];
    }

    /** Sr. 18, Credit (Non-Business). The only liability head on the form. */
    public static function liabilityHeads(): array
    {
        return [
            '7021' => [
                'sr' => 18, 'label' => 'Credit (Non-Business)',
                'fields' => [
                    ['key' => 'form',          'label' => 'Form', 'type' => 'select', 'options' => ['Advance', 'Borrowing', 'Credit', 'Loan', 'Mortgage', 'Overdraft', 'Payable', 'Others']],
                    ['key' => 'creditor',      'label' => "Creditor's name", 'type' => 'text', 'wide' => true],
                    ['key' => 'creditor_ntn',  'label' => "Creditor's NTN / CNIC", 'type' => 'text'],
                ],
            ],
        ];
    }

    public static function head(string $code): ?array
    {
        return self::assetHeads()[$code] ?? self::liabilityHeads()[$code] ?? null;
    }

    public static function headLabel(string $code): string
    {
        return self::head($code)['label'] ?? $code;
    }

    /** Sr. 23 — inflows. */
    public const INFLOWS = [
        '7031' => 'Income declared as per Return for the year — subject to normal tax',
        '7032' => 'Income declared as per Return for the year — exempt from tax',
        '7033' => 'Income attributable to receipts declared under final / fixed tax',
        '7034' => 'Adjustments in income declared as per Return',
        '7035' => 'Foreign remittance',
        '7036' => 'Inheritance',
        '7037' => 'Gift received',
        '7038' => 'Gain on disposal of assets, excluding capital gain on immovable property',
        '7048' => 'Others',
    ];

    /** Sr. 25 — outflows. Personal expenses (7089) are separate, at Sr. 24. */
    public const OUTFLOWS = [
        '7091' => 'Gift given',
        '7092' => 'Loss on disposal of assets',
        '7098' => 'Others',
    ];

    /** Annex-F personal expenses. 7088 is deducted, not added. */
    public const EXPENSES = [
        '7051' => 'Rent',
        '7052' => 'Rates / taxes / charge / cess',
        '7055' => 'Vehicle running / maintenance',
        '7056' => 'Travelling',
        '7058' => 'Electricity',
        '7059' => 'Water',
        '7060' => 'Gas',
        '7061' => 'Telephone',
        '7066' => 'Asset insurance / security',
        '7070' => 'Medical',
        '7071' => 'Educational',
        '7072' => 'Club',
        '7073' => 'Functions / gatherings',
        '7076' => 'Donation, Zakat, annuity, profit on debt, life insurance premium',
        '7087' => 'Other personal / household expenses',
    ];

    public const EXPENSE_CONTRA = '7088';
    public const EXPENSE_CONTRA_LABEL = 'Less: contribution to expenses by family members';

    /**
     * Income heads, each with the working IRIS expects behind it.
     *
     * The detail lines are where the return is actually built: a capital gain
     * is computed per disposal, property income per property, salary per
     * employer. 'computed' names the field derived from the others, and is the
     * only arithmetic done here - no tax is calculated anywhere.
     */
    public static function incomeHeads(): array
    {
        return [
            // Salary is not here: it has an income side, a deduction side and an
            // annual-or-monthly basis, so it gets its own tables and its own
            // page. See SalaryWorking.

            'property' => [
                'label' => 'Property (rental)', 'line' => 'property',
                'links_asset' => ['7002', '7001'],
                'fields' => [
                    ['key' => 'rent_received',  'label' => 'Rent received / receivable', 'type' => 'money'],
                    ['key' => 'other_receipts', 'label' => 'Forfeited deposit / other receipts', 'type' => 'money'],
                    ['key' => 'repairs',        'label' => 'Less: repairs allowance', 'type' => 'money'],
                    ['key' => 'insurance',      'label' => 'Less: insurance premium', 'type' => 'money'],
                    ['key' => 'local_rate',     'label' => 'Less: local rate / tax / cess', 'type' => 'money'],
                    ['key' => 'ground_rent',    'label' => 'Less: ground rent', 'type' => 'money'],
                    ['key' => 'interest',       'label' => 'Less: interest on borrowed capital', 'type' => 'money'],
                    ['key' => 'collection',     'label' => 'Less: rent collection expenditure', 'type' => 'money'],
                    ['key' => 'other_expense',  'label' => 'Less: other admissible deductions', 'type' => 'money'],
                ],
                'computed' => ['rent_received', 'other_receipts'],
                'less'     => ['repairs', 'insurance', 'local_rate', 'ground_rent', 'interest', 'collection', 'other_expense'],
            ],
            'capital_gain' => [
                'label' => 'Capital gains', 'line' => 'disposal',
                'links_asset' => ['7002', '7001', '7006', '7008'],
                'fields' => [
                    ['key' => 'acquired_on',   'label' => 'Date acquired', 'type' => 'date'],
                    ['key' => 'disposed_on',   'label' => 'Date disposed', 'type' => 'date'],
                    ['key' => 'holding',       'label' => 'Holding period', 'type' => 'text'],
                    ['key' => 'consideration', 'label' => 'Sale consideration', 'type' => 'money'],
                    ['key' => 'fair_value',    'label' => 'FBR / fair value, if higher', 'type' => 'money'],
                    ['key' => 'cost',          'label' => 'Less: cost of acquisition', 'type' => 'money'],
                    ['key' => 'improvements',  'label' => 'Less: improvements', 'type' => 'money'],
                    ['key' => 'selling_cost',  'label' => 'Less: expenses of sale', 'type' => 'money'],
                    ['key' => 'share',         'label' => 'Share disposed %', 'type' => 'number'],
                ],
                'computed' => ['consideration'],
                'less'     => ['cost', 'improvements', 'selling_cost'],
            ],
            'business' => [
                'label' => 'Business', 'line' => 'business',
                'fields' => [
                    ['key' => 'business_name', 'label' => 'Business', 'type' => 'text', 'wide' => true],
                    ['key' => 'turnover',      'label' => 'Turnover / receipts', 'type' => 'money'],
                    ['key' => 'cost_of_sales', 'label' => 'Less: cost of sales', 'type' => 'money'],
                    ['key' => 'expenses',      'label' => 'Less: admissible expenses', 'type' => 'money'],
                    ['key' => 'inadmissible',  'label' => 'Add: inadmissible deductions', 'type' => 'money'],
                ],
                'computed' => ['turnover', 'inadmissible'],
                'less'     => ['cost_of_sales', 'expenses'],
            ],
            'other_sources' => [
                'label' => 'Other sources', 'line' => 'source',
                'fields' => [
                    ['key' => 'nature',   'label' => 'Nature', 'type' => 'select', 'options' => ['Profit on debt', 'Dividend', 'Royalty', 'Prize / winnings', 'Annuity', 'Rent from sub-lease', 'Others']],
                    ['key' => 'payer',    'label' => 'Payer', 'type' => 'text', 'wide' => true],
                    ['key' => 'gross',    'label' => 'Gross receipt', 'type' => 'money'],
                    ['key' => 'expenses', 'label' => 'Less: related expenses', 'type' => 'money'],
                ],
                'computed' => ['gross'],
                'less'     => ['expenses'],
            ],
            'foreign_sources' => [
                'label' => 'Foreign sources', 'line' => 'source',
                'fields' => [
                    ['key' => 'country',   'label' => 'Country', 'type' => 'text'],
                    ['key' => 'nature',    'label' => 'Nature', 'type' => 'text'],
                    ['key' => 'currency',  'label' => 'Currency', 'type' => 'text'],
                    ['key' => 'fx_amount', 'label' => 'Amount in that currency', 'type' => 'number'],
                    ['key' => 'fx_rate',   'label' => 'FX rate used', 'type' => 'number'],
                    ['key' => 'gross',     'label' => 'Gross, in PKR', 'type' => 'money'],
                    ['key' => 'foreign_tax', 'label' => 'Foreign tax paid', 'type' => 'money'],
                ],
                'computed' => ['gross'],
                'less'     => [],
            ],
            'agriculture' => [
                'label' => 'Agriculture', 'line' => 'source',
                'hint'  => 'Exempt from federal tax; still declared, and still a source on the reconciliation',
                'fields' => [
                    ['key' => 'land',     'label' => 'Land / Mauza', 'type' => 'text', 'wide' => true],
                    ['key' => 'crop',     'label' => 'Crop', 'type' => 'text'],
                    ['key' => 'gross',    'label' => 'Gross receipts', 'type' => 'money'],
                    ['key' => 'expenses', 'label' => 'Less: expenses', 'type' => 'money'],
                    ['key' => 'provincial_tax', 'label' => 'Provincial agriculture tax paid', 'type' => 'money'],
                ],
                'computed' => ['gross'],
                'less'     => ['expenses'],
            ],
        ];
    }

    /** How a line is treated, and which reconciliation code it lands on. */
    public const TREATMENTS = [
        'taxable' => ['label' => 'Subject to normal tax', 'code' => '7031'],
        'exempt'  => ['label' => 'Exempt from tax',       'code' => '7032'],
        'final'   => ['label' => 'Final / fixed tax',     'code' => '7033'],
    ];
}
