<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\Pricing\ProductPricingService;
use App\Services\Request\RequestOrchestrator;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\JsonResponse;
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
     */
    public function category(Category $category): View
    {
        $products = $category->products()
            ->where('status', 'published')
            ->where('is_available', true)
            ->orderBy('sort_order')
            ->get();

        return view('catalogue.category', compact('category', 'products'));
    }

    /**
     * Product detail with configuration form.
     */
    public function show(Product $product): View
    {
        if ($product->status !== 'published' || $product->is_available === false) {
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
     * Get price preview for a product (JSON).
     */
    public function quote(HttpRequest $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'nullable|numeric|min:0.01',
            'option_ids' => 'array',
            'option_ids.*' => 'exists:product_option_values,id',
        ]);

        try {
            $quote = $this->pricingService->quote(
                $product,
                $validated['quantity'] ?? null,
                $validated['option_ids'] ?? []
            );

            return response()->json([
                'success' => true,
                'quote' => [
                    'pricing_type' => $quote->pricing_type->value,
                    'unit_price' => $quote->unit_price,
                    'quantity' => $quote->quantity,
                    'unit' => $quote->unit,
                    'subtotal' => $quote->subtotal,
                    'total' => $quote->total,
                    'requires_pmb_quote' => $quote->requires_pmb_quote,
                    'breakdown' => $quote->breakdown,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
