# SB118 Private Sites

WordPress must-use plugin that enforces login on private multisite sub-sites (`blog_public=0`).

## What it does

Blocks three access vectors on private sites for users **not explicitly added to that sub-site**:

1. **REST API** — returns 401 on all `/wp-json/` endpoints
2. **RSS/Atom feeds** — returns 403 (wp_die)
3. **Direct page access** — 302 redirect to `wp-login.php`

**Access requires per-site membership.** Being logged into the WordPress network is NOT enough — users must be added to each private sub-site's user list individually. Network super admins always have access. This is critical for Authentik SSO: when OIDC auto-creates WordPress accounts, those accounts won't have access to private sites unless explicitly added as members.

Allowed scripts (`wp-login.php`, `wp-cron.php`, `admin-ajax.php`, `wp-activate.php`) are not affected. Public sites (`blog_public=1`) are completely unaffected.

## Deployment

- **Server:** BigScoots VPS (server.starbase118.net)
- **Path:** `/home/starba13/public_html/wp-content/mu-plugins/sb118-private-sites.php`
- **Ownership:** Must be `starba13:starba13` (cPanel PHP-FPM requirement)
- **GitHub:** https://github.com/ufopsb118/sb118-private-sites

### Deploy updated version

```bash
scp -i ~/.ssh/id_ed25519_claude -P 2222 sb118-private-sites.php root@server.starbase118.net:/home/starba13/public_html/wp-content/mu-plugins/sb118-private-sites.php
ssh -i ~/.ssh/id_ed25519_claude root@server.starbase118.net -p 2222 "chown starba13:starba13 /home/starba13/public_html/wp-content/mu-plugins/sb118-private-sites.php"
```

## Protected sites

| Site ID | Slug | blog_public |
|---------|------|-------------|
| 4 | training-admin | 0 |
| 6 | personnel | 0 |
| 7 | training | 0 |
| 8 | ltcmdrs | 0 |
| 9 | staff | 0 |
| 10 | tech | 0 |

## History

- **2026-03-05:** v1.1.0 — Upgraded from `is_user_logged_in()` to per-site membership check (`is_user_member_of_blog()`). Prepares for Authentik SSO rollout — OIDC-provisioned WordPress accounts won't automatically get access to private sub-sites. Super admins always pass.
- **2026-02-17:** v1.0.0 — Created. Replaces `jonradio-private-site` which only blocked direct page access but left REST API and feeds wide open. Also cleared WP Super Cache for private site paths and added them to `$cache_rejected_uri` in `wp-cache-config.php`.

## Testing

Verify from outside the server (unauthenticated):

```
# Should return 401 (not JSON content)
curl -s -o /dev/null -w "%{http_code}" "https://www.starbase118.net/tech/wp-json/wp/v2/pages"

# Should redirect to login (302)
curl -s -o /dev/null -w "%{http_code}" "https://www.starbase118.net/ltcmdrs/"

# Should NOT return RSS content
curl -s -o /dev/null -w "%{http_code}" "https://www.starbase118.net/staff/feed/"

# Public sites should still return 200
curl -s -o /dev/null -w "%{http_code}" "https://www.starbase118.net/wp-json/wp/v2/posts"
```
