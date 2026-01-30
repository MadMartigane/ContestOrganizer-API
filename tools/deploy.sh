#!/bin/bash
set -e

TARGET=$1
if [ "$TARGET" == "prod" ]; then
    DEST="/var/www/marius.click/html/contest/api"
elif [ "$TARGET" == "pre-prod" ]; then
    DEST="/var/www/marius.click/html/contest-preprod/api"
else
    echo "Usage: $0 {prod|pre-prod}"
    exit 1
fi

BACKUP_DIR="$HOME/backup/tournament-data"
mkdir -p "$BACKUP_DIR"

echo "Deploying to $TARGET ($DEST)..."

# 1. Backup existing data if any
if [ -d "$DEST/data" ] && [ "$(ls -A "$DEST/data")" ]; then
    echo "Backing up data..."
    cp -r "$DEST/data/"* "$BACKUP_DIR/"
fi

# 2. Prepare target directory
mkdir -p "$DEST/data"

# 3. Clean old code directories
echo "Cleaning old directories..."
rm -rf "$DEST/controllers" "$DEST/models" "$DEST/utils" "$DEST/config"

# 4. Copy new files
echo "Copying files..."
cp ./src/index.php "$DEST/"
cp -r ./src/config "$DEST/"
cp -r ./src/models "$DEST/"
cp -r ./src/controllers "$DEST/"
cp -r ./src/utils "$DEST/"

# 5. Restore data if backup exists
if [ "$(ls -A "$BACKUP_DIR")" ]; then
    echo "Restoring data..."
    cp -r "$BACKUP_DIR/"* "$DEST/data/"
fi

# 6. Set permissions (requires appropriate rights)
echo "Setting permissions..."
chown -R fedora:nginx "$DEST/data" || echo "Warning: Could not change owner. Run with sudo if needed."
chmod -R g+w "$DEST/data" || echo "Warning: Could not change permissions. Run with sudo if needed."
if command -v chcon >/dev/null 2>&1; then
    chcon -R -t httpd_sys_rw_content_t "$DEST/data" || echo "Warning: Could not set SELinux context."
fi

echo "Deployment to $TARGET completed successfully!"