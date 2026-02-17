# MPA

Planning docs:

- `docs/wordpress-2026-theme-woocommerce-plan.md` — confirmed requirements, architecture, phased execution plan, localization requirements, and base-URL language routing behavior.
- `docs/mpa-book-sync-payload-spec.md` — app-to-WordPress payload contract for book/product synchronization, including language/locale and genre metadata.

Implementation scaffolds:

- `wordpress/wp-content/plugins/mpa-book-sync/` — WordPress plugin scaffold for secure app sync and WooCommerce variable product upserts.
- `wordpress/wp-content/themes/mpa-books-2026/` — modernized theme scaffold preserving series → books structure with preview/buy actions, language filtering, genre landing pages, and preferred-language base URL routing.


Build/upload theme ZIP:

- macOS/Linux: `./scripts/build-theme-zip.sh`
- Windows PowerShell: `./scripts/build-theme-zip.ps1`

Windows GitHub CLI quick start (no angle-bracket placeholders):

1. Authenticate: `gh auth login --hostname github.com --git-protocol https --web --scopes repo`
2. Find your repo name: `gh repo list --limit 50`
3. Clone your repo with the exact name from the list, e.g. `gh repo clone McAndersPublishing/MPA` (if already cloned, skip clone and `cd MPA`)
4. Enter the cloned folder (for this repo: `cd MPA`) and run `git status`

New to all this? Use the full beginner walkthrough: `docs/first-time-github-powershell-guide.md`.


If the PowerShell ZIP script is missing on your cloned branch, use the fallback in `docs/first-time-github-powershell-guide.md`.

Important: if `wordpress/wp-content/themes/mpa-books-2026` is missing in your clone, that GitHub branch does not yet contain the theme source, so ZIP build commands will fail until those files are pushed to GitHub.

Need a clear handoff explanation? See: `docs/first-time-github-powershell-guide.md` section **"How the ZIP file leaves this assistant environment"**.

Known repo names from this project: `McAndersPublishing/MPA`, `McAndersPublishing/MPA-Sales_Theme`, `McAndersPublishing/BookishB-Backend`, `McAndersPublishing/BookishB`.

If `Get-ChildItem .\wordpress\wp-content\themes` fails, run `git branch -a` and `git ls-tree --name-only -r HEAD` to confirm whether the theme source exists on your current branch.
If you only see `README.md` and ZIP files in `git ls-tree --name-only -r HEAD`, the theme source is not yet on your GitHub branch; it must be pushed from the Linux workspace that contains it.


## No-Linux transfer fallback
If you cannot access Linux/container files directly, you can reconstruct the theme ZIP from a Base64 file in this repo:

```powershell
cd $HOME\MPA
.\scripts\restore-theme-zip-from-base64.ps1
```

This writes `dist\mpa-books-2026-theme.zip` locally on Windows.


If PowerShell says `.\scripts\restore-theme-zip-from-base64.ps1` is not recognized, your current branch does not contain the fallback files yet. Run:
`git branch -a` and `git ls-tree --name-only -r HEAD`
and confirm both `scripts/restore-theme-zip-from-base64.ps1` and `dist/mpa-books-2026-theme.zip.b64` exist before trying that command.


The generated `dist\mpa-books-2026-theme.zip` is intentionally not committed to Git so web-based PR flows that reject binary diffs still work. Build it locally with one of the scripts before uploading in WordPress.
