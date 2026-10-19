<?php

declare(strict_types=1);

namespace App\Repositories\Master;

use App\Constants\EndUserConst;
use App\Models\User\EndUser;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * End users as the admin screens see them (ADR-0020): list, lock, reset the password, delete.
 */
class EndUserRepository extends CrudRepository
{
    public function __construct(EndUser $model)
    {
        parent::__construct($model);
    }

    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->select(['id', 'username', 'display_name', 'is_active', 'last_login_at', 'created_at', 'updated_at'])
            ->withCount('savedItems');

        $this->applyFilters($query, $payload, ['id', 'is_active']);
        foreach (['username', 'display_name'] as $column) {
            if (isset($payload[$column])) {
                $query->whereRaw("lower({$column}) like lower(?)", ['%'.$payload[$column].'%']);
            }
        }

        $this->applySorting($query, $payload, ['id', 'username', 'display_name', 'is_active', 'last_login_at', 'created_at'], 'created_at', 'desc');

        return $query->paginate($payload['per_page'] ?? EndUserConst::PER_PAGE, ['*'], 'page', $payload['page'] ?? 1);
    }

    public function create(string $username, string $displayName, string $password): EndUser
    {
        /** @var EndUser */
        return $this->model->newQuery()->create([
            'username' => self::normalizeUsername($username),
            'display_name' => $displayName,
            'password' => $password,
        ]);
    }

    /** Usernames are kept lower case, so the login lookup and the unique index agree. */
    public static function normalizeUsername(string $username): string
    {
        return Str::lower(trim($username));
    }

    /**
     * An empty password means "keep the current one" (the model cast hashes a new one).
     */
    protected function fillable(array $payload): array
    {
        $fields = parent::fillable($payload);

        return empty($fields['password']) ? Arr::except($fields, 'password') : $fields;
    }
}
