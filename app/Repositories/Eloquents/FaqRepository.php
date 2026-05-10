<?php

namespace App\Repositories\Eloquents;

use App\Models\Faq;
use App\Repositories\Contracts\FaqRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class FaqRepository implements FaqRepositoryInterface
{
    public function getAllActive(): Collection
    {
        return Faq::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function getAll(): Collection
    {
        return Faq::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function findById(int $id): ?Faq
    {
        return Faq::query()->find($id);
    }

    public function create(array $data): Faq
    {
        return Faq::create($data);
    }

    public function update(Faq $faq, array $data): Faq
    {
        $faq->update($data);
        return $faq->refresh();
    }

    public function delete(Faq $faq): void
    {
        $faq->delete();
    }
}
