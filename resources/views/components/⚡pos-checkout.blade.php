<?php

use App\Models\Product;
use App\Services\PosService;
use function Livewire\Volt\{state, computed};

// 1. Define Reactive Component State
state([
    'search' => '',
    'cart' => [],
    'cashProvided' => 0.0,
    'errorMessage' => '',
    'successMessage' => ''
]);

// 2. Computed Property to dynamically calculate cart total amount
$total = computed(function () {
    return array_reduce($this->cart, function ($carry, $item) {
        return $carry + ($item['price'] * $item['qty']);
    }, 0.0);
});

// 3. Action Logic: Add an item to the reactive cart array
$addToCart = function (int $productId) {
    $product = Product::findOrFail($productId);

    if (isset($this->cart[$productId])) {
        if ($product->stock_level > $this->cart[$productId]['qty']) {
            $this->cart[$productId]['qty']++;
        } else {
            $this->errorMessage = "Cannot add more. Stock limit reached.";
            return;
        }
    } else {
        if ($product->stock_level > 0) {
            $this->cart[$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->selling_price,
                'qty' => 1
            ];
        } else {
            $this->errorMessage = "{$product->name} is out of stock.";
            return;
        }
    }
    $this->errorMessage = '';
    $this->successMessage = '';
};

// 4. Action Logic: Remove an item line completely from cart
$removeFromCart = function (int $productId) {
    unset($this->cart[$productId]);
};

// 5. Action Logic: Handle Checkout execution
$submitCheckout = function (PosService $posService) {
    if (empty($this->cart)) {
        $this->errorMessage = "Cart is empty.";
        return;
    }

    if ($this->cashProvided < $this->total) {
        $this->errorMessage = "Cash provided is less than the total amount due.";
        return;
    }

    try {
        $posService->checkout($this->cart, $this->cashProvided);
        $this->cart = [];
        $this->cashProvided = 0.0;
        $this->successMessage = "Transaction successful!";
        $this->errorMessage = '';
    } catch (\Exception $e) {
        $this->errorMessage = $e->getMessage();
    }
};

// 6. Dynamic Catalog queries mapped to active searching input
$products = Product::with('category')
    ->where('name', 'like', '%' . $this->search . '%')
    ->orWhere('sku', 'like', '%' . $this->search . '%')
    ->take(12)
    ->get();

?>