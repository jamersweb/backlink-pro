# PowerShell script to set up API token for Python worker

Write-Host "=" * 70
Write-Host "Python Worker API Token Setup"
Write-Host "=" * 70
Write-Host ""

# Generate secure random token
$token = -join ((48..57) + (65..90) + (97..122) | Get-Random -Count 64 | ForEach-Object {[char]$_})

Write-Host "Generated secure API token: $($token.Substring(0, 10))..."
Write-Host ""

# Check if root .env exists
$rootEnv = "..\.env"
if (Test-Path $rootEnv) {
    Write-Host "Found root .env file"
    
    # Check if APP_API_TOKEN already exists
    $existing = Get-Content $rootEnv | Select-String "APP_API_TOKEN="
    if ($existing) {
        Write-Host "⚠️  APP_API_TOKEN already exists in root .env"
        Write-Host "Current value: $existing"
        Write-Host ""
        $useExisting = Read-Host "Use existing token? (Y/n)"
        if ($useExisting -eq "n" -or $useExisting -eq "N") {
            # Update existing token
            (Get-Content $rootEnv) -replace "APP_API_TOKEN=.*", "APP_API_TOKEN=$token" | Set-Content $rootEnv
            Write-Host "✅ Updated APP_API_TOKEN in root .env"
        } else {
            # Extract existing token
            $token = ($existing -split "=")[1]
            Write-Host "✅ Using existing token"
        }
    } else {
        # Add new token
        Add-Content -Path $rootEnv -Value "`nAPP_API_TOKEN=$token"
        Write-Host "✅ Added APP_API_TOKEN to root .env"
    }
} else {
    Write-Host "⚠️  Root .env file not found at $rootEnv"
    Write-Host "You'll need to add APP_API_TOKEN=$token to your root .env file"
    Write-Host ""
}

# Create/update python/.env
$pythonEnv = ".env"
if (Test-Path $pythonEnv) {
    Write-Host "Found python/.env file"
    
    # Check if token already exists
    $existing = Get-Content $pythonEnv | Select-String "LARAVEL_API_TOKEN=|APP_API_TOKEN="
    if ($existing) {
        # Update existing
        (Get-Content $pythonEnv) -replace "LARAVEL_API_TOKEN=.*", "LARAVEL_API_TOKEN=$token" | Set-Content $pythonEnv
        (Get-Content $pythonEnv) -replace "APP_API_TOKEN=.*", "APP_API_TOKEN=$token" | Set-Content $pythonEnv
        Write-Host "✅ Updated tokens in python/.env"
    } else {
        # Add new
        Add-Content -Path $pythonEnv -Value "LARAVEL_API_URL=http://nginx`nLARAVEL_API_TOKEN=$token`nAPP_API_TOKEN=$token"
        Write-Host "✅ Added tokens to python/.env"
    }
} else {
    # Create new
    @"
LARAVEL_API_URL=http://nginx
LARAVEL_API_TOKEN=$token
APP_API_TOKEN=$token
"@ | Out-File -FilePath $pythonEnv -Encoding utf8
    Write-Host "✅ Created python/.env with tokens"
}

Write-Host ""
Write-Host "=" * 70
Write-Host "Setup Complete!"
Write-Host "=" * 70
Write-Host ""
Write-Host "You can now run:"
Write-Host "  python worker.py --once --limit 1"
Write-Host ""


