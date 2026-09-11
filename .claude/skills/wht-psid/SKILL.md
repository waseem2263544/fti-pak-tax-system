---
name: wht-psid
description: Build the FBR PSID / withholding bulk-upload Excel file for a withholding agent and tax period, and store it in SharePoint so it opens in Excel Online. Use when the user asks to prepare a PSID, generate the WHT upload file, produce the withholding challan sheet, or file the monthly withholding statement — e.g. "prepare PSID for Universal Dairies June 2026", "WHT upload file for last month", "put the withholding data in Excel online".
---

# WHT PSID upload file

Produces the Excel file that gets uploaded to FBR IRIS to raise a PSID, and files
a copy in SharePoint so it is editable in Excel Online.

Two things are deliberately separated:

- **Where the data comes from** — the app's read-only API (`/api/wht/psid`).
- **What the file looks like** — `references/psid-columns.json`.

FBR changes its template from time to time. When that happens, edit only
`references/psid-columns.json`. Never hardcode a column layout anywhere else.

## Before the first run

Check these once; if anything is missing, tell the user exactly what to do and stop.

1. `WHT_API_TOKEN` is set in the project `.env` **and** in the server's `.env` (same value).
2. `/api/wht/psid` responds — the endpoint must be deployed.

## Step 1 — Establish agent and period

You need a withholding agent and a tax month (`YYYY-MM`).

- "last month", "June", "this month" → resolve against today's date and **say which month you resolved to**.
- Partial agent names are fine; the API matches on `LIKE`.
- If the user named neither, list the agents and ask. Do not guess.

```bash
TOKEN=$(grep -E '^WHT_API_TOKEN=' .env | cut -d= -f2-)
BASE=$(grep -E '^WHT_API_BASE_URL=' .env | cut -d= -f2- || echo https://app.fairtaxint.com)

curl -sS -H "X-Wht-Token: $TOKEN" "$BASE/api/wht/agents"
```

## Step 2 — Fetch the period data

```bash
curl -sS -H "X-Wht-Token: $TOKEN" \
  "$BASE/api/wht/psid?agent=Universal%20Dairies&month=2026-06" \
  -o "$SCRATCH/wht-data.json"
```

Rows are selected by **tax period**, not payment date — a June liability paid in
August still belongs to June. Do not "fix" this.

Read back `totals` and check it against the app's Monthly Statement page before
building anything. If `sections` is empty, say so and stop — do not produce an
empty workbook.

**If the API is unreachable** (not yet deployed, no token), fall back: ask the user to
download **WHT → Monthly Statement → Excel** and give you the file. Convert its
detail sheets into the same JSON shape (`agent`, `period`, `sections[].rows[]`).
Say you are using the fallback.

## Step 3 — Build the workbook

```bash
php .claude/skills/wht-psid/scripts/build_psid_workbook.php \
  "$SCRATCH/wht-data.json" \
  "$SCRATCH/PSID-Universal-Dairies-2026-06.xlsx"
```

The script prints JSON: `output`, `bytes`, `sheets`, `rows`. Confirm `rows`
matches `totals.rows` from Step 2.

One sheet per section, because IRIS takes one upload per payment section. The
sheet tab is the section label with `/ \ ? * [ ] :` replaced by `-` (Excel
forbids them), truncated to 31 characters.

Naming: `PSID-<agent-slug>-<YYYY-MM>.xlsx`.

## Step 4 — Hand the file to the user

Send it with `SendUserFile`, `display: "attach"`. Say which sections are in it
and the total tax per section, so the figures can be eyeballed before upload.

## Step 5 — Store it in Excel Online

Put it in the agent's SharePoint folder so it opens in Excel for the web.

```
mcp__claude_ai_Microsoft_365__sharepoint_folder_search   → find the agent's folder
mcp__claude_ai_Microsoft_365__sharepoint_upload_file     → upload
```

Client folders live under `Operations/3. Clients` in the `FairTaxInternational723`
site. Use a `WHT` subfolder inside the agent's folder; create it with
`sharepoint_create_folder` if absent.

`.xlsx` is binary, so it must go as `contentBase64` — strict, unbroken base64:

```bash
base64 -i "$SCRATCH/PSID-....xlsx" | tr -d '\n'
```

Pass `expectedBytes` using the byte count the build script reported, and
`conflictBehavior: "replace"` when regenerating a month that already exists
(say so first — it overwrites).

**Upload cap is 1 MB.** If the file is larger, split by section and upload one
file per section rather than truncating.

Give the user the returned `webUrl`. That link opens the file in Excel Online,
where several people can edit it at once.

## Step 6 — Append to the register

Keep a cumulative workbook per agent per tax year: `WHT-Register-<agent>-TY<year>.xlsx`,
one sheet per month. Tax year runs July–June and is named for the year it ends in,
so July 2025 – June 2026 is **TY2026**.

Find it with `sharepoint_search`; read it with `read_resource`; add the month's
sheet; re-upload with `conflictBehavior: "replace"`.

If the register would exceed the 1 MB cap, start a new file for the next tax
year rather than dropping rows, and tell the user.

## The one thing to get right

**The column layout in `references/psid-columns.json` is a best guess, not a
verified FBR template.** A wrong layout means IRIS rejects the upload.

If the user has a real FBR template file, read its header row and rewrite
`references/psid-columns.json` to match exactly — same header text, same order —
then rebuild. Once a run is confirmed accepted by IRIS, note that in the JSON's
`_comment` so nobody second-guesses it later.

Ask for a real template on the first run if the file still says it is unverified.

## Do not

- Recalculate tax. The app is the source of truth; this skill only reshapes.
- Write anything back to the app — the API is read-only by design.
- Commit `.xlsx` output or the token to git.
- Put client tax data anywhere other than SharePoint or the scratchpad.
