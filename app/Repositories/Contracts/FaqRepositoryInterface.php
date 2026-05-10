<?php

namespace App\Repositories\Contracts;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Collection;

interface FaqRepositoryInterface
{
    public function getAllActive(): Collection;

    public function getAll(): Collection;

    public function findById(int $id): ?Faq;

    public function create(array $data): Faq;

    public function update(Faq $faq, array $data): Faq;

    public function delete(Faq $faq): void;
}
