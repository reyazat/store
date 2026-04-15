<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class ClearDiscounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:clear-discounts
                            {--expired-only : Only clear expired discounts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear discounts from products';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredOnly = $this->option('expired-only');

        if ($expiredOnly) {
            $this->info('🧹 Clearing expired discounts...');
            $products = Product::whereNotNull('discount_end_at')
                ->where('discount_end_at', '<', now())
                ->get();
        } else {
            $this->info('🧹 Clearing all discounts...');
            $products = Product::whereNotNull('discount_percent')->get();
        }

        if ($products->isEmpty()) {
            $this->warn('No products found with discounts.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($products as $product) {
            $product->update([
                'discount_percent' => null,
                'discount_start_at' => null,
                'discount_end_at' => null,
            ]);
            $count++;
        }

        $this->info("✅ Successfully cleared discounts from {$count} products!");

        return self::SUCCESS;
    }
}
