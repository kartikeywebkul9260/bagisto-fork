<?php

namespace Webkul\Product\Type;

use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Product\Contracts\Product;
use Webkul\Product\Helpers\Indexers\Price\Grouped as GroupedIndexer;
use Webkul\Product\Repositories\ProductAttributeValueRepository;
use Webkul\Product\Repositories\ProductCustomerGroupPriceRepository;
use Webkul\Product\Repositories\ProductGroupedProductRepository;
use Webkul\Product\Repositories\ProductImageRepository;
use Webkul\Product\Repositories\ProductInventoryRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Repositories\ProductVideoRepository;

class Grouped extends AbstractType
{
    /**
     * Skip attribute for downloadable product type.
     *
     * @var array
     */
    protected $skipAttributes = [
        'price',
        'cost',
        'special_price',
        'special_price_from',
        'special_price_to',
        'length',
        'width',
        'height',
        'weight',
        'depth',
        'manage_stock',
    ];

    /**
     * Is a composite product type.
     *
     * @var bool
     */
    protected $isComposite = true;

    /**
     * Product can be added to cart with options or not.
     *
     * @var bool
     */
    protected $canBeAddedToCartWithoutOptions = false;

    /**
     * Create a new product type instance.
     *
     * @return void
     */
    public function __construct(
        CustomerRepository $customerRepository,
        AttributeRepository $attributeRepository,
        ProductRepository $productRepository,
        ProductAttributeValueRepository $attributeValueRepository,
        ProductInventoryRepository $productInventoryRepository,
        ProductImageRepository $productImageRepository,
        ProductVideoRepository $productVideoRepository,
        ProductCustomerGroupPriceRepository $productCustomerGroupPriceRepository,
        protected ProductGroupedProductRepository $productGroupedProductRepository
    ) {
        parent::__construct(
            $customerRepository,
            $attributeRepository,
            $productRepository,
            $attributeValueRepository,
            $productInventoryRepository,
            $productImageRepository,
            $productVideoRepository,
            $productCustomerGroupPriceRepository
        );
    }

    /**
     * Update.
     *
     * @param  int  $id
     * @param  array  $attributes
     * @return Product
     */
    public function update(array $data, $id, $attributes = [])
    {
        $product = parent::update($data, $id);

        if (! empty($attributes)) {
            return $product;
        }

        $this->productGroupedProductRepository->saveGroupedProducts($data, $product);

        return $product;
    }

    /**
     * Returns children ids.
     *
     * @return array
     */
    public function getChildrenIds()
    {
        return array_unique($this->product->grouped_products()->pluck('associated_product_id')->toArray());
    }

    /**
     * Check if catalog rule can be applied.
     *
     * @return bool
     */
    public function priceRuleCanBeApplied()
    {
        return false;
    }

    /**
     * Is saleable.
     *
     * @return bool
     */
    public function isSaleable()
    {
        if (! $this->product->status) {
            return false;
        }

        foreach ($this->product->grouped_products as $groupedProduct) {
            if ($groupedProduct->associated_product->isSaleable()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is product have sufficient quantity.
     */
    public function haveSufficientQuantity(int $qty): bool
    {
        foreach ($this->product->grouped_products as $groupedProduct) {
            if ($groupedProduct->associated_product->haveSufficientQuantity($qty)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get product minimal price.
     *
     * @return string
     */
    public function getPriceHtml()
    {
        return view('shop::products.prices.grouped', [
            'product' => $this->product,
            'prices' => $this->getProductPrices(),
        ])->render();
    }

    /**
     * Add product. Returns error message if can't prepare product.
     *
     * @param  array  $data
     * @return array|string
     */
    public function prepareForCart($data)
    {
        if (
            ! isset($data['qty'])
            || ! is_array($data['qty'])
        ) {
            return trans('product::app.checkout.cart.missing-options');
        }

        $cartProductsList = [];

        $associatedProductIds = $this->product->grouped_products->pluck('associated_product_id');

        foreach ($data['qty'] as $productId => $qty) {
            if (
                (int) $qty < 1
                || ! $associatedProductIds->contains($productId)
            ) {
                continue;
            }

            $product = $this->productRepository->find($productId);

            if ($product->type !== 'simple') {
                return trans('product::app.checkout.cart.selected-products-simple');
            }

            $cartProducts = $product->getTypeInstance()->prepareForCart([
                'product_id' => $productId,
                'quantity' => $qty,
            ]);

            if (is_string($cartProducts)) {
                return $cartProducts;
            }

            $cartProductsList[] = $cartProducts;
        }

        $products = array_merge(...$cartProductsList);

        if (! count($products)) {
            return trans('product::app.checkout.cart.integrity.qty-missing');
        }

        return $products;
    }

    /**
     * Return the selected child quantities, falling back to the salable defaults when none are chosen.
     */
    public function prepareForWishlist(array $data): array|string
    {
        $quantities = $this->normalizeQuantities((array) ($data['qty'] ?? []));

        $salableProducts = $this->product->grouped_products
            ->sortBy('sort_order')
            ->filter(fn ($groupedProduct) => $groupedProduct->associated_product?->isSaleable());

        if (! $quantities) {
            $quantities = $this->normalizeQuantities($salableProducts
                ->mapWithKeys(fn ($groupedProduct) => [$groupedProduct->associated_product_id => $groupedProduct->qty])
                ->all());
        }

        if (! $quantities) {
            $quantities = $this->normalizeQuantities([
                $salableProducts->first()?->associated_product_id => 1,
            ]);
        }

        if (! $quantities) {
            return trans('product::app.checkout.cart.integrity.qty-missing');
        }

        return [
            'product_id' => $this->product->id,
            'qty' => $quantities,
        ];
    }

    /**
     * Return true when both option sets ask for the same quantity of each child product.
     *
     * @param  array  $options1
     * @param  array  $options2
     * @return bool
     */
    public function compareOptions($options1, $options2)
    {
        if (
            $this->product->id != ($options2['product_id'] ?? null)
            || ! isset($options1['qty'])
        ) {
            return false;
        }

        return $this->normalizeQuantities((array) $options1['qty']) === $this->normalizeQuantities((array) ($options2['qty'] ?? []));
    }

    /**
     * Returns price indexer class for a specific product type.
     *
     * @return string
     */
    public function getPriceIndexer()
    {
        return app(GroupedIndexer::class);
    }

    /**
     * Returns validation rules.
     *
     * @return array
     */
    public function getTypeValidationRules()
    {
        return [
            'links' => 'array',
            'links' => function ($attribute, $value, $fail) {
                $associatedProductIds = collect($value)->pluck('associated_product_id')->toArray();

                $products = $this->productRepository->findWhereIn('id', $associatedProductIds)
                    ->pluck('type')
                    ->filter(fn ($type) => $type !== 'simple')
                    ->count();

                if ($products) {
                    $fail(trans('product::app.checkout.cart.selected-products-simple'));
                }
            },
        ];
    }

    /**
     * Copy relationships.
     *
     * @param  \Webkul\Product\Models\Product  $product
     * @return void
     */
    protected function copyRelationships($product)
    {
        parent::copyRelationships($product);

        $attributesToSkip = config('products.copy.skip_attributes') ?? [];

        if (in_array('grouped_products', $attributesToSkip)) {
            return;
        }

        foreach ($this->product->grouped_products as $groupedProduct) {
            $product->grouped_products()->save($groupedProduct->replicate());
        }
    }

    /**
     * Keep the positive whole quantities of this product's own children, ordered by child id.
     */
    protected function normalizeQuantities(array $quantities): array
    {
        $childrenIds = array_map('intval', $this->getChildrenIds());

        $normalized = [];

        foreach ($quantities as $productId => $qty) {
            if (
                (int) $qty > 0
                && in_array((int) $productId, $childrenIds, true)
            ) {
                $normalized[(int) $productId] = (int) $qty;
            }
        }

        ksort($normalized);

        return $normalized;
    }
}
