# First-time setup guide (Windows + GitHub + PowerShell)

This guide assumes you are brand new to GitHub and PowerShell.

## Goal
Get a theme ZIP file you can upload in WordPress:

- `dist/mpa-books-2026-theme.zip`

---

## Important reality (so this makes sense)

- Your PowerShell runs on **your Windows PC**.
- This assistant runs in a separate **Linux container**.
- You **cannot** browse/download files directly from the container filesystem.

So the only practical way to get files is:
1. Put them in GitHub (repo/branch/release), then clone/download on Windows, **or**
2. Build the ZIP locally on your PC **after** the theme files exist in your local clone.

If your clone does not contain `wordpress/wp-content/themes/mpa-books-2026`, ZIP commands will fail every time.

---

## How the ZIP file leaves this assistant environment (plain English)

This is the key point that caused confusion:

- I can create files inside the assistant's Linux container.
- You cannot directly open that container from your Windows PC.
- Therefore, the file must be moved through a shared place you can access.

In this project, that shared place is **GitHub**. The transfer path is:

1. File is created in the container (`dist/mpa-books-2026-theme.zip`).
2. File is committed and pushed to a GitHub branch/release by someone with git push access from that environment.
3. You download it from GitHub (website) or clone/pull it on Windows.

If step 2 never happens, the ZIP stays only in the container and you will never see it on your PC.

Alternative shared location (if GitHub push is unavailable):
- Upload the ZIP to any location you can access (for example: cloud drive, hosting file manager, or a release artifact system), then download from there.

---

## Exactly how files move from this Linux container to your GitHub

You asked the key question: "How does the file leave your Linux and get to GitHub?"

It only happens if someone runs git push from the environment that has the files.

### What your latest output proves
Your `git ls-tree` output shows only:
- `README.md`
- `pba-theme.zip`
- `pub-assistant-new woking.zip`

So your GitHub `main` branch currently does **not** include the WordPress theme source or build scripts.

### The only transfer mechanism (container -> GitHub)
A person/session that has the files in Linux must run:

```bash
# in the Linux workspace that contains the theme files
cd /workspace/MPA
git remote -v
git remote add origin https://github.com/McAndersPublishing/MPA.git   # only if origin is missing
git fetch origin
git checkout -B work
# (files already present in this workspace)
git add .
git commit -m "Publish theme scaffold and build scripts"             # only if there are uncommitted changes
git push -u origin work
```

After that, you can access the files on GitHub by either:
1. opening the `work` branch in the website, or
2. running locally:

```powershell
cd $HOME\MPA
git fetch --all
git checkout -b work origin/work
```

Then `Get-ChildItem .\wordpress\wp-content\themes` should show `mpa-books-2026`.

### If push credentials are not available in Linux
Then the Linux session cannot publish to GitHub directly. In that case, the files must be exported by another shared channel (release artifact, cloud drive, etc.) and uploaded by someone who has GitHub push access.

---

## Step 1) Open PowerShell in your user folder

1. Press **Start**.
2. Type **PowerShell**.
3. Open **Windows PowerShell**.
4. Run:

```powershell
cd $HOME
```

You should now be in a path like `C:\Users\your-name`.

---

## Step 2) Sign in to GitHub CLI (`gh`)

Run:

```powershell
gh auth login --hostname github.com --git-protocol https --web --scopes repo
```

When asked:
- **Authenticate Git with your GitHub credentials?** → type `Y` then Enter.

A browser window opens.
- Approve GitHub access.
- Return to PowerShell.

Verify login:

```powershell
gh auth status
```

---

## Quick start (single copy/paste block)

If you prefer one block, run this exactly:

```powershell
cd $HOME
gh auth status
if (-not (Test-Path .\MPA)) { gh repo clone McAndersPublishing/MPA }
cd MPA
git pull --ff-only
git status
Get-ChildItem .\wordpress\wp-content\themes
```

If the last command does not show `mpa-books-2026`, stop and report that output.

If the last command errors with "Cannot find path", run:

```powershell
git branch -a
git ls-tree --name-only -r HEAD
```

If you only see `main`/`origin/main` and no `wordpress/wp-content/themes/mpa-books-2026`, stop there — the theme source is not in your current GitHub branch yet.

---

## Step 3) Find your repository name

Run:

```powershell
gh repo list --limit 50
```

Look for your repo in the list. It will look like:
- `your-username/your-repo-name`

> Important: do **not** type angle brackets like `<owner>/<repo>`.

---

## Step 3.5) Your known repository names (copy/paste ready)

From your account history in this project, these are your repo names:

- `McAndersPublishing/MPA`
- `McAndersPublishing/MPA-Sales_Theme`
- `McAndersPublishing/BookishB-Backend`
- `McAndersPublishing/BookishB`

You can copy/paste these exact commands:

```powershell
# Main repo used in this project
gh repo clone McAndersPublishing/MPA
cd MPA
```

If you want to check the other repo too:

```powershell
cd $HOME
gh repo clone McAndersPublishing/MPA-Sales_Theme
cd MPA-Sales_Theme
```

---

## Step 4) Clone your repository

Use this exact command for the main project repo:

```powershell
gh repo clone McAndersPublishing/MPA
cd MPA
```

Now you are inside a real git repository.

Check:

```powershell
git status
```

Now confirm the source files exist before trying to build:

```powershell
Get-ChildItem .\wordpress\wp-content\themes
```

You must see `mpa-books-2026` in the output.

If you **do not** see `mpa-books-2026`, stop here: this branch/repo does not yet contain the theme files, so no ZIP can be created from this clone.

---

## Step 5) Build the WordPress theme ZIP (PowerShell method)

Run:

```powershell
.\scripts\build-theme-zip.ps1
```

Verify the ZIP exists:

```powershell
Get-Item .\dist\mpa-books-2026-theme.zip
```

---

> Note: `dist\mpa-books-2026-theme.zip` is a build artifact and may not be committed in Git history. Always generate it locally in Step 5 before upload.

## Step 6) Upload ZIP in WordPress Admin

1. In WordPress Admin go to:
   - **Appearance → Themes → Add New → Upload Theme**
2. Select:
   - `dist\mpa-books-2026-theme.zip`
3. Click **Install Now**.
4. Click **Activate**.

---

## Common errors and fixes

### Error: `Get-ChildItem ... themescd $HOME\MPA`
This means two commands were accidentally pasted together on one line.

Run these as **separate lines**:
```powershell
Get-ChildItem .\wordpress\wp-content\themes
cd $HOME\MPA
```

### Error: `fatal: destination path 'MPA' already exists and is not an empty directory`
This is normal if you already cloned the repo earlier.

Fix (copy/paste):
```powershell
cd $HOME\MPA
git pull --ff-only
git status
```

Then continue with:
```powershell
Get-ChildItem .\wordpress\wp-content\themes
```

### Error: `fatal: not a git repository`
You are not in the cloned repo folder.

Fix:
```powershell
cd $HOME
cd MPA
```

### Error: `The '<' operator is reserved for future use`
You copied placeholder text like `<owner>/<repo>` literally.

Fix:
- Use your real value, e.g. `McAndersPublishing/MPA`.

### Message during login: `failed to authenticate via web browser ... 401 Unauthorized`
If `gh auth status` says you are logged in, you can continue.

Check:
```powershell
gh auth status
```

Then continue to clone/pull the repo and check for `wordpress\wp-content\themes\mpa-books-2026`.

### Error: `.\scripts\build-theme-zip.ps1 is not recognized`
This means the script is not in the branch you cloned yet.

First check if it exists:
```powershell
Get-ChildItem .\scripts
```

If `build-theme-zip.ps1` is missing, create the ZIP with this fallback command:
```powershell
New-Item -ItemType Directory -Path .\dist -Force | Out-Null
Compress-Archive -Path ".\wordpress\wp-content\themes\mpa-books-2026\*" -DestinationPath ".\dist\mpa-books-2026-theme.zip" -Force
```

Then verify:
```powershell
Get-Item .\dist\mpa-books-2026-theme.zip
```


### Error: `.\scripts\restore-theme-zip-from-base64.ps1 is not recognized`
This means your local clone does not contain the fallback script yet.

Check what is actually in your current branch:
```powershell
git branch -a
git ls-tree --name-only -r HEAD
```

If `scripts/restore-theme-zip-from-base64.ps1` and `dist/mpa-books-2026-theme.zip.b64` are not listed, there is nothing to run locally yet; your clone simply does not have those files.

If both files are listed, run:
```powershell
.\scripts\restore-theme-zip-from-base64.ps1
Get-Item .\dist\mpa-books-2026-theme.zip
```

### Error: `Get-Item .\dist\mpa-books-2026-theme.zip` cannot find path
This means the ZIP has not been generated in your clone yet.

Use one of these generation paths:
1. If theme source exists (`wordpress\wp-content\themes\mpa-books-2026`), run:
```powershell
New-Item -ItemType Directory -Path .\dist -Force | Out-Null
Compress-Archive -Path ".\wordpress\wp-content\themes\mpa-books-2026\*" -DestinationPath ".\dist\mpa-books-2026-theme.zip" -Force
```
2. If fallback files exist (`scripts/restore-theme-zip-from-base64.ps1` + `dist/mpa-books-2026-theme.zip.b64`), run:
```powershell
.\scripts\restore-theme-zip-from-base64.ps1
```

Then verify:
```powershell
Get-Item .\dist\mpa-books-2026-theme.zip
```

### Error: `gh is not recognized`
Use full path once:

```powershell
& "$Env:ProgramFiles\GitHub CLI\gh.exe" --version
```

If that works, run auth using full path too:

```powershell
& "$Env:ProgramFiles\GitHub CLI\gh.exe" auth login --hostname github.com --git-protocol https --web --scopes repo
```

---

## What to send me if something fails

Copy and paste:
1. The exact command you ran.
2. The full error output.
3. Output of:

```powershell
pwd
git status
gh auth status
```

### Error: `Cannot find path .\wordpress\wp-content\themes`
Your local clone does not contain the theme source yet.

For your exact current state (`main`, up to date, folder missing), run:
```powershell
cd $HOME\MPA
git branch -a
git pull --ff-only
git ls-tree --name-only -r HEAD
```

Interpretation:
- If `git branch -a` shows only `main` and `origin/main`, and
- `git ls-tree` output does **not** include `wordpress/wp-content/themes/mpa-books-2026`,
then the theme files are not in GitHub on the branch you cloned yet.

At that point, ZIP build commands cannot work from your PC until a branch/repo containing those files is pushed to GitHub.

