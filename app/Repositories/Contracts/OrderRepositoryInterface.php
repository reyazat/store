<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    public function getUserOrders(User $user, array $filters = []): LengthAwarePaginator;

    /**
     * @param array{
     *   status?: string,
     *   payment_status?: string,
     *   user_id?: int,
     *   search?: string,
     *   date_from?: string,
     *   date_to?: string,
     *   min_amount?: float,
     *   max_amount?: float,
     *   per_page?: int,
     * } $filters
     */
    public function getAllOrders(array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?Order;
}
