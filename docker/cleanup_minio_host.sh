#!/bin/bash
DOCKER_CMD="docker exec ml-minio mc"
$DOCKER_CMD alias set myminio http://127.0.0.1:9000 ml_minio_admin ml_minio_password123 > /dev/null

echo "Fetching versions from MinIO..."
$DOCKER_CMD ls --json --versions --recursive myminio/media-official > /tmp/minio_versions.json

while read -r line; do
  # Extract fields
  key=$(echo "$line" | grep -o '"key":"[^"]*"' | cut -d'"' -f4)
  size=$(echo "$line" | grep -o '"size":[0-9]*' | cut -d':' -f2)
  vid=$(echo "$line" | grep -o '"versionId":"[^"]*"' | cut -d'"' -f4)
  isDeleteMarker=$(echo "$line" | grep -o '"isDeleteMarker":true')

  if [ -z "$key" ]; then continue; fi

  if [ -n "$isDeleteMarker" ]; then
    echo "Restoring/Purging Delete Marker: $key ($vid)"
    $DOCKER_CMD rm --version-id "$vid" --force "myminio/media-official/$key"
  else
    if [[ -n "$size" && "$size" -gt 104857600 ]]; then
      echo "Permanently deleting large file >100MB: $key ($size bytes)"
      $DOCKER_CMD rm --version-id "$vid" --force "myminio/media-official/$key"
    else
      echo "Keeping small file <=100MB: $key ($size bytes)"
    fi
  fi
done < /tmp/minio_versions.json

echo "MinIO state after cleanup:"
$DOCKER_CMD ls --recursive myminio/media-official
