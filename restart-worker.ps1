# Stop any running Python workers
Get-Process python -ErrorAction SilentlyContinue | Where-Object {$_.Path -like "*backlink-pro*"} | Stop-Process -Force

# Wait a moment
Start-Sleep -Seconds 2

# Start the worker
cd python
$env:LARAVEL_API_URL = "http://127.0.0.1:8000"
$env:LARAVEL_API_TOKEN = "your-secure-api-token-change-in-production"
$env:POLL_INTERVAL = "30"

Write-Host "Starting Python worker..."
python worker.py

