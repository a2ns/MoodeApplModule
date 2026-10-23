#!/bin/bash
#
# moOde audio player Apple Music module
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

# 1: Plugin name and release date
PLUGIN_NAME="applemusic"
PLUGIN_RELEASE_DATE="2026-10-23"

# 2: Initialize the step counter
STEP=0
TOTAL_STEPS=1

# System vars
SQLDB=/var/local/www/db/moode-sqlite3.db

# Log files
MOODE_LOG="/var/log/moode.log"
PLUGIN_LOG="/var/log/moode_plugin.log"

#
# Functions
#

cancel_update () {
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

message_log "Start $PLUGIN_RELEASE_DATE install for $PLUGIN_NAME"

# 1 - Verify moOde environment
STEP=$((STEP + 1))
message_log "** Step $STEP-$TOTAL_STEPS: Verify moOde environment"
if [ ! -f "$SQLDB" ]; then
	cancel_update "** moOde database not found. Is moOde installed?"
fi

message_log "** Install complete. Reboot moOde to activate the module."
message_log "** NOTE: This module is under development and is not yet functional."

exit 0
