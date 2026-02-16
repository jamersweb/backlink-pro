@echo off
echo Clearing all Laravel caches...
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan optimize:clear
echo.
echo Done! Please hard refresh your browser (Ctrl+Shift+R)
pause
