# Login Protection and WAF Deployment

## Application Protections

- Customer login locks an account/IP pair for 5 minutes after 5 failed credentials within a 15-minute window.
- Admin login allows 10 failed attempts per account/IP pair in a 15-minute window. The 10th failure starts a 15-minute lockout and returns HTTP 429 with a `Retry-After` header.
- Failed-attempt records store a SHA-256 key, not the submitted username or IP address.
- Passwords created through registration are hashed. Existing plaintext passwords are upgraded to password hashes after a successful login.
- Admin pages and admin-only APIs require a server-side PHP session. The browser no longer authenticates an admin with a local-storage flag or a hardcoded password.
- New installations create an initial admin only when `INITIAL_ADMIN_PASSWORD` is set to a strong value of at least 12 characters. Optionally set `INITIAL_ADMIN_EMAIL`. Existing accounts are not reset automatically.

Set these environment variables in the hosting control panel or PHP-FPM/Apache environment before initializing a new database:

```text
INITIAL_ADMIN_PASSWORD=<unique password of at least 12 characters>
INITIAL_ADMIN_EMAIL=<admin mailbox>
```

Do not put the real password in source code, SQL files, or a public `.env` file. When `INITIAL_ADMIN_PASSWORD` is configured, the initializer replaces only the known legacy sample admin password with its hash. Other existing admin passwords are left unchanged and should be rotated before production if they are weak or shared.

For every installation, import `auth_login_attempts.sql` once using the hosting database tool. The public installer is intentionally blocked by `.htaccess`. The web database account needs SELECT, INSERT, UPDATE, DELETE, and CREATE permissions for the login-attempt table.

## Cloudflare

1. Proxy the website DNS record through Cloudflare and set SSL/TLS encryption to **Full (strict)**. Enable an HTTPS redirect.
2. Enable Cloudflare's managed WAF ruleset. Review events in Security > Events and tune false positives without allowing login abuse.
3. Create a rate-limiting rule for `POST` requests to the deployed paths for `/api_login.php` and `/api_admin_login.php`. A reasonable edge starting point is 10 requests per source IP in 5 minutes with a 15-minute block. The PHP lockout separately counts failed credentials per account/IP pair.
4. Restrict direct access to the origin to Cloudflare's published IP ranges. Only after that restriction is active, set `TRUST_CLOUDFLARE_CONNECTING_IP=1` in the server environment. The PHP limiter will then use Cloudflare's `CF-Connecting-IP`; otherwise it uses the connection's remote IP.
5. Test both successful sign-in and lockout through the public hostname. Confirm the origin cannot be reached directly.

Cloudflare features and rule limits depend on the account plan. The expression must use the site's actual deployed paths if the project is hosted in a subdirectory.

## AWS WAF

1. Associate a regional Web ACL with the public Application Load Balancer/API Gateway, or a CloudFront-scope Web ACL with the CloudFront distribution serving the site.
2. Add AWS Managed Rules groups such as `AWSManagedRulesCommonRuleSet`, `AWSManagedRulesKnownBadInputsRuleSet`, and `AWSManagedRulesAmazonIpReputationList`. Review sampled requests before tightening any rule that blocks legitimate traffic.
3. Add a rate-based rule scoped to `POST` requests whose URI path matches `/api_login.php` or `/api_admin_login.php`. Start at 10 requests per source IP over a 5-minute evaluation window and use the Block action. AWS WAF blocks while the request rate remains over the configured threshold; its rate rule is not a fixed 15-minute timer. The PHP lockout provides the fixed 15-minute credential-failure lockout.
4. If Cloudflare sits in front of AWS WAF, configure the rate rule's forwarded-IP configuration to use `CF-Connecting-IP`, and restrict origin access to Cloudflare. Without this, AWS may count Cloudflare proxy addresses and rate-limit unrelated visitors together.
5. Enable WAF logging and alarms for blocked login traffic, then validate the public sign-in flow and lockout behavior.

AWS WAF rules must be deployed to the resource that actually receives public traffic. Do not create a Web ACL without associating it to that resource.

## Verification

- A visitor without an admin session receives HTTP 401 from admin-only endpoints.
- After 5 failed customer login attempts for one account/IP pair within 15 minutes, the account is locked for 5 minutes.
- After 10 failed admin attempts for one account/IP pair, the next login request is locked for 15 minutes and returns HTTP 429.
- A valid admin login creates a server-side session; logout destroys it.
- Customer product listing and customer login remain available.

Cloudflare and AWS WAF configuration cannot be activated from this PHP/XAMPP workspace; it must be applied in the hosting provider's dashboard or infrastructure configuration.