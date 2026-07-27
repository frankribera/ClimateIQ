# ClimateIQ

ClimateIQ turns home-climate telemetry into a compact, visual story: what the HVAC system is doing, how comfortable the home is, how long equipment has run, and what deserves attention.

## Included

- Linked home dashboard and detail pages
- Live Home Assistant entity adapter
- Runtime, temperature, humidity, comfort, and system-state metrics
- Responsive compact design
- Automatic fallback/demo mode when live credentials are not configured
- Raspberry Pi / Debian Apache installer
- Footer credit and contact link on every page

## Quick install

```bash
chmod +x install.sh
sudo ./install.sh
```

The installer deploys ClimateIQ to `/var/www/frankribera/climateiq` and creates `/etc/climateiq/config.php`.

Then edit:

```bash
sudo nano /etc/climateiq/config.php
```

Add your Home Assistant URL, long-lived access token, and entity IDs. Reload Apache:

```bash
sudo systemctl reload apache2
```

Open:

- `https://frankribera.com/climateiq/`
- or `http://<pi-address>/climateiq/`

## Configuration

ClimateIQ expects a PHP array in `/etc/climateiq/config.php`. The installer creates a documented template. Credentials stay outside the web root and are not committed to GitHub.

## Repository layout

- `public/` — website and API
- `install.sh` — idempotent Raspberry Pi installer
- `config.example.php` — configuration reference
