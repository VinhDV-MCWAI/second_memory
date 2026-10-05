#!/bin/sh
set -e

# Install dependencies
if command -v apk &> /dev/null; then
    apk add --no-cache curl sed aws-cli
fi

# Install mc if not present (Cache it in the mounted volume)
MC_PATH="/minio/mc"
if [ ! -f "$MC_PATH" ]; then
    echo "Downloading MinIO Client (mc)..."
    curl -o "$MC_PATH" https://dl.min.io/client/mc/release/linux-amd64/mc
    chmod +x "$MC_PATH"
fi

# Add simple alias/function or add to PATH
export PATH=$PATH:/minio

# Wait for MinIO to be ready and set alias
echo "Waiting for MinIO to be ready..."
until "$MC_PATH" alias set myminio http://ml-minio:9000 "$MINIO_ROOT_USER" "$MINIO_ROOT_PASSWORD"; do
  echo "MinIO is unavailable - sleeping"
  sleep 2
done

echo "MinIO alias set successfully."

# Variables
IAM_USER="${MINIO_IAM_USER:-backend-user}"
IAM_PASSWORD="${MINIO_IAM_PASSWORD:-strong-backend-password}"
BUCKET_OFFICIAL="${MINIO_BUCKET_OFFICIAL:-media-official}"
BUCKET_TEMP="${MINIO_BUCKET_TEMP:-media-temp}"

echo "Using Bucket Names: Official=$BUCKET_OFFICIAL, Temp=$BUCKET_TEMP"
echo "Using IAM User: $IAM_USER"

# 1. Create Buckets
echo "Creating buckets..."
mc mb --ignore-existing "myminio/$BUCKET_OFFICIAL"
mc mb --ignore-existing "myminio/$BUCKET_TEMP"

# 2. Versioning Configuration
echo "Configuring versioning..."
mc version enable "myminio/$BUCKET_OFFICIAL"
mc version suspend "myminio/$BUCKET_TEMP"

# Set public download policy for official bucket
echo "Setting public download policy for $BUCKET_OFFICIAL..."
mc anonymous set download "myminio/$BUCKET_OFFICIAL"


# 3. Lifecycle Configuration
echo "Configuring lifecycle rules using AWS CLI..."

# Configure AWS CLI environment variables
export AWS_ACCESS_KEY_ID="$MINIO_ROOT_USER"
export AWS_SECRET_ACCESS_KEY="$MINIO_ROOT_PASSWORD"
export AWS_DEFAULT_REGION="us-east-1" 

# Apply history retention (30 days) to official
if [ -f /minio/ilm-history.json ]; then
    echo "Applying lifecycle to $BUCKET_OFFICIAL..."
    aws s3api put-bucket-lifecycle-configuration \
        --bucket "$BUCKET_OFFICIAL" \
        --lifecycle-configuration file:///minio/ilm-history.json \
        --endpoint-url http://ml-minio:9000
else
    echo "Warning: /minio/ilm-history.json not found"
fi

# Apply temp cleanup (1 day) to temp
if [ -f /minio/ilm-temp.json ]; then
    echo "Applying lifecycle to $BUCKET_TEMP..."
    aws s3api put-bucket-lifecycle-configuration \
        --bucket "$BUCKET_TEMP" \
        --lifecycle-configuration file:///minio/ilm-temp.json \
        --endpoint-url http://ml-minio:9000
else
    echo "Warning: /minio/ilm-temp.json not found"
fi

# 4. Policy and User Setup
echo "Generating policy.json..."
cat <<EOF > /tmp/policy.json
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

echo "Setting up access control..."

# Create policy
mc admin policy create myminio backend-policy /tmp/policy.json

# Create user
if ! mc admin user info myminio "$IAM_USER" > /dev/null 2>&1; then
    echo "Creating user $IAM_USER..."
    mc admin user add myminio "$IAM_USER" "$IAM_PASSWORD"
else
    echo "User $IAM_USER already exists. updating password..."
    mc admin user add myminio "$IAM_USER" "$IAM_PASSWORD"
fi

# Assign policy to user
mc admin policy attach myminio backend-policy --user "$IAM_USER"

# 5. Update Laravel env
echo "Updating Laravel .env.example..."
if [ -f /laravel-api/.env.example ]; then
    # Escape special characters for sed
    SAFE_USER=$(echo "$IAM_USER" | sed 's/[\/&]/\\&/g')
    SAFE_PASS=$(echo "$IAM_PASSWORD" | sed 's/[\/&]/\\&/g')

    # Replace Access Key
    sed -i "s/^AWS_ACCESS_KEY_ID=.*/AWS_ACCESS_KEY_ID=$SAFE_USER/" /laravel-api/.env.example
    
    # Replace Secret Key
    sed -i "s/^AWS_SECRET_ACCESS_KEY=.*/AWS_SECRET_ACCESS_KEY=$SAFE_PASS/" /laravel-api/.env.example
    
    echo "Laravel .env.example updated with new MinIO credentials."
else
    echo "Warning: /laravel-api/.env.example not found. Skipping auto-config."
fi

echo "MinIO Setup Complete!"
