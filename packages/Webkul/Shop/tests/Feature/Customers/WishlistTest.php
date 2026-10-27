<?php

use Webkul\Customer\Models\Customer;
use Webkul\Customer\Models\Wishlist;
use Webkul\Product\Models\Product;
use Webkul\Shop\Http\Resources\ProductCardResource;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

/**
 * Put a product on the wishlist of the given customer on the current channel.
 */
function wishlistItemFor(Customer $customer, int $productId): Wishlist
{
    return Wishlist::factory()->create([
        'channel_id' => core()->getCurrentChannel()->id,
        'product_id' => $productId,
        'customer_id' => $customer->id,
    ]);
}

/**
 * The product card the storefront renders for the given product.
 */
function wishlistProductCard(int $productId): array
{
    return (new ProductCardResource(Product::find($productId)))->toArray(request());
}

// ============================================================================
// Index
// ============================================================================

it('should return the wishlist page', function () {
    $product = $this->createSimpleProduct();

    $customer = Customer::factory()->create();

    wishlistItemFor($customer, $product->id);

    $this->loginAsCustomer($customer);

    get(route('shop.customers.account.wishlist.index'))
        ->assertOk()
        ->assertSeeText(trans('shop::app.customers.account.wishlist.page-title'));
});

it('should list the wishlisted items of the customer via the api', function () {
    $product = $this->createSimpleProduct();

    $customer = Customer::factory()->create();

    wishlistItemFor($customer, $product->id);

    wishlistItemFor(Customer::factory()->create(), $product->id);

    $this->loginAsCustomer($customer);

    getJson(route('shop.api.customers.account.wishlist.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.product.id', $product->id);
});

// ============================================================================
// Store
// ============================================================================

it('should fail validation when product id is missing on wishlist store', function () {
    $this->loginAsCustomer();

    postJson(route('shop.api.customers.account.wishlist.store'))
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('product_id');
});

it('should add a product to the wishlist', function () {
    $product = $this->createSimpleProduct();

    $customer = $this->loginAsCustomer();

    postJson(route('shop.api.customers.account.wishlist.store'), [
        'product_id' => $product->id,
    ])
        ->assertOk()
        ->assertJsonPath('data.message', trans('shop::app.customers.account.wishlist.success'));

    $this->assertDatabaseHas('wishlist_items', [
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'channel_id' => core()->getCurrentChannel()->id,
    ]);
});

it('should take a product off the wishlist when it is added a second time', function () {
    $product = $this->createSimpleProduct();

    $customer = $this->loginAsCustomer();

    postJson(route('shop.api.customers.account.wishlist.store'), ['product_id' => $product->id])->assertOk();

    postJson(route('shop.api.customers.account.wishlist.store'), ['product_id' => $product->id])
        ->assertOk()
        ->assertJsonPath('data.message', trans('shop::app.customers.account.wishlist.removed'));

    $this->assertDatabaseMissing('wishlist_items', [
        'customer_id' => $customer->id,
        'product_id' => $product->id,
    ]);
});

// ============================================================================
// Remove
// ============================================================================

it('should remove a product from the wishlist', function () {
    $product = $this->createSimpleProduct();

    $customer = Customer::factory()->create();

    $wishlistItem = wishlistItemFor($customer, $product->id);

    $this->loginAsCustomer($customer);

    deleteJson(route('shop.api.customers.account.wishlist.destroy', $wishlistItem->id))
        ->assertOk()
        ->assertJsonPath('message', trans('shop::app.customers.account.wishlist.removed'));

    $this->assertDatabaseMissing('wishlist_items', ['id' => $wishlistItem->id]);
});

it('should not remove the wishlist item of another customer', function () {
    $wishlistItem = wishlistItemFor(Customer::factory()->create(), $this->createSimpleProduct()->id);

    $this->loginAsCustomer();

    deleteJson(route('shop.api.customers.account.wishlist.destroy', $wishlistItem->id))
        ->assertOk()
        ->assertJsonPath('data.message', trans('shop::app.customers.account.wishlist.remove-fail'));

    $this->assertDatabaseHas('wishlist_items', ['id' => $wishlistItem->id]);
});

it('should remove all products from the wishlist', function () {
    $customer = Customer::factory()->create();

    foreach (range(1, 2) as $index) {
        wishlistItemFor($customer, $this->createSimpleProduct()->id);
    }

    $this->loginAsCustomer($customer);

    deleteJson(route('shop.api.customers.account.wishlist.destroy_all'))
        ->assertOk()
        ->assertJsonPath('data.message', trans('shop::app.customers.account.wishlist.removed'));

    $this->assertDatabaseMissing('wishlist_items', ['customer_id' => $customer->id]);
});

// ============================================================================
// Move To Cart
// ============================================================================

it('should move a wishlist item to the cart', function () {
    $product = $this->createSimpleProduct();

    $customer = Customer::factory()->create();

    $wishlistItem = wishlistItemFor($customer, $product->id);

    $this->loginAsCustomer($customer);

    postJson(route('shop.api.customers.account.wishlist.move_to_cart', $wishlistItem->id), [
        'quantity' => 2,
    ])
        ->assertOk()
        ->assertJsonPath('message', trans('shop::app.customers.account.wishlist.moved-success'));

    $this->assertDatabaseMissing('wishlist_items', ['id' => $wishlistItem->id]);

    $this->assertCartHasProduct($product->id, 2);
});

it('should not move the wishlist item of another customer to the cart', function () {
    $product = $this->createSimpleProduct();

    $wishlistItem = wishlistItemFor(Customer::factory()->create(), $product->id);

    $this->loginAsCustomer();

    postJson(route('shop.api.customers.account.wishlist.move_to_cart', $wishlistItem->id), [
        'quantity' => 1,
    ])
        ->assertNotFound();

    $this->assertDatabaseHas('wishlist_items', ['id' => $wishlistItem->id]);

    $this->assertCartIsEmpty();
});

// ============================================================================
// Product Page Toggle
// ============================================================================

it('should add the first salable variant when no variant is selected on the product page', function () {
    $product = $this->createConfigurableProduct();

    $customer = $this->loginAsCustomer();

    postJson(route('shop.api.customers.account.wishlist.toggle'), ['product_id' => $product->id])
        ->assertOk()
        ->assertJsonPath('data.is_wishlist', true)
        ->assertJsonPath('data.message', trans('shop::app.customers.account.wishlist.success'));

    $wishlistItem = Wishlist::where('customer_id', $customer->id)->sole();

    expect($wishlistItem->additional['selected_configurable_option'])->toBe($product->variants->first()->id);
});

it('should add the selected variant on the product page', function () {
    $product = $this->createConfigurableProduct();

    $customer = $this->loginAsCustomer();

    postJson(route('shop.api.customers.account.wishlist.toggle'), [
        'product_id' => $product->id,
        'selected_configurable_option' => (string) $product->variants->last()->id,
    ])->assertOk();

    $wishlistItem = Wishlist::where('customer_id', $customer->id)->sole();

    expect($wishlistItem->additional['selected_configurable_option'])->toBe($product->variants->last()->id);
});

it('should keep a separate wishlist entry for each variant added from the product page', function () {
    $product = $this->createConfigurableProduct();

    $customer = $this->loginAsCustomer();

    foreach ($product->variants as $variant) {
        postJson(route('shop.api.customers.account.wishlist.toggle'), [
            'product_id' => $product->id,
            'selected_configurable_option' => $variant->id,
        ])->assertOk();
    }

    expect(Wishlist::where('customer_id', $customer->id)->count())->toBe(2);
});

it('should remove only the selected variant when it is toggled again on the product page', function () {
    $product = $this->createConfigurableProduct();

    [$first, $second] = $product->variants->all();

    $customer = $this->loginAsCustomer();

    foreach ([$first, $second, $first] as $variant) {
        postJson(route('shop.api.customers.account.wishlist.toggle'), [
            'product_id' => $product->id,
            'selected_configurable_option' => $variant->id,
        ])->assertOk();
    }

    $wishlistItem = Wishlist::where('customer_id', $customer->id)->sole();

    expect($wishlistItem->additional['selected_configurable_option'])->toBe($second->id);
});

it('should reject a variant that does not belong to the product', function () {
    $product = $this->createConfigurableProduct();

    $customer = $this->loginAsCustomer();

    postJson(route('shop.api.customers.account.wishlist.toggle'), [
        'product_id' => $product->id,
        'selected_configurable_option' => $this->createSimpleProduct()->id,
    ])
        ->assertUnprocessable()
        ->assertJsonPath('data.message', trans('product::app.checkout.cart.missing-options'));

    $this->assertDatabaseMissing('wishlist_items', ['customer_id' => $customer->id]);
});

it('should reject a partly selected variant', function () {
    $product = $this->createConfigurableProduct();

    $this->loginAsCustomer();

    postJson(route('shop.api.customers.account.wishlist.toggle'), [
        'product_id' => $product->id,
        'super_attribute' => [1 => 1, 2 => ''],
    ])->assertUnprocessable();
});

it('should not remove a parent only wishlist entry when a variant is toggled on the product page', function () {
    $product = $this->createConfigurableProduct();

    $customer = Customer::factory()->create();

    $parentOnlyItem = wishlistItemFor($customer, $product->id);

    $this->loginAsCustomer($customer);

    postJson(route('shop.api.customers.account.wishlist.toggle'), ['product_id' => $product->id])
        ->assertOk()
        ->assertJsonPath('data.is_wishlist', true);

    $this->assertDatabaseHas('wishlist_items', ['id' => $parentOnlyItem->id]);

    expect(Wishlist::where('customer_id', $customer->id)->count())->toBe(2);
});

it('should add the default bundle options when none are selected on the product page', function () {
    $product = $this->createBundleProduct();

    $option = $product->bundle_options->first();

    $customer = $this->loginAsCustomer();

    postJson(route('shop.api.customers.account.wishlist.toggle'), ['product_id' => $product->id])->assertOk();

    $wishlistItem = Wishlist::where('customer_id', $customer->id)->sole();

    expect($wishlistItem->additional['bundle_options'])
        ->toBe([$option->id => [$option->bundle_option_products->first()->id]]);
});

it('should keep a separate wishlist entry for each bundle selection added from the product page', function () {
    $product = $this->createBundleProduct();

    $option = $product->bundle_options->first();

    $customer = $this->loginAsCustomer();

    foreach ($option->bundle_option_products as $optionProduct) {
        postJson(route('shop.api.customers.account.wishlist.toggle'), [
            'product_id' => $product->id,
            'bundle_options' => [$option->id => [$optionProduct->id]],
            'bundle_option_qty' => [$option->id => 1],
        ])->assertOk();
    }

    expect(Wishlist::where('customer_id', $customer->id)->count())->toBe(2);
});

it('should reject a bundle option product that does not belong to the option', function () {
    $product = $this->createBundleProduct();

    $otherBundle = $this->createBundleProduct();

    $this->loginAsCustomer();

    postJson(route('shop.api.customers.account.wishlist.toggle'), [
        'product_id' => $product->id,
        'bundle_options' => [
            $product->bundle_options->first()->id => [$otherBundle->bundle_options->first()->bundle_option_products->first()->id],
        ],
    ])->assertUnprocessable();
});

it('should add the default grouped quantities when none are selected on the product page', function () {
    $product = $this->createGroupedProduct();

    $customer = $this->loginAsCustomer();

    $childIds = $product->grouped_products->pluck('associated_product_id');

    postJson(route('shop.api.customers.account.wishlist.toggle'), [
        'product_id' => $product->id,
        'qty' => $childIds->mapWithKeys(fn ($id) => [$id => 0])->all(),
    ])->assertOk();

    $wishlistItem = Wishlist::where('customer_id', $customer->id)->sole();

    expect($wishlistItem->additional['qty'])->toBe($childIds->mapWithKeys(fn ($id) => [$id => 1])->all());
});

it('should remove only the selected grouped quantities when they are toggled again on the product page', function () {
    $product = $this->createGroupedProduct();

    [$first, $second] = $product->grouped_products->pluck('associated_product_id')->all();

    $customer = $this->loginAsCustomer();

    foreach ([[$first => 2, $second => 0], [$first => 1, $second => 1], [$first => 2, $second => 0]] as $quantities) {
        postJson(route('shop.api.customers.account.wishlist.toggle'), [
            'product_id' => $product->id,
            'qty' => $quantities,
        ])->assertOk();
    }

    $wishlistItem = Wishlist::where('customer_id', $customer->id)->sole();

    expect($wishlistItem->additional['qty'])->toBe([$first => 1, $second => 1]);
});

it('should move a variant added from the product page to the cart', function () {
    $product = $this->createConfigurableProduct();

    $customer = $this->loginAsCustomer();

    postJson(route('shop.api.customers.account.wishlist.toggle'), ['product_id' => $product->id])->assertOk();

    $wishlistItem = Wishlist::where('customer_id', $customer->id)->sole();

    postJson(route('shop.api.customers.account.wishlist.move_to_cart', $wishlistItem->id), ['quantity' => 1])
        ->assertOk()
        ->assertJsonPath('message', trans('shop::app.customers.account.wishlist.moved-success'));

    $this->assertCartHasProduct($product->id, 1);

    $this->assertDatabaseHas('cart_items', ['product_id' => $product->variants->first()->id]);
});

// ============================================================================
// Product Page Status
// ============================================================================

it('should report only the wishlisted variant as in the wishlist', function () {
    $product = $this->createConfigurableProduct();

    [$first, $second] = $product->variants->all();

    $this->loginAsCustomer();

    postJson(route('shop.api.customers.account.wishlist.toggle'), [
        'product_id' => $product->id,
        'selected_configurable_option' => $second->id,
    ])->assertOk();

    getJson(route('shop.api.customers.account.wishlist.status', [
        'product_id' => $product->id,
        'selected_configurable_option' => $second->id,
    ]))
        ->assertOk()
        ->assertJsonPath('data.is_wishlist', true);

    getJson(route('shop.api.customers.account.wishlist.status', [
        'product_id' => $product->id,
        'selected_configurable_option' => $first->id,
    ]))
        ->assertOk()
        ->assertJsonPath('data.is_wishlist', false);
});

it('should not report a variant as in the wishlist when only the parent product is', function () {
    $product = $this->createConfigurableProduct();

    $customer = Customer::factory()->create();

    wishlistItemFor($customer, $product->id);

    $this->loginAsCustomer($customer);

    getJson(route('shop.api.customers.account.wishlist.status', ['product_id' => $product->id]))
        ->assertOk()
        ->assertJsonPath('data.is_wishlist', false);
});

it('should report a variant moved from the cart as in the wishlist', function () {
    $product = $this->createConfigurableProduct();

    $variant = $product->variants->last();

    $customer = Customer::factory()->create();

    Wishlist::factory()->create([
        'channel_id' => core()->getCurrentChannel()->id,
        'product_id' => $product->id,
        'customer_id' => $customer->id,
        'additional' => [
            'product_id' => (string) $product->id,
            'selected_configurable_option' => (string) $variant->id,
            'quantity' => 1,
        ],
    ]);

    $this->loginAsCustomer($customer);

    getJson(route('shop.api.customers.account.wishlist.status', [
        'product_id' => $product->id,
        'selected_configurable_option' => $variant->id,
    ]))
        ->assertOk()
        ->assertJsonPath('data.is_wishlist', true);
});

it('should not report a partly selected variant as in the wishlist', function () {
    $product = $this->createConfigurableProduct();

    $this->loginAsCustomer();

    postJson(route('shop.api.customers.account.wishlist.toggle'), ['product_id' => $product->id])->assertOk();

    getJson(route('shop.api.customers.account.wishlist.status', [
        'product_id' => $product->id,
        'super_attribute' => [1 => 1, 2 => ''],
    ]))
        ->assertOk()
        ->assertJsonPath('data.is_wishlist', false);
});

// ============================================================================
// Product Card
// ============================================================================

it('should show the card heart as filled when the first salable variant is on the wishlist', function () {
    $product = $this->createConfigurableProduct();

    $this->loginAsCustomer();

    postJson(route('shop.api.customers.account.wishlist.toggle'), ['product_id' => $product->id])->assertOk();

    expect(wishlistProductCard($product->id)['is_wishlist'])->toBeTrue();
});

it('should not show the card heart as filled when only another variant is on the wishlist', function () {
    $product = $this->createConfigurableProduct();

    $this->loginAsCustomer();

    postJson(route('shop.api.customers.account.wishlist.toggle'), [
        'product_id' => $product->id,
        'selected_configurable_option' => $product->variants->last()->id,
    ])->assertOk();

    expect(wishlistProductCard($product->id)['is_wishlist'])->toBeFalse();
});

it('should not show the card heart as filled when only the parent product is on the wishlist', function () {
    $product = $this->createConfigurableProduct();

    $customer = Customer::factory()->create();

    wishlistItemFor($customer, $product->id);

    $this->loginAsCustomer($customer);

    expect(wishlistProductCard($product->id)['is_wishlist'])->toBeFalse();
});

it('should show the card heart as filled when the default grouped quantities are on the wishlist', function () {
    $product = $this->createGroupedProduct();

    $this->loginAsCustomer();

    postJson(route('shop.api.customers.account.wishlist.toggle'), ['product_id' => $product->id])->assertOk();

    expect(wishlistProductCard($product->id)['is_wishlist'])->toBeTrue();
});

it('should remove only the first salable variant when the card heart is tapped again', function () {
    $product = $this->createConfigurableProduct();

    $customer = $this->loginAsCustomer();

    postJson(route('shop.api.customers.account.wishlist.toggle'), [
        'product_id' => $product->id,
        'selected_configurable_option' => $product->variants->last()->id,
    ])->assertOk();

    postJson(route('shop.api.customers.account.wishlist.toggle'), ['product_id' => $product->id])
        ->assertOk()
        ->assertJsonPath('data.is_wishlist', true);

    postJson(route('shop.api.customers.account.wishlist.toggle'), ['product_id' => $product->id])
        ->assertOk()
        ->assertJsonPath('data.is_wishlist', false);

    $wishlistItem = Wishlist::where('customer_id', $customer->id)->sole();

    expect($wishlistItem->additional['selected_configurable_option'])->toBe($product->variants->last()->id);
});

it('should keep showing the card heart as filled for a simple product on the wishlist', function () {
    $product = $this->createSimpleProduct();

    $customer = Customer::factory()->create();

    wishlistItemFor($customer, $product->id);

    $this->loginAsCustomer($customer);

    expect(wishlistProductCard($product->id)['is_wishlist'])->toBeTrue();
});
