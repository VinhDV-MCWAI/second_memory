#!/bin/sh
# Idempotent MinIO bootstrap: buckets, versioning, lifecycle rules, backend IAM user.
# Runs inside the MinIO image (ships `mc`), so no extra tooling is downloaded.
set -eu

MINIO_ENDPOINT="${MINIO_ENDPOINT:-http://ml-minio:9000}"
ALIAS="myminio"
IAM_USER="${MINIO_IAM_USER:?MINIO_IAM_USER is required}"
IAM_PASSWORD="${MINIO_IAM_PASSWORD:?MINIO_IAM_PASSWORD is required}"
BUCKET_OFFICIAL="${MINIO_BUCKET_OFFICIAL:-media-official}"
BUCKET_TEMP="${MINIO_BUCKET_TEMP:-media-temp}"
POLICY_NAME="backend-policy"
POLICY_FILE="$(mktemp)"
trap 'rm -f "$POLICY_FILE"' EXIT

echo "Waiting for MinIO at $MINIO_ENDPOINT..."
until mc alias set "$ALIAS" "$MINIO_ENDPOINT" "$MINIO_ROOT_USER" "$MINIO_ROOT_PASSWORD" >/dev/null 2>&1; do
  sleep 2
done
echo "MinIO is ready. Buckets: official=$BUCKET_OFFICIAL, temp=$BUCKET_TEMP"

# 1. Buckets
mc mb --ignore-existing "$ALIAS/$BUCKET_OFFICIAL"
mc mb --ignore-existing "$ALIAS/$BUCKET_TEMP"

# 2. Versioning: official keeps history, temp does not
mc version enable "$ALIAS/$BUCKET_OFFICIAL"
mc version suspend "$ALIAS/$BUCKET_TEMP"

# Anonymous read on the official bucket (current behavior; see .claude/refactor/PROGRESS.md findings)
mc anonymous set download "$ALIAS/$BUCKET_OFFICIAL"

# 3. Lifecycle rules (S3 lifecycle JSON)
mc ilm import "$ALIAS/$BUCKET_OFFICIAL" < /minio/ilm-history.json
mc ilm import "$ALIAS/$BUCKET_TEMP" < /minio/ilm-temp.json

# 4. Backend IAM user limited to the two buckets
cat > "$POLICY_FILE" <<EOF
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "s3:GetBucketLocation",
        "s3:GetObject",
        "s3:PutObject",
        "s3:DeleteObject",
        "s3:ListBucket",
        "s3:ListBucketMultipartUploads",
        "s3:AbortMultipartUpload"
      ],
      "Resource": [
        "arn:aws:s3:::$BUCKET_OFFICIAL",
        "arn:aws:s3:::$BUCKET_OFFICIAL/*",
        "arn:aws:s3:::$BUCKET_TEMP",
        "arn:aws:s3:::$BUCKET_TEMP/*"
      ]
    }
  ]
}
EOF

mc admin policy create "$ALIAS" "$POLICY_NAME" "$POLICY_FILE"
# `user add` creates the user or updates the password if it already exists
mc admin user add "$ALIAS" "$IAM_USER" "$IAM_PASSWORD"
mc admin policy attach "$ALIAS" "$POLICY_NAME" --user "$IAM_USER" >/dev/null 2>&1 || true

echo "MinIO setup complete."
