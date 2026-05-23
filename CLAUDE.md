# WP-Inspector — Claude Instructions

## Project Context
This is a WordPress plugin connected to GitHub and WP Pusher for deployment.

## Rules

- Do not rewrite the whole plugin.
- Do not inspect the whole project unless needed.
- Work file-by-file to reduce tokens.
- Only edit files needed for the issue.
- Ask before making major changes.
- Keep existing features working.
- After code changes, update the version number in `WP-Inspector.php`.
- After finishing, tell the user exactly which files changed and why.
