<?php

namespace App\Http\Controllers;

use App\Enums\CatalogStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use App\Services\Pricing\InvalidQuantityException;
use App\Services\Pricing\PricingException;
use App\Services\Pricing\ProductPricingService;
use App\Services\Pricing\TierOverflowException;
use App\Services\Pricing\UnavailableItemException;
use App\Services\Request\RequestOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CatalogueBrowseController extends Controller
{
    public function __construct(
        protected ProductPricingService $pricingService,
        protected RequestOrchestrator $orchestrator,
    ) {}

    /**
     * Catalogue landing page - show all categories.
     */
    public function index(): View
    {
        $categories = Category::active()->orderBy('sort_order')->get();

        return view('catalogue.index', compact('categories'));
    }

    /**
     * Products filtered by category.
     * Active items are listed; unavailable ones stay visible but flagged
     * "Currently unavailable" by the view (requestability is enforced
     * server-side by the pricing engine).
     */
    public function category(Category $category): View
    {
        $products = $category->products()
            ->where('status', CatalogStatus::ACTIVE->value)
            ->orderBy('sort_order')
            ->get();

        return view('catalogue.category', compact('category', 'products'));
    }

    /**
     * Product detail with configuration form.
     */
    public function show(Product $product): View
    {
        if ($product->status !== CatalogStatus::ACTIVE) {
            abort(404);
        }

        $cart = $this->orchestrator->cart();
        $inCart = isset($cart["product:{$product->id}"]);

        return view('catalogue.show', compact('product', 'inCart'));
    }

    /**
     * Add to session cart.
     */
    public function add(HttpRequest $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'option_ids' => 'array',
            'option_ids.*' => 'exists:product_option_values,id',
        ]);

        $this->orchestrator->addToCart(
            'product',
            $product->id,
            (float) $validated['quantity'],
            $validated['option_ids'] ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Added to cart',
            'cart_count' => count($this->orchestrator->cart()),
        ]);
    }

    /**
     * Remove from session cart.
     */
    public function remove(string $itemKey): JsonResponse
    {
        $this->orchestrator->removeFromCart($itemKey);

        return response()->json([
            'success' => true,
            'message' => 'Removed from cart',
            'cart_count' => count($this->orchestrator->cart()),
        ]);
    }

    /**
     * View/edit cart.
     */
    public function cart(): View
    {
        $cart = $this->orchestrator->cart();
        $items = [];

        foreach ($cart as $key => $item) {
            $product = Product::find($item['item_id']);
            if ($product) {
                try {
                    $quote = $this->pricingService->quote(
                        $product,
                        $item['quantity'],
                        $item['option_ids'] ?? []
                    );
                    $items[$key] = [
                        'product' => $product,
                        'quantity' => $item['quantity'],
                        'option_ids' => $item['option_ids'] ?? [],
                        'quote' => $quote,
                    ];
                } catch (\Exception $e) {
                    // Skip items that can't be quoted
                }
            }
        }

        $total = collect($items)->sum(fn ($item) => $item['quote']->total ?? 0);
        $requiresQuote = collect($items)->contains(fn ($item) => $item['quote']->requires_pmb_quote);

        return view('request.cart', compact('items', 'total', 'requiresQuote'));
    }

    /**
     * Estimate a price for a catalogue item (public JSON endpoint).
     *
     * POST body: {type: 'product'|'service', id, quantity?, option_ids?|option_value_ids?}
     * Returns top-level quote fields; pricing/unavailability problems
     * surface as a 422 validation error.
     */
    public function quote(HttpRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:product,service'],
            'id' => ['required', 'integer'],
            'quantity' => ['nullable', 'numeric', 'min:0.01'],
            'option_ids' => ['nullable', 'array'],
            'option_ids.*' => ['integer'],
            'option_value_ids' => ['nullable', 'array'],
            'option_value_ids.*' => ['integer'],
        ]);

        $item = $validated['type'] === 'service'
            ? Service::find($validated['id'])
            : Product::find($validated['id']);

        if ($item === null) {
            throw ValidationException::withMessages([
                'id' => ['The requested catalogue item could not be found.'],
            ]);
        }

        $optionIds = array_merge(
            $validated['option_ids'] ?? [],
            $validated['option_value_ids'] ?? []
        );

        try {
            $quote = $this->pricingService->quote(
                $item,
                isset($validated['quantity']) ? (float) $validated['quantity'] : null,
                $optionIds
            );
        } catch (UnavailableItemException|InvalidQuantityException|TierOverflowException|PricingException $e) {
            throw ValidationException::withMessages([
                'quantity' => [$e->getMessage()],
            ]);
        }

        return response()->json([
            'pricing_type' => $quote->pricing_type->value,
            'unit_price' => $quote->unit_price,
            'quantity' => $quote->quantity,
            'unit' => $quote->unit,
            'option_total' => $quote->option_total,
            'subtotal' => $quote->subtotal,
            'total' => $quote->total,
            'currency' => $quote->currency,
            'requires_pmb_quote' => $quote->requires_pmb_quote,
            'breakdown' => $quote->breakdown,
        ]);
    }
}
