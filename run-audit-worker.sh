#!/bin/bash
# BacklinkPro Audit Queue Worker
# Run this in a separate terminal to process audit jobs.
# Jobs use default queue on database connection.
echo "Starting Audit Queue Worker (database)..."
php artisan queue:work database --tries=3 --timeout=300 --sleep=3
