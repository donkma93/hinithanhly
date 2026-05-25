<?php

namespace App\Repositories;

use App\Models\ConsignmentNote;
use App\Repositories\Contracts\ConsignmentNoteRepositoryInterface;

class ConsignmentNoteRepository extends BaseRepository implements ConsignmentNoteRepositoryInterface
{
    public function __construct(ConsignmentNote $model)
    {
        parent::__construct($model);
    }
}