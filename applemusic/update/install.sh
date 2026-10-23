#!/bin/bash
#
# moOde Apple Music module installer
# https://github.com/a2ns/MoodeApplModule
#
# This Program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 3, or (at your option)
# any later version.
#
# This Program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
#
# Apple Music module install
#

#
# Environment
#

PLUGIN_NAME="applemusic"
PLUGIN_RELEASE_DATE="2026-10-23"

STEP=0
TOTAL_STEPS=4

WWW_DIR="/var/local/www"
INSTALL_DIR="$WWW_DIR/$PLUGIN_NAME"
SQLDB="$WWW_DIR/db/moode-sqlite3.db"

MOODE_LOG="/var/log/moode.log"
PLUGIN_LOG="/var/log/moode_plugin.log"

#
# Functions
#

cancel_install () {
	if [ $# -gt 0 ] ; then
		message_log "$1"
	fi
	message_log "** Exiting install"
	exit 1
}

message_log () {
	echo "$1"
	TIME=$(date +'%Y%m%d %H%M%S')
	echo "$TIME applemusic: $1" >> $MOODE_LOG
	echo "$TIME applemusic: $1" >> $PLUGIN_LOG
}

#
# Main
#

echo
echo "**********************************************************"
echo "**"
echo "**  Apple Music module for moOde"
echo "**"
echo "**  This package installs the Apple Music module."
echo "**"
echo "**********************************************************"
echo

if [ "$(id -u)" -ne 0 ]; then
	cancel_install "** This script must be run as root (sudo)."
fi

message_log "Start $PLUGIN_RELEASE_DATE install for $PLUGIN_NAME"

# 1 - Verify moOde environment
STEP=$((STEP + 1))
message_log "** Step $STEP-$TOTAL_STEPS: Verify moOde environment"
if [ ! -d "$WWW_DIR/www" ]; then
	cancel_install "** /var/local/www/www not found. Is moOde installed?"
fi
if ! command -v php >/dev/null 2>&1; then
	cancel_install "** php not found in PATH."
fi

# 2 - Copy module files
STEP=$((STEP + 1))
message_log "** Step $STEP-$TOTAL_STEPS: Copy module files"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MODULE_SRC="$(dirname "$SCRIPT_DIR")"

mkdir -p "$INSTALL_DIR"
cp -r "$MODULE_SRC/api" "$MODULE_SRC/lib" "$MODULE_SRC/index.html" "$INSTALL_DIR/"
mkdir -p "$INSTALL_DIR/data"
chmod 755 "$INSTALL_DIR/data"

# 3 - Install config if absent (preserve existing config)
STEP=$((STEP + 1))
message_log "** Step $STEP-$TOTAL_STEPS: Install configuration"
if [ -f "$MODULE_SRC/config.json" ]; then
	if [ ! -f "$INSTALL_DIR/config.json" ] || [ "$1" == "--force-config" ]; then
		cp "$MODULE_SRC/config.json" "$INSTALL_DIR/config.json"
		message_log "** - Config installed at $INSTALL_DIR/config.json (edit it before use)"
	else
		message_log "** - Existing config preserved at $INSTALL_DIR/config.json"
	fi
fi

# Default config path expected by the API
if [ ! -f "$INSTALL_DIR/config.json" ]; then
	cancel_install "** Config file missing: $INSTALL_DIR/config.json"
fi

# 4 - Permissions
STEP=$((STEP + 1))
message_log "** Step $STEP-$TOTAL_STEPS: Set permissions"
chown -R www-data:www-data "$INSTALL_DIR"
chmod 640 "$INSTALL_DIR/config.json"
if ls "$INSTALL_DIR"/AuthKey_*.p8 >/dev/null 2>&1; then
	chmod 640 "$INSTALL_DIR"/AuthKey_*.p8
fi
chmod 750 "$INSTALL_DIR/api" "$INSTALL_DIR/lib"

message_log "** Install complete."
echo
echo "** Le module est installé dans $INSTALL_DIR"
echo "** 1. Copiez votre clé Apple AuthKey_<KEYID>.p8 dans $INSTALL_DIR/"
echo "** 2. Éditez $INSTALL_DIR/config.json (team_id, key_id, private_key_path, storefront)"
echo "** 3. Redémarrez moOde puis ouvrez http://<moode-ip>/applemusic/"
echo
message_log "** NOTE: Reboot moOde, then configure config.json."

exit 0
