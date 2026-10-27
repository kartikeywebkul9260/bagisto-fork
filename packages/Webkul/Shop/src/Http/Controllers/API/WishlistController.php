<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Webkul\Checkout\Facades\Cart;
use Webkul\Customer\Contracts\Wishlist;
use Webkul\Customer\Repositories\WishlistRepository;
use Webkul\Product\Contracts\Product;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Shop\Http\Resources\CartResource;
use Webkul\Shop\Http\Resources\WishlistResource;

class WishlistController extends APIController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected WishlistRepository $wishlistRepository,
        protected ProductRepository $productRepository
    ) {}

    /**
     * Displays the listing resources if the customer has items on the wishlist.
     */
    public function index(): JsonResource
    {
        $this->removeInactiveItems();

        $items = $this->wishlistRepository
            ->where([
                'channel_id' => core()->getCurrentChannel()->id,
                'customer_id' => auth()->guard('customer')->user()->id,
            ])
            ->get();

        return WishlistResource::collection($items);
    }

    /**
     * Function to add items to the wishlist.
     */
    public function store(): JsonResource
    {
        $this->validate(request(), [
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $productId = request()->input('product_id');

        $product = $this->productRepository->find($productId);

        if (! $product) {
            return new JsonResource([
                'message' => trans('shop::app.customers.account.wishlist.product-removed'),
            ]);
        }

        $data = [
            'channel_id' => core()->getCurrentChannel()->id,
            'product_id' => $product->id,
            'customer_id' => auth()->guard()->user()->id,
        ];

        if (! $this->wishlistRepository->findOneWhere($data)) {
            Event::dispatch('customer.wishlist.create.before', $productId);

            $wishlist = $this->wishlistRepository->create($data);

            Event::dispatch('customer.wishlist.create.after', $wishlist);

            return new JsonResource([
                'message' => trans('shop::app.customers.account.wishlist.success'),
            ]);
        }

        $this->wishlistRepository->deleteWhere([
            'product_id' => $product->id,
            'customer_id' => auth()->guard()->user()->id,
        ]);

        return new JsonResource([
            'message' => trans('shop::app.customers.account.wishlist.removed'),
        ]);
    }

    /**
     * Add the product page selection to the wishlist, or take that selection off when it is already there.
     */
    public function toggle(): JsonResource|JsonResponse
    {
        $this->validate(request(), [
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $product = $this->productRepository->find(request()->input('product_id'));

        $options = $product->getTypeInstance()->prepareForWishlist(request()->all());

        if (is_string($options)) {
            return (new JsonResource([
                'message' => $options,
            ]))->response()->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $wishlistItem = $this->findWishlistItem($product, $options);

        if ($wishlistItem) {
            Event::dispatch('customer.wishlist.delete.before', $wishlistItem->id);

            $this->wishlistRepository->delete($wishlistItem->id);

            Event::dispatch('customer.wishlist.delete.after', $wishlistItem->id);

            return new JsonResource([
                'is_wishlist' => false,
                'message' => trans('shop::app.customers.account.wishlist.removed'),
            ]);
        }

        Event::dispatch('customer.wishlist.create.before', $product->id);

        $wishlistItem = $this->wishlistRepository->create([
            'channel_id' => core()->getCurrentChannel()->id,
            'product_id' => $product->id,
            'customer_id' => auth()->guard('customer')->user()->id,
            'additional' => $options ?: null,
        ]);

        Event::dispatch('customer.wishlist.create.after', $wishlistItem);

        return new JsonResource([
            'is_wishlist' => true,
            'message' => trans('shop::app.customers.account.wishlist.success'),
        ]);
    }

    /**
     * Tell the product page whether its current selection is already on the wishlist.
     */
    public function status(): JsonResource
    {
        $this->validate(request(), [
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $product = $this->productRepository->find(request()->input('product_id'));

        $options = $product->getTypeInstance()->prepareForWishlist(request()->all());

        if (is_string($options)) {
            return new JsonResource([
                'is_wishlist' => false,
            ]);
        }

        return new JsonResource([
            'is_wishlist' => (bool) $this->findWishlistItem($product, $options),
        ]);
    }

    /**
     * Move the wishlist item to the cart.
     *
     * @param  int  $id
     */
    public function moveToCart($id): JsonResource
    {
        $this->validate(request(), [
            'quantity' => 'nullable|integer|min:1',
        ]);

        $wishlistItem = $this->wishlistRepository->findOneWhere([
            'id' => $id,
            'customer_id' => auth()->guard('customer')->user()->id,
        ]);

        if (! $wishlistItem) {
            abort(404);
        }

        try {
            Event::dispatch('customer.wishlist.move-to-cart.before', $id);

            $result = Cart::moveToCart($wishlistItem, request()->input('quantity'));

            Event::dispatch('customer.wishlist.move-to-cart.after', $id);

            if ($result) {
                $wishlistItems = $this->wishlistRepository
                    ->where([
                        'customer_id' => auth()->guard('customer')->user()->id,
                        'channel_id' => core()->getCurrentChannel()->id,
                    ])
                    ->get();

                return new JsonResource([
                    'data' => [
                        'wishlist' => WishlistResource::collection($wishlistItems),
                        'cart' => new CartResource(Cart::getCart()),
                    ],

                    'message' => trans('shop::app.customers.account.wishlist.moved-success'),
                ]);
            }

            return new JsonResource([
                'redirect' => true,
                'data' => route('shop.product_or_category.index', $wishlistItem->product->url_key),
                'message' => trans('shop::app.checkout.cart.missing-options'),
            ]);
        } catch (\Exception $exception) {
            return new JsonResource([
                'redirect' => true,
                'data' => route('shop.product_or_category.index', $wishlistItem->product->url_key),
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Function to remove items to the wishlist.
     *
     * @param  int  $id
     */
    public function destroy($id): JsonResource
    {
        Event::dispatch('customer.wishlist.delete.before', $id);

        $success = $this->wishlistRepository->deleteWhere([
            'id' => $id,
            'customer_id' => auth()->guard('customer')->user()->id,
        ]);

        Event::dispatch('customer.wishlist.delete.after', $id);

        if (! $success) {
            return new JsonResource([
                'message' => trans('shop::app.customers.account.wishlist.remove-fail'),
            ]);
        }

        $wishlistItems = $this->wishlistRepository
            ->where([
                'customer_id' => auth()->guard('customer')->user()->id,
                'channel_id' => core()->getCurrentChannel()->id,
            ])
            ->get();

        return new JsonResource([
            'data' => WishlistResource::collection($wishlistItems),
            'message' => trans('shop::app.customers.account.wishlist.removed'),
        ]);
    }

    /**
     * Method for removing all items from the wishlist.
     */
    public function destroyAll(): JsonResource
    {
        Event::dispatch('customer.wishlist.delete-all.before');

        $success = $this->wishlistRepository->deleteWhere([
            'customer_id' => auth()->guard('customer')->user()->id,
        ]);

        Event::dispatch('customer.wishlist.delete-all.after');

        if (! $success) {
            return new JsonResource([
                'message' => trans('shop::app.customers.account.wishlist.remove-fail'),
            ]);
        }

        return new JsonResource([
            'message' => trans('shop::app.customers.account.wishlist.removed'),
        ]);
    }

    /**
     * Removing inactive wishlist items.
     *
     * @return int
     */
    protected function removeInactiveItems()
    {
        $customer = auth()->guard('customer')->user();

        $customer->load(['wishlist_items.product']);

        $inactiveItemIds = $customer->wishlist_items
            ->filter(fn ($item) => ! $item->product->status)
            ->pluck('product_id')
            ->toArray();

        return $customer->wishlist_items()
            ->whereIn('product_id', $inactiveItemIds)
            ->delete();
    }

    /**
     * Find the customer's wishlist entry for the product that holds the given options.
     */
    protected function findWishlistItem(Product $product, array $options): ?Wishlist
    {
        return $this->wishlistRepository
            ->findWhere([
                'channel_id' => core()->getCurrentChannel()->id,
                'customer_id' => auth()->guard('customer')->user()->id,
                'product_id' => $product->id,
            ])
            ->first(fn ($wishlistItem) => $product->getTypeInstance()->compareWishlistOptions($wishlistItem->additional, $options));
    }
}
