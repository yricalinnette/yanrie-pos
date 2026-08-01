<?php

namespace App\Mail;
namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Category;
use App\Models\ProductPriceHistory;
use App\Models\StockAdjustment;
use App\Services\PosService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
use Exception;

class PosCheckout extends Component
{
    use WithPagination;

    // Global Navigation State Variables
    public string $activeTab = 'pos'; 
    public string $search = '';
    public array $cart = [];
    public float $cashProvided = 0.0;
    public float $discount = 0.0; 
    
    // Status Feedback Messages
    public string $errorMessage = '';
    public string $successMessage = '';

    // Modal Receipt Management State Variables
    public bool $showReceipt = false;
    public ?Sale $lastSale = null;
    public array $receiptItems = [];

    // Form States
    public $new_sku = '';
    public $new_name = '';
    public $new_category_id = '';
    public $new_cost = 0.00;
    public $new_selling = 0.00;
    public $new_stock = 0;       // Serves as "Current Stock Display" in Edit Mode / "Initial Stock" in Create Mode
    public $new_stock_to_add = 0;
    public $new_threshold = 5;

    // Mode Flags & Selection helpers
    public $is_edit_mode = false;
    public $editing_product_id = null;
    
    // Manual Stock Correction states
    public $show_adjust_modal = false;
    public $selected_product_for_audit = null;
    public $audit_new_stock_value;
    public $audit_remarks_message = 'Physical inventory count reconciliation';

    protected $queryString = ['activeTab', 'search'];

    public $purchase_filter = 'daily'; // Default filter option for Purchase History
    public $sales_filter = 'daily'; // Default sales view window filter for Sales Report
    public $supplier_name = '';
    public $selected_supplier = '';
    public $selected_month;
    public $selected_day;
    public $selected_quarter;
    public $selected_year;

    public $searchBarcode = ''; // Bound to your main scanner/SKU entry field
    public $weighedProduct = null; // Holds the target item when the scale overlay is active
    public $manualWeight = ''; // Bound to the input text box inside the weight modal

    public $transaction_date;

    // Add these public properties if you haven't already
    public $new_cost_per_box = 0;
    public $new_items_per_box = 0;
    public $profit_margin_percent = 10;
    public $num_boxes = 0;
    public $new_selling_price;
    public $new_delivery_date;
    public $is_wholesale = false;
    public $new_wholesale_price;
    public $create_retail_variant = false;
    public $new_retail_price;
    public $new_wholesale_qty = 0;
    public $reports_sub_tab = 'retail_rice';
    public $selectedCategory;

    public $settings_sub_tab = 'categories'; // 'categories' or 'suppliers'

    // Category Form States
    public $category_id = null;
    public $category_name = '';
    public $show_category_modal = false;

    // Supplier Form States
    public $supplier_id = null;
    public $supplier_contact = '';
    public $supplier_phone = '';
    public $supplier_address = '';
    public $show_supplier_modal = false;
    public $supplier_remarks;

    public $showHistoryModal = false;
    public $selectedProductForHistory = null;
    public $selected_ricemill_product = '';

    public $audit_delivery_date;

    // for Digital Transaction
    public $digital_service_type = 'gcash_cash_in';
    public $digital_amount;
    public $digital_fee;
    public $digital_phone;
    public $digital_ref;
    public $show_capital_form = false; // Toggles the entry box
    public $inject_type = 'cash';      // 'cash' or 'gcash'
    public $inject_amount;             // The amount you are adding
    public $editing_tx_id = null; // Holds the ID of the record being updated
    public $digital_date; // Tracks custom dates for backtracking
    public $filter_type = 'daily';   // 'daily' or 'monthly'
    public $filter_date;             // Holds YYYY-MM-DD for daily scopes
    public $filter_month;            // Holds YYYY-MM for monthly scopes

    public $viewHiddenAlerts = false;
    public $other_product_category = 'all';
    
    public $selectedSackVariant = [];
    public $sackQuantities = [];
    public $backdatedSackDate = [];
    public $filter_service = '';

    public function togglePriceMode()
    {
        $this->is_wholesale = !$this->is_wholesale;
    }

    public function updatingSearch()
    {
        $this->resetPage('productsPage');
    }

    // Lifecycle hooks to trigger math
    public function updatedNewCostPerBox($value)
    {
        $this->calculateUnitCost();
    }

    public function switchToPos()
    {
        // 1. Change the tab focus area state target
        $this->activeTab = 'pos';

        // 2. Clear out any active POS terminal search queries and category filters
        $this->search = '';
        $this->selectedCategory = '';
        
        // Optional: If your POS view handles paginated item catalog cards, 
        // reset its page cursor to the front frame panel line list:
        // $this->resetPage('posProductsPage'); 
    }

    public function calculateUnitCost()
    {
        // 1. Ensure values are treated as numeric floats
        $costPerBox = is_numeric($this->new_cost_per_box) ? (float) $this->new_cost_per_box : 0.00;
        $itemsPerBox = is_numeric($this->new_items_per_box) ? (float) $this->new_items_per_box : 0.00;

        // 2. Prevent division by zero
        if ($itemsPerBox > 0) {
            $calculatedCost = $costPerBox / $itemsPerBox;
            
            // 3. Enforce strict 2-decimal string formatting and cast back to float
            $this->new_cost = (float) number_format(round($calculatedCost, 2), 2, '.', '');
        } else {
            $this->new_cost = 0.00;
        }
    }

    public function updatedNumBoxes($value)
    {
        $this->calculateNewStock();
    }

    // Modify your existing ItemsPerBox hook if necessary
    public function updatedNewItemsPerBox($value)
    {
        $this->calculateUnitCost(); // From previous step
        $this->calculateNewStock();
    }

    public function calculateNewStock()
    {
        // The core calculation: (Number of Boxes) * (Items Per Box)
        $this->new_stock_to_add = (float)$this->num_boxes * (float)$this->new_items_per_box;
    }

    public function updated($propertyName)
    {
        // 1. Sanitize base values safely, falling back to 0 if input becomes empty
        $cost = is_numeric($this->new_cost) ? round((float) $this->new_cost, 2) : 0.00;
        $margin = is_numeric($this->profit_margin_percent) ? round((float) $this->profit_margin_percent, 2) : 0.00;
        $price = is_numeric($this->new_selling_price) ? round((float) $this->new_selling_price, 2) : 0.00;

        // 2. Handle changes to Box Procurement variables (Cost & Calculations)
        if (in_array($propertyName, ['new_cost_per_box', 'new_items_per_box', 'num_boxes'])) {
            $costPerBox  = is_numeric($this->new_cost_per_box) ? (float) $this->new_cost_per_box : 0.00;
            $itemsPerBox = is_numeric($this->new_items_per_box) ? (float) $this->new_items_per_box : 0.00;
            $numBoxes    = is_numeric($this->num_boxes) ? (float) $this->num_boxes : 0.00;

            // A. If items per box changes, update Unit Cost & Price
            if ($itemsPerBox > 0 && in_array($propertyName, ['new_cost_per_box', 'new_items_per_box'])) {
                // Strict 2-decimal truncation string to clear float architecture noise
                $calculatedCost = $costPerBox / $itemsPerBox;
                $this->new_cost = (float) number_format(round($calculatedCost, 2), 2, '.', '');
                
                // Re-evaluate Selling Price using the newly standardized clean Unit Cost
                $calculatedPrice = $this->new_cost * (1 + ($margin / 100));
                $this->new_selling_price = (float) number_format(round($calculatedPrice, 2), 2, '.', '');
            }

            // B. 📊 AUTO-CALCULATE INITIAL STOCK (Only if it's a NEW product registration!)
            if (!$this->is_edit_mode) {
                if ($numBoxes > 0 && $itemsPerBox > 0) {
                    $this->new_stock = $numBoxes * $itemsPerBox;
                } else {
                    $this->new_stock = 0; // Reset if fields are cleared
                }
            }

            return; // Break execution early to avoid duplicate processing loops
        }

        // 3. If Unit Cost or Margin changes, update Selling Price
        if (in_array($propertyName, ['new_cost', 'profit_margin_percent'])) {
            $calculatedPrice = $cost * (1 + ($margin / 100));
            $this->new_selling_price = (float) number_format(round($calculatedPrice, 2), 2, '.', '');
            $this->new_cost = (float) number_format($cost, 2, '.', '');
        }

        // 4. If Selling Price changes manually, back-calculate Margin %
        if ($propertyName === 'new_selling_price') {
            if ($cost > 0) {
                $calculatedMargin = (($price - $cost) / $cost) * 100;
                $this->profit_margin_percent = (float) number_format(round($calculatedMargin, 2), 2, '.', '');
            } else {
                $this->profit_margin_percent = 0.00;
            }
            $this->new_selling_price = (float) number_format($price, 2, '.', '');
        }
    }

    public function mount()
    {
        // Initialize with current date and time in the format required by HTML <input type="datetime-local">
        $this->transaction_date = now()->format('Y-m-d\TH:i');
        $this->selected_month = now()->format('Y-m');
        $this->selected_day = date('Y-m-d');
        $this->new_delivery_date = date('Y-m-d');
        $this->cart = [];
        $this->search = '';
        $this->selected_quarter = ceil(date('n') / 3);
        $this->selected_year = (int)date('Y');
        $this->selectedCategory = '';
        $this->digital_date = now()->format('Y-m-d');
        $this->filter_date  = now()->format('Y-m-d');
        $this->filter_month = now()->format('Y-m');
    }

    // Ensure you reset/handle logic when filter changes
    public function updatedSalesFilter()
    {
        // Optional: Reset month or trigger a data refresh
        $this->resetPage();
    }

    public function updatedSelectedCategory()
    {
        $this->resetPage('productsPage');
    }

    public function scanBarcode()
    {
        if (empty($this->searchBarcode)) return;

        // Query by your table's exact SKU column
        $product = Product::where('sku', $this->searchBarcode)->first();

        if (!$product) {
            // Your existing product-not-found handler logic goes here...
            $this->searchBarcode = '';
            return;
        }

        // 💡 SCANNER INTERCEPTION: If the item requires manual scale weight entry
        if ($product->is_weighed) {
            $this->weighedProduct = $product;
            $this->manualWeight = '';
            
            // Fires a window event so JavaScript immediately forces keyboard focus on the modal input field
            $this->dispatch('focus-weight-prompt'); 
            return;
        }

        // Standard discrete pieces pass right through to your standard cart array
        $this->addToCart($product, 1);
        $this->searchBarcode = '';
    }

    /**
     * Adds the calculated loose fractional mass to your checkout line-items array
     */
    public function confirmWeighedItem()
    {
        $this->validate([
            'manualWeight' => 'required|numeric|gt:0',
        ]);

        // Inject the fractional decimal weight directly as the quantity parameters
        $this->addToCart($this->weighedProduct, floatval($this->manualWeight));

        // Flush tracking values out of memory to receive the next item sweep
        $this->weighedProduct = null;
        $this->searchBarcode = '';
        $this->manualWeight = '';
    }

    /**
     * 🔓 BACKEND MANUAL ACTION: Converted to handle multi-weight breakdown options (5kg, 10kg, 25kg, 50kg)
     * Decants selected custom-weight storage packaging sizes directly into the loose retail rice display box.
     */
    public function breakSack($productId)
    {
        $retailProduct = Product::findOrFail($productId);

        // Retrieve input values from state arrays or fall back to defaults
        $chosenVariantId = $this->selectedSackVariant[$productId] ?? null;
        $sacksToConvert = (int)($this->sackQuantities[$productId] ?? 1);
        
        // Get chosen backtrack date from the datepicker input array
        $customDate = $this->backdatedSackDate[$productId] ?? null;

        // Guard 1: Enforce a selection choice
        if (!$chosenVariantId) {
            session()->flash('error', "Please select which sack size you are opening from the list option dropdown.");
            return;
        }

        $wholesaleProduct = Product::findOrFail($chosenVariantId);

        // Guard 2: Verify adequate backroom stock of selected packaging unit
        if ($wholesaleProduct->stock_level < $sacksToConvert) {
            session()->flash('error', "Insufficient stock. You only have " . number_format($wholesaleProduct->stock_level, 0) . " units left of {$wholesaleProduct->name}.");
            return;
        }

        // Determine weight conversion value dynamically based on your product settings.
        $weightPerUnit = (float)$wholesaleProduct->conversion_factor;
        if ($weightPerUnit <= 0) {
            preg_match('/(\d+)\s*(?:kg|kls|kilo)/i', $wholesaleProduct->name, $matches);
            $weightPerUnit = isset($matches[1]) ? (float)$matches[1] : 50.00; 
        }

        $totalKilosToAdd = $weightPerUnit * $sacksToConvert;

        // Strict Date Assignment: Locks down the final delivery date to your custom selection
        if (!empty($customDate)) {
            $timestamp = \Carbon\Carbon::createFromFormat('Y-m-d', $customDate, 'Asia/Manila')
                ->setTime(now('Asia/Manila')->hour, now('Asia/Manila')->minute, now('Asia/Manila')->second);
            $finalDeliveryDate = $customDate; 
        } else {
            $timestamp = now('Asia/Manila');
            $finalDeliveryDate = $timestamp->format('Y-m-d');
        }

        // DB Transaction execution block
        DB::transaction(function () use ($wholesaleProduct, $retailProduct, $sacksToConvert, $totalKilosToAdd, $weightPerUnit, $finalDeliveryDate, $timestamp) {
            // 1. Deduct chosen count directly from specific packaging variant stock
            $wholesaleProduct->decrement('stock_level', $sacksToConvert);

            // 2. Deposit calculated net poundage weight straight into loose display box counter
            $retailProduct->increment('stock_level', $totalKilosToAdd);

            // 🚀 3. LOG BATCH TRAIL FOR AUDITING AND BACKTRACKING
            $formattedTimestamp = $timestamp->toDateTimeString();

            $retailProduct->batches()->create([
                'supplier_id'    => $wholesaleProduct->supplier_id ?: null,
                'cost_price'     => (float)$retailProduct->cost_price, 
                'selling_price'  => (float)$retailProduct->selling_price, 
                'quantity_added' => (float)$totalKilosToAdd,
                'delivery_date'  => $finalDeliveryDate,
                'num_boxes'      => $sacksToConvert,
                'items_per_box'  => (int)$weightPerUnit,
                'cost_per_box'   => (float)$wholesaleProduct->cost_price,
                'remarks'        => "Added Ricebox conversion from " . ($wholesaleProduct->name ?? 'Sack'),
                'created_at'     => $formattedTimestamp, 
                'updated_at'     => $formattedTimestamp,
            ]);
        });

        // Reset loop state tracking arrays for this row item
        unset($this->selectedSackVariant[$productId]);
        unset($this->sackQuantities[$productId]);
        unset($this->backdatedSackDate[$productId]);

        session()->flash('message', "Successfully split {$sacksToConvert} sack(s) of {$wholesaleProduct->name}! Injected +{$totalKilosToAdd}kg into loose retail stock bins.");
    }

    // this is for manually adding of items to the cart
    public function addToCart($productId, $qty = 1)
    {
        if (!is_array($this->cart)) {
            $this->cart = [];
        }

        $product = Product::find($productId);
        if (!$product) return;

        $existingIndex = collect($this->cart)->search(function ($item) use ($productId) {
            return $item['id'] === $productId;
        });

        if ($existingIndex !== false) {
            $this->cart[$existingIndex]['qty'] += $qty;
            
            // 💡 UX safety feature: If they switch to wholesale later but increase quantity here,
            // it retains whatever price mode they had toggled.
        } else {
            $this->cart[] = [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'qty' => $qty,
                'is_weighed' => $product->is_weighed,
                
                // 🌟 Keep current tracking architecture but save baselines
                'retail_price' => (float)$product->selling_price,
                'wholesale_price' => $product->wholesale_price ? (float)$product->wholesale_price : null,
                'wholesale_qty' => $product->wholesale_qty ? (int)$product->wholesale_qty : null,
                
                // 🌟 Defaults
                'price_mode' => 'retail',
                'price' => (float)$product->selling_price,
            ];
        }
    }

    public function toggleItemPriceMode($index, $mode)
    {
        if (isset($this->cart[$index])) {
            // Update price_mode metadata key for this specific line index entry only
            $this->cart[$index]['price_mode'] = $mode;

            if ($mode === 'wholesale' && !empty($this->cart[$index]['wholesale_price'])) {
                $this->cart[$index]['price'] = (float)$this->cart[$index]['wholesale_price'];
            } else {
                $this->cart[$index]['price'] = (float)$this->cart[$index]['retail_price'];
            }
        }
    }

    public function increaseQty($index)
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['qty']++;
        }
    }

    public function decreaseQty($index)
    {
        if (isset($this->cart[$index])) {
            if ($this->cart[$index]['qty'] > 1) {
                $this->cart[$index]['qty']--;
            } else {
                // Remove item completely if reduced past 1
                unset($this->cart[$index]);
                $this->cart = array_values($this->cart); // Re-index array indices cleanly
            }
        }
    }

    // update quantity in the cart manually
    public function updateQuantity(int $productId, $newQty): void
    {
        $qtyFloat = (float)$newQty;

        // 1. Find the index correctly
        $key = collect($this->cart)->search(function ($item) use ($productId) {
            return $item['id'] === $productId;
        });

        if ($key === false) return; 

        // 2. Handle removal
        if ($qtyFloat <= 0) {
            $this->removeFromCart($productId);
            return;
        }

        // 3. Stock validation
        $product = Product::with('category')->findOrFail($productId);
        
        if ($qtyFloat > (float)$product->stock_level) {
            $this->errorMessage = "Only {$product->stock_level} available.";
            $this->cart[$key]['qty'] = (float)$product->stock_level;
            return;
        }

        // 4. Update the cart using the found $key
        $this->cart[$key]['qty'] = $qtyFloat;
        $this->errorMessage = '';
    }

    public function removeFromCart(int $productId): void
    {
        // 1. Search for the key where the product ID matches
        $key = collect($this->cart)->search(function ($item) use ($productId) {
            return $item['id'] === $productId;
        });

        // 2. If the key was found, remove it
        if ($key !== false) {
            unset($this->cart[$key]);
            
            // 3. Optional: Re-index the array so it doesn't leave gaps
            $this->cart = array_values($this->cart);
        }
    }

    public function getSubtotalAmount(): float
    {
        $subtotal = 0.0;
        foreach ($this->cart as $item) {
            $productId = $item['id'] ?? null;
            $product = Product::find($productId);
            $itemQty = (float)($item['qty'] ?? 1);
            
            if ($product) {
                $isWholesaleActive = ($this->is_wholesale || (isset($this->pricing_mode) && strpos(strtolower($this->pricing_mode), 'wholesale') !== false) || ($item['price_mode'] ?? 'retail') === 'wholesale');
                
                if ($isWholesaleActive && (float)$product->wholesale_price > 0) {
                    $itemPrice = (float)$product->wholesale_price;
                } else {
                    $itemPrice = (float)$product->selling_price;
                }
            } else {
                $itemPrice = (float)($item['price'] ?? 0.0);
            }
            $subtotal += $itemPrice * $itemQty;
        }
        return $subtotal;
    }

    public function getTotalAmount(): float
    {
        $actualDiscount = is_numeric($this->discount) ? floatval($this->discount) : 0.0;
        return max(0.0, $this->getSubtotalAmount() - $actualDiscount);
    }

    // submit transaction in the Cart
    public function submitCheckout(PosService $posService): void
    {
        $totalAmountDue = $this->getTotalAmount();
        $actualCash = is_numeric($this->cashProvided) ? floatval($this->cashProvided) : 0.0;
        $actualDiscount = is_numeric($this->discount) ? floatval($this->discount) : 0.0;

        $this->validate([
            'transaction_date' => 'required|date',
            'cashProvided' => ['required', 'numeric', 'min:' . $totalAmountDue],
        ], [
            'cashProvided.min' => 'Insufficient cash processing funds allocated!',
        ]);

        if (empty($this->cart)) {
            $this->errorMessage = "Transaction aborted. Sales cart is empty.";
            return;
        }

        try {
            $processedCart = [];
            foreach ($this->cart as $key => $item) {
                $productId = $item['id'];
                $product = Product::find($productId);
                $itemQty = (float)$item['qty'];
                
                if ($product) {
                    $isWholesaleItem = ($this->is_wholesale || ($item['price_mode'] ?? 'retail') === 'wholesale');
                    $itemPrice = ($isWholesaleItem && (float)$product->wholesale_price > 0) ? (float)$product->wholesale_price : (float)$product->selling_price;

                    $processedCart[$key] = [
                        'id' => $productId,
                        'product_id' => $productId,
                        'qty' => $itemQty,
                        'quantity' => $itemQty, 
                        'price' => $itemPrice,
                        'unit_price' => $itemPrice,
                        'is_wholesale' => $isWholesaleItem,
                        'subtotal' => $itemPrice * $itemQty
                    ];
                }
            }

            $sale = $posService->checkout($processedCart, $actualCash, $actualDiscount, $this->transaction_date);
            
            $this->receiptItems = $processedCart;
            $this->lastSale = $sale;
            $this->showReceipt = true;

            $this->dispatch('checkout-completed');

            $this->reset(['cart', 'cashProvided', 'discount', 'errorMessage']);
            $this->successMessage = "Checkout transaction finalized successfully!";
            $this->transaction_date = now()->format('Y-m-d\TH:i');
            
        } catch (\Exception $e) {
            $this->errorMessage = "Error processing checkout: " . $e->getMessage();
        }
    }

    public function restockProduct(int $productId, $quantity): void
    {
        $qtyInt = (int)$quantity;
        if ($qtyInt <= 0) {
            $this->errorMessage = "Restock value calculation parameters must exceed 0 units.";
            return;
        }

        $product = Product::findOrFail($productId);
        $product->increment('stock_level', $qtyInt);

        // Optional: If you track adjustments via a dedicated logger table, 
        // you can record it here as a historical fallback log.
        StockAdjustment::create([
            'product_id' => $product->id,
            'delta' => $qtyInt,
            'type' => 'restock'
        ]);

        $this->successMessage = "Successfully updated stock levels for {$product->name}! (+{$qtyInt} Units)";
        $this->errorMessage = '';
    }

    public function createNewProduct(): void
    {
        $this->validate([
            'new_name' => 'required|string|max:255',
            'new_sku' => 'required|string|unique:products,sku',
            'new_cost' => 'required|numeric|min:0',
            'new_selling' => 'required|numeric|min:0',
            'new_stock' => 'required|integer|min:0',
            // Simple validation rule for the plain string input
            'supplier_name' => 'nullable|string|max:255', 
        ]);

        Product::create([
            'name' => $this->new_name,
            'sku' => $this->new_sku,
            'category_id' => $this->new_category_id,
            'cost_price' => $this->new_cost,
            'selling_price' => $this->new_selling,
            'stock_level' => $this->new_stock,
            'low_stock_threshold' => $this->new_threshold,
            // UPDATED: Saves the plain text supplier entry string directly into the column
            'supplier_name' => !empty($this->supplier_name) ? trim($this->supplier_name) : null,
        ]);

        $this->successMessage = "{$this->new_name} has been successfully added to the database!";
        
        // Reset form variables cleanly including the supplier input field
        $this->reset([
            'new_name', 'new_sku', 'new_category_id', 'new_cost', 
            'new_selling', 'new_stock', 'new_threshold', 'supplier_name'
        ]);
        $this->errorMessage = '';
    }

    public function closeReceipt(): void
    {
        $this->showReceipt = false;
        $this->lastSale = null;
        $this->receiptItems = [];
    }

    public function render()
    {
        // 🌾 Fetch all ACTIVE Rice management items completely separate from pagination
        $riceManagementProducts = Product::with(['category', 'parent'])
            ->where('is_active', 1) // 📦 Only include active products
            ->where(function($query) {
                $query->whereHas('category', function($q) {
                    $q->where('name', 'like', '%rice%');
                })->orWhere('name', 'like', '%rice%');
            })
            ->orderBy('name', 'asc')
            ->get();

        // 1. Core Catalog Search Query (ACTIVE products only)
        $products = Product::with(['category', 'supplier']) // 🚀 Eager load supplier model relation entries
            ->where('is_active', 1) // 📦 Scopes out archived products from the inventory table
            ->where(function($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('sku', 'like', '%' . $this->search . '%');
            })
            ->when(!empty($this->selectedCategory), function($query) {
                $query->where('category_id', $this->selectedCategory);
                $query->orderBy('name', 'asc');
            }, function($query) {
                $query->latest();
            })
            ->paginate(12, ['*'], 'productsPage');

        // 📦 Low Stock Counter (Updated to ignore archived products)
        $lowStockCount = \App\Models\Product::where('is_active', 1) 
            ->where('hide_from_alerts', false)
            ->whereRaw('stock_level <= low_stock_threshold')
            ->count();

        // ⚙️ SETTINGS ENGINE: Fresh Database Library Source Queries (Sorted Alphabetically)
        $categoriesAlphabetical = \App\Models\Category::orderBy('name', 'asc')->get();
        $suppliersLibrary = \App\Models\Supplier::orderBy('name', 'asc')->get();

        // 🚀 CRITICAL FIX: Instantiate the missing sales queries so metrics build correctly!
        $salesQuery = \App\Models\Sale::with(['items.product']);
        $transactionsLogQuery = \App\Models\Sale::with(['items.product']);

        // Apply Timeframe Constraints to Sales Queries
        switch ($this->sales_filter ?? 'daily') {
            case 'daily':
                $targetDay = !empty($this->selected_day) ? $this->selected_day : date('Y-m-d');
                $salesQuery->whereDate('created_at', $targetDay);
                $transactionsLogQuery->whereDate('created_at', $targetDay);
                break;

            case 'monthly':
                $year = substr($this->selected_month, 0, 4);
                $month = substr($this->selected_month, 5, 2);
                $salesQuery->whereYear('created_at', $year)->whereMonth('created_at', $month);
                $transactionsLogQuery->whereYear('created_at', $year)->whereMonth('created_at', $month);
                break;

            case 'quarterly':
                $currentYear = date('Y');
                $quarter = (int)($this->selected_quarter ?? ceil(date('n') / 3));
                $startMonth = ($quarter - 1) * 3 + 1;
                $endMonth = $quarter * 3;

                $startDate = "{$currentYear}-" . str_pad($startMonth, 2, '0', STR_PAD_LEFT) . "-01 00:00:00";
                $endDate = \Carbon\Carbon::parse("{$currentYear}-" . str_pad($endMonth, 2, '0', STR_PAD_LEFT) . "-01")->endMonth()->toDateTimeString();

                $salesQuery->whereBetween('created_at', [$startDate, $endDate]);
                $transactionsLogQuery->whereBetween('created_at', [$startDate, $endDate]);
                break;

            case 'yearly':
                $targetYear = !empty($this->selected_year) ? (int)$this->selected_year : (int)date('Y');
                $salesQuery->whereYear('created_at', $targetYear);
                $transactionsLogQuery->whereYear('created_at', $targetYear);
                break;
        }

        // Apply Segment Sub-Tab Filter Constraints
        if (($this->reports_sub_tab ?? '') === 'retail_rice') {
            $salesQuery->whereHas('items.product', function ($query) {
                $query->where('category_id', 1)->where('sku', 'like', 'KILO-%');
            });
            $transactionsLogQuery->whereHas('items.product', function ($query) {
                $query->where('category_id', 1)->where('sku', 'like', 'KILO-%');
            });
        } elseif (($this->reports_sub_tab ?? '') === 'whole_rice') {
            $salesQuery->whereHas('items.product', function ($query) {
                $query->where('category_id', 1)->where('sku', 'not like', 'KILO-%');
            });
            $transactionsLogQuery->whereHas('items.product', function ($query) {
                $query->where('category_id', 1)->where('sku', 'not like', 'KILO-%');
            });
        } elseif (($this->reports_sub_tab ?? '') === 'other_products') {
            
            // ✨ FIX: Check if a specific numeric category ID is selected 
            if (!empty($this->other_product_category) && is_numeric($this->other_product_category)) {
                $selectedCatId = (int)$this->other_product_category;
                
                $salesQuery->whereHas('items.product', function ($query) use ($selectedCatId) {
                    $query->where('category_id', $selectedCatId);
                });
                $transactionsLogQuery->whereHas('items.product', function ($query) use ($selectedCatId) {
                    $query->where('category_id', $selectedCatId);
                });
            } else {
                // Fallback: If "All Non-Rice Categories" is chosen, show everything except Rice (ID 1)
                $salesQuery->whereHas('items.product', function ($query) {
                    $query->where('category_id', '!=', 1);
                });
                $transactionsLogQuery->whereHas('items.product', function ($query) {
                    $query->where('category_id', '!=', 1);
                });
            }
        }

        // Safe Process matching data metrics
        $dailyRevenue = 0.00;
        $dailyDiscounts = 0.00;
        $exactProfitTotal = 0.00;
        $cigaretteRevenueInsideOther = 0.00;
        $dailyTransactionsCount = $this->todayTransactions->count();

        (clone $salesQuery)->where('is_cancelled', false)
            ->chunkById(100, function ($sales) use (&$dailyRevenue, &$dailyDiscounts, &$exactProfitTotal) {
                foreach ($sales as $sale) {
                    $saleGrossRevenue = 0.00;
                    $saleGrossMargin = 0.00;
                    $entireInvoiceGrossTotal = 0.00; 
                    $hasMatchingItems = false;

                    // 1. Calculate Entire Invoice Gross Total to handle proportional discount splits safely
                    foreach ($sale->items as $item) {
                        if (!$item->product) continue;
                        $actualPrice = (float)($item->unit_price ?? ($item->price ?? ($item->product->selling_price ?? 0.00)));
                        $entireInvoiceGrossTotal += $actualPrice * (float)$item->quantity;
                    }

                    // Avoid division by zero issues
                    if ($entireInvoiceGrossTotal <= 0) continue;

                    // 2. Loop again to extract segment values
                    foreach ($sale->items as $item) {
                        if (!$item->product) continue;

                        $productCategoryId = (int)$item->product->category_id;
                        $isRice = ($productCategoryId === 1);
                        $isKilo = str_starts_with($item->product->sku, 'KILO-');

                        // Segment evaluations matching Front-end Tabs
                        if (($this->reports_sub_tab ?? '') === 'retail_rice' && (!$isRice || !$isKilo)) continue;
                        if (($this->reports_sub_tab ?? '') === 'whole_rice' && (!$isRice || $isKilo)) continue;
                        
                        if (($this->reports_sub_tab ?? '') === 'other_products') {
                            if ($isRice) continue; // CRITICAL: Exclude any Rice totals completely from calculations

                            // Apply the dynamic category filter condition during deep metric extraction
                            if (!empty($this->other_product_category) && is_numeric($this->other_product_category)) {
                                if ($productCategoryId !== (int)$this->other_product_category) continue;
                            }
                        }

                        $hasMatchingItems = true;

                        $actualUnitPricePaid = (float)($item->unit_price ?? ($item->price ?? ($item->product->selling_price ?? 0.00)));
                        
                        // Fetch the purchase item cost from historical row or product master fallback safely
                        $unitCost = (float)($item->cost_price ?? ($item->product->cost_price ?? 0.00));
                        $quantity = (float)$item->quantity;

                        $itemTotalPrice = $actualUnitPricePaid * $quantity;
                        $saleGrossRevenue += $itemTotalPrice;

                        // 🛠️ FIX: Determine if unitCost represents a single item or a whole package to avoid inflation
                        if (!empty($item->is_wholesale)) {
                            // Check if cost_price inside database is already wholesale (e.g. > single unit retail item price)
                            if ($unitCost > $actualUnitPricePaid) {
                                // The database stored cost price is already a large sack wholesale value, treat as single item package cost
                                $saleGrossMargin += ($itemTotalPrice - ($unitCost * $quantity));
                            } else {
                                // The database tracks singular base kg cost, apply the package scale multiplier
                                $multiplier = ($item->product->wholesale_qty > 0) ? (float)$item->product->wholesale_qty : 1.0;
                                $totalCostForThisPackage = $unitCost * $multiplier;
                                $saleGrossMargin += ($actualUnitPricePaid - $totalCostForThisPackage) * $quantity;
                            }
                        } else {
                            // Standard Retail Math: Total Item Paid minus Total Base Cost Matrix
                            $saleGrossMargin += ($actualUnitPricePaid - $unitCost) * $quantity;
                        }
                    }

                    if ($hasMatchingItems) {
                        // 3. Determine how much of the invoice discount belongs to this tab segment
                        $segmentRatio = $saleGrossRevenue / $entireInvoiceGrossTotal;
                        $allocatedDiscount = (float)$sale->discount * $segmentRatio;

                        // 4. Deduct allocated discounts to find true NET metrics
                        $dailyRevenue += ($saleGrossRevenue - $allocatedDiscount);
                        $dailyDiscounts += $allocatedDiscount;
                        $exactProfitTotal += ($saleGrossMargin - $allocatedDiscount);
                    }
                }
            });

        $todayTransactions = $transactionsLogQuery->orderBy('id', 'desc')->paginate(20, ['*'], 'salesLogPage');

        // 4. Procurement Management System Filtering Logic & Consolidation
        // 🚀 Eager load product and supplier entities
        $purchaseBaseQuery = \App\Models\ProductBatch::with([
            'product.stockAdjustments', 
            'supplier'
        ])
        /** * 🔥 CRITICAL UPDATE: Filter out retail items and zero-quantity logs at the source.
         * Excludes items where SKU starts with "KILO" and ignores rows with a 0 quantity change.
         */
        ->whereHas('product', function($query) {
            $query->where('sku', 'NOT LIKE', 'KILO%');
        })
        ->where('quantity_added', '!=', 0); // 👈 Exclude raw 0 modifications

        // Apply dynamic delivery date timeframe constraints
        switch ($this->purchase_filter ?? 'daily') {
            case 'daily': 
                $targetDay = !empty($this->selected_day) ? $this->selected_day : date('Y-m-d');
                $purchaseBaseQuery->whereDate('delivery_date', $targetDay); 
                break;

            case 'monthly': 
                $targetMonthStr = !empty($this->selected_month) ? $this->selected_month : date('Y-m');
                $year = substr($targetMonthStr, 0, 4);
                $month = substr($targetMonthStr, 5, 2);
                
                $purchaseBaseQuery->whereYear('delivery_date', $year)
                                ->whereMonth('delivery_date', $month); 
                break;

            case 'quarterly': 
                $purchaseBaseQuery->whereBetween('delivery_date', [
                    now()->startOfQuarter()->toDateString(), 
                    now()->endOfQuarter()->toDateString()
                ]); 
                break;

            case 'yearly': 
                $targetYear = !empty($this->selected_year) ? (int)$this->selected_year : (int)date('Y');
                $purchaseBaseQuery->whereYear('delivery_date', $targetYear); 
                break;
        }

        // 🏢 Filter by Supplier directly on the batch's indexing column
        if (!empty($this->selected_supplier)) {
            $purchaseBaseQuery->where('supplier_id', $this->selected_supplier);
        }

        // 🌾 DYNAMIC SECOND-LEVEL AQUARIUS RICEMILL PRODUCT FILTER ENGINE
        $aquariusRicemill = \App\Models\Supplier::where('name', 'LIKE', '%Aquarius Ricemill%')->first();
        $aquariusId = $aquariusRicemill ? $aquariusRicemill->id : null;
        $ricemillProducts = [];

        if (!empty($this->selected_supplier) && $this->selected_supplier == $aquariusId) {
            // Collect a distinct catalog array of all non-retail products loaded from this mill, sorted alphabetically
            $ricemillProducts = \App\Models\ProductBatch::with('product')
                ->where('supplier_id', $aquariusId)
                ->whereHas('product', function($q) {
                    $q->where('sku', 'NOT LIKE', 'KILO%');
                })
                ->get()
                ->pluck('product')
                ->filter()
                ->unique('id')
                ->sortBy('name') // 👈 Sorts the collection alphabetically by product name
                ->values();

            // If a valid secondary product filter option is active, isolate it inside the builder layout context
            if (!empty($this->selected_ricemill_product)) {
                $purchaseBaseQuery->where('product_id', $this->selected_ricemill_product);
            }
        } else {
            // Safe Lifecycle Reset: Flush state if focus jumps to a different supplier profile entirely
            $this->selected_ricemill_product = '';
        }

        $totalPurchaseCost = 0;
        $estimatedRetailRevenue = 0;

        // Financial calculators naturally process non-zero entities now
        (clone $purchaseBaseQuery)->chunkById(100, function ($batches) use (&$totalPurchaseCost, &$estimatedRetailRevenue) {
            foreach ($batches as $batch) {
                $costPrice = (float)($batch->cost_price ?? ($batch->product->cost_price ?? 0));
                $sellingPrice = (float)($batch->selling_price ?? ($batch->product->selling_price ?? 0));
                $qty = (float)($batch->quantity_added ?? 0);

                if ($qty > 0) {
                    $totalPurchaseCost += ($qty * $costPrice);
                    $estimatedRetailRevenue += ($qty * $sellingPrice);
                }
            }
        });

        $projectedProcurementMargin = $estimatedRetailRevenue - $totalPurchaseCost;

        // 🔄 CONSOLIDATION ENGINE
        // Note: Handled properly, SUM(quantity_added) will naturally drop combinations that aggregate exactly to 0
        $loggedBatches = $purchaseBaseQuery->select([
                'product_id',
                'supplier_id',
                'delivery_date',
                \DB::raw('MAX(id) as id'), 
                \DB::raw('SUM(quantity_added) as quantity_added'), 
                \DB::raw('MAX(cost_price) as cost_price'), 
                \DB::raw('MAX(selling_price) as selling_price'),
                \DB::raw('MAX(num_boxes) as num_boxes'),
                \DB::raw('MAX(items_per_box) as items_per_box'),
                \DB::raw('MAX(created_at) as created_at')
            ])
            ->groupBy('product_id', 'supplier_id', 'delivery_date')
            ->having('quantity_added', '!=', 0) // 👈 Protects grouped consolidations from totaling to 0
            ->orderBy('delivery_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25, ['*'], 'purchasesPage');

        // 5. Checkout Basket Subtotals
        $cartSubtotal = $this->getSubtotalAmount();
        $liveCalculatedTotal = $this->getTotalAmount();

        // 📱 For digital transactions =================
        $digitalQuery = \App\Models\DigitalTransaction::where('is_active', true);

        // 🔥 1. Apply Service Type Filter Dropdown Dynamically
        if (!empty($this->filter_service)) {
            $digitalQuery->where('service_type', $this->filter_service);
        }

        // 2. Apply Timeframe Scope Filter dynamically
        if ($this->filter_type === 'daily') {
            $digitalQuery->whereDate('logged_at', $this->filter_date);
        } else {
            $carbonMonth = Carbon::parse($this->filter_month);
            $digitalQuery->whereYear('logged_at', $carbonMonth->year)
                        ->whereMonth('logged_at', $carbonMonth->month);
        }

        // Execute Paginated Query for the history log table matrix
        $digitalTransactions = $digitalQuery->latest()->paginate(10, ['*'], 'digitalPage');

        // 🛠️ DYNAMIC BACKTRACKING MODIFICATION START
        // 1. Establish the historical point-in-time boundary for the balances snapshot
        $endOfPeriod = now('Asia/Manila');

        if ($this->filter_type === 'daily' && $this->filter_date) {
            $endOfPeriod = Carbon::parse($this->filter_date, 'Asia/Manila')->endOfDay();
        } elseif ($this->filter_type === 'monthly' && $this->filter_month) {
            $endOfPeriod = Carbon::parse($this->filter_month . '-01', 'Asia/Manila')->endOfMonth();
        }

        // 2. Query cumulative sums from day one up until that historical boundary timestamp
        // (Keep these unfiltered by service type so your absolute running ledger stays mathematically correct)
        $runningCashOnHand = \App\Models\DigitalTransaction::where('is_active', true)
            ->where('logged_at', '<=', $endOfPeriod)
            ->sum('cash_on_hand_change');

        $runningGCashBalance = \App\Models\DigitalTransaction::where('is_active', true)
            ->where('logged_at', '<=', $endOfPeriod)
            ->sum('gcash_balance_change');
        // 🛠️ DYNAMIC BACKTRACKING MODIFICATION END

        // 3. Calculate filtered shifts based on active records inside the selected filters scope
        $totalCashMovement = (clone $digitalQuery)->sum('cash_on_hand_change');
        $totalGCashMovement = (clone $digitalQuery)->sum('gcash_balance_change');
        $totalFeesEarned = (clone $digitalQuery)->sum('service_fee');

        // 4. Volume Breakdown Sums (Scoped strictly to the active date + service platform filters)
        $totalCashInVolume   = (clone $digitalQuery)->where('service_type', 'gcash_cash_in')->sum('amount');
        $totalCashOutVolume  = (clone $digitalQuery)->where('service_type', 'gcash_cash_out')->sum('amount');
        $totalELoadVolume    = (clone $digitalQuery)->where('service_type', 'eload')->sum('amount');

        return view('livewire.pos-checkout', [
            'riceManagementProducts'     => $riceManagementProducts,
            'products'                   => $products,
            'lowStockCount'              => $lowStockCount,
            'availableSuppliers'         => $suppliersLibrary, 
            'dailyRevenue'               => $dailyRevenue,
            'dailyDiscounts'             => $dailyDiscounts,
            'dailyTransactionsCount'     => $dailyTransactionsCount,
            
            // 🔒 FIX HERE: Provide both key handles to prevent variable undefined errors in Blade layouts
            'exactProfitTotal'           => $exactProfitTotal, 
            'dailyProfitBaseline'        => $exactProfitTotal,
            'cigaretteRevenueInsideOther'=> $cigaretteRevenueInsideOther,

            'todayTransactions'          => $todayTransactions,
            'purchaseLogs'               => $loggedBatches,
            'loggedBatches'              => $loggedBatches, 
            'totalPurchaseCost'          => $totalPurchaseCost,
            'estimatedRetailRevenue'     => $estimatedRetailRevenue,
            'projectedProcurementMargin' => $projectedProcurementMargin,
            'subtotal'                   => $cartSubtotal,
            'total'                      => $liveCalculatedTotal,
            'categories'                 => $categoriesAlphabetical,
            'categoriesAlphabetical'     => $categoriesAlphabetical, 
            'suppliers'                  => $suppliersLibrary,
            'aquariusId'                 => $aquariusId,
            'ricemillProducts'           => $ricemillProducts,
            'digitalTransactions'        => $digitalTransactions,
            'totalCashMovement'          => $totalCashMovement,
            'totalGCashMovement'         => $totalGCashMovement,
            'totalFeesEarned'            => $totalFeesEarned,
            'runningCashOnHand'          => $runningCashOnHand,
            'runningGCashBalance'        => $runningGCashBalance,
            'totalCashInVolume'          => $totalCashInVolume,
            'totalCashOutVolume'         => $totalCashOutVolume,
            'totalELoadVolume'           => $totalELoadVolume,
        ]);
    }

    public function updatedReportsSubTab()
    {
        // Resets the sales log pagination counter back to page 1 upon filter swaps
        $this->resetPage('salesLogPage');
    }

    public function getTodayTransactionsProperty()
    {
        $query = Sale::query();

        if ($this->sales_filter === 'daily') {
            $query->whereDate('created_at', now()->format('Y-m-d'));
        } 
        elseif ($this->sales_filter === 'monthly') {
            // Use the selected_month (e.g., '2026-05')
            $query->whereYear('created_at', substr($this->selected_month, 0, 4))
                ->whereMonth('created_at', substr($this->selected_month, 5, 2));
        }
        // ... add logic for quarterly/yearly

        return $query->latest()->paginate(10);
    }

    // 3. New Action: Toggle cancellation state status on a target invoice
    public function cancelSale(int $saleId): void
    {
        $sale = Sale::with('items')->findOrFail($saleId);

        DB::transaction(function () use ($sale) {
            $previousStatus = $sale->is_cancelled;
            $sale->is_cancelled = !$previousStatus;
            $sale->save();

            foreach ($sale->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $multiplier = 1;
                    if ($item->is_wholesale || str_contains(strtolower($item->product_name ?? ''), '(wholesale)')) {
                        $multiplier = ($product->wholesale_qty > 0) ? $product->wholesale_qty : 1;
                    }
                    $calculatedQty = $item->quantity * $multiplier;

                    if (!$previousStatus && $sale->is_cancelled) {
                        $product->increment('stock_level', $calculatedQty);
                    } elseif ($previousStatus && !$sale->is_cancelled) {
                        $product->decrement('stock_level', $calculatedQty);
                    }
                }
            }
        });
    }

    public function reprintSale(int $saleId): void
    {
        $sale = Sale::with(['items.product'])->findOrFail($saleId);
        $this->receiptItems = [];
        
        if ($sale->items && $sale->items->count() > 0) {
            foreach ($sale->items as $item) {
                $unitPrice = $item->price ?? ($item->selling_price ?? ($item->product->selling_price ?? 0.00));
                $itemName = $item->product_name ?? ($item->product->name ?? 'Unknown Item');

                $this->receiptItems[$item->product_id] = [
                    'id' => $item->product_id,
                    'name' => $itemName,
                    'price' => (float)$unitPrice,
                    'qty' => (float)$item->quantity
                ];
            }
        }

        $this->lastSale = $sale;
        $this->showReceipt = true;
        $this->successMessage = "Loaded receipt data for Invoice #" . str_pad($saleId, 6, '0', STR_PAD_LEFT);
    }

    // This auto-calculates the suggested retail price as you type the cost
    public function updatedNewSellingPrice($value)
    {
        // If it's rice/bulk and the toggle is on, suggest the kilo price
        if ($this->create_retail_variant && (float)$value > 0) {
            $this->new_retail_price = round(((float)$value / 50) + 5, 2);
        }
    }

    // Ensure the retail price field appears if they toggle it ON
    public function updatedCreateRetailVariant($value)
    {
        if ($value && $this->new_selling_price) {
            $this->updatedNewSellingPrice($this->new_selling_price);
        }
    }

    /**
     * Unified Handler to Register or Update Product Records
     */
    public function saveProductRegistryEntry()
    {
        // 🚀 FAST ENTRY FIX: If SKU is blank during creation, auto-generate a unique temporary barcode
        if (!$this->is_edit_mode && empty(trim($this->new_sku))) {
            // Generates a clean, unique identifier like: TEMP-1719782400 or TEMP64C2B9
            $this->new_sku = 'TEMP-' . time() . '-' . rand(10, 99);
        }

        // 1. AUTO-CALCULATION
        if ($this->new_cost_per_box > 0 && $this->new_items_per_box > 0) {
            $this->new_cost = (float)$this->new_cost_per_box / (float)$this->new_items_per_box;
        }

        // 2. VALIDATION
        $isEdit = $this->is_edit_mode && !empty($this->editing_product_id);
        $isRiceProduct = ($this->new_category_id == 1) || str_contains(strtolower($this->new_name), 'rice');

        // Determine if wholesale pricing is actively configured
        $hasWholesalePrice = !empty($this->new_wholesale_price) && $this->new_wholesale_price > 0;

        $this->validate([
            // 💡 VALIDATION UPDATE: Allow nullable so users can completely skip it on the frontend
            'new_sku' => 'required|string|unique:products,sku,' . ($this->editing_product_id ?? 'NULL'),
            'new_name' => 'required|string|max:255',
            'new_category_id' => 'required|exists:categories,id',
            'new_cost' => 'required|numeric|min:0',
            'new_selling_price' => 'required|numeric|min:0',
            'new_wholesale_price' => 'nullable|numeric|min:0',
            
            // 💡 VALIDATION RULE: If a wholesale price is given, the deduction quantity must be at least 1
            'new_wholesale_qty' => $hasWholesalePrice ? 'required|numeric|min:1' : 'nullable|numeric|min:0',
            
            'new_stock' => $isEdit ? 'nullable|numeric' : ($isRiceProduct ? 'required|numeric|min:0' : 'required|integer|min:0'),
            'new_threshold' => 'required|numeric|min:0',
            'new_delivery_date' => 'required|date',
            'supplier_id' => 'nullable|exists:suppliers,id', 
        ]);

        DB::transaction(function () use ($isEdit, $hasWholesalePrice) {
            $mainProduct = null;
            $quantityAdded = 0;

            $finalWholesaleQty = ($hasWholesalePrice && (int)$this->new_wholesale_qty > 0) 
                ? (int)$this->new_wholesale_qty 
                : null;

            if ($isEdit) {
                // --- UPDATE EXISTING ---
                $mainProduct = Product::findOrFail($this->editing_product_id);
                $oldStock = $mainProduct->stock_level;
                
                $quantityAdded = (float)($this->new_stock_to_add ?? 0);
                $newCalculatedStock = $oldStock + $quantityAdded;

                // 1. Update the Main Product Entry (Added 'sku' tracking)
                $mainProduct->update([
                    'sku'                 => $this->new_sku, // 👈 ALLOW SKU UPDATES
                    'name'                => $this->new_name,
                    'category_id'         => $this->new_category_id,
                    'cost_price'          => (float)$this->new_cost,
                    'selling_price'       => (float)$this->new_selling_price,
                    'wholesale_price'     => $hasWholesalePrice ? (float)$this->new_wholesale_price : null,
                    'wholesale_qty'       => $finalWholesaleQty, 
                    'stock_level'         => $newCalculatedStock,
                    'low_stock_threshold' => $this->new_threshold,
                    'supplier_id'         => $this->supplier_id ?: null,
                    'delivery_date'       => $this->new_delivery_date,
                ]);
                
                // 2. Process Batch Updates / Creations
                $targetDeliveryDate = $this->new_delivery_date ?? now()->format('Y-m-d');
                $targetSupplierId   = $this->supplier_id ?: null;

                // Smart Check Context: Check if delivery date, supplier, and category match the baseline info
                $isSameContext = ($targetDeliveryDate === $mainProduct->getOriginal('delivery_date')) &&
                                ($targetSupplierId == $mainProduct->getOriginal('supplier_id')) &&
                                ($this->new_category_id == $mainProduct->getOriginal('category_id'));

                if ($isSameContext) {
                    // 🔄 SAME CONTEXT: Match and update the active row entry row
                    $existingBatch = $mainProduct->batches()
                        ->where('delivery_date', $targetDeliveryDate)
                        ->where('supplier_id', $targetSupplierId)
                        ->first();

                    if ($existingBatch) {
                        $existingBatch->update([
                            'cost_per_box'   => !empty($this->new_cost_per_box) ? (float)$this->new_cost_per_box : null,
                            'items_per_box'  => !empty($this->new_items_per_box) ? (int)$this->new_items_per_box : null,
                            'num_boxes'      => !empty($this->num_boxes) ? (int)$this->num_boxes : null,
                            'cost_price'     => (float)$this->new_cost,
                            'selling_price'  => (float)$this->new_selling_price,
                            'quantity_added' => $existingBatch->quantity_added + $quantityAdded,
                        ]);
                    } else {
                        // Fallback to creation if database context matches but initial batch record was missing
                        $mainProduct->batches()->create([
                            'supplier_id'    => $targetSupplierId,
                            'cost_per_box'   => !empty($this->new_cost_per_box) ? (float)$this->new_cost_per_box : null,
                            'items_per_box'  => !empty($this->new_items_per_box) ? (int)$this->new_items_per_box : null,
                            'num_boxes'      => !empty($this->num_boxes) ? (int)$this->num_boxes : null,
                            'cost_price'     => (float)$this->new_cost,
                            'selling_price'  => (float)$this->new_selling_price,
                            'quantity_added' => $quantityAdded,
                            'delivery_date'  => $targetDeliveryDate,
                        ]);
                    }
                } else {
                    // ➕ DIFFERENT CONTEXT (New Date, Supplier, or Category modification detected): 
                    // Forces a pristine standalone record row to safely track delivery history
                    $mainProduct->batches()->create([
                        'supplier_id'    => $targetSupplierId,
                        'cost_per_box'   => !empty($this->new_cost_per_box) ? (float)$this->new_cost_per_box : null,
                        'items_per_box'  => !empty($this->new_items_per_box) ? (int)$this->new_items_per_box : null,
                        'num_boxes'      => !empty($this->num_boxes) ? (int)$this->num_boxes : null,
                        'cost_price'     => (float)$this->new_cost,
                        'selling_price'  => (float)$this->new_selling_price,
                        'quantity_added' => $quantityAdded, // Captures only the incoming change payload
                        'delivery_date'  => $targetDeliveryDate,
                    ]);
                }

                session()->flash('message', 'Product updated successfully.');
            } else {
                // --- CREATE NEW PRODUCT REGISTRY ENTRY ---
                $quantityAdded = (float)$this->new_stock;

                $mainProduct = Product::create([
                    'sku'                 => $this->new_sku,
                    'name'                => $this->new_name,
                    'category_id'         => $this->new_category_id,
                    'cost_price'          => (float)$this->new_cost,
                    'selling_price'       => (float)$this->new_selling_price,
                    'wholesale_price'     => $hasWholesalePrice ? (float)$this->new_wholesale_price : null,
                    'wholesale_qty'       => $finalWholesaleQty, 
                    'stock_level'         => $quantityAdded,
                    'low_stock_threshold' => $this->new_threshold,
                    'supplier_id'         => $this->supplier_id ?: null,
                    'delivery_date'       => $this->new_delivery_date,
                    'is_weighed'          => false,
                ]);

                if ($quantityAdded > 0) {
                    $mainProduct->batches()->create([
                        'supplier_id'    => $this->supplier_id ?: null,
                        'cost_per_box'   => !empty($this->new_cost_per_box) ? (float)$this->new_cost_per_box : null,
                        'items_per_box'  => !empty($this->new_items_per_box) ? (int)$this->new_items_per_box : null,
                        'num_boxes'      => !empty($this->num_boxes) ? (int)$this->num_boxes : null,
                        'cost_price'     => (float)$this->new_cost,
                        'selling_price'  => (float)$this->new_selling_price,
                        'quantity_added' => $quantityAdded,
                        'delivery_date'  => $this->new_delivery_date ?? now()->format('Y-m-d'),
                    ]);
                }

                session()->flash('message', 'New product created successfully.');
            }

            // --- RETAIL VARIANT LOGIC (Auto-updates descriptive strings) ---
            if ($this->create_retail_variant && $mainProduct) {
                // 💡 Lookup primarily via parent_id to prevent duplicate creation when changing SKUs
                $retailProduct = Product::where('parent_id', $mainProduct->id)->first();

                if (!$retailProduct) {
                    $retailProduct = new Product();
                    $retailProduct->parent_id = $mainProduct->id;
                    $retailProduct->stock_level = 0.00;
                    $retailProduct->low_stock_threshold = 10;
                }

                $retailProduct->fill([
                    'category_id'       => $mainProduct->category_id,
                    'name'              => $mainProduct->name . ' (Retail/Kilo)', 
                    'sku'               => 'KILO-' . $mainProduct->sku, // 👈 Dynamically inherits the updated barcode
                    'is_weighed'        => true,
                    'conversion_factor' => 50,
                    'cost_price'        => (float)$mainProduct->cost_price / 50,
                    'selling_price'     => (float)$this->new_retail_price,
                    'supplier_id'       => $mainProduct->supplier_id,
                    'delivery_date'     => $mainProduct->delivery_date,
                ]);

                $retailProduct->save();
            }
        });

        // Reset Form Fields
        $this->reset([
            'new_sku', 'new_name', 'new_cost', 'new_selling_price', 'new_wholesale_price', 'new_wholesale_qty',
            'new_stock', 'new_stock_to_add', 'new_threshold', 'supplier_id', 
            'editing_product_id', 'is_edit_mode', 'create_retail_variant', 'new_retail_price',
            'new_cost_per_box', 'new_items_per_box', 'num_boxes' // Clear bulk options on finish
        ]);
        
        $this->new_wholesale_qty = 0; // 💡 Clear/Reset form baseline back to 0
        $this->new_delivery_date = date('Y-m-d');
        $this->dispatch('focus-barcode');
    }

    public function updatedNewSku($value)
    {
        session()->forget('message');   
        
        // 1. Clean up incoming scanned string values
        $skuValue = trim($value);
        
        // 💡 FIX 1: If we are ALREADY in edit mode, do NOT search for another product.
        // This allows the user to overwrite a "TEMP-xxxx" barcode with a real one safely.
        if ($this->is_edit_mode && !empty($this->editing_product_id)) {
            return;
        }

        if (empty($skuValue)) {
            if (method_exists($this, 'resetEditFormState')) {
                $this->resetEditFormState();
            } else {
                $this->resetFormInputs();
            }
            return;
        }

        $product = \App\Models\Product::where('sku', $skuValue)->first();

        if ($product) {
            // --- PRODUCT FOUND: POPULATE EDIT MODE STATE ---
            $this->is_edit_mode = true;
            $this->editing_product_id = $product->id;
            $this->new_name = $product->name;
            $this->new_category_id = $product->category_id;
            $this->new_delivery_date = $product->delivery_date;
            $this->new_cost = $product->cost_price;
            $this->new_selling_price = $product->selling_price;
            $this->new_wholesale_price = $product->wholesale_price;
            $this->new_wholesale_qty = $product->wholesale_qty ?? 0; 
            $this->new_stock = $product->stock_level;
            $this->new_threshold = $product->low_stock_threshold;
            $this->supplier_id = $product->supplier_id; 

            // 🕒 Pull packaging context properties from the latest delivery record
            $latestBatch = $product->batches()
                ->orderByDesc('delivery_date')
                ->orderByDesc('id')
                ->first();

            if ($latestBatch) {
                $this->new_cost_per_box  = $latestBatch->cost_per_box;
                $this->new_items_per_box = $latestBatch->items_per_box;
                $this->num_boxes          = $latestBatch->num_boxes;
            } else {
                $this->new_cost_per_box  = null;
                $this->new_items_per_box = null;
                $this->num_boxes          = null;
            }

            // 💡 FIX 2: Check for retail variants via parent_id primarily to adapt to dynamic SKU modifications safely
            $retailVariant = \App\Models\Product::where('parent_id', $product->id)
                ->orWhere('sku', 'KILO-' . $product->sku)
                ->first();

            if ($retailVariant) {
                $this->create_retail_variant = true;
                $this->new_retail_price = $retailVariant->selling_price;
            } else {
                $this->create_retail_variant = false;
                $this->new_retail_price = null;
            }

        } else {
            // --- PRODUCT NOT FOUND: PRESERVE THE BARCODE FOR ENCODING ---
            $this->is_edit_mode = false;
            $this->editing_product_id = null;

            // Clear out properties safely
            $this->reset([
                'new_name', 'new_cost', 'new_selling_price', 'new_wholesale_price', 
                'new_stock', 'new_stock_to_add', 'new_threshold', 'supplier_id', 
                'create_retail_variant', 'new_retail_price',
                'new_cost_per_box', 'new_items_per_box', 'num_boxes'
            ]);

            $this->new_sku = $skuValue; 
            $this->new_wholesale_qty = 0;
            $this->new_delivery_date = date('Y-m-d');
        }
    }

    public function loadProductForEdit($id)
    {
        $product = Product::findOrFail($id);

        // Track the active product ID context
        $this->editing_product_id = $product->id;
        $this->is_edit_mode = true;

        // Map database properties directly to form wire:models
        $this->new_sku = $product->sku;
        $this->new_name = $product->name;
        $this->new_delivery_date = $product->delivery_date;
        $this->supplier_id = $product->supplier_id;
        $this->new_category_id = $product->category_id;
        
        $this->new_cost = $product->cost_price;
        $this->new_selling_price = $product->selling_price;
        $this->new_wholesale_price = $product->wholesale_price;
        $this->new_wholesale_qty = $product->wholesale_qty;
        $this->new_stock = $product->stock_level;
        $this->new_threshold = $product->low_stock_threshold;
        $this->new_stock_to_add = 0; // Reset stock adjustment value placeholder

        // 🕒 LOOKUP THE LATEST BATCH CONFIGURATION
        // Grabs the newest batch entry by delivery date, falling back to creation order
        $latestBatch = $product->batches()
            ->orderByDesc('delivery_date')
            ->orderByDesc('id')
            ->first();

        if ($latestBatch) {
            $this->new_cost_per_box  = $latestBatch->cost_per_box;
            $this->new_items_per_box = $latestBatch->items_per_box;
            $this->num_boxes          = $latestBatch->num_boxes;
        } else {
            // Fallback clean state if no historical batches exist yet
            $this->new_cost_per_box  = null;
            $this->new_items_per_box = null;
            $this->num_boxes          = null;
        }

        // If your edit workflow requires scroll focus back to top input:
        $this->dispatch('focus-barcode');
    }

    public function openAuditAdjustmentModal(int $productId)
    {
        $product = Product::findOrFail($productId);
        $this->selected_product_for_audit = $product->toArray();
        $this->audit_new_stock_value = $product->stock_level;
        $this->audit_remarks_message = '';
        $this->show_adjust_modal = true;
        $this->audit_delivery_date = date('Y-m-d');
    }

    public function processAuditedStockCorrection()
    {
        $this->validate([
            'audit_new_stock_value' => 'required|numeric|min:0',
            'audit_delivery_date'   => 'required|date|before_or_equal:today', // Safeguard against accidental future-dating logs
            'audit_remarks_message' => 'required|string|min:4',
        ]);

        \DB::transaction(function() {
            $product = Product::findOrFail($this->selected_product_for_audit['id']);
            $oldStock = (float)$product->stock_level;
            $newStock = (float)$this->audit_new_stock_value; 
            $delta = $newStock - $oldStock;

            // 1. Maintain Standard Discrepancy Registry Log
            StockAdjustment::create([
                'product_id' => $product->id,
                'old_stock'  => $oldStock,
                'new_stock'  => $newStock,
                'delta'      => $delta,
                'remarks'    => $this->audit_remarks_message,
            ]);

            // 🚀 2. LOG NEW ROW ENTRY IN PRODUCT_BATCHES WITH CUSTOM DELIVERY DATE
            // Passing the user's tracked back-date allows accurate chronological consolidation!
            $product->batches()->create([
                'supplier_id'    => $product->supplier_id ?: null,
                'cost_price'     => (float)$product->cost_price,
                'selling_price'  => (float)$product->selling_price,
                'quantity_added' => $delta, 
                'delivery_date'  => $this->audit_delivery_date, // 📅 Injected dynamic historical target date
                'num_boxes'      => null, 
                'items_per_box'  => null,
                'cost_per_box'   => null,
            ]);

            // 3. Apply the absolute new figure down to the main product index card counters
            $product->update(['stock_level' => $newStock]);
        });

        $this->show_adjust_modal = false;
        session()->flash('message', 'Discrepancy audit log and batch history trails backdated successfully.');
        $this->dispatch('focus-barcode');
    }

    public function resetFormInputs()
    {
        $this->new_sku = '';
        $this->new_name = '';
        $this->new_category_id = '';
        $this->new_cost_per_box = 0;
        $this->new_items_per_box = 0;
        $this->new_cost = 0;
        $this->profit_margin_percent = 0;
        $this->new_selling_price = 0; 
        $this->new_wholesale_price = 0; // Added: Reset wholesale price
        $this->new_stock = 0;
        $this->new_stock_to_add = 0;
        $this->new_threshold = 5;
        $this->supplier_name = '';
        $this->is_edit_mode = false;
        $this->editing_product_id = null;
        $this->new_delivery_date = null; // Included for completeness
    }

    // this is for the scanning of barcode product and add to Cart directly
    public function scanAndAddToCart()
    {
        $sku = trim($this->search);
        
        if (empty($sku)) {
            return;
        }

        // Find product
        $product = Product::where('sku', $sku)->first();

        if (!$product) {
            $this->errorMessage = "Product not found for SKU: " . $sku;
            $this->search = ''; 
            return;
        }

        // Call the unified add-to-cart logic
        $this->addToCart($product->id, 1);

        // Clear search and reset state
        $this->search = ''; 
        $this->errorMessage = '';
        
        // Trigger JS focus event to keep scanner active
        $this->dispatch('focus-barcode-input');
    }

    public function exportToExcel()
    {
        // Initialize query baselines matching your exact configuration
        $salesQuery = \App\Models\Sale::with(['items.product']);

        // Apply exact same filter constraints based on user choice
        switch ($this->sales_filter) {
            case 'daily':
                $targetDay = !empty($this->selected_day) ? $this->selected_day : date('Y-m-d');
                $salesQuery->whereDate('created_at', $targetDay);
                $filename = "sales_report_daily_{$targetDay}.csv";
                break;

            case 'monthly':
                $year = substr($this->selected_month, 0, 4);
                $month = substr($this->selected_month, 5, 2);
                $salesQuery->whereYear('created_at', $year)->whereMonth('created_at', $month);
                $filename = "sales_report_monthly_{$this->selected_month}.csv";
                break;

            case 'quarterly':
                $currentYear = date('Y');
                $quarter = (int)($this->selected_quarter ?? ceil(date('n') / 3));

                $startMonth = ($quarter - 1) * 3 + 1;
                $endMonth = $quarter * 3;

                $startDate = "{$currentYear}-" . str_pad($startMonth, 2, '0', STR_PAD_LEFT) . "-01 00:00:00";
                $endDate = Carbon::parse("{$currentYear}-" . str_pad($endMonth, 2, '0', STR_PAD_LEFT) . "-01")->endOfMonth()->toDateTimeString();

                $salesQuery->whereBetween('created_at', [$startDate, $endDate]);
                $filename = "sales_report_q{$quarter}_{$currentYear}.csv";
                break;

            case 'yearly':
                $targetYear = !empty($this->selected_year) ? (int)$this->selected_year : (int)date('Y');
                $salesQuery->whereYear('created_at', $targetYear);
                $filename = "sales_report_yearly_{$targetYear}.csv";
                break;
        }

        // Apply the exact same conditional visibility parameters used on the main dashboard cards
        $filteredSalesMetrics = (clone $salesQuery)->where('is_cancelled', false)->get();

        // Stream and download directly as an Excel-readable spreadsheet file
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($filteredSalesMetrics) {
            $file = fopen('php://output', 'w');
            
            // Add Excel BOM to format the Philippine Peso symbols (₱) beautifully
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Column Header Layout
            fputcsv($file, ['Invoice Receipt ID', 'Date & Timestamp', 'Purchased Items Breakdown', 'Net Collection Revenue (₱)', 'Discounts Awarded (₱)', 'Actual Net Profit (₱)']);

            $accumulatedRevenue = 0.00;
            $accumulatedDiscounts = 0.00;
            $accumulatedProfit = 0.00;

            foreach ($filteredSalesMetrics as $sale) {
                $itemDetails = [];
                $grossSaleMargin = 0.00;

                foreach ($sale->items as $item) {
                    $qty = (float)$item->quantity;
                    $itemDetails[] = ($item->product_name ?? ($item->product->name ?? 'Item')) . " (×{$qty})";
                    
                    // --- EXACT ACTUAL PROFIT CALCULATION PATTERN MATCH ---
                    if ($item->product) {
                        $unitCost = (float)$item->product->cost_price;
                        $unitSelling = (float)$item->product->selling_price; 

                        // Math: (Selling Price - Cost Price) * Qty Sold
                        $itemNetProfit = ($unitSelling - $unitCost) * $qty;
                        $grossSaleMargin += $itemNetProfit;
                    }
                }

                // Exact Net Profit = Total item profit minus the overall receipt discount
                $exactSaleNetProfit = $grossSaleMargin - (float)$sale->discount;

                // Update bottom summary trackers
                $accumulatedRevenue += (float)$sale->total_amount;
                $accumulatedDiscounts += (float)$sale->discount;
                $accumulatedProfit += $exactSaleNetProfit;

                fputcsv($file, [
                    '#' . str_pad($sale->id, 6, '0', STR_PAD_LEFT),
                    $sale->created_at->timezone('Asia/Manila')->format('M d, Y h:i A'),
                    implode(' | ', $itemDetails),
                    number_format($sale->total_amount, 2, '.', ''),
                    number_format($sale->discount, 2, '.', ''),
                    number_format($exactSaleNetProfit, 2, '.', '')
                ]);
            }

            // Structural visual spacer row
            fputcsv($file, []);

            // Bottom Summary Metric Row mapping your 4 display cards identically
            fputcsv($file, [
                'GRAND FINANCIAL SUMMARY', 
                '', 
                '', 
                number_format($accumulatedRevenue, 2, '.', ''), 
                number_format($accumulatedDiscounts, 2, '.', ''), 
                number_format($accumulatedProfit, 2, '.', '')
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // =========================================================================
    //  CATEGORY CRUD LOGIC
    // =========================================================================
    public function openCategoryModal($id = null)
    {
        $this->resetErrorBag();
        if ($id) {
            $category = \App\Models\Category::findOrFail($id);
            $this->category_id = $category->id;
            $this->category_name = $category->name;
        } else {
            $this->category_id = null;
            $this->category_name = '';
        }
        $this->show_category_modal = true;
    }

    public function saveCategory()
    {
        $this->validate([
            // Ensures category name is unique, ignoring current ID if editing
            'category_name' => 'required|string|max:255|unique:categories,name,' . $this->category_id,
        ], [
            'category_name.unique' => 'This category segment already exists.',
        ]);

        \App\Models\Category::updateOrCreate(
            ['id' => $this->category_id],
            ['name' => trim($this->category_name)]
        );

        $this->show_category_modal = false;
        session()->flash('settings_success', 'Category library updated!');
    }

    public function deleteCategory($id)
    {
        // Prevent deletion if products are attached
        $category = \App\Models\Category::findOrFail($id);
        if (\App\Models\Product::where('category_id', $id)->exists()) {
            session()->flash('settings_error', 'Cannot delete category. It is tied to existing items.');
            return;
        }
        $category->delete();
        session()->flash('settings_success', 'Category removed successfully.');
    }

    // =========================================================================
    //  SUPPLIER CRUD LOGIC
    // =========================================================================
    public function openSupplierModal($id = null)
    {
        $this->resetErrorBag();
        if ($id) {
            $supplier = \App\Models\Supplier::findOrFail($id);
            $this->supplier_id = $supplier->id;
            $this->supplier_name = $supplier->name;
            $this->supplier_contact = $supplier->contact_person;
            $this->supplier_phone = $supplier->phone;
            $this->supplier_address = $supplier->address;
            $this->supplier_remarks = $supplier->remarks; // Load field value from DB
        } else {
            $this->supplier_id = null;
            $this->supplier_name = '';
            $this->supplier_contact = '';
            $this->supplier_phone = '';
            $this->supplier_address = '';
            $this->supplier_remarks = ''; // Initialize blank state on create requests
        }
        $this->show_supplier_modal = true;
    }

    public function saveSupplier()
    {
        $this->validate([
            'supplier_name' => 'required|string|max:255|unique:suppliers,name,' . $this->supplier_id,
            'supplier_contact' => 'nullable|string|max:255',
            'supplier_phone' => 'nullable|string|max:255',
            'supplier_address' => 'nullable|string',
            'supplier_remarks' => 'nullable|string|max:1000', // Added safety validation rules
        ], [
            'supplier_name.unique' => 'This supplier is already registered in the library.',
        ]);

        \App\Models\Supplier::updateOrCreate(
            ['id' => $this->supplier_id],
            [
                'name' => trim($this->supplier_name),
                'contact_person' => $this->supplier_contact,
                'phone' => $this->supplier_phone,
                'address' => $this->supplier_address,
                'remarks' => $this->supplier_remarks ? trim($this->supplier_remarks) : null, // Handle null context mapping smoothly
            ]
        );

        $this->show_supplier_modal = false;
        session()->flash('settings_success', 'Supplier profiles updated successfully!');
    }

    public function deleteSupplier($id)
    {
        $supplier = \App\Models\Supplier::findOrFail($id);
        $supplier->delete();
        session()->flash('settings_success', 'Supplier record removed safely.');
    }

    // Add this method to handle fetching history and opening the modal
    public function viewHistory($productId)
    {
        $this->selectedProductForHistory = \App\Models\Product::with(['batches.supplier'])->findOrFail($productId);
        $this->showHistoryModal = true;
    }

    // =========================================================================
    // for digital transaction
    // =========================================================================
    public function saveDigitalTransaction()
    {
        $this->validate([
            'digital_service_type' => 'required|in:gcash_cash_in,gcash_cash_out,eload,drawer_to_gcash_refill,gcash_to_drawer_refill',
            'digital_amount'       => 'required|numeric|min:0',
            'digital_fee'          => 'required|numeric|min:0',
            'digital_phone'        => 'nullable|string',
            'digital_ref'          => 'nullable|string',
        ]);

        $amount = (float)$this->digital_amount;
        $fee    = (float)$this->digital_fee; 

        // Determine balance changes dynamically based on selection layout type
        $cashChange  = 0.00;
        $gcashChange = 0.00;

        if ($this->digital_service_type === 'gcash_cash_in') {
            // Customer gives you cash (+Principal +Fee), your app balance goes down (-Principal)
            $cashChange  = $amount + $fee;
            $gcashChange = -$amount;
        } elseif ($this->digital_service_type === 'gcash_cash_out') {
            // Customer sends you app money (+Principal), you pay cash from register (-Principal), they pay fee in cash (+Fee)
            $cashChange  = -$amount + $fee;
            $gcashChange = $amount;
        } elseif ($this->digital_service_type === 'eload') {
            // Customer gives you cash (+Principal +Fee), your distributor sim wallet goes down (-Principal)
            $cashChange  = $amount + $fee;
            $gcashChange = -$amount; 
        } elseif ($this->digital_service_type === 'drawer_to_gcash_refill') {
            // 🔄 INTERNAL REFILL BALANCE LOGIC (Cash ➡️ GCash):
            // The cash drawer drops by both the targeted principal and the extra transfer fee paid to the agent.
            // The digital GCash wallet increases by exactly the principal amount funded.
            $cashChange  = -($amount + $fee);
            $gcashChange = $amount;
            $fee         = 0.00; // Do not count provider cost as store operational revenue
        } elseif ($this->digital_service_type === 'gcash_to_drawer_refill') {
            // 🔄 INTERNAL REFILL BALANCE LOGIC (GCash ➡️ Cash):
            // Your physical drawer receives the absolute exact principal cash amount brought back.
            // Your digital GCash wallet is reduced by both the principal withdrawn AND the withdrawal fee.
            $cashChange  = $amount;
            $gcashChange = -($amount + $fee);
            $fee         = 0.00; // Do not count provider withdrawal charge as store operational revenue
        }

        $dataPayload = [
            'service_type'         => $this->digital_service_type,
            'amount'               => $amount,
            'service_fee'          => $fee,
            'cash_on_hand_change'  => $cashChange,
            'gcash_balance_change' => $gcashChange,
            'customer_phone'       => $this->digital_phone,
            'reference_number'     => $this->digital_ref,
            'logged_at'            => $this->digital_date . ' ' . now()->format('H:i:s'),
        ];

        if ($this->is_edit_mode && $this->editing_tx_id) {
            $tx = \App\Models\DigitalTransaction::findOrFail($this->editing_tx_id);
            $tx->update($dataPayload);
            session()->flash('digital_success', 'Transaction updated successfully! Running balances recalculated.');
        } else {
            \App\Models\DigitalTransaction::create($dataPayload);
            session()->flash('digital_success', 'Commit service log entry successful!');
        }

        // Clear and reset form state back to creation mode
        $this->cancelTxEdit();
    }

    public function submitCapitalInjection()
    {
        $this->validate([
            'inject_type' => 'required|in:cash,gcash',
            'inject_amount' => 'required|numeric|min:1',
            'digital_date' => 'required|date', // Validate custom date
        ]);

        $amount = (float)$this->inject_amount;

        \App\Models\DigitalTransaction::create([
            'service_type' => $this->inject_type === 'cash' ? 'capital_inject_cash' : 'capital_inject_gcash',
            'amount' => $amount,
            'service_fee' => 0.00,
            'cash_on_hand_change' => $this->inject_type === 'cash' ? $amount : 0.00,
            'gcash_balance_change' => $this->inject_type === 'gcash' ? $amount : 0.00,
            'reference_number' => 'CAPITAL_INJECTION',
            'customer_phone' => null,
            'logged_at' => $this->digital_date . ' ' . now()->format('H:i:s'), // 👈 Saves user date with current time
        ]);

        $this->reset(['inject_amount']);
        $this->digital_date = now()->format('Y-m-d'); // Reset date back to today
        session()->flash('capital_success', 'Capital successfully backtracked into history log!');
    }

    public function editDigitalTransaction($id)
    {
        $tx = \App\Models\DigitalTransaction::findOrFail($id);
        
        $this->editing_tx_id        = $tx->id;
        $this->digital_service_type = $tx->service_type;
        $this->digital_amount       = $tx->amount;
        $this->digital_fee          = $tx->service_fee;
        $this->digital_phone        = $tx->customer_phone;
        $this->digital_ref          = $tx->reference_number;
        $this->digital_date         = \Carbon\Carbon::parse($tx->logged_at)->format('Y-m-d'); // 👈 Load recorded date
        
        $this->is_edit_mode = true;
    }

    public function cancelTxEdit()
    {
        $this->reset([
            'editing_tx_id', 'is_edit_mode', 'digital_service_type', 
            'digital_amount', 'digital_fee', 'digital_phone', 'digital_ref'
        ]);
        $this->digital_date = now()->format('Y-m-d'); // 👈 Reset to today
        $this->digital_service_type = 'gcash_cash_in';
    }

    public function archiveDigitalTransaction($id)
    {
        $tx = \App\Models\DigitalTransaction::findOrFail($id);
        
        // Flip active status to false (Archived)
        $tx->update(['is_active' => false]);

        // If you happen to be editing the entry you just archived, reset the form
        if ($this->editing_tx_id === $id) {
            $this->cancelTxEdit();
        }

        session()->flash('digital_success', 'Transaction successfully archived. Running metrics re-balanced.');
    }


    // hide products from alert tab
    public function dismissFromAlerts(int $productId): void
    {
        $product = \App\Models\Product::find($productId);
        
        if ($product) {
            $product->update(['hide_from_alerts' => true]);
            $this->successMessage = "{$product->name} has been removed from low stock visibility.";
        }
    }

    public function restoreToAlerts(int $productId): void
    {
        $product = \App\Models\Product::find($productId);
        
        if ($product) {
            $product->update(['hide_from_alerts' => false]);
            $this->successMessage = "{$product->name} is now being tracked for low stock alerts again.";
        }
    }

    public function downloadDatabaseBackup(): StreamedResponse
    {
        // 1. Enforce that this system only runs against MySQL configurations
        if (config('database.default') !== 'mysql') {
            session()->flash('settings_error', 'System utility mismatch: Active driver is not MySQL.');
            return redirect()->back();
        }

        $filename = 'backup-' . config('database.connections.mysql.database') . '-' . now()->format('Y-m-d-His') . '.sql';

        // 2. Return a streamed response to handle heavy data transfers without hitting memory limits
        return response()->streamDownload(function () {
            $pdo = DB::connection()->getPdo();
            
            // Disable foreign key checks at the top of the SQL file for clean restoration later
            echo "-- Yanrie Mini Mart - Automated Database System Backup\n";
            echo "-- Generated: " . now()->toDateTimeString() . "\n";
            echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

            // Fetch all tables present in the current database matching the configuration
            $tables = [];
            $query = $pdo->query('SHOW TABLES');
            while ($row = $query->fetch(\PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }

            foreach ($tables as $table) {
                echo "-- ------------------------------------------------------\n";
                echo "-- Table structure for table `{$table}`\n";
                echo "-- ------------------------------------------------------\n";
                echo "DROP TABLE IF EXISTS `{$table}`;\n";

                // Fetch the exact layout schema blueprint for the table
                $tableSchemaQuery = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $tableSchema = $tableSchemaQuery->fetch(\PDO::FETCH_ASSOC);
                echo $tableSchema['Create Table'] . ";\n\n";

                echo "-- Dumping data for table `{$table}`\n";
                
                // Stream the table data row-by-row
                $dataQuery = $pdo->query("SELECT * FROM `{$table}`");
                while ($row = $dataQuery->fetch(\PDO::FETCH_ASSOC)) {
                    $keys = array_map(fn($key) => "`{$key}`", array_keys($row));
                    $values = array_map(function($value) use ($pdo) {
                        if (is_null($value)) return 'NULL';
                        return $pdo->quote($value);
                    }, array_values($row));

                    echo "INSERT INTO `{$table}` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
                }
                echo "\n\n";
            }

            // Re-enable validation constraints at the end of execution
            echo "SET FOREIGN_KEY_CHECKS=1;\n";
        }, $filename, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // Local scope to easily get active items: Product::active()->get()
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', 1);
    }

    public function archiveProduct($id)
    {
        $product = \App\Models\Product::findOrFail($id);
        $product->update(['is_active' => 0]);

        session()->flash('message', 'Product moved to archives successfully.');
    }
}