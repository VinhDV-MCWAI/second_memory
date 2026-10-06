<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CommonVal;
use App\Constants\Messages;
use LogicException;

abstract class BaseJunctionService
{
    /**
     * Validate that junction records exist
     *
     * @throws LogicException
     */
    protected function validateExistence(
        array $payload,
        callable $getIdsCallback,
        string $attributeName,
        string $tableName
    ): void {
        $values = collect($payload)->map(function ($item) {
            return array_values($item);
        })->all();

        $existingIds = $getIdsCallback($values)->toArray();

        $differences = array_udiff($values, $existingIds, function ($a, $b) {
            return strcmp(json_encode($a), json_encode($b));
        });

        $diffString = implode(', ', array_map(function ($arr) {
            return '['.implode(',', $arr).']';
        }, $differences));

        if (! empty($differences)) {
            throw new LogicException(
                Messages::getMessage(
                    Messages::E0017,
                    [
                        'attributes' => __("messages.{$attributeName}").': '.$diffString,
                        'tableName' => __("messages.{$tableName}"),
                    ]
                ),
                CommonVal::HTTP_UNPROCESSABLE_CONTENT
            );
        }
    }

    /**
     * Validate that junction records do NOT exist
     *
     * @throws LogicException
     */
    protected function validateNonExistence(
        array $payload,
        callable $getIdsCallback,
        string $attributeName,
        string $tableName
    ): void {
        $values = collect($payload)->map(function ($item) {
            return array_values($item);
        })->all();

        $existingIds = $getIdsCallback($values)->toArray();

        $diffString = implode(', ', array_map(function ($arr) {
            return '['.implode(',', $arr).']';
        }, $existingIds));

        if (! empty($existingIds)) {
            throw new LogicException(
                Messages::getMessage(
                    Messages::E0020,
                    [
                        'attributes' => __("messages.{$attributeName}").': '.$diffString,
                        'tableName' => __("messages.{$tableName}"),
                    ]
                ),
                CommonVal::HTTP_UNPROCESSABLE_CONTENT
            );
        }
    }
}
