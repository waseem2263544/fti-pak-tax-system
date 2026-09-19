# Keeping the extension up to date

## The short answer: load it from this folder, not from a download

Chrome can load an unpacked extension straight from a directory. Point it at
this one and updating becomes `git pull` plus a click, with no download, unzip,
remove or re-add.

1. `chrome://extensions` → remove any existing **FairTax Credential Manager**
2. Turn on **Developer mode** (top right)
3. **Load unpacked** → choose
   `/Users/mac/Desktop/FTI Pak Software/chrome-extension`
4. Sign in from the toolbar popup

From then on, whenever a new version ships:

```
cd "/Users/mac/Desktop/FTI Pak Software" && git pull
```

then press the ↻ reload icon on the extension's card. That is the whole update.

The one caveat: the folder must stay where it is, since Chrome remembers the
path.

---

## The longer answer: the Chrome Web Store

Worth doing once the extension settles down, because it updates itself and
survives a new machine. It cannot be done from here — it needs a Google account
signed in to the Developer Dashboard — so what follows is everything the
submission asks for.

**One-off:** a Chrome Web Store developer account, US$5, at
https://chrome.google.com/webstore/devconsole

**Visibility: Unlisted.** Not Public — this is internal tooling with no reason
to be in search results. Not Private either: that requires a Google Workspace
domain, and Fair Tax runs on Microsoft 365. Unlisted means only people with the
link can install it.

**Package:** zip the *contents* of this folder — `manifest.json` at the top of
the archive, not inside a subfolder. `public/fairtax-extension.zip` in this repo
is already built that way.

### Listing

*Name:* FairTax Credential Manager

*Summary (132 characters max):*
> Opens FBR, KPRA and SECP portals signed in, and prepares withholding tax
> challans, for Fair Tax International staff.

*Description:*
> An internal tool for Fair Tax (Private) Limited, a tax practice in Peshawar,
> Pakistan.
>
> It signs staff in to the tax portals they work in every day, using
> credentials held in the firm's own system, and prepares FBR withholding tax
> challans from entries selected there — filling the payment period and
> attaching the deposit file, then recording the resulting PSID number against
> those entries.
>
> It is of no use without an account on the firm's system.

### Permission justifications

Reviewers ask for each one in its own field. These are accurate; do not
embellish them.

| Permission | Why |
|---|---|
| `storage` | Holds the signed-in session token and the current challan job. Nothing else is stored. |
| `activeTab`, `scripting` | Fills the login form on the tax portal tab the user is looking at. |
| `host_permissions` on `iris.fbr.gov.pk`, `*.fbr.gov.pk`, `kpra.kp.gov.pk`, `*.secp.gov.pk`, `eservices.secp.gov.pk`, `leap.secp.gov.pk` | The portals the firm files on. The extension fills forms on these and reads back the challan number FBR issues. |
| `host_permissions` on `app.fairtaxint.com` | The firm's own system, which the extension authenticates against and reads credentials from. |

*Single purpose:* Sign in to Pakistani tax portals and prepare withholding tax
challans on behalf of Fair Tax International staff.

### Data disclosures

Answer these honestly; getting them wrong is what gets an extension pulled.

- **Personally identifiable information** — yes. Client names and registration
  numbers pass through in order to fill forms.
- **Authentication information** — yes. Portal credentials are fetched from the
  firm's system and entered into portal login forms.
- **Financial information** — yes. Tax amounts appear in the challan file.
- Not sold to third parties. Not used for anything unrelated. Not used for
  creditworthiness or lending.

Everything travels between the firm's own server and the government portals the
firm files on. Nothing goes anywhere else, and the extension contains no remote
code.

### What to expect

Review usually takes a few days and can take longer for an extension with
broad host permissions that handles credentials. Expect at least one round of
questions. Until it clears, the unpacked folder above keeps working.
