@echo off
REM BacklinkPro Audit Queue Worker
REM Run this in a separate terminal to process audit jobs.
REM Jobs use default queue on database connection.
echo Starting Audit Queue Worker (database)...
php artisan queue:work database --tries=3 --timeout=300 --sleep=3
pause
