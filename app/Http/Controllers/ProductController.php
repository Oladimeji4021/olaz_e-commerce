<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // Public: list products
    public function index()
    {
        $products = Product::latest()->get();

        // convert image path to URL if present
        $products->transform(function ($p) {
            if ($p->image) {
                $p->image = asset('storage/products/' . $p->image);
            }
            return $p;
        });

        return response()->json($products);
    }

    // Public: show single product
    public function show($id)
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        if ($product->image) {
            $product->image = asset('storage/products/' . $product->image);
        }

        return response()->json($product);
    }

    // Admin only (ProductRequest handles authorize)
    public function store(ProductRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $filename = time() . '_' . $request->file('image')->getClientOriginalName();
            $request->file('image')->storeAs('products', $filename, 'public');
            $validated['image'] = $filename;
        }

        $product = Product::create($validated);

        if ($product->image) {
            $product->image = asset('storage/products/' . $product->image);
        }

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }

    // Admin only
    public function update(ProductRequest $request, $id)
    {
        $product = Product::find($id);
        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $validated = $request->validated();

        if ($request->hasFile('image')) {
            // delete old image if exists
            if ($product->image && Storage::disk('public')->exists('products/' . $product->image)) {
                Storage::disk('public')->delete('products/' . $product->image);
            }
            $filename = time() . '_' . $request->file('image')->getClientOriginalName();
            $request->file('image')->storeAs('products', $filename, 'public');
            $validated['image'] = $filename;
        }

        $product->update($validated);

        if ($product->image) {
            $product->image = asset('storage/products/' . $product->image);
        }

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product
        ]);
    }

    // Admin only
    public function destroy(Request $request, $id)
    {
        // ProductRequest authorize won't run here automatically because we used Request type.
        // So double-check admin status manually (keep consistent)
        $user = $request->user();
        if (! $user || $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product = Product::find($id);
        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        if ($product->image && Storage::disk('public')->exists('products/' . $product->image)) {
            Storage::disk('public')->delete('products/' . $product->image);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
