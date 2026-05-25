<?php

namespace App\Repositories\Contracts;

use App\Models\ConsignmentNote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ConsignmentNoteRepositoryInterface
{
    public function paginate(int $perPage = 10): LengthAwarePaginator;

    public function find(int $id): ?Model;

    public function create(array $data): Model;

    public function update(int $id, array $data): Model;

    public function delete(int $id): bool;
}