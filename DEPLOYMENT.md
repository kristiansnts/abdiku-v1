# Deployment Guide - Coolify with Cloudflare Tunnel

## Your Setup

| Service | Domain |
|---------|--------|
| Cloudflare Tunnel | `deskbranch.site` |
| Coolify Dashboard | `dashboard.deskbranch.site` |
| Laravel App (Abdiku) | `abdiku.dev` |

## Prerequisites

- Coolify running at `dashboard.deskbranch.site`
- Cloudflare Tunnel already configured
- `abdiku.dev` domain added to Cloudflare

---

## 1. Add abdiku.dev to Cloudflare (If Not Already)

1. Go to [Cloudflare Dashboard](https://dash.cloudflare.com)
2. Click **Add a Site**
3. Enter `abdiku.dev`
4. Select plan (Free works)
5. Update nameservers at your domain registrar to Cloudflare's nameservers

---

## 2. Add Public Hostname to Existing Tunnel

Since your tunnel is already running for `deskbranch.site`, you just need to add a new hostname.

1. Go to [Cloudflare Zero Trust](https://one.dash.cloudflare.com)
2. Navigate to **Networks** > **Tunnels**
3. Click on your existing tunnel (the one serving `dashboard.deskbranch.site`)
4. Go to **Public Hostname** tab
5. Click **Add a public hostname**

| Field | Value |
|-------|-------|
| Subdomain | *(leave empty for root domain)* |
| Domain | `abdiku.dev` |
| Type | `HTTP` |
| URL | `host.docker.internal:8000` or `<container-name>:8000` |

> **Important**: The URL depends on how Coolify exposes your container. Check your Coolify network settings.

### Alternative URL Options

| Scenario | URL Value |
|----------|-----------|
| Container on same network | `abdiku-container:8000` |
| Using Docker host | `host.docker.internal:8000` |
| Direct localhost | `localhost:8000` |

---

## 3. Tunnel Additional Settings

In the public hostname settings for `abdiku.dev`:

| Setting | Value |
|---------|-------|
| **HTTP Settings** > HTTP Host Header | `abdiku.dev` |
| **TLS** > No TLS Verify | Enable (if not using internal SSL) |

---

## 4. Cloudflare SSL/TLS Settings for abdiku.dev

1. Go to [Cloudflare Dashboard](https://dash.cloudflare.com)
2. Select **abdiku.dev**
3. Go to **SSL/TLS** > **Overview**
4. Set encryption mode to **Full**

### Edge Certificates

1. Go to **SSL/TLS** > **Edge Certificates**
2. Enable **Always Use HTTPS**
3. Enable **Automatic HTTPS Rewrites**
4. Set **Minimum TLS Version** to `TLS 1.2`

> With Cloudflare Tunnel, SSL is handled automatically - no Let's Encrypt needed in Coolify.

---

## 5. Coolify Project Setup

### Create New Project

1. Open Coolify at `https://dashboard.deskbranch.site`
2. Click **+ Add New Resource**
3. Select **Docker** > **Dockerfile**

### Configure Source

Connect your Git repository or use public repository URL.

### Build Configuration

| Setting | Value |
|---------|-------|
| Dockerfile Location | `Dockerfile` |
| Build Path | `/` |

### Environment Variables

```env
APP_NAME=Abdiku
APP_ENV=production
APP_DEBUG=false
APP_URL=https://abdiku.dev

# Generate with: php artisan key:generate --show
APP_KEY=base64:your-generated-key-here

# Database
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=abdiku
DB_USERNAME=your-username
DB_PASSWORD=your-password

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```
3. Select **Docker** > **Dockerfile**

### Configure Source

1. Connect your Git repository, or
2. Use **Public Repository** with your repo URL

### Build Configuration

| Setting | Value |
|---------|-------|
| Dockerfile Location | `Dockerfile` |
| Build Path | `/` |

### Environment Variables

Add these in Coolify's **Environment Variables** section:

```env
APP_NAME=YourAppName
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.yourdomain.com

# Generate with: php artisan key:generate --show
APP_KEY=base64:your-generated-key-here

# Database (adjust based on your setup)
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=your-database
DB_USERNAME=your-username
DB_PASSWORD=your-password

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Or use Redis if available
# REDIS_HOST=your-redis-host
# REDIS_PASSWORD=null
# REDIS_PORT=6379
# SESSION_DRIVER=redis
# CACHE_STORE=redis
# QUEUE_CONNECTION=redis
```

### Network Configuration

| Setting | Value |
|---------|-------|
| Port | `8000` |
| Domain | `abdiku.dev` |

### Domain & SSL in Coolify

Since you're using Cloudflare Tunnel:

1. Go to your application settings in Coolify
2. Navigate to **Domain** section
3. Add domain: `abdiku.dev`
4. **Disable** SSL certificate generation (Cloudflare handles it via tunnel)

---

## 6. Cloudflare Page Rules (Optional)

### Cache Static Assets

1. Select `abdiku.dev` in Cloudflare
2. Go to **Rules** > **Page Rules**
3. Create rule for: `abdiku.dev/build/*`
3. Settings:
   - **Cache Level**: Cache Everything
   - **Edge Cache TTL**: 1 month

### Bypass Cache for API

1. Create rule for: `abdiku.dev/api/*`
2. Settings:
   - **Cache Level**: Bypass

---

## 7. Cloudflare Security (Recommended)

### Firewall Rules

1. Go to **Security** > **WAF**
2. Enable **Managed Rules** (if available on your plan)

### Bot Protection

1. Go to **Security** > **Bots**
2. Enable **Bot Fight Mode**

### Rate Limiting (Optional)

1. Go to **Security** > **WAF** > **Rate limiting rules**
2. Create rule for login endpoint:
   - URL: `*/login*`
   - Requests: 10 per minute
   - Action: Block

---

## 8. Post-Deployment Commands

After deployment, run these commands via Coolify's terminal or SSH:

```bash
# Run migrations
php artisan migrate --force

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link
php artisan storage:link

# Generate Filament assets
php artisan filament:assets
```

---

## 9. Health Check

Verify your deployment:

1. Visit `https://abdiku.dev`
2. Check Cloudflare Analytics for traffic
3. Verify SSL certificate in browser (padlock icon)

---

## Troubleshooting

### 502 Bad Gateway

- Check if container is running in Coolify
- Verify port `8000` is exposed correctly
- Check container logs in Coolify
- **Tunnel users**: Verify tunnel is connected (green status in Zero Trust dashboard)

### Tunnel Not Connecting

- Check tunnel status in Zero Trust > Tunnels
- Verify token is correct in Coolify settings
- Restart the cloudflared connector
- Check Coolify server has outbound internet access

### SSL Certificate Issues

- **Tunnel users**: Ensure SSL mode is **Full** (not Full Strict)
- **Direct IP users**: Ensure SSL mode is **Full (Strict)**
- Wait for Let's Encrypt certificate generation (up to 5 minutes)
- Check Coolify logs for certificate errors

### Mixed Content Warnings

- Ensure `APP_URL` uses `https://`
- Enable **Automatic HTTPS Rewrites** in Cloudflare

### Slow Response Times

- Enable Cloudflare caching for static assets
- Check if Octane is running: container logs should show `Server running…`

---

## Quick Reference

| Service | URL |
|---------|-----|
| Abdiku App | `https://abdiku.dev` |
| Coolify Dashboard | `https://dashboard.deskbranch.site` |
| Cloudflare Dashboard | `https://dash.cloudflare.com` |
| Cloudflare Zero Trust | `https://one.dash.cloudflare.com` |
