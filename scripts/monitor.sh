#!/usr/bin/env bash

echo "=== PASEREXPRESS SERVER MONITOR ==="
echo "Time: $(date)"
echo

echo "[CPU Load]"
uptime | awk -F'load average:' '{print $2}'
echo

echo "[RAM]"
free -h
echo

echo "[Disk]"
df -h /
echo

echo "[Services]"
systemctl is-active nginx
systemctl is-active php8.3-fpm
systemctl is-active mariadb

echo "Monitoring complete."
