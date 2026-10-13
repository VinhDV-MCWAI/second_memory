/**
 * File manager (ADR-0014). Generated from laravel-api/openapi.json (`pnpm gen:api`).
 */
import type { components, operations } from '@/shared/types/openapi';
import type { OkData } from './ok-data';

export type MediaItem = components['schemas']['MediaResource'];

/** `media/upload-url`: one presigned PUT for a small file. */
export type SmallUpload = OkData<operations['media.upload-url']>;

/** `media/multipart/start`: how to cut a large file. */
export type MultipartUpload = OkData<operations['media.multipart-start']>;

/** `media/multipart/part-urls/{id}`: presigned PUTs for some parts. */
export type PartUrls = OkData<operations['media.multipart-part-urls']>;
