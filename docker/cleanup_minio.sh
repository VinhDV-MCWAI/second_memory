#!/bin/sh
echo "Setting up MinIO alias..."
mc alias set myminio http://127.0.0.1:9000 ml_minio_admin ml_minio_password123

echo "Fetching version history..."
mc ls --json --versions --recursive myminio/media-official > /tmp/versions.json

cat /tmp/versions.json | while read -r line; do
  name=$(echo "$line" | grep -o '"key":"[^"]*"' | cut -d'"' -f4)
  size=$(echo "$line" | grep -o '"size":[0-9]*' | cut -d':' -f2)
  vid=$(echo "$line" | grep -o '"versionId":"[^"]*"' | cut -d'"' -f4)
  isDeleteMarker=$(echo "$line" | grep -o '"isDeleteMarker":true')

  if [ -n "$isDeleteMarker" ]; then
    echo "Removing Delete Marker to restore or purge: $name ($vid)"
    mc rm --version-id "$vid" --force "myminio/media-official/$name"
  else
    # is a real file version
    if [ -n "$size" ] && [ "$size" -gt 104857600 ]; then
      echo "Permanently deleting LARGE file (>100MB): $name ($size bytes)"
      mc rm --version-id "$vid" --force "myminio/media-official/$name"
    else
      echo "Keeping small file: $name ($size bytes)"
    fi
  fi
done

echo "Cleanup complete. Current state of MinIO:"
mc ls --recursive myminio/media-official
