#!/usr/bin/env bash

fix() {
    if ! systemctl is-active --quiet "$1"; then
        echo "[Guardian] $1 is DOWN → restarting..."
        systemctl restart "$1"
    else
        echo "[Guardian] $1 OK"
    fi
}

fix nginx
fix php8.3-fpm
fix mariadb

echo "[Guardian] All services checked."
