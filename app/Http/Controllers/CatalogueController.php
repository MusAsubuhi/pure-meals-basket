<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Enums\CatalogStatus;
use App\Services\Pricing\PricingException;
use App\Services\Pricing\ProductPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public catalogue for customers.
 *
 * Only requestable items are listed (active status + available +
 * active category). This module does NOT persist anything — quoting is
 * pure computation; order/quotation persistence belongs to the future
 * Request Engine.
 */
class CatalogueController extends Controller
{
    public function __construct(protected ProductPricingService $pricing)
    {
    }

    public function index(): View
    {
        return view('catalogue.index', [
            'categories' => Category::active()
                ->orderBy('sort_order')
                ->withCount(['products' => fn ($q) => $q->requestable()])
                ->get(),
        ]);
    }

    public function category(Category $category): View
    {
        abort_unless($category->is_active, 404);

        return view('catalogue.category', [
            'category' => $category,
            // Active items are listed even when currently unavailable;
            // only truly non-active items are hidden from customers.
            'products' => Product::query()
                ->where('category_id', $category->id)
                ->where('status', CatalogStatus::ACTIVE)
                ->whereHas('category', fn ($q) => $q->where('is_active', true))
                ->orderBy('sort_order')
                ->with(['options.values', 'tiers'])
                ->get(),
        ]);
    }

    public function show(Product $product): View
    {
        // Reachable only while requestable
        abort_unless(
            $product->status->isRequestable()
                && $product->is_available !== false
                && ($product->category?->is_active ?? false),
            404
        );

        return view('catalogue.show', [
            'product' => $product->load(['options.values', 'tiers', 'category']),
        ]);
    }

    /**
     * Live price estimation endpoint used by the product page.
     * The single seam between the catalogue and the future Request Engine.
     */
    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:product'],
            'id' => ['required', 'integer'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'option_value_ids' => ['nullable', 'array'],
            'option_value_ids.*' => ['integer'],
        ]);

        $product = Product::find($validated['id']);

        if (! $product) {
            return response()->json([
                'message' => 'Item not found.',
            ], 404);
        }

        try {
            $quote = $this->pricing->quote(
                $product,
                isset($validated['quantity']) ? (float) $validated['quantity'] : null,
                $validated['option_value_ids'] ?? []
            );
        } catch (PricingException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'requires_pmb_quote' => false,
            ], 422);
        }

        return response()->json($quote->toArray());
    }
}
