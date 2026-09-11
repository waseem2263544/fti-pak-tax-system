---
name: wht-psid
description: Convert a client's payment spreadsheet into the WHT import template, check withholding tax deposit status across agents, and pull FBR PSID upload files in bulk. Use when the user hands over a client payment sheet to prepare for import, asks which agents still owe a WHT deposit, or wants PSID upload files for several agents at once — e.g. "convert this sheet for the WHT import", "turn this client Excel into withholding entries", "which agents haven't deposited June?", "get me the PSID files for all agents for June".
---

# WHT deposit status and PSID files

The app does the real work. **WHT → Prepare PSID** in the software handles the
whole cycle for one agent and month: download the upload file, paste in the PSID
that IRIS returned, record the CPR once paid. Every entry in the batch is
stamped at once.

This skill exists only for what that screen is bad at — looking across **all
agents at once**, and pulling **several files in one go**.

If the user is working on a single agent for a single month, say so and point
them at Prepare PSID rather than doing it here. It is faster for them and it
keeps the app as the record.

## Converting a client's payment sheet

The most common request. A client sends a spreadsheet in whatever shape they
like; the app's **WHT → Import Payments** screen needs it in a known shape.

**Never calculate the tax yourself.** The app recomputes every figure from the
rate matrix on import — that is deliberate, because the rate depends on the
payee's category and ATL status and on the tax period, none of which a
spreadsheet reliably knows. If the client's sheet has tax figures, keep them in
the `tax_withheld` column: the app compares rather than trusts, and reports any
disagreement. That is how a client's arithmetic errors get caught.

Target shape — header row exactly these names:

```
payee_name, payee_cnic_ntn, payment_date, period_month, section, amount, amount_basis, tax_withheld, remarks
```

- `payment_date` — `d/m/Y`
- `period_month` — `YYYY-MM`; leave blank to use the payment month
- `section` — leave blank to use the payee's default section
- `amount_basis` — `gross` or `net`; blank uses the payee's usual mode
- `tax_withheld` — only if the client's sheet states it; blank otherwise
- Anything else the client sent can stay in `remarks`

The importer already matches many column names by alias (`supplier`, `NIC`,
`invoice amount`, …), so a tidy client sheet often imports unchanged. Convert
only when it will not, or when the sheet needs real reshaping — several sheets
in one file, merged header rows, totals mixed into the data, one column per
month.

Write the result as `.csv` or `.xlsx` and send it with `SendUserFile`. Then tell
the user to upload it at **WHT → Import Payments**, where they get a preview
before anything is written.

Flag, do not fix:
- payees that look new — they must be added first, with the correct category and
  ATL status, because both change the rate
- rows with no amount or no usable date
- anything ambiguous about which section applies

## Setup

```bash
TOKEN=$(grep -E '^WHT_API_TOKEN=' .env | cut -d= -f2-)
BASE=$(grep -E '^WHT_API_BASE_URL=' .env | cut -d= -f2- || echo https://app.fairtaxint.com)
```

If `WHT_API_TOKEN` is empty in `.env`, or the API returns 401, stop and tell the
user the token needs setting to the same value here and in the server's `.env`.
Do not try to work around it.

## Deposit status across every agent

```bash
curl -sS -H "X-Wht-Token: $TOKEN" "$BASE/api/wht/status?month=2026-06"
```

Each agent returns two batches, `purchases` and `salaries`, each with a `status`:

| status | meaning | what the user should do |
|---|---|---|
| `empty` | nothing recorded that month | check entries were actually keyed in |
| `pending` | recorded, no PSID yet | generate the file and upload to IRIS |
| `partial` | some entries carry a PSID, some do not | investigate — usually a half-finished assignment |
| `psid` | PSID assigned, not yet paid | pay the challan, then record the CPR |
| `paid` | CPR recorded against every entry | done |

Report this as a compact table, grouped so what needs action is obvious. Lead
with `pending` and `psid` — those are the ones with a deadline. Mention `partial`
explicitly; it usually means something went wrong.

**Deadline context worth surfacing:** withholding tax is due within 7 days of the
end of each fortnight. If the user is asking near or past a due date, say which
agents are exposed.

## Pulling the upload files

```bash
curl -sS -H "X-Wht-Token: $TOKEN" \
  "$BASE/api/wht/psid/file?agent=Universal%20Dairies&month=2026-06&kind=purchases" \
  -o "$SCRATCH/PSID-universal-dairies-vendors-2026-06.xlsx"
```

`kind` is `purchases` (vendors — all sections under one PSID) or `salaries`.
`agent` takes an id or a partial name.

This returns the exact same workbook the Prepare PSID screen produces — same
service, so there is no chance of a discrepancy. Never rebuild the file yourself.

Verify each download is a real workbook before handing it over:

```bash
file "$SCRATCH/..."   # expect: Microsoft Excel 2007+
```

A JSON body instead means an error — read it and report it rather than sending a
broken file.

Send files with `SendUserFile`, `display: "attach"`, and state the tax total per
file so figures can be eyeballed against IRIS.

## Raw rows

`GET /api/wht/psid?agent=&month=` returns the underlying rows as JSON. Use it for
questions like "what did we withhold from Acme this year" — not for building
spreadsheets.

## Boundaries

- **The API is read-only.** Assigning a PSID or recording a CPR is a deliberate
  human action in Prepare PSID. Never ask the user for a PSID number so you can
  record it — send them to the screen.
- **Never recalculate tax.** The app is the source of truth.
- **Never rebuild the upload file.** If its columns are wrong, the fix is the
  *Upload file column layout* editor on the Prepare PSID screen, which an admin
  can change with no deploy. Tell them that; don't work around it.
- Client tax data stays in the scratchpad or goes to the user. Nowhere else.
- Never commit `.xlsx` output or the token.

## If IRIS rejects an upload

The generated file matches FBR's ePayments Import Template as downloaded from
IRIS: ten columns, header on row 1, data from row 2, sheet named Sheet1. IRIS
reads the grid literally — a title block or totals row breaks it.

If FBR revises the template, the fix is the *Upload file column layout* editor on
the Prepare PSID screen, which an admin changes with no deploy. Tell the user
that; do not rebuild the file yourself.
