# Quick Commands to Check Browser Libraries

## Step 1: Check for Missing Libraries

Run this command to see what libraries are missing:

```bash
docker-compose exec scheduler bash -c "CHROME=\$(find /root/.cache/ms-playwright -name chrome -type f | head -1) && ldd \"\$CHROME\" 2>&1 | grep 'not found'"
```

## Step 2: If Libraries Are Missing, Install Them

If you see any "not found" libraries, install the common ones:

```bash
docker-compose exec scheduler bash -c "apt-get update && apt-get install -y libdrm2 libxshmfence1 libxcomposite1 libxdamage1 libgbm1 && ldconfig"
```

## Step 3: Run the Full Diagnostic

```bash
docker-compose exec scheduler python3 /var/www/html/python/check_browser_deps.py
```

## Step 4: Or Install All Common Dependencies at Once

```bash
docker-compose exec scheduler bash /var/www/html/docker/install-browser-deps.sh
```

## Alternative: Rebuild Container

If the above doesn't work, rebuild the container to ensure all dependencies are installed:

```bash
docker-compose build scheduler
docker-compose up -d scheduler
```








