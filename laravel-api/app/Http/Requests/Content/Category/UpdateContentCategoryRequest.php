<?php

declare(strict_types=1);

namespace App\Http\Requests\Content\Category;

/**
 * Same fields as on create; without `attachments` the attachments stay as they are.
 */
class UpdateContentCategoryRequest extends StoreContentCategoryRequest {}
