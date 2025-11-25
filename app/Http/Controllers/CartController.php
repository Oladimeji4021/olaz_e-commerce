<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CartRequest;
use App\Models\Cart;
use App\Models\Product;

class CartController extends Controller
{
    /**
     * Add product to cart
     */
    public function add(CartRequest $request)
    {
        $user = $request->user();
        $product = Product::findOrFail($request->product_id);

        // Check if product already exists in cart
        $cartItem = Cart::where('user_id', $user->id)
                        ->where('product_id', $product->id)
                        ->first();

        if ($cartItem) {
            $cartItem->quantity += $request->quantity;
            $cartItem->save();
        } else {
            $cartItem = Cart::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'price' => $product->price,
            ]);
        }

        return response()->json([
            'message' => 'Product added to cart successfully',
            'cart' => $cartItem
        ]);
    }

    /**
     * Get all cart items for authenticated user
     */
    public function index(Request $request)
    {
        $cart = Cart::with('product')
                    ->where('user_id', $request->user()->id)
                    ->get();

        return response()->json($cart);
    }

    /**
     * Update quantity of a cart item
     */
   public function update(Request $request, $id)
{
    $request->validate([
        'quantity' => 'required|integer|min:1',
    ]);

    $cartItem = Cart::where('user_id', $request->user()->id)
                    ->where('id', $id)
                    ->firstOrFail();

    $cartItem->quantity = $request->quantity;
    $cartItem->save();

    return response()->json([
        'message' => 'Cart updated successfully',
        'cart' => $cartItem
    ]);
}


    /**
     * Remove a cart item
     */
    public function remove(Request $request, $id)
    {
        $cartItem = Cart::where('user_id', $request->user()->id)
                        ->where('id', $id)
                        ->firstOrFail();

        $cartItem->delete();

        return response()->json(['message' => 'Product removed from cart']);
    }


    /**
 * Clear all cart items for authenticated user
 */
public function clear(Request $request)
{
    $user = $request->user();

    $cartItems = Cart::where('user_id', $user->id)->get();

    if ($cartItems->isEmpty()) {
        return response()->json([
            'message' => 'Your cart is already empty'
        ]);
    }

    Cart::where('user_id', $user->id)->delete();

    return response()->json([
        'message' => 'All items removed from cart successfully'
    ]);
}

}
