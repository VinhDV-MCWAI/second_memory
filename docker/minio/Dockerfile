# Community MinIO build (ADR-0011), pinned. Runs as an unprivileged user: /data is created
# here owned by that user, so a new named volume mounted on it inherits the ownership.
ARG MINIO_IMAGE=pgsty/minio:RELEASE.2026-08-04T00-00-00Z
FROM ${MINIO_IMAGE}
ARG MINIO_UID=1000
RUN mkdir -p /data && chown "${MINIO_UID}:${MINIO_UID}" /data
USER ${MINIO_UID}:${MINIO_UID}
