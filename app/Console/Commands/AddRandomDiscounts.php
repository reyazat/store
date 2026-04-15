<?php

namespace App\Console\Commands;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AddRandomDiscounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:add-discounts
                            {--percentage=60 : Percentage of products to add discounts to (default: 60)}
                            {--min=10 : Minimum discount percentage (default: 10)}
                            {--max=50 : Maximum discount percentage (default: 50)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add random discounts to existing products';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $percentage = (int) $this->option('percentage');
        $minDiscount = (int) $this->option('min');
        $maxDiscount = (int) $this->option('max');

        $this->info('🎉 Adding random discounts to products...');
        $this->newLine();

        $products = Product::whereNull('discount_percent')
            ->orWhere('discount_percent', 0)
            ->get();

        if ($products->isEmpty()) {
            $this->warn('No products found without discounts.');
            return self::SUCCESS;
        }

        $count = 0;
        $totalToUpdate = (int) ceil($products->count() * $percentage / 100);

        $this->info("Found {$products->count()} products without discounts.");
        $this->info("Will add discounts to {$totalToUpdate} products ({$percentage}%).");
        $this->newLine();

        foreach ($products->shuffle()->take($totalToUpdate) as $product) {
            $discountPercent = rand($minDiscount, $maxDiscount);
            $startAt = Carbon::now()->subDays(rand(1, 7));
            $endAt = Carbon::now()->addDays(rand(7, 30));

            $product->update([
                'discount_percent' => $discountPercent,
                'discount_start_at' => $startAt,
                'discount_end_at' => $endAt,
            ]);

            $count++;
            $this->line("   ✓ {$product->name}: {$discountPercent}% off (until {$endAt->format('Y-m-d')})");
        }

        $this->newLine();
        $this->info("✅ Successfully added discounts to {$count} products!");

        return self::SUCCESS;
    }
}
