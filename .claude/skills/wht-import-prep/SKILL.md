---
name: wht-import-prep
description: Convert a client's payment or salary spreadsheet into the FTI Pak withholding-tax import template so it can be uploaded to the WHT module. Use whenever the user shares an Excel or CSV of payments to vendors, suppliers, contractors or employees and wants it prepared for the withholding software, the WHT import, or "the tax app" — e.g. "prepare this for the WHT import", "arrange this sheet for the software", "turn this client Excel into withholding entries".
---

# Prepare a client sheet for the WHT import

The user runs a tax practice. Clients send payment records in whatever shape
they like. The withholding software imports a fixed shape. Your job is the
translation, and nothing else.

## Output template

One file. Header row exactly these nine names, in this order:

```
payee_name,payee_cnic_ntn,payment_date,period_month,section,amount,amount_basis,tax_withheld,remarks
```

| Column | Required | Format | Notes |
|---|---|---|---|
| `payee_name` | yes* | text | The vendor, supplier, contractor or employee paid |
| `payee_cnic_ntn` | yes* | digits only | Strip dashes and spaces. Keep leading zeros |
| `payment_date` | **yes** | `d/m/Y` | e.g. `15/06/2026` |
| `period_month` | no | `YYYY-MM` | Leave blank unless the sheet states a tax period separate from the payment date |
| `section` | no | text | Only if the sheet says so. **Never guess** |
| `amount` | **yes** | plain number | No commas, no currency symbols |
| `amount_basis` | no | `gross` or `net` | Only if the sheet makes it clear |
| `tax_withheld` | no | plain number | Only if the sheet states it. Never compute it |
| `remarks` | no | text | Invoice numbers, cost centres, anything else worth keeping |

\* At least one of `payee_name` or `payee_cnic_ntn` must be present on every
row. Both is much better — the software matches on CNIC/NTN first.

## Rules you must not break

**Never calculate tax.** The rate depends on the payee's category
(company / individual / AOP), their filer status on the ATL, and the tax period
— none of which a client spreadsheet reliably knows. The software recomputes
every figure from its own rate matrix on import. If you compute anything, you
produce numbers that look authoritative and are wrong.

If the sheet already states tax, copy it into `tax_withheld` unchanged. The
software compares its own calculation against it and reports any difference.
That is how the client's arithmetic errors get caught, so preserving their
figure is genuinely useful — just never invent or "correct" one.

**Never guess a section.** If the sheet gives one — a section like
`153(1)(a)/9` or an FBR payment code like `64060009` — pass it through exactly
as written; both are understood. If it does not, leave `section` blank: the
software falls back to the payee's configured default.

Where a payee has no default, that row is blocked at the preview with "no
section given" and the user sets it. That is the correct outcome — a blocked
row they can fix beats a guessed section that silently applies the wrong rate.
Mention it in your reply when you leave sections blank.

**Never invent a CNIC/NTN.** Blank is fine; wrong is not.

**Never drop a row silently.** If a row cannot be converted, leave it out of the
file and list it explicitly in your reply.

## Reading the workbook

Client sheets are rarely tidy. Check for all of these before converting:

- **Several sheets in one workbook** — often one tab per month or per vendor.
  Ask which to use, or combine them and say that you did.
- **Title and logo rows above the real header** — find the row that actually
  names columns.
- **Merged or two-line headers** — e.g. "Payment" spanning "Gross" and "Tax".
  Flatten them.
- **Totals and subtotals mixed into the data** — drop them. A row whose payee
  cell reads "Total", "Sub-total", "Grand Total" or is blank while amounts are
  filled is a total, not a payment.
- **One column per month** — a vendor per row with twelve amount columns needs
  unpivoting into one row per payment.
- **Running balances** — the amount column may be cumulative rather than
  per-payment. If consecutive values only ever increase, say so and ask.
- **Blank padding rows** between sections.

## Normalising values

**Dates.** Pakistani sheets are overwhelmingly day-first. Read `05/06/2026` as
5 June, not 6 May. Excel serial numbers (like `46177`) are dates too. If a date
is genuinely ambiguous and you cannot tell from the surrounding rows, leave the
row out and flag it rather than guessing.

**Amounts.** Strip thousands separators, currency words and symbols: `1,000,000`
→ `1000000`, `Rs. 250,000/-` → `250000`. A value in brackets like `(5,000)` is
negative — flag it, don't import it.

**Names.** Keep the client's spelling. Do not expand abbreviations or "correct"
them — the software matches against its own party list and your tidy-up may stop
a match. Trim whitespace only.

**CNIC/NTN.** Digits only. `17301-1234567-8` → `1730112345678`.

## Output

Write a **CSV** — it imports most reliably. UTF-8, one header row, no blank rows,
no totals row, no formatting.

Name it `wht-import-<client>-<YYYY-MM>.csv` where you can tell; otherwise
`wht-import.csv`.

Attach the file to your reply.

## What to say back

Keep it short and factual:

- how many payment rows you produced
- **which rows you left out, and why** — this matters most
- any payee names that look inconsistent within the sheet (e.g. "Acme Traders"
  and "Acme Trader's" both appearing) — the software will treat them as
  different parties
- anything you had to assume

Then tell the user: upload it in the software at **WHT → Import Payments**,
where they get a preview before anything is written. Any payee not already in
the system will be blocked there — they add it with the correct category and
ATL status, then re-upload.

Do not tell them the totals are correct. You have not calculated tax, and the
software's figures are the ones that count.
