<?php

namespace App\Services;

use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;


class ProductService
{
    public function __construct(private readonly ProductRepositoryInterface $productRepository)
    {
    }

    public function getProducts(array $filters = []): LengthAwarePaginator
    {
        return $this->productRepository->getAll($filters);
    }

    public function getProductById(int $id): ?Product
    {
        return $this->productRepository->findById($id);
    }

    public function getProductBySlug(string $slug): ?Product
    {
        return Product::query()->where('slug', $slug)->first();
    }

    /**
     * @throws Throwable
     */
    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create($this->productAttributes($data));
            $this->syncRelations($product, $data);

            return $product->refresh();
        });
    }

    /**
     * @throws Throwable
     */
    public function updateProduct(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->update($this->productAttributes($data));
            $this->syncRelations($product, $data);

            return $product->refresh();
        });
    }

    public function deleteProduct(Product $product): void
    {
        Product::query()->whereKey($product->getKey())->delete();
    }

    private function productAttributes(array $data): array
    {
        return Arr::only($data, [
            'name',
            'slug',
            'base_sku',
            'short_description',
            'description',
            'main_image',
            'manufacturer_id',
            'tax_class_id',
            'weight',
            'length',
            'width',
            'height',
            'date_available',
            'meta_title',
            'meta_description',
            'sorted',
            'status',
            'discount_percent',
            'discount_start_at',
            'discount_end_at',
        ]);
    }

    private function syncRelations(Product $product, array $data): void
    {
        if (array_key_exists('category_ids', $data)) {
            $product->categories()->sync($data['category_ids'] ?? []);
        }

        if (array_key_exists('options', $data)) {
            $optionPayload = collect($data['options'] ?? [])->mapWithKeys(function ($option) {
                return [
                    $option['option_id'] => ['required' => (int) ($option['required'] ?? 0)],
                ];
            })->all();

            $product->options()->sync($optionPayload);
        }

        if (array_key_exists('images', $data)) {
            $product->images()->delete();
            $product->images()->createMany($data['images'] ?? []);
        }

        if (array_key_exists('variants', $data)) {
            foreach (($data['variants'] ?? []) as $variantData) {
                $variantAttributes = Arr::only($variantData, [
                    'sku',
                    'barcode',
                    'weight',
                    'length',
                    'width',
                    'height',
                    'status',
                ]);

                $variant = $product->variants()->updateOrCreate(
                    ['sku' => $variantData['sku']],
                    $variantAttributes
                );

                if (!empty($variantData['option_values'])) {
                    $optionValuesPayload = collect($variantData['option_values'])
                        ->mapWithKeys(fn ($item) => [
                            $item['option_value_id'] => ['option_id' => $item['option_id']],
                        ])
                        ->all();

                    $variant->optionValues()->sync($optionValuesPayload);
                }
            }
        }
    }
}
