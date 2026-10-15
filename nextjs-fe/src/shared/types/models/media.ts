/**
 * File manager (ADR-0014). Generated from laravel-api/openapi.json (`pnpm gen:api`).
 */
import type { components, operations } from '@/shared/types/openapi';
import type { OkData } from './ok-data';

export type MediaItem = components['schemas']['MediaResource'];

/** `media/trash/list`: a deleted file still kept (30 days). */
export type TrashItem = components['schemas']['TrashItemResource'];

/** `media/upload-url`: one presigned PUT for a small file. */
export type SmallUpload = OkData<operations['media.upload-url']>;

/** `media/multipart/start`: how to cut a large file. */
export type MultipartUpload = OkData<operations['media.multipart-start']>;

/** `media/multipart/part-urls/{id}`: presigned PUTs for some parts. */
export type PartUrls = OkData<operations['media.multipart-part-urls']>;

/** `media/multipart/unfinished`: a large upload that stopped before the end. */
export type UnfinishedUpload = OkData<operations['media.multipart-unfinished']>[number];

/** `media/multipart/resume/{id}`: how the file is cut and the parts already on the server. */
export type ResumedUpload = OkData<operations['media.multipart-resume']>;
