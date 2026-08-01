<div class="p-4 md:p-6 w-full mx-auto font-sans bg-gray-100 min-h-screen">
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-3">
            <span class="text-3xl">🏪</span>
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Yanrie Variety Store</h1>
                <p class="text-sm font-medium text-gray-500">Retail POS & Inventory Suite</p>
            </div>
        </div>
        
        <div class="flex bg-gray-100 p-1.5 rounded-xl border border-gray-200">
            <button 
                wire:click="switchToPos" 
                class="px-5 py-2.5 text-sm font-bold rounded-lg transition-all flex items-center gap-2 {{ $activeTab === 'pos' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}"
            >
                🛒 POS
            </button>
            
            <button 
                wire:click="$set('activeTab', 'inventory')" 
                class="px-5 py-2.5 text-sm font-bold rounded-lg transition-all flex items-center gap-2 {{ $activeTab === 'inventory' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}"
            >
                📦 Stock Management
            </button>
            
            <button 
                wire:click="$set('activeTab', 'reports')" 
                class="px-5 py-2.5 text-sm font-bold rounded-lg transition-all flex items-center gap-2 {{ $activeTab === 'reports' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}"
            >
                📊 Sales Report
            </button>
            
            <button wire:click="$set('activeTab', 'purchases')" 
                    class="px-4 py-2 rounded-xl font-bold text-sm transition-all flex items-center gap-2
                    {{ $activeTab === 'purchases' ? 'bg-blue-600 text-white shadow-sm' : 'bg-transparent text-gray-600 hover:bg-gray-100' }}">
                📦 Purchase History
            </button>

            <button wire:click="$set('activeTab', 'digital_services')" class="px-4 py-2 font-bold text-sm uppercase tracking-wide border-b-2 {{ $activeTab === 'digital_services' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500' }}">
                📱 GCash & E-Load
            </button>
            
            <button 
                wire:click="$set('activeTab', 'alerts')" 
                class="px-5 py-2.5 text-sm font-bold rounded-lg transition-all flex items-center gap-2 relative {{ $activeTab === 'alerts' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}"
            >
                ⚠️ Alerts
                @if($lowStockCount > 0)
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white font-black text-xs px-2 py-0.5 rounded-full animate-pulse">
                        {{ $lowStockCount }}
                    </span>
                @endif
            </button>
            <button 
                wire:click="$set('activeTab', 'settings')" 
                class="px-5 py-2.5 text-sm font-bold rounded-lg transition-all flex items-center gap-2 {{ $activeTab === 'settings' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}"
            >
                ⚙️ Settings
            </button>
        </div>
    </div>

    @if($errorMessage)
        <div class="p-4 mb-6 text-base font-bold text-red-800 bg-red-100 border border-red-200 rounded-xl flex items-center gap-2 shadow-sm">
            🛑 {{ $errorMessage }}
        </div>
    @endif

    @if($successMessage)
        <div class="p-4 mb-6 text-base font-bold text-green-800 bg-green-100 border border-green-200 rounded-xl flex items-center gap-2 shadow-sm">
            ✅ {{ $successMessage }}
        </div>
    @endif

    @if($activeTab === 'pos')
        <div x-data="{}" class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start p-4 lg:p-6">
            
            <div class="xl:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-200" wire:key="pos-catalog-panel">
    
                <div class="mb-6" wire:ignore wire:key="pos-search-standalone-wrapper" x-data="{
                    clearAndReset() {
                        $refs.barcodeInput.value = '';
                        $refs.barcodeInput.focus();
                    }
                }">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-xl text-gray-400">🔍</span>
                        <input 
                            type="text" 
                            x-ref="barcodeInput"
                            wire:model.live.debounce.300ms="search" 
                            placeholder="Type product item name or scan product barcode..." 
                            class="w-full pl-11 pr-4 py-3.5 text-lg text-gray-950 bg-gray-50 border border-gray-300 rounded-xl focus:bg-white focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all shadow-inner"
                            @keydown.enter.prevent="$wire.scanAndAddToCart().then(() => clearAndReset())"
                            x-init="$nextTick(() => $el.focus())"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4" wire:key="pos-products-grid-page-{{ request('productsPage', 1) }}">
                    @foreach($products as $product)
                        @php $isLow = $product->stock_level <= $product->low_stock_threshold; @endphp
                        
                        <div 
                            wire:click="addToCart({{ $product->id }})" 
                            wire:key="product-card-id-{{ $product->id }}-stock-{{ $product->stock_level }}"
                            class="p-5 border rounded-xl cursor-pointer transition-all duration-150 flex flex-col justify-between group min-h-[185px] relative
                                {{ $product->stock_level <= 0 ? 'bg-gray-100 border-gray-300 opacity-60 pointer-events-none' : 'bg-white border-gray-200 hover:border-blue-500 hover:shadow-md hover:-translate-y-0.5' }}
                                {{ $isLow && $product->stock_level > 0 ? 'ring-2 ring-amber-400 border-transparent bg-amber-50/30' : '' }}"
                        >
                            <div>
                                <div class="flex justify-between items-start gap-2 mb-1.5">
                                    <span class="text-xs font-bold uppercase tracking-wider text-gray-400 bg-gray-100 px-2.5 py-1 rounded-md">
                                        {{ $product->category->name ?? 'General' }}
                                    </span>
                                    @if($product->stock_level <= 0)
                                        <span class="bg-red-500 text-white text-[10px] font-black uppercase px-2 py-0.5 rounded">SOLD OUT</span>
                                    @elseif($isLow)
                                        <span class="bg-amber-500 text-white text-[10px] font-black uppercase px-2 py-0.5 rounded">LOW STOCK</span>
                                    @endif
                                </div>
                                
                                <h3 class="font-extrabold text-gray-900 text-lg group-hover:text-blue-600 transition-colors leading-snug">
                                    {{ $product->name }}
                                </h3>
                                
                                <p class="text-[11px] font-mono font-bold tracking-wider text-slate-400 mt-1 flex flex-wrap items-center gap-2">
                                    <span class="flex items-center gap-1">
                                        <span>📦 Barcode:</span>
                                        <span class="text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded font-black border border-blue-100">
                                            {{ $product->sku ? $product->sku : 'N/A' }}
                                        </span>
                                    </span>
                                </p>
                                <p class="text-xs font-bold mt-1.5 {{ $product->stock_level <= 0 ? 'text-red-600' : 'text-gray-500' }}">
                                    Available Stock: 
                                    <span class="text-sm font-black">
                                        {{ number_format($product->stock_level, (strtolower($product->category->name ?? '') === 'rice' || $product->is_weighed) ? 2 : 0) }}
                                    </span>
                                </p>
                            </div>

                            <div class="mt-4 pt-3 border-t border-gray-100 flex justify-between items-center">
                                <span class="text-blue-600 font-black text-xl">₱{{ number_format($product->selling_price, 2) }}</span>
                                <span class="text-xs font-bold text-gray-500 bg-gray-50 group-hover:bg-blue-600 group-hover:text-white px-3 py-1.5 rounded-lg border border-gray-200 group-hover:border-transparent transition-all">
                                    + Add to Cart
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 pt-4 border-t border-gray-100" wire:key="pos-pagination-navigation-links">
                    {{ $products->links(data: ['pageName' => 'productsPage']) }}
                </div>

            </div>

            <div class="bg-slate-900 text-white p-6 rounded-2xl shadow-xl flex flex-col justify-between min-h-[620px] border border-slate-800" wire:key="pos-basket-panel-container">
                <div>
                    <div class="flex justify-between items-center border-b border-slate-800 pb-4 mb-4">
                        <h2 class="text-xl font-black tracking-wide flex items-center gap-2">
                            <span>Current Cart Basket</span>
                        </h2>
                        <span class="bg-blue-600 text-white text-xs font-black px-3 py-1 rounded-full shadow-sm">
                            {{ count($cart) }} Items Included
                        </span>
                    </div>

                    <div class="space-y-3 max-h-[320px] overflow-y-auto pr-1 text-slate-300">
                        @forelse($cart as $index => $item)
                            @php 
                                $isWeighedItem = (isset($item['is_weighed']) && $item['is_weighed']) || str_contains(strtolower($item['name'] ?? ''), 'rice');
                                $stepSize = $isWeighedItem ? 0.5 : 1.0;
                            @endphp

                            <div class="bg-slate-800/50 p-3 rounded-xl border border-slate-800 flex flex-col gap-2" wire:key="cart-item-{{ $item['id'] }}">
                                
                                <div class="flex justify-between items-start">
                                    <div class="flex-1 pr-2">
                                        <p class="font-extrabold text-white text-base leading-tight">{{ $item['name'] }}</p>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-xs font-semibold {{ ($item['price_mode'] ?? 'retail') === 'wholesale' ? 'text-amber-400' : 'text-slate-400' }}">
                                                ₱{{ number_format($item['price'], 2) }} each
                                            </span>
                                            
                                            @if(($item['price_mode'] ?? 'retail') === 'wholesale')
                                                <span class="bg-amber-500/10 text-amber-400 border border-amber-500/20 text-[9px] font-black uppercase px-1.5 py-0.5 rounded-md tracking-wider">
                                                    Wholesale Price Active
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="font-black text-emerald-400 text-base">
                                        ₱{{ number_format((float)$item['price'] * (float)$item['qty'], 2) }}
                                    </span>
                                </div>
                                
                                <div class="flex flex-wrap justify-between items-center gap-2 mt-1 pt-2 border-t border-slate-800/60">
                                    
                                    @if(!empty($item['wholesale_price']))
                                        <div class="inline-flex p-0.5 bg-slate-900 rounded-lg border border-slate-700/60">
                                            <button type="button"
                                                    wire:click="toggleItemPriceMode({{ $index }}, 'retail')"
                                                    class="px-2 py-1 rounded-md text-[10px] font-black uppercase tracking-wider transition-all {{ ($item['price_mode'] ?? 'retail') === 'retail' ? 'bg-blue-600 text-white shadow' : 'text-slate-400 hover:text-slate-200' }}">
                                                🛒 Retail
                                            </button>
                                            
                                            <button type="button"
                                                    wire:click="toggleItemPriceMode({{ $index }}, 'wholesale')"
                                                    class="px-2 py-1 rounded-md text-[10px] font-black uppercase tracking-wider transition-all {{ ($item['price_mode'] ?? 'retail') === 'wholesale' ? 'bg-amber-500 text-slate-950 shadow' : 'text-slate-400 hover:text-amber-400' }}">
                                                📦 Wholesale
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-[9px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-900/40 border border-slate-800/60 px-2 py-1 rounded-lg">
                                            🛒 Retail Only
                                        </span>
                                    @endif

                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center bg-slate-900 rounded-lg border border-slate-700 p-0.5">
                                            <button 
                                                type="button"
                                                wire:click="updateQuantity({{ $item['id'] }}, {{ (float)$item['qty'] - $stepSize }})"
                                                class="px-2.5 py-1 text-slate-400 hover:text-white font-bold transition-colors focus:outline-none"
                                            >
                                                —
                                            </button>
                                            
                                            <input 
                                                type="number" 
                                                step="{{ $isWeighedItem ? 'any' : '1' }}"
                                                min="0.01"
                                                value="{{ $isWeighedItem ? number_format($item['qty'], 2, '.', '') : number_format($item['qty'], 0) }}" 
                                                wire:change="updateQuantity({{ $item['id'] }}, $event.target.value)"
                                                class="w-16 bg-transparent text-center font-black font-mono text-sm text-white focus:outline-none"
                                            />
                                            
                                            <button 
                                                type="button"
                                                wire:click="updateQuantity({{ $item['id'] }}, {{ (float)$item['qty'] + $stepSize }})"
                                                class="px-2.5 py-1 text-slate-400 hover:text-white font-bold transition-colors focus:outline-none"
                                            >
                                                +
                                            </button>
                                        </div>
                                        
                                        <button 
                                            type="button"
                                            wire:click="removeFromCart({{ $item['id'] }})" 
                                            class="text-xs font-bold text-slate-500 hover:text-red-400 transition-colors flex items-center gap-1 p-1"
                                            title="Remove Item"
                                        >
                                            🗑️
                                        </button>
                                    </div>
                                </div>

                                @if(($item['price_mode'] ?? 'retail') === 'wholesale' && (float)$item['qty'] < (float)($item['wholesale_qty'] ?? 0))
                                    <div class="bg-amber-500/10 border border-amber-500/20 text-amber-400 px-2 py-1 rounded-md text-[9px] font-bold uppercase tracking-wider text-center animate-pulse">
                                        ⚠️ Min Wholesale Req: {{ $item['wholesale_qty'] }} pcs
                                    </div>
                                @endif

                            </div>
                        @empty
                            <div class="text-center text-slate-500 py-24">
                                <span class="text-4xl block mb-2">🛒</span>
                                <p class="text-sm font-bold">Cart is empty. Click items to append.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="border-t border-slate-800 pt-4 mt-6">
                    @if(!empty($cart))
                        <div class="mb-3 flex justify-between items-center gap-4 bg-slate-800/20 p-2 rounded-xl border border-slate-800/50">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider pl-2">Discount (Optional)</label>
                            <div class="relative rounded-lg w-36 shadow-sm">
                                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-xs text-slate-500 font-bold">₱</span>
                                <input 
                                    type="number" 
                                    step="0.01"
                                    wire:model.blur="discount" 
                                    class="w-full bg-slate-900 text-white border border-slate-700 rounded-lg pl-6 pr-2 py-1.5 font-mono text-sm font-bold text-right focus:ring-1 focus:ring-blue-500" 
                                    placeholder="0.00"
                                />
                            </div>
                        </div>
                        
                        @if((float)$discount > 0)
                            <div class="flex justify-between text-xs font-semibold px-2 mb-2 text-slate-400">
                                <span>Cart Items Subtotal:</span>
                                <span>₱{{ number_format($subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-xs font-semibold px-2 mb-3 text-red-400">
                                <span>Deducted Discount:</span>
                                <span>-₱{{ number_format($discount, 2) }}</span>
                            </div>
                        @endif
                    @endif

                    <div class="mb-4 flex gap-2">
                        <button type="button" wire:click="togglePriceMode" class="w-full py-2 px-3 text-xs font-black rounded-xl transition-all border tracking-wide uppercase shadow-sm
                            {{ $is_wholesale ? 'bg-amber-500 text-slate-950 border-amber-400 hover:bg-amber-600' : 'bg-slate-800 text-slate-200 border-slate-700 hover:bg-slate-700' }}"
                        >
                            Pricing Mode: {{ $is_wholesale ? 'Wholesale Business' : 'Standard Retail' }}
                        </button>
                    </div>

                    <div class="flex justify-between items-center mb-4 bg-slate-800/40 p-3 rounded-xl border border-slate-800">
                        <span class="text-slate-400 text-sm font-bold uppercase tracking-wider">Total Amount Due</span>
                        <span class="text-3xl font-black text-emerald-400 font-mono">₱{{ number_format($total, 2) }}</span>
                    </div>

                    <div class="mb-4 bg-slate-950/40 p-3 rounded-xl border border-slate-800/80">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Transaction Processing Timestamp</label>
                        <div class="relative rounded-xl shadow-sm">
                            <input 
                                type="datetime-local" 
                                wire:model.blur="transaction_date" 
                                class="w-full bg-slate-950 text-white border border-slate-800 rounded-xl px-4 py-2.5 font-mono text-base font-bold focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" 
                            />
                        </div>
                        @error('transaction_date')
                            <span class="text-xs text-red-400 font-semibold mt-1 block pl-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Cash Payment Tendered</label>
                        <div class="relative rounded-xl shadow-sm">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 font-black text-xl">₱</span>
                            <input 
                                type="number" 
                                step="0.01"
                                wire:model.live="cashProvided" 
                                class="w-full bg-slate-950 text-white border border-slate-800 rounded-xl pl-9 pr-4 py-3.5 font-mono text-2xl font-bold focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-right shadow-inner" 
                                placeholder="0.00"
                            />
                        </div>
                    </div>

                    @if(!empty($cashProvided) && (float)$cashProvided > 0)
                        @if((float)$cashProvided >= (float)$total)
                            <div class="flex justify-between items-center mb-5 bg-blue-950/40 p-3.5 rounded-xl border border-blue-900/60" wire:key="change-box-success">
                                <span class="text-xs text-blue-400 uppercase font-black tracking-widest">Change Return Due</span>
                                <span class="font-mono text-2xl font-black text-blue-400">₱{{ number_format((float)$cashProvided - (float)$total, 2) }}</span>
                            </div>
                        @else
                            <div class="flex justify-between items-center mb-5 bg-red-950/40 p-3.5 rounded-xl border border-red-900/60" wire:key="change-box-warning">
                                <span class="text-xs text-red-400 uppercase font-black tracking-widest">Short Balance Penalty</span>
                                <span class="font-mono text-lg font-bold text-red-400">Lacks ₱{{ number_format((float)$total - (float)$cashProvided, 2) }}</span>
                            </div>
                        @endif
                    @endif

                    <button 
                        type="button"
                        wire:click="submitCheckout" 
                        class="w-full py-4 bg-emerald-500 hover:bg-emerald-600 active:bg-emerald-700 text-white font-black text-lg rounded-xl shadow-lg transition-all transform hover:-translate-y-0.5 active:translate-y-0 text-center flex justify-center items-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed disabled:transform-none"
                        @if(empty($cart) || (float)$cashProvided < (float)$total) disabled @endif
                    >
                        <span>🏁 Complete Checkout Transaction</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($activeTab === 'inventory')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6"
            x-data="{ focusInput() { $refs.barcodeField.focus(); } }"
            x-init="focusInput();"
            x-on:focus-barcode.window="focusInput()">
            
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 h-fit">
                <h2 class="text-xs font-bold text-gray-900 uppercase tracking-wider border-b pb-3 mb-5 flex justify-between items-center">
                    <span class="flex items-center gap-2">
                        @if($is_edit_mode)
                            <span class="text-amber-500">📝</span> Modify Scanned Product
                        @else
                            <span class="text-blue-500">✨</span> Register New Store Product
                        @endif
                    </span>
                    @if($is_edit_mode)
                        <button type="button" wire:click="resetFormInputs" class="text-xs font-semibold text-blue-600 hover:text-blue-700 hover:underline transition-colors">Clear / Reset</button>
                    @endif
                </h2>

                @if(session()->has('message'))
                    <div class="p-3 mb-5 text-xs font-semibold text-green-800 bg-green-50 rounded-xl border border-green-200">
                        {{ session('message') }}
                    </div>
                @endif

                <form wire:submit.prevent="saveProductRegistryEntry" class="space-y-5">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                                SKU Barcode <span class="text-blue-500 font-normal lowercase">(Leave blank to auto-generate)</span>
                            </label>
                            <input type="text" 
                                wire:model.live.debounce.400ms="new_sku" 
                                x-ref="barcodeField" 
                                class="w-full px-3 py-2 bg-slate-50 border border-gray-300 rounded-xl text-sm font-mono font-bold text-gray-900 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none" 
                                placeholder="Scan barcode or press TAB to skip..." 
                                autocomplete="off" 
                                @keydown.enter.prevent />
                            @error('new_sku') <span class="text-[11px] font-bold text-red-500 mt-1 block">❌ {{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Date of Delivery</label>
                            <input type="date" wire:model="new_delivery_date" class="w-full px-3 py-2 bg-slate-50 border border-gray-300 rounded-xl text-sm font-medium text-gray-900 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none" />
                            @error('new_delivery_date') <span class="text-[11px] font-bold text-red-500 mt-1 block">❌ {{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Product Description</label>
                            <input type="text" wire:model="new_name" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-medium text-gray-900 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none" placeholder="e.g. Rice Variety / Item Name" />
                            @error('new_name') <span class="text-[11px] font-bold text-red-500 mt-1 block">❌ {{ $message }}</span> @enderror
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Assigned Supplier</label>
                                <select wire:model="supplier_id" id="supplier_id" class="w-full px-3 py-2 border rounded-xl text-sm font-semibold text-gray-900 bg-white focus:outline-none focus:border-purple-500">
                                    <option value="">-- Select Supplier --</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                                @error('selected_supplier_id') <span class="text-[11px] font-bold text-red-500 mt-1 block">❌ {{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Category</label>
                                <select wire:model="new_category_id" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-semibold text-gray-900 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none bg-white">
                                    <option value="">Select Category...</option>
                                    @foreach($categoriesAlphabetical as $cat)
                                        <option value="{{ $cat->id }}">
                                            {{ ucwords(strtolower($cat->name)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('new_category_id') <span class="text-[11px] font-bold text-red-500 mt-1 block">❌ {{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/60 space-y-4">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-200 pb-2 flex items-center gap-1.5">
                            <span>📦</span> Procurement & Pricing
                        </p>
                        
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Cost / Box</label>
                                <input type="number" step="0.01" wire:model.live="new_cost_per_box" class="w-full p-2 border border-gray-300 rounded-lg text-sm font-mono text-gray-900 focus:border-blue-500 outline-none" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Qty / Box</label>
                                <input type="number" wire:model.live="new_items_per_box" class="w-full p-2 border border-gray-300 rounded-lg text-sm font-mono text-gray-900 focus:border-blue-500 outline-none" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1"># Boxes</label>
                                <input type="number" wire:model.live="num_boxes" class="w-full p-2 border border-gray-300 rounded-lg text-sm font-mono text-gray-900 focus:border-blue-500 outline-none" />
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Unit Cost (₱)</label>
                                <input type="number" step="0.01" wire:model.live="new_cost" class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-sm font-mono font-bold text-gray-900" />
                                @error('new_cost') <span class="text-[11px] font-bold text-red-500 mt-1 block">❌ {{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Margin (%)</label>
                                <input type="number" step="0.01" wire:model.live="profit_margin_percent" class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-sm font-mono font-bold text-orange-600 bg-orange-50/30" />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2.5">
                            <div>
                                <label class="block text-[10px] font-bold text-blue-800 uppercase tracking-wider mb-1">Retail (₱)</label>
                                <input type="number" step="0.01" wire:model.live="new_selling_price" class="w-full p-2 border-2 border-blue-400 rounded-xl text-base font-bold text-blue-700 outline-none focus:ring-2 focus:ring-blue-500/20" />
                                @error('new_selling_price') <span class="text-[11px] font-bold text-red-500 mt-1 block">❌ {{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-amber-700 uppercase tracking-wider mb-1">Wholesale</label>
                                <input type="number" step="0.01" wire:model.live="new_wholesale_price" class="w-full p-2 border-2 border-amber-300 rounded-xl text-base font-bold text-amber-700 bg-amber-50/50 outline-none" placeholder="0.00" />
                                @error('new_wholesale_price') <span class="text-[11px] font-bold text-red-500 mt-1 block">❌ {{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-purple-700 uppercase tracking-wider mb-1">Wholesale Qty</label>
                                <input type="number" wire:model.live="new_wholesale_qty" class="w-full p-2 border-2 border-purple-300 rounded-xl text-base font-bold text-purple-700 bg-purple-50/50 outline-none" placeholder="0" min="0" />
                                @error('new_wholesale_qty') <span class="text-[11px] font-bold text-red-500 mt-1 block">❌ {{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="mt-4 p-3 bg-gray-100/70 rounded-xl border border-dashed border-gray-300">
                            <label class="flex items-center cursor-pointer select-none">
                                <input type="checkbox" wire:model.live="create_retail_variant" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4 mr-2.5">
                                <span class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Enable Retail/Kilo Variant</span>
                            </label>

                            @if($create_retail_variant)
                                <div class="mt-3 pt-3 border-t border-gray-200 transition-all">
                                    <label class="block text-[11px] font-bold text-emerald-700 uppercase tracking-wider mb-1">Kilo Retail Price (₱)</label>
                                    <input type="number" step="0.01" wire:model="new_retail_price" class="w-full px-3 py-1.5 border-2 border-emerald-400 rounded-lg text-sm font-bold text-emerald-700 bg-emerald-50/50 outline-none" />
                                    @error('new_retail_price') <span class="text-[11px] font-bold text-red-500 mt-1 block">❌ {{ $message }}</span> @enderror
                                    <p class="text-[10px] text-gray-500 mt-1.5 italic font-medium">Auto-creates a linked display variant mapping.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/60 space-y-4">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-200 pb-2 flex items-center gap-1.5">
                            <span>📊</span> Inventory Levels
                        </p>
                        <div class="grid grid-cols-2 gap-4">
                            @if($is_edit_mode)
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Current Stock</label>
                                    <input type="number" step="any" wire:model="new_stock" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-mono font-bold text-gray-900" />
                                    @error('new_stock') <span class="text-[11px] font-bold text-red-500 mt-1 block">❌ {{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-emerald-600 uppercase tracking-wider mb-1">Add Stock</label>
                                    <input type="number" wire:model="new_stock_to_add" class="w-full px-3 py-1.5 border border-emerald-400 bg-emerald-50 rounded-lg text-sm font-mono text-emerald-800 font-bold" />
                                    @error('new_stock_to_add') <span class="text-[11px] font-bold text-red-500 mt-1 block">❌ {{ $message }}</span> @enderror
                                </div>
                            @else
                                <div class="col-span-2">
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Initial Stock Level</label>
                                    <input type="number" wire:model="new_stock" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-mono font-bold text-gray-900" />
                                    @error('new_stock') <span class="text-[11px] font-bold text-red-500 mt-1 block">❌ {{ $message }}</span> @enderror
                                </div>
                            @endif
                            <div class="col-span-2">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Low Stock Threshold</label>
                                <input type="number" wire:model="new_threshold" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-mono font-bold text-gray-900" />
                                @error('new_threshold') <span class="text-[11px] font-bold text-red-500 mt-1 block">❌ {{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-3.5 {{ $is_edit_mode ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-slate-900 hover:bg-slate-800' }} text-white font-bold rounded-xl text-xs uppercase tracking-widest shadow-md transition-all">
                        {{ $is_edit_mode ? '💾 Save Changes' : '➕ Register Product' }}
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex flex-col justify-between">
                <div>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b pb-4 mb-4 gap-3">
                        <h2 class="text-xs font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                            <span>📝</span> Product Inventory Stocks
                        </h2>
                        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                            <div class="relative w-full sm:w-48">
                                <select wire:model.live="selectedCategory" class="w-full pl-3 pr-8 py-1.5 bg-slate-50 border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none appearance-none">
                                    <option value="">📁 All Categories</option>
                                    @foreach($categoriesAlphabetical as $category)
                                        <option value="{{ $category->id }}">
                                            {{ ucwords(strtolower($category->name)) }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400 text-[10px]">▼</div>
                            </div>

                            <div class="relative w-full sm:w-64">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400 text-xs">🔍</span>
                                <input type="search" wire:model.live.debounce.300ms="search" class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-gray-300 rounded-xl text-xs font-medium text-gray-900 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none" placeholder="Search descriptions, barcodes..." />
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead>
                                <tr class="bg-gray-50/70 border-b border-gray-200 text-gray-400 text-[11px] font-bold uppercase tracking-wider">
                                    <th class="p-3">Product Description</th>
                                    <th class="p-3">Price Structures</th>
                                    <th class="p-3">Delivered Date</th>
                                    <th class="p-3">Supplier</th>
                                    <th class="p-3 text-center">In Stock</th>
                                    <th class="p-3 text-right">Adjustment Controls</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-700">
                                @forelse($products as $prod)
                                    <tr wire:key="inventory-prod-row-{{ $prod->id }}" class="hover:bg-slate-50/50 transition-colors">
                                        <td class="p-3">
                                            <p class="font-bold text-gray-900 text-sm leading-snug">{{ $prod->name }}</p>
                                            <div class="text-[11px] text-gray-400 font-mono mt-0.5 flex items-center gap-1.5">
                                                <span>{{ $prod->sku }}</span>
                                                <span class="text-gray-300">•</span>
                                                <span class="bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-sans font-bold text-[10px]">
                                                    {{ $prod->category->name ?? 'General' }}
                                                </span>
                                            </div>
                                        </td>
                                        
                                        <td class="p-3 font-medium">
                                            <p class="text-[11px] text-gray-400">Cost: ₱{{ number_format($prod->cost_price, 2) }}</p>
                                            <p class="text-xs font-bold text-blue-600 mt-0.5">Retail: ₱{{ number_format($prod->selling_price, 2) }}</p>
                                            @if($prod->wholesale_price)
                                                <p class="text-xs font-bold text-amber-600 mt-0.5">Wholesale: ₱{{ number_format($prod->wholesale_price, 2) }}</p>
                                            @else
                                                <p class="text-[11px] text-slate-400 italic mt-0.5">No wholesale set</p>
                                            @endif
                                        </td>

                                        <td class="p-3">
                                            <p class="text-xs text-gray-500 font-medium">{{ $prod->delivery_date ? \Carbon\Carbon::parse($prod->delivery_date)->format('M d, Y') : 'N/A' }}</p>
                                        </td>

                                        <td class="p-3">
                                            @if($prod->supplier)
                                                <span class="inline-flex items-center text-[10px] bg-slate-100 text-slate-700 font-bold px-2 py-0.5 rounded-md uppercase tracking-wider border border-slate-200/40">
                                                    🏢 {{ $prod->supplier->name }}
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400 italic">No Supplier</span>
                                            @endif
                                        </td>
                                        
                                        <td class="p-3 text-center">
                                            <span class="px-2.5 py-0.5 rounded-md text-xs font-bold inline-block min-w-[40px]
                                                {{ $prod->stock_level <= $prod->low_stock_threshold ? 'bg-red-50 text-red-600 border border-red-200 animate-pulse' : 'bg-green-50 text-green-700 border border-green-200' }}">
                                                @if(strtolower($prod->category->name ?? '') === 'rice' || $prod->is_weighed)
                                                    {{ number_format($prod->stock_level, 2) }}
                                                @else
                                                    {{ number_format($prod->stock_level, 0) }}
                                                @endif
                                            </span>
                                        </td>

                                        <td class="p-3 text-right">
                                            <div class="inline-flex items-center gap-2">
                                                <button type="button" wire:click="loadProductForEdit({{ $prod->id }})" class="bg-slate-100 hover:bg-amber-100 border border-slate-300 hover:border-amber-400 text-slate-700 hover:text-amber-800 font-semibold text-xs px-2.5 py-1.5 rounded-lg shadow-sm transition-all flex items-center gap-1">
                                                    📝 Edit
                                                </button>
                                                
                                                <button type="button" wire:click="openAuditAdjustmentModal({{ $prod->id }})" class="bg-white border border-gray-300 hover:border-blue-500 hover:text-blue-600 text-gray-700 font-semibold text-xs px-2.5 py-1.5 rounded-lg shadow-sm transition-all">
                                                    ⚙️ Adjust Stock
                                                </button>

                                                <!-- CUSTOM IS_ACTIVE ARCHIVE TRIGGER -->
                                                <button type="button" 
                                                    wire:click="archiveProduct({{ $prod->id }})" 
                                                    wire:confirm="Are you sure you want to archive this product? This updates status to inactive without hard-deleting the row."
                                                    class="bg-red-50 hover:bg-red-100 border border-red-200 hover:border-red-300 text-red-600 font-semibold text-xs px-2.5 py-1.5 rounded-lg shadow-sm transition-all flex items-center gap-1"
                                                >
                                                    📦 Archive
                                                </button>

                                                <button type="button" 
                                                    wire:click="viewHistory({{ $prod->id }})" 
                                                    class="text-[10px] font-bold text-slate-400 hover:text-blue-600 uppercase tracking-tighter ml-1">
                                                    View History 🕒
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="p-8 text-center text-xs font-medium text-gray-400 italic">
                                            No registered product data records found matching search queries.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div> 
                </div>

                @if($products->hasPages())
                    <div class="mt-5 pt-4 border-t border-gray-100 bg-white">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6 mt-6">
            @if (session()->has('error'))
                <div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 text-sm font-semibold text-red-600 flex items-center gap-2">
                    <span>⚠️</span> {{ session('error') }}
                </div>
            @endif

            @if (session()->has('message'))
                <div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-sm font-semibold text-emerald-600 flex items-center gap-2">
                    <span>✅</span> {{ session('message') }}
                </div>
            @endif

            <div>
                <h2 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <span>🌾</span> Rice Stock Management
                </h2>
                <div class="overflow-x-auto bg-white rounded-xl border border-gray-100 shadow-sm">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-gray-400 font-bold text-[11px] uppercase tracking-wider bg-gray-50/50">
                                <th class="p-3">Product Name</th>
                                <th class="p-3">SKU Barcode</th>
                                <th class="p-3">Current Inventory Stock Level</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            @foreach($riceManagementProducts as $product)
                                <tr wire:key="rice-mgmt-row-{{ $product->id }}" class="hover:bg-slate-50/40 transition-colors">
                                    <td class="p-3 font-medium text-gray-900">
                                        <span class="font-bold text-sm text-gray-900">{{ $product->name }}</span>
                                        @if($product->parent)
                                            <span class="block text-[11px] text-gray-400 font-medium normal-case mt-0.5">
                                                Base Rice Strain: {{ $product->parent->name }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-3 font-mono text-xs text-gray-500">{{ $product->sku }}</td>
                                    <td class="p-3">
                                        <div class="flex items-center gap-3 flex-wrap">
                                            <div>
                                                <span class="font-bold text-gray-950 text-sm">
                                                    {{ $product->parent_id ? number_format($product->stock_level, 2) : number_format($product->stock_level, 0) }}
                                                </span>
                                                <span class="text-xs text-gray-400 font-medium ml-0.5">
                                                    {{ $product->parent_id ? 'kg' : 'sacks' }}
                                                </span>
                                            </div>
                                            
                                            @if($product->parent_id)
                                                <div class="flex items-center gap-1.5 bg-gray-50 p-1.5 rounded-lg border border-gray-200 shadow-sm ml-auto flex-wrap sm:flex-nowrap">
                                                    <select wire:model.defer="selectedSackVariant.{{ $product->id }}" 
                                                            class="text-xs font-bold rounded border-gray-300 bg-white py-1 px-2 text-gray-700 focus:ring-amber-500 focus:border-amber-500">
                                                        <option value="">-- Choose Sack Size --</option>
                                                        @php
                                                            $cleanBaseName = preg_replace('/(50kg|25kg|10kg|5kg|Retail|Kilo|Loose|\(|\)|\/)/i', '', $product->name);
                                                            $words = array_filter(explode(' ', trim($cleanBaseName)));
                                                            $firstWord = reset($words); 

                                                            if (strtolower($firstWord) === 'don' || strtolower($firstWord) === 'senior') {
                                                                $searchString = implode(' ', array_slice($words, 0, 2));
                                                            } else {
                                                                $searchString = $firstWord;
                                                            }

                                                            $availableSizes = \App\Models\Product::where('category_id', 1)
                                                                ->whereNull('parent_id')
                                                                ->where('name', 'like', '%' . $searchString . '%')
                                                                ->orderBy('conversion_factor', 'desc')
                                                                ->get();
                                                        @endphp
                                                        @foreach($availableSizes as $sizeOpt)
                                                            <option value="{{ $sizeOpt->id }}">
                                                                {{ $sizeOpt->name }} (In Stock: {{ number_format($sizeOpt->stock_level, 0) }})
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                    <select wire:model.defer="sackQuantities.{{ $product->id }}" 
                                                            class="text-xs font-bold rounded border-gray-300 bg-white py-1 px-1.5 text-gray-700 focus:ring-amber-500 focus:border-amber-500 w-14">
                                                        @for($i = 1; $i <= 5; $i++)
                                                            <option value="{{ $i }}">{{ $i }}×</option>
                                                        @endfor
                                                    </select>

                                                    <input type="date" 
                                                        wire:model="backdatedSackDate.{{ $product->id }}"
                                                        class="text-xs font-semibold rounded border-gray-300 bg-white py-1 px-1.5 text-gray-600 focus:ring-amber-500 focus:border-amber-500 max-w-[130px]">

                                                    <button wire:click="breakSack({{ $product->id }})" 
                                                            wire:confirm="Are you sure you want to open and decant this stock into the retail rice box?"
                                                            wire:loading.attr="disabled"
                                                            type="button" 
                                                            class="px-2.5 py-1.5 bg-amber-500 hover:bg-amber-600 disabled:bg-gray-300 text-white text-[10px] font-bold rounded transition-all uppercase tracking-wider flex items-center gap-1 whitespace-nowrap">
                                                        <span>🔓</span> Add to Ricebox
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-8">
                    <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <span>📋</span> Recent Ricebox Ricebox Logs (Backtracking)
                    </h3>
                    <div class="overflow-x-auto bg-white rounded-xl border border-gray-100 shadow-sm">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-200 text-gray-400 font-bold text-[11px] uppercase tracking-wider bg-gray-50/50">
                                    <th class="p-3">Date & Time</th>
                                    <th class="p-3">Action Details</th>
                                    <th class="p-3 text-right">Stock Added</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-xs font-mono text-gray-600">
                                @php
                                    $riceProductIds = $riceManagementProducts->pluck('id')->toArray();

                                    $conversionLogs = \DB::table('product_batches')
                                        ->whereIn('product_id', $riceProductIds)
                                        ->where(function($query) {
                                            $query->where('num_boxes', '>', 0)
                                                ->where('items_per_box', '>', 0);
                                        })
                                        ->latest()
                                        ->take(5)
                                        ->get();
                                        
                                    $productNames = $riceManagementProducts->pluck('name', 'id')->toArray();
                                @endphp

                                @forelse($conversionLogs as $log)
                                    @php
                                        $currentProductName = $productNames[$log->product_id] ?? 'Rice Variety';
                                        $cleanName = preg_replace('/(Retail|Kilo|Loose|\(|\)|\/)/i', '', $currentProductName);
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="p-3 text-gray-900 font-bold whitespace-nowrap">
                                            {{ \Carbon\Carbon::parse($log->created_at)->timezone('Asia/Manila')->format('Y-m-d h:i A') }}
                                        </td>
                                        <td class="p-3 text-gray-700 normal-case font-sans font-medium">
                                            <div>
                                                Added <span class="font-bold text-gray-950">{{ (int)$log->num_boxes }} sack(s)</span> into the ricebox for <span class="font-bold text-gray-950">{{ trim($cleanName) }} retail</span> ({{ (int)$log->items_per_box }}kg per sack).
                                            </div>
                                            
                                            {{-- Check if remarks are stored to accurately expose historical backdated data --}}
                                            @if(!empty($log->remarks))
                                                <div class="mt-1 text-[11px] text-amber-600 font-semibold flex items-center gap-1 bg-amber-50 rounded px-2 py-0.5 w-fit border border-amber-100 font-mono">
                                                    <span>📅 Backtracked Inventory Entry Date:</span>
                                                    <span class="underline text-amber-800">{{ \Carbon\Carbon::parse($log->delivery_date)->format('Y-m-d') }}</span>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="p-3 text-right text-emerald-600 font-bold font-mono text-sm whitespace-nowrap">
                                            +{{ number_format($log->quantity_added, 2) }} kg
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="p-4 text-center text-gray-400 font-sans normal-case italic">
                                            No recent ricebox decanting records found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        @if($show_adjust_modal)
            <div class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-fade-in">
                <div class="bg-white max-w-md w-full rounded-2xl p-6 shadow-xl border border-gray-100">
                    <h3 class="text-xs font-bold text-red-600 uppercase tracking-wider border-b pb-3 mb-4 flex items-center gap-2">
                        <span>⚠️</span> Inventory Discrepancy Correction Log
                    </h3>
                    
                    <div class="mb-4 bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Target Product Details:</label>
                        <p class="text-sm font-bold text-slate-900">{{ $selected_product_for_audit['name'] ?? '' }}</p>
                        <p class="text-xs text-gray-500 font-mono mt-0.5">{{ $selected_product_for_audit['sku'] ?? '' }}</p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">New Adjusted Absolute Total Stock Level</label>
                            <input type="number" step="any" wire:model="audit_new_stock_value" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-base font-mono font-bold text-gray-900 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition-all" />
                            @error('audit_new_stock_value') <p class="text-xs font-semibold text-red-600 mt-1.5 flex items-center gap-1"><span>❌</span> {{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Effective Audit Adjustment Date</label>
                            <input type="date" wire:model="audit_delivery_date" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-mono font-bold text-gray-900 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition-all" />
                            @error('audit_delivery_date') <p class="text-xs font-semibold text-red-600 mt-1.5 flex items-center gap-1"><span>❌</span> {{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Audit Change Log Reason Remarks</label>
                            <select wire:model="audit_remarks_message" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm font-bold text-gray-900 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none bg-white transition-all">
                                <option value="">Select Audit Code Reason...</option>
                                <option value="Physical Count Stock Take Audit Alignment">Physical Count Discrepancy Correction</option>
                                <option value="Damaged / Spoiled Goods Warehouse Write-off">Damaged / Spoiled / Expired Goods</option>
                                <option value="Supplier Delivery Shortage Ingestion Settlement">Supplier Miscount Shortage Return</option>
                                <option value="Theft Loss Discovered Variance Log Entry">Stolen / Lost Inventory Audit Adjust</option>
                            </select>
                            @error('audit_remarks_message') <p class="text-xs font-semibold text-red-600 mt-1.5 flex items-center gap-1"><span>❌</span> {{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6 border-t pt-4">
                        <button type="button" wire:click="$set('show_adjust_modal', false)" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl uppercase tracking-wider transition-all">Cancel</button>
                        <button type="button" wire:click="processAuditedStockCorrection" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl uppercase tracking-wider shadow transition-all">Commit Adjustment</button>
                    </div>
                </div>
            </div>
        @endif

       @if($showHistoryModal && $selectedProductForHistory)
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-[9999] p-2 sm:p-4 animate-fadeIn">
                
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col overflow-hidden border border-slate-200">
                    
                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="bg-blue-100 text-blue-600 p-2 rounded-xl shadow-sm">
                                <span class="text-lg">🕒</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 text-sm sm:text-base leading-tight">Stock Delivery History</h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                                    {{ $selectedProductForHistory->name }}
                                </p>
                            </div>
                        </div>
                        <button wire:click="$set('showHistoryModal', false)" 
                                class="h-8 w-8 flex items-center justify-center rounded-full bg-white border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-200 transition-all shadow-sm">
                            ✕
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto min-h-0 bg-white" style="max-height: calc(90vh - 140px); -webkit-overflow-scrolling: touch;">
                        <table class="w-full text-left border-collapse">
                            <thead class="sticky top-0 bg-white shadow-sm z-50">
                                <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-white">
                                    <th class="px-6 py-3 border-b border-slate-100 bg-white">Date Received</th>
                                    <th class="px-6 py-3 border-b border-slate-100 bg-white">Supplier / Source Action</th>
                                    <th class="px-6 py-3 border-b border-slate-100 bg-white text-right">Cost Price</th>
                                    <th class="px-6 py-3 border-b border-slate-100 bg-white text-right">Selling Price</th>
                                    <th class="px-6 py-3 border-b border-slate-100 bg-white text-right">Profit Margin</th>
                                    <th class="px-6 py-3 border-b border-slate-100 bg-white text-center">Qty Added</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($selectedProductForHistory->batches ?? [] as $batch)
                                <tr class="hover:bg-blue-50/20 transition-colors group">
                                    <td class="px-6 py-3.5 text-xs font-semibold text-gray-600 whitespace-nowrap">
                                        {{ $batch->delivery_date ? \Carbon\Carbon::parse($batch->delivery_date)->format('M d, Y') : 'N/A' }}
                                    </td>
                                    
                                    <td class="px-6 py-3.5">
                                        <div class="flex flex-col gap-1">
                                            @if(is_null($batch->num_boxes) && is_null($batch->items_per_box))
                                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 text-[10px] font-bold border border-amber-200/40 w-fit">
                                                    ⚙️ INVENTORY AUDIT CORRECTION
                                                </span>
                                                
                                                @php 
                                                    $adjustmentsCollection = $selectedProductForHistory->stockAdjustments ?? $selectedProductForHistory->adjustments ?? null;
                                                    $adjustment = $adjustmentsCollection ? $adjustmentsCollection->firstWhere('delta', $batch->quantity_added) : null;
                                                @endphp

                                                @if($adjustment && !empty($adjustment->remarks))
                                                    <div class="text-[10px] text-amber-800 font-medium mt-1 bg-amber-50/40 px-2.5 py-1.5 rounded-lg border border-dashed border-amber-200 w-fit max-w-md">
                                                        <span class="font-bold uppercase tracking-wider text-[9px] text-amber-600 block mb-0.5">Audit Note:</span>
                                                        "{{ $adjustment->remarks }}"
                                                    </div>
                                                @else
                                                    <div class="text-[10px] text-slate-400 italic mt-0.5">
                                                        No specific audit log details found.
                                                    </div>
                                                @endif
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[10px] font-bold border border-slate-200/50 w-fit group-hover:bg-white transition-colors">
                                                    🏢 {{ $batch->supplier->name ?? 'Direct Purchase' }}
                                                </span>
                                                
                                                <div class="text-[10px] text-slate-400 font-medium mt-0.5">
                                                    Delivered: <span class="text-slate-600 font-semibold">{{ $batch->num_boxes ?? 0 }} bulk packs</span> 
                                                    x {{ $batch->items_per_box ?? 0 }} pcs 
                                                    (@ ₱{{ number_format($batch->cost_per_box ?? 0, 2) }})
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    
                                    @php
                                        $costPrice = floatval($batch->cost_price ?? 0);
                                        $sellingPrice = floatval($batch->selling_price ?? $selectedProductForHistory->selling_price ?? 0);
                                        $profit = $sellingPrice - $costPrice;
                                        $isAudit = is_null($batch->num_boxes) && is_null($batch->items_per_box);
                                    @endphp

                                    <td class="px-6 py-3.5 text-right whitespace-nowrap">
                                        <div class="flex flex-col text-right">
                                            <span class="text-xs font-mono font-bold text-gray-900">₱{{ number_format($costPrice, 2) }}</span>
                                            <span class="text-[9px] font-medium text-slate-400 uppercase tracking-tight">per unit</span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-3.5 text-right whitespace-nowrap">
                                        <div class="flex flex-col text-right">
                                            <span class="text-xs font-mono font-bold text-slate-700">₱{{ number_format($sellingPrice, 2) }}</span>
                                            <span class="text-[9px] font-medium text-slate-400 uppercase tracking-tight">per unit</span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-3.5 text-right whitespace-nowrap">
                                        <div class="flex flex-col text-right">
                                            @if($isAudit)
                                                <span class="text-xs font-medium text-slate-400 italic">—</span>
                                                <span class="text-[9px] font-medium text-slate-400 uppercase tracking-tight">audit row</span>
                                            @else
                                                @if($profit > 0)
                                                    <span class="text-xs font-mono font-bold text-emerald-600">₱{{ number_format($profit, 2) }}</span>
                                                    <span class="text-[9px] font-bold text-emerald-500/80 uppercase tracking-tight">
                                                        +{{ number_format(($profit / ($costPrice ?: 1)) * 100, 1) }}% markup
                                                    </span>
                                                @elseif($profit == 0)
                                                    <span class="text-xs font-mono font-bold text-amber-600">₱0.00</span>
                                                    <span class="text-[9px] font-medium text-amber-500 uppercase tracking-tight">at cost</span>
                                                @else
                                                    <span class="text-xs font-mono font-bold text-rose-600">₱{{ number_format($profit, 2) }}</span>
                                                    <span class="text-[9px] font-bold text-rose-400 uppercase tracking-tight">loss</span>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                    
                                    <td class="px-6 py-3.5 text-center whitespace-nowrap">
                                        @if(($batch->quantity_added ?? 0) >= 0)
                                            <span class="inline-block px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-md text-[11px] font-bold border border-emerald-100">
                                                +{{ number_format($batch->quantity_added, 0) }}
                                            </span>
                                        @else
                                            <span class="inline-block px-2 py-0.5 bg-rose-50 text-rose-700 rounded-md text-[11px] font-bold border border-rose-100">
                                                {{ number_format($batch->quantity_added, 0) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-sm font-medium text-slate-400 italic">
                                        No delivery history found for this item.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-between items-center shrink-0">
                        <p class="text-[11px] text-slate-500 font-medium">
                            Current Total Stock: <span class="font-bold text-gray-900">{{ number_format($selectedProductForHistory->stock_level ?? 0, 0) }}</span>
                        </p>
                        <button wire:click="$set('showHistoryModal', false)" 
                                class="px-5 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition-all shadow-sm">
                            Close View
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif

    @if($activeTab === 'alerts')
        <style>
        @media print {
            /* 1. Kill browser headers, footers, and infinite scroll paper feed loops */
            @page { 
                size: 58mm auto;
                margin: 0mm !important;
            }
            
            html, body {
                background: #ffffff !important;
                color: #000000 !important;
                width: 58mm !important;
                max-width: 58mm !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
                height: auto !important;
            }

            /* 2. Hide absolutely every element on the page background */
            body * {
                visibility: hidden !important;
            }
            
            /* 3. Isolate and safely force visibility on the print wrapper card only */
            #low-stock-alert-section,
            #low-stock-alert-section * {
                visibility: visible !important;
            }
            
            #low-stock-alert-section {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 52mm !important; /* Safety inner print zone margins to prevent clipped edges */
                max-width: 52mm !important;
                padding: 2mm 0mm !important;
                margin: 0 auto !important;
                box-shadow: none !important;
                border: none !important;
                background: transparent !important;
                display: block !important;
                height: auto !important;
            }

            /* 4. Convert table layout cleanly into a compact side-by-side flex layout */
            .thermal-print-table {
                width: 100% !important;
                display: block !important;
                font-family: 'Courier New', Courier, monospace !important;
                font-size: 11px !important;
                line-height: 1.2 !important;
            }

            .thermal-print-table thead {
                display: none !important; /* Erases screen header elements */
            }

            .thermal-print-table tbody {
                display: block !important;
                width: 100% !important;
                height: auto !important;
            }

            .thermal-print-table tr {
                display: flex !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
                width: 100% !important;
                padding: 3px 0 !important;
                border-bottom: 1px dashed #000000 !important;
                page-break-inside: avoid !important;
            }

            .thermal-print-table td {
                display: inline-block !important;
                padding: 0 !important;
                background: transparent !important;
                color: #000000 !important;
            }

            .thermal-item-cell {
                width: auto !important;
                max-width: 34mm !important; /* Forces item name wrapping without layout clipping */
                font-weight: bold !important;
                word-break: break-word !important;
                white-space: normal !important;
                text-transform: uppercase !important;
                text-align: left !important;
            }

            .thermal-qty-cell {
                width: auto !important;
                text-align: right !important;
                font-weight: bold !important;
                white-space: nowrap !important;
            }

            /* 5. Instantly strip away visual controls, actions, buttons, and target web-only columns */
            Tar.print\:hidden, button, a, th, td:nth-child(3), td:nth-child(4), .text-blue-600 {
                display: none !important;
            }
        }
        </style>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 print:border-none print:shadow-none print:p-0" id="low-stock-alert-section">
            
            <div class="border-b border-gray-100 pb-3 mb-4 flex items-center justify-between print:border-none print:pb-0 print:mb-0">
                <div class="flex flex-col gap-1">
                    <h2 class="text-xl font-black text-red-700 uppercase tracking-wider flex items-center gap-2 print:text-black print:text-xs print:font-bold">
                        <span>⚠️ {{ $viewHiddenAlerts ? 'Hidden From Alerts' : 'Low Stock Alerts' }}</span>
                    </h2>
                    <button 
                        type="button" 
                        wire:click="$toggle('viewHiddenAlerts')" 
                        class="text-left text-xs font-bold text-blue-600 hover:text-blue-800 underline cursor-pointer print:hidden"
                    >
                        {{ $viewHiddenAlerts ? '← Back to Active Alerts' : '⚙️ Manage Hidden Alerts' }}
                    </button>
                </div>
                
                @if(!$viewHiddenAlerts && \App\Models\Product::where('hide_from_alerts', false)->whereRaw('stock_level <= low_stock_threshold')->exists())
                    <button type="button" 
                            onclick="window.print()" 
                            class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl uppercase tracking-wider transition-all shadow-sm flex items-center gap-2 print:hidden cursor-pointer">
                        🖨️ Print Purchase List
                    </button>
                @endif
            </div>
            
            <div class="hidden print:block mb-2 text-center font-mono" style="width: 100%;">
                <h1 class="text-[13px] font-black uppercase text-black tracking-wide">YANRIE VARIETY STORE</h1>
                <h2 class="text-[10px] font-bold text-black tracking-tight">STOCKS TO PURCHASE</h2>
                <p class="text-[9px] text-black mt-0.5">{{ now()->timezone('Asia/Manila')->format('Y-m-d h:i A') }}</p>
                <div class="border-b border-dashed border-black mt-2"></div>
            </div>
            
            @php
                if ($viewHiddenAlerts) {
                    $productsList = \App\Models\Product::where('hide_from_alerts', true)->get();
                } else {
                    $productsList = \App\Models\Product::where('hide_from_alerts', false)
                        ->whereRaw('stock_level <= low_stock_threshold')
                        ->get();
                }
            @endphp

            <div class="overflow-x-auto print:overflow-visible">
                <table class="thermal-print-table w-full text-left border-collapse text-sm text-gray-900 font-sans">
                    <thead>
                        <tr class="bg-slate-50 border-b border-gray-200">
                            <th class="py-3 px-4 font-black uppercase tracking-wider text-gray-500 text-xs">Item Details</th>
                            <th class="py-3 px-4 font-black uppercase tracking-wider text-gray-500 text-xs text-center">Current</th>
                            <th class="py-3 px-4 font-black uppercase tracking-wider text-gray-500 text-xs text-center">Limit</th>
                            <th class="py-3 px-4 font-black uppercase tracking-wider text-gray-500 text-xs text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 print:divide-y-0">
                        @forelse($productsList as $alertProd)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="thermal-item-cell py-3.5 px-4">
                                    <div class="font-extrabold text-gray-900 print:text-black print:text-[11px] print:font-bold uppercase">
                                        {{ $alertProd->name }}
                                    </div>
                                    <div class="text-xs font-mono text-gray-400 mt-0.5 print:hidden">
                                        SKU: {{ $alertProd->sku }}
                                    </div>
                                </td>
                                
                                <td class="thermal-qty-cell py-3.5 px-4 text-center font-mono">
                                    <span class="px-2.5 py-1 rounded-md bg-red-50 text-red-600 font-black text-sm print:bg-transparent print:p-0 print:text-black print:text-[11px]">
                                        <span class="hidden print:inline">x</span>{{ $alertProd->stock_level }}
                                    </span>
                                </td>
                                
                                <td class="py-3.5 px-4 text-center font-bold text-gray-500 font-mono print:hidden">
                                    {{ $alertProd->low_stock_threshold }}
                                </td>
                                
                                <td class="py-3.5 px-4 text-right print:hidden">
                                    @if($viewHiddenAlerts)
                                        <button type="button" wire:click="restoreToAlerts({{ $alertProd->id }})" class="px-3 py-1.5 bg-white hover:bg-green-50 text-green-700 hover:text-green-800 border border-gray-200 hover:border-green-300 font-bold text-[11px] rounded-lg uppercase tracking-wide shadow-sm transition-all cursor-pointer">
                                            🔄 Restore Alert
                                        </button>
                                    @else
                                        <button type="button" wire:click="dismissFromAlerts({{ $alertProd->id }})" wire:confirm="Are you sure you want to stop tracking alerts for '{{ $alertProd->name }}'?" class="px-3 py-1.5 bg-white hover:bg-amber-50 text-amber-700 hover:text-amber-800 border border-gray-200 hover:border-amber-300 font-bold text-[11px] rounded-lg uppercase tracking-wide shadow-sm transition-all cursor-pointer">
                                            ❌ Hide Alert
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-12 text-slate-500 font-bold bg-slate-50 rounded-2xl print:text-black print:bg-transparent print:py-2">
                                    <span class="text-3xl block mb-1 print:hidden">📋</span>
                                    <p class="text-sm print:text-[11px] print:font-mono print:text-center">
                                        {{ $viewHiddenAlerts ? 'No hidden products found.' : 'Excellent! All tracking lines reside safely above risk thresholds.' }}
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="hidden print:block mb-8"></div>
        </div>
    @endif


   @if($showReceipt && $lastSale)
        <div 
            x-data="cashDrawerHardware()"
            @checkout-completed.window="triggerDrawerRelay()"
            class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 overflow-y-auto p-4 flex justify-center"
        >
            <div class="bg-white rounded-2xl shadow-2xl p-5 max-w-[340px] w-full border border-gray-100 flex flex-col h-fit my-auto md:my-8 animate-in fade-in zoom-in-95 duration-150">
                
                <div class="overflow-y-auto pr-1 max-h-[65vh] custom-receipt-scrollbar" style="scrollbar-width: thin; -webkit-overflow-scrolling: touch;">
                    <div id="printable-receipt-area" class="text-gray-900 font-mono text-sm bg-white p-1 leading-tight tracking-tight">
                        
                        <div class="text-center border-b border-dashed border-gray-400 pb-2 mb-2">
                            <h2 class="text-base font-black tracking-tight uppercase">Yanrie Variety Store</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Date: {{ $lastSale->created_at->timezone('Asia/Manila')->format('y-m-d H:i') }}</p>
                            <p class="text-xs text-gray-500">OR ID: #{{ str_pad($lastSale->id, 5, '0', STR_PAD_LEFT) }}</p>
                        </div>

                        <div class="space-y-2 border-b border-dashed border-gray-400 pb-2 mb-2">
                            @foreach($lastSale->items()->with('product')->get() as $dbItem)
                                @php
                                    $itemName = $dbItem->product->name ?? $dbItem->product_name ?? 'Unknown Item';
                                    $sku = strtolower($dbItem->product->sku ?? '');
                                    $isRiceRetail = str_starts_with($sku, 'kilo');
                                    
                                    $unit = $isRiceRetail ? 'kg' : ($dbItem->quantity > 1 ? 'pcs' : 'pc');
                                    $formattedQty = $isRiceRetail ? number_format($dbItem->quantity, 2) : (float)$dbItem->quantity;
                                @endphp
                                <div class="block-item">
                                    <p class="font-black text-sm uppercase tracking-tight text-gray-950 item-title">{{ $itemName }}</p>
                                    <div class="data-row flex justify-between items-end text-sm text-gray-700 mt-0.5 pl-1 w-full">
                                        <span class="qty-calc-col whitespace-nowrap">{{ $formattedQty }}{{ $unit }}×₱{{ number_format($dbItem->unit_price, 2) }}</span>
                                        <span class="total-price-col font-black text-gray-950 whitespace-nowrap pr-2">₱{{ number_format($dbItem->subtotal, 2) }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Breakdown Section -->
                        <div class="space-y-1 text-sm calculation-rows w-full">
                            <div class="font-bold text-sm text-gray-950 pt-0.5 data-row flex justify-between items-end w-full">
                                <span>TOTAL DUE:</span>
                                <span class="text-base font-black pr-2">₱{{ number_format($lastSale->total_amount, 2) }}</span>
                            </div>

                            @if($lastSale->discount > 0)
                                <div class="text-red-600 font-bold data-row flex justify-between items-end w-full">
                                    <span>DISCOUNT:</span>
                                    <span class="pr-2">-₱{{ number_format($lastSale->discount, 2) }}</span>
                                </div>
                            @endif

                            <div class="text-gray-700 data-row flex justify-between items-end w-full">
                                <span>CASH PAID:</span>
                                <span class="pr-2">₱{{ number_format($lastSale->amount_paid, 2) }}</span>
                            </div>

                            <div class="font-bold text-sm text-gray-900 pt-1 border-t border-dashed border-gray-400 mt-1 data-row flex justify-between items-end w-full">
                                <span>CHANGE DUE:</span>
                                <span class="text-sm font-black pr-2">₱{{ number_format($lastSale->change_amount, 2) }}</span>
                            </div>
                        </div>

                        <div class="text-center mt-4 pt-2 border-t border-dashed border-gray-400 text-xs text-gray-600 footer-area">
                            <p class="font-bold uppercase tracking-wider text-gray-800">Thank you!</p>
                            <p class="text-[10px] text-gray-400 mt-1.5 leading-tight">Note: For internal tracking purposes only — not an official receipt. Please ask for official receipt.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2 text-xs pt-3 border-t border-gray-100 shrink-0">
                    <button 
                        @click="
                            let content = document.getElementById('printable-receipt-area').innerHTML;
                            
                            let htmlDoc = `<!DOCTYPE html>
                            <html>
                            <head>
                                <meta charset='utf-8'>
                                <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
                                <title>POS Print</title>
                                <style>
                                    * { box-sizing: border-box; margin: 0; padding: 0; font-weight: 900 !important; }
                                    @page { margin: 0; size: 58mm auto; }
                                    html, body { background: #ffffff !important; color: #000000; width: 100%; max-width: 42mm; }
                                    body { margin: 0; padding: 6px 0px 6px 2px; font-family: 'Courier New', Courier, monospace, system-ui; font-size: 11px; line-height: 1.4; -webkit-font-smoothing: antialiased; }
                                    .text-center { text-align: center; } 
                                    .uppercase { text-transform: uppercase; } 
                                    .mt-0.5 { margin-top: 2px; } 
                                    .mt-1 { margin-top: 4px; } 
                                    .mt-4 { margin-top: 12px; } 
                                    .mb-2 { margin-bottom: 4px; }
                                    .pb-2 { padding-bottom: 4px; } 
                                    .pt-1.5 { padding-top: 4px; } 
                                    .pt-0.5 { padding-top: 1px; } 
                                    .pt-2 { padding-top: 4px; } 
                                    .pl-1 { padding-left: 2px; }
                                    .border-b { border-bottom: 1px dashed #000000; } 
                                    .border-t { border-top: 1px dashed #000000; }
                                    .font-black, .font-bold { font-weight: 900 !important; } 
                                    h2 { font-size: 12px; line-height: 1.3; padding-bottom: 2px !important; } 
                                    .text-xs { font-size: 9px; }
                                    .space-y-2 > * + * { margin-top: 6px; } 
                                    .space-y-1 > * + * { margin-top: 3px; }
                                    .text-gray-400, .text-gray-500, .text-gray-600, .text-gray-700 { color: #000000 !important; }
                                    
                                    .data-row { 
                                        display: flex !important; 
                                        width: 100% !important; 
                                        justify-content: space-between !important;
                                        align-items: flex-end !important;
                                        padding-bottom: 4px !important;
                                    }
                                    .qty-calc-col { 
                                        text-align: left !important; 
                                        white-space: nowrap !important;
                                        padding-right: 4px !important;
                                    }
                                    .total-price-col, .data-row > span:last-child { 
                                        text-align: right !important; 
                                        white-space: nowrap !important;
                                        padding-right: 8px !important; 
                                    }
                                    .calculation-rows {
                                        display: block !important;
                                        width: 100% !important;
                                        margin-top: 4px !important;
                                    }
                                    .block-item {
                                        display: block !important;
                                        width: 100% !important;
                                        padding-bottom: 3px !important;
                                    }
                                    .item-title {
                                        display: block !important;
                                        width: 100% !important;
                                        line-height: 1.3 !important;
                                        padding-bottom: 1px !important;
                                        word-wrap: break-word;
                                        white-space: normal;
                                    }
                                    .footer-area p {
                                        line-height: 1.3 !important;
                                        padding-bottom: 2px !important;
                                    }
                                </style>
                            </head>
                            <body>` + content + `</body>
                            </html>`;

                            let blob = new Blob([htmlDoc], { type: 'text/html;charset=utf-8' });
                            let blobUrl = URL.createObjectURL(blob);

                            let w = window.open(blobUrl, '_blank', 'width=360,height=700');
                            if (w) {
                                w.focus();
                                setTimeout(() => { 
                                    w.print(); 
                                    w.close();
                                    URL.revokeObjectURL(blobUrl);
                                }, 250);
                            }
                            $wire.closeReceipt();
                        " 
                        type="button"
                        class="py-2.5 bg-gray-950 hover:bg-gray-800 text-white font-black rounded-lg transition-all text-center uppercase tracking-wider shadow cursor-pointer"
                    >
                        🖨️ Print
                    </button>
                    
                    <button 
                        x-init="$nextTick(() => { $el.focus(); })"
                        wire:click="closeReceipt" 
                        type="button"
                        class="py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-black rounded-lg transition-all text-center uppercase tracking-wide ring-4 ring-blue-300 outline-none focus:ring-4 focus:ring-blue-400 cursor-pointer"
                    >
                        Dismiss
                    </button>
                </div>

            </div>
        </div>
    @endif

    <style>
        .custom-receipt-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-receipt-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-receipt-scrollbar::-webkit-scrollbar-thumb {
            background-color: rgb(209, 213, 219);
            border-radius: 10px;
        }
    </style>

    @if($activeTab === 'reports')
        <div class="space-y-6">
            
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Sales Report</h2>
                </div>
                
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-xl">
                        @foreach(['daily' => 'Daily', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly'] as $value => $label)
                            <button type="button" 
                                    wire:click="$set('sales_filter', '{{ $value }}')"
                                    class="px-4 py-1.5 text-xs font-extrabold uppercase tracking-wide rounded-lg transition-all
                                    {{ $sales_filter === $value ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    @if($sales_filter === 'daily')
                        <div class="relative animate-in fade-in zoom-in-95 duration-150">
                            <input type="date" wire:model.live="selected_day" class="px-3 py-1.5 text-xs font-bold uppercase border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-700 bg-white">
                        </div>
                    @endif

                    @if($sales_filter === 'monthly')
                        <div class="relative animate-in fade-in zoom-in-95 duration-150">
                            <input type="month" wire:model.live="selected_month" class="px-3 py-1.5 text-xs font-bold uppercase border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-700 bg-white">
                        </div>
                    @endif

                    @if($sales_filter === 'quarterly')
                        <div class="relative animate-in fade-in zoom-in-95 duration-150">
                            <select wire:model.live="selected_quarter" class="px-3 py-1.5 text-xs font-bold uppercase border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-700 bg-white pr-8 cursor-pointer">
                                <option value="1">1st Quarter (Jan - Mar)</option>
                                <option value="2">2nd Quarter (Apr - Jun)</option>
                                <option value="3">3rd Quarter (Jul - Sep)</option>
                                <option value="4">4th Quarter (Oct - Dec)</option>
                            </select>
                        </div>
                    @endif

                    @if($sales_filter === 'yearly')
                        <div class="relative animate-in fade-in zoom-in-95 duration-150">
                            <select wire:model.live="selected_year" class="px-3 py-1.5 text-xs font-bold uppercase border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-700 bg-white pr-8 cursor-pointer">
                                @php $currentYear = (int)date('Y'); @endphp
                                @php $startYear = 2024; @endphp
                                @foreach(range($currentYear, $startYear) as $year)
                                    <option value="{{ $year }}">{{ $year }} Financials</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Sub-category Selection Filter transferred directly beside the Date Filters --}}
                    @if($reports_sub_tab === 'other_products')
                        <div class="relative w-full sm:w-56">
                            <select wire:model.live="other_product_category" class="w-full pl-3 pr-8 py-1.5 bg-slate-50 border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none appearance-none">
                                
                                <option value="">📦 All Non-Rice Categories</option>
                                
                                @foreach($categoriesAlphabetical as $category)
                                    @if($category->id !== 1) {{-- Skip rice here as it has its own dedicated tab panels --}}
                                        <option value="{{ $category->id }}">
                                            📁 {{ ucwords(strtolower($category->name)) }} Sales
                                        </option>
                                    @endif
                                @endforeach
                                
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400 text-[10px]">▼</div>
                        </div>
                    @endif

                    <button type="button" 
                            wire:click="exportToExcel" 
                            wire:loading.attr="disabled"
                            class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 text-white text-xs font-extrabold uppercase tracking-wide rounded-lg shadow-sm transition-all flex items-center gap-1.5"
                    >
                        <span wire:loading.remove wire:target="exportToExcel">📥 Export Report</span>
                        <span wire:loading wire:target="exportToExcel" class="inline-block animate-spin mr-0.5">⏳</span>
                        <span wire:loading wire:target="exportToExcel">Generating...</span>
                    </button>
                </div>
            </div>

            <div class="border-b border-gray-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <nav class="flex gap-2" aria-label="Tabs">
                    <button type="button"
                        wire:click="$set('reports_sub_tab', 'retail_rice')"
                        class="px-5 py-3 font-black text-xs uppercase tracking-wider border-b-2 rounded-t-xl transition-all flex items-center gap-2
                        {{ $reports_sub_tab === 'retail_rice' ? 'border-blue-600 bg-blue-50 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        🌾 Retail Rice (Kilo)
                    </button>

                    <button type="button"
                        wire:click="$set('reports_sub_tab', 'whole_rice')"
                        class="px-5 py-3 font-black text-xs uppercase tracking-wider border-b-2 rounded-t-xl transition-all flex items-center gap-2
                        {{ $reports_sub_tab === 'whole_rice' ? 'border-amber-600 bg-amber-50 text-amber-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        📦 Whole Rice Sacks
                    </button>

                    <button type="button"
                        wire:click="$set('reports_sub_tab', 'other_products')"
                        class="px-5 py-3 font-black text-xs uppercase tracking-wider border-b-2 rounded-t-xl transition-all flex items-center gap-2
                        {{ $reports_sub_tab === 'other_products' ? 'border-purple-600 bg-purple-50 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        🏪 Other Grocery Products
                    </button>
                </nav>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 border border-gray-200 rounded-2xl shadow-sm flex items-center gap-4">
                    <span class="text-3xl bg-green-50 p-3 rounded-xl">💰</span>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Net Revenue ({{ $sales_filter }})</p>
                        <h3 class="text-2xl font-black text-gray-900 mt-0.5">₱{{ number_format($dailyRevenue, 2) }}</h3>
                    </div>
                </div>

                <div class="bg-white p-5 border border-gray-200 rounded-2xl shadow-sm flex items-center gap-4">
                    <span class="text-3xl bg-emerald-50 p-3 rounded-xl">📈</span>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Actual Profit</p>
                        <h3 class="text-2xl font-black text-emerald-600 mt-0.5">₱{{ number_format($exactProfitTotal, 2) }}</h3>
                        <p class="text-[11px] text-gray-400 font-semibold mt-0.5">Based on Cost-Selling</p>
                    </div>
                </div>

                <div class="bg-white p-5 border border-gray-200 rounded-2xl shadow-sm flex items-center gap-4">
                    <span class="text-3xl bg-amber-50 p-3 rounded-xl">🏷️</span>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Discounts Awarded</p>
                        <h3 class="text-2xl font-black text-amber-600 mt-0.5">₱{{ number_format($dailyDiscounts, 2) }}</h3>
                        <p class="text-[11px] text-gray-400 font-semibold mt-0.5">Deducted upon payment</p>
                    </div>
                </div>

                <div class="bg-white p-5 border border-gray-200 rounded-2xl shadow-sm flex items-center gap-4">
                    <span class="text-3xl bg-blue-50 p-3 rounded-xl">🧾</span>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Transactions Logged</p>
                        <h3 class="text-2xl font-black text-blue-600 mt-0.5">{{ $dailyTransactionsCount }} Orders</h3>
                        <p class="text-[11px] text-gray-400 font-semibold mt-0.5">Segmented checkout tally</p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-black text-gray-900 uppercase tracking-wider border-b pb-3 mb-4 flex items-center gap-2">
                    <span>📋 Itemized Segment Log:</span> 
                    <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold uppercase tracking-wider
                        {{ $reports_sub_tab === 'retail_rice' ? 'bg-blue-100 text-blue-800' : '' }}
                        {{ $reports_sub_tab === 'whole_rice' ? 'bg-amber-100 text-amber-800' : '' }}
                        {{ $reports_sub_tab === 'other_products' ? 'bg-purple-100 text-purple-800' : '' }}">
                        {{ str_replace('_', ' ', $reports_sub_tab) }} 
                        @if($reports_sub_tab === 'other_products' && ($other_product_category ?? '') === 'cigarettes')
                            (Cigarettes Only)
                        @endif 
                        ({{ $sales_filter }})
                    </span>
                </h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-gray-400 text-xs font-bold uppercase tracking-wider">
                                <th class="p-3">Invoice Receipt ID</th>
                                <th class="p-3">Purchased Items Breakdown</th>
                                <th class="p-3">Status</th> 
                                <th class="p-3">Date & Timestamp</th>
                                <th class="p-3 text-right">Cash Received</th>
                                <th class="p-3 text-right">Discount Given</th>
                                <th class="p-3 text-right">Net Collection Amount</th>
                                <th class="p-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            @forelse($todayTransactions as $trans)
                                @php
                                    $filteredGrossAmount = 0.00;
                                    $entireInvoiceGrossTotal = 0.00;
                                    
                                    $isRiceSubTab = in_array($reports_sub_tab, ['retail_rice', 'whole_rice']);
                                    $isOtherProductsSubTab = ($reports_sub_tab === 'other_products');
                                    $requiresItemScrubbing = $isRiceSubTab || $isOtherProductsSubTab;

                                    // 1. Pre-calculate whole invoice gross total to parse exact proportional discount ratios 
                                    if ($trans->items && $trans->items->count() > 0) {
                                        foreach($trans->items as $pivotItem) {
                                            if (!optional($pivotItem->product)) continue;
                                            $actualPrice = (float)($pivotItem->unit_price ?? ($pivotItem->price ?? ($pivotItem->product->selling_price ?? 0.00)));
                                            $entireInvoiceGrossTotal += $actualPrice * (float)$pivotItem->quantity;
                                        }
                                    }

                                    $shouldSkipRow = false;
                                    $hasMatchingSegmentItems = false;

                                    // 2. Compute Segment Specific Aggregations
                                    if ($trans->items && $trans->items->count() > 0 && $entireInvoiceGrossTotal > 0) {
                                        foreach($trans->items as $pivotItem) {
                                            if (!optional($pivotItem->product)) continue;

                                            $itemCatId = (int)$pivotItem->product->category_id;
                                            $itemSku = $pivotItem->product->sku;
                                            $isItemRice = ($itemCatId === 1);
                                            $isItemKilo = str_starts_with($itemSku, 'KILO-');

                                            // Evaluate matching conditions based on the active tab segment
                                            $matchesSegment = true;
                                            if ($reports_sub_tab === 'retail_rice' && (!$isItemRice || !$isItemKilo)) $matchesSegment = false;
                                            if ($reports_sub_tab === 'whole_rice' && (!$isItemRice || $isItemKilo)) $matchesSegment = false;
                                            
                                            if ($reports_sub_tab === 'other_products') {
                                                if ($isItemRice) {
                                                    $matchesSegment = false;
                                                } elseif (!empty($other_product_category) && is_numeric($other_product_category)) {
                                                    // ✨ FIX: Apply dynamic category constraints inside item-scrubbing loops
                                                    if ($itemCatId !== (int)$other_product_category) $matchesSegment = false;
                                                }
                                            }

                                            if ($matchesSegment) {
                                                $hasMatchingSegmentItems = true;
                                                $uPrice = (float)($pivotItem->unit_price ?? ($pivotItem->price ?? (optional($pivotItem->product)->selling_price ?? 0)));
                                                $quantity = (float)($pivotItem->quantity ?? 0);
                                                $filteredGrossAmount += ($uPrice * $quantity);
                                            }
                                        }
                                    }

                                    if ($requiresItemScrubbing && (!$hasMatchingSegmentItems || $filteredGrossAmount == 0)) {
                                        $shouldSkipRow = true;
                                    }

                                    // 3. Proportional discount assignment variables
                                    $allocatedSegmentDiscount = 0.00;
                                    if ($entireInvoiceGrossTotal > 0 && $filteredGrossAmount > 0) {
                                        $segmentRatio = $filteredGrossAmount / $entireInvoiceGrossTotal;
                                        $allocatedSegmentDiscount = (float)$trans->discount * $segmentRatio;
                                    }
                                    
                                    $filteredNetAmount = $filteredGrossAmount - $allocatedSegmentDiscount;
                                @endphp

                                {{-- Clean DOM processing logic approach --}}
                                @if($shouldSkipRow)
                                    {{-- Empty block prevents the JS DOM-patch error --}}
                                @else
                                    <tr wire:key="tx-row-v3-{{ $trans->id }}-subtab-{{ $reports_sub_tab }}" 
                                        class="hover:bg-gray-50/60 transition-colors align-top {{ $trans->is_cancelled ? 'bg-gray-100/70 opacity-55 saturate-50 select-none' : '' }}">
                                        
                                        <td class="p-3 font-mono font-bold text-blue-600 pt-4 {{ $trans->is_cancelled ? 'text-gray-400 line-through' : '' }}">
                                            #{{ str_pad($trans->id, 6, '0', STR_PAD_LEFT) }}
                                        </td>
                                        
                                        <td class="p-3 pt-4 max-w-sm">
                                            <div class="space-y-1.5">
                                                @if($trans->items && $trans->items->count() > 0)
                                                    @foreach($trans->items as $pivotItem)
                                                        @php
                                                            if (!optional($pivotItem->product)) continue;
                                                            $itemCatId = (int)$pivotItem->product->category_id;
                                                            $itemSku = $pivotItem->product->sku;
                                                            $isItemRice = ($itemCatId === 1);
                                                            $isItemKilo = str_starts_with($itemSku, 'KILO-');

                                                            // Dynamic rendering filter matching visibility routines
                                                            if ($reports_sub_tab === 'retail_rice' && (!$isItemRice || !$isItemKilo)) continue;
                                                            if ($reports_sub_tab === 'whole_rice' && (!$isItemRice || $isItemKilo)) continue;
                                                            if ($reports_sub_tab === 'other_products') {
                                                                if ($isItemRice) continue;
                                                                if (!empty($other_product_category) && is_numeric($other_product_category)) {
                                                                    if ($itemCatId !== (int)$other_product_category) continue;
                                                                }
                                                            }

                                                            $unitPrice = (float)($pivotItem->unit_price ?? ($pivotItem->price ?? (optional($pivotItem->product)->selling_price ?? 0)));
                                                            $qty = (float)($pivotItem->quantity ?? 0);
                                                            $itemLineTotal = $unitPrice * $qty;
                                                        @endphp

                                                        <div class="text-xs text-gray-600 bg-gray-50 px-2.5 py-1.5 rounded border border-gray-100 flex flex-col {{ $trans->is_cancelled ? 'line-through bg-gray-200/50 text-gray-400' : '' }}">
                                                            <div class="flex items-center justify-between gap-3">
                                                                <div class="font-bold text-gray-900 break-words whitespace-normal min-w-0 flex-1">
                                                                    {{ $pivotItem->product_name ?? (optional($pivotItem->product)->name ?? 'Item') }}
                                                                    
                                                                    @if(isset($pivotItem->is_wholesale) && $pivotItem->is_wholesale)
                                                                        <span class="ml-1 inline-block bg-blue-100 text-blue-800 font-medium text-[10px] px-1.5 py-0.25 rounded uppercase tracking-wider whitespace-nowrap">Wholesale</span>
                                                                    @endif
                                                                </div>

                                                                <div class="flex items-center gap-2 font-mono text-[11px] shrink-0">
                                                                    <div class="text-gray-500 flex items-center gap-1">
                                                                        <span class="bg-gray-200/60 px-1.5 py-0.5 rounded text-gray-700">
                                                                            ₱{{ number_format($unitPrice, 2) }}
                                                                        </span>
                                                                        <span>×{{ $pivotItem->quantity }}</span>
                                                                    </div>
                                                                    <span class="text-gray-300 font-sans text-xs">=</span>
                                                                    <div class="font-bold text-purple-700">
                                                                        ₱{{ number_format($itemLineTotal, 2) }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <span class="text-xs text-gray-400 italic">No breakdown logs found</span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="p-3 pt-4 align-middle">
                                            @if($trans->is_cancelled)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold uppercase tracking-wider bg-red-100 text-red-700 border border-red-200">Voided</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold uppercase tracking-wider bg-green-100 text-green-700 border border-green-200">Settled</span>
                                            @endif
                                        </td>

                                        <td class="p-3 pt-4 font-medium text-gray-500 whitespace-nowrap {{ $trans->is_cancelled ? 'line-through' : '' }}">
                                            <span class="block text-xs font-bold text-gray-900">{{ $trans->created_at->timezone('Asia/Manila')->format('M d, Y') }}</span>
                                            <span class="block text-[11px] text-gray-400 font-mono mt-0.5">
                                                {{ $trans->created_at->timezone('Asia/Manila')->format('h:i A') }} 
                                                <span class="text-[10px]">({{ $trans->created_at->diffForHumans() }})</span>
                                            </span>
                                        </td>
                                        
                                        {{-- Gross Cash Received for matched category items --}}
                                        <td class="p-3 pt-4 text-right font-semibold text-gray-600 font-mono {{ $trans->is_cancelled ? 'line-through text-gray-400' : '' }}">
                                            ₱{{ number_format($requiresItemScrubbing ? $filteredGrossAmount : $trans->amount_paid, 2) }}
                                        </td>

                                        {{-- Exact Proportional Discount Component allocated exclusively to selected items --}}
                                        <td class="p-3 pt-4 text-right font-bold text-amber-600 font-mono {{ $trans->is_cancelled ? 'line-through text-gray-400' : '' }}">
                                            {{ $allocatedSegmentDiscount > 0 ? '₱' . number_format($allocatedSegmentDiscount, 2) : '—' }}
                                        </td>

                                        {{-- True Net Amount Display (Gross Amount minus allocated discount slice) --}}
                                        <td class="p-3 pt-4 text-right font-black text-gray-900 text-base font-mono {{ $trans->is_cancelled ? 'line-through text-gray-400 decoration-1' : '' }}">
                                            ₱{{ number_format($requiresItemScrubbing ? $filteredNetAmount : $trans->total_amount, 2) }}
                                        </td>

                                        <td class="p-3 pt-3.5 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button wire:click="reprintSale({{ $trans->id }})" {{ $trans->is_cancelled ? 'disabled' : '' }} class="px-2.5 py-1.5 bg-white hover:bg-blue-50 text-gray-700 hover:text-blue-600 text-xs font-black rounded-lg border border-gray-200 hover:border-blue-200 transition-all flex items-center gap-1 shadow-sm disabled:opacity-40 disabled:pointer-events-none">🖨Reprint</button>
                                                <button wire:click="cancelSale({{ $trans->id }})" wire:confirm="Are you sure you want to alter the status logs of Invoice #{{ str_pad($trans->id, 6, '0', STR_PAD_LEFT) }}?" class="px-2.5 py-1.5 rounded-lg text-xs font-black border transition-all shadow-sm flex items-center justify-center {{ $trans->is_cancelled ? 'bg-amber-50 border-amber-200 text-amber-700 hover:bg-amber-100' : 'bg-red-50 border-red-100 text-red-600 hover:bg-red-100' }}" title="{{ $trans->is_cancelled ? 'Restore Transaction' : 'Void/Cancel Transaction' }}">
                                                    {{ $trans->is_cancelled ? 'Restore' : 'Void' }}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="8" class="p-8 text-center text-sm text-gray-400 italic">No sales transactions logged under this specific filter rule yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                <div class="text-xs font-semibold text-gray-500">
                    Showing entries 
                    <span class="text-gray-900 font-bold">{{ $todayTransactions->firstItem() ?? 0 }}</span> 
                    to 
                    <span class="text-gray-900 font-bold">{{ $todayTransactions->lastItem() ?? 0 }}</span> 
                    of 
                    <span class="text-gray-900 font-bold">{{ $todayTransactions->total() }}</span> total receipts
                </div>

                <div class="flex items-center gap-2">
                    @if ($todayTransactions->onFirstPage())
                        <button type="button" disabled class="px-3 py-1.5 bg-gray-50 border border-gray-200 text-gray-400 text-xs font-extrabold rounded-lg cursor-not-allowed select-none transition-all uppercase tracking-wider">◀ Previous</button>
                    @else
                        <button type="button" wire:click="previousPage('salesLogPage')" wire:loading.attr="disabled" class="px-3 py-1.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 hover:text-blue-600 text-xs font-extrabold rounded-lg transition-all shadow-sm uppercase tracking-wider">◀ Previous</button>
                    @endif

                    @if ($todayTransactions->hasMorePages())
                        <button type="button" wire:click="nextPage('salesLogPage')" wire:loading.attr="disabled" class="px-3 py-1.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 hover:text-blue-600 text-xs font-extrabold rounded-lg transition-all shadow-sm uppercase tracking-wider">Next ▶</button>
                    @else
                        <button type="button" disabled class="px-3 py-1.5 bg-gray-50 border border-gray-200 text-gray-400 text-xs font-extrabold rounded-lg cursor-not-allowed select-none transition-all uppercase tracking-wider">Next ▶</button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($activeTab === 'purchases')
        <div class="space-y-6">
            
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Replenishment & Purchase Logs</h2>
                </div>
                
                <div class="flex flex-col md:flex-row items-start md:items-center gap-4 w-full xl:w-auto">
                    
                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <label class="text-xs font-bold uppercase text-gray-400 tracking-wider whitespace-nowrap">Filter Supplier:</label>
                        <select wire:model.live="selected_supplier" class="p-2 bg-white border border-gray-300 rounded-xl text-xs font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 outline-none min-w-[180px] w-full">
                            <option value="">All Registered Suppliers</option>
                            @foreach($availableSuppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if(!empty($selected_supplier) && $selected_supplier == $aquariusId)
                        <div class="flex items-center gap-2 w-full md:w-auto transition-all duration-200 animate-fadeIn">
                            <label class="text-xs font-bold uppercase text-slate-500 tracking-wider whitespace-nowrap">🌾 Rice Variety:</label>
                            <select wire:model.live="selected_ricemill_product" class="p-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-blue-500 outline-none min-w-[200px] w-full">
                                <option value="">All Rice Varieties</option>
                                @foreach($ricemillProducts as $riceProduct)
                                    <option value="{{ $riceProduct->id }}">{{ $riceProduct->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($purchase_filter === 'daily')
                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <label class="text-xs font-bold uppercase text-gray-400 tracking-wider whitespace-nowrap">Select Day:</label>
                            <input type="date" wire:model.live="selected_day" class="p-2 bg-white border border-gray-300 rounded-xl text-xs font-bold font-mono text-gray-700 focus:ring-2 focus:ring-blue-500 outline-none w-full md:w-auto" />
                        </div>
                    @endif

                    @if($purchase_filter === 'monthly')
                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <label class="text-xs font-bold uppercase text-gray-400 tracking-wider whitespace-nowrap">Select Month:</label>
                            <input type="month" wire:model.live="selected_month" class="p-2 bg-white border border-gray-300 rounded-xl text-xs font-bold font-mono text-gray-700 focus:ring-2 focus:ring-blue-500 outline-none w-full md:w-auto" />
                        </div>
                    @endif

                    <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-xl w-full md:w-auto justify-center">
                        @foreach(['daily' => 'Daily', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly'] as $value => $label)
                            <button type="button" 
                                    wire:click="$set('purchase_filter', '{{ $value }}')"
                                    class="px-4 py-1.5 text-xs font-extrabold uppercase tracking-wide rounded-lg transition-all w-full md:w-auto text-center
                                    {{ $purchase_filter === $value ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <div class="bg-slate-900 p-6 rounded-2xl shadow-sm text-white h-fit">
                    <p class="text-xs uppercase font-mono tracking-widest text-slate-400">Total Purchase Cost outlay</p>
                    <p class="text-3xl font-black mt-1 text-emerald-400">₱{{ number_format($totalPurchaseCost, 2) }}</p>
                    <div class="border-t border-slate-800 my-4"></div>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Accumulated cost layout matrix evaluated across recorded incoming supplier inventory intakes during your selected <span class="font-bold text-white underline uppercase">{{ $purchase_filter }}</span> filter framework.
                    </p>
                </div>

                <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider border-b pb-3 mb-4">
                        📋 Logged Batch Quantities
                    </h3>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200 text-gray-400 text-xs font-bold uppercase tracking-wider">
                                    <th class="p-3">Date / Time</th>
                                    <th class="p-3">Item Description</th>
                                    <th class="p-3">Supplier / Source Action</th>
                                    <th class="p-3 text-center">New Stock Qty</th>
                                    <th class="p-3 text-right">Unit Cost</th>
                                    <th class="p-3 text-right">Total Cost Ledger</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-700">
                                @forelse($loggedBatches as $batch)
                                    @php
                                        $product = $batch->product;
                                        $sku = strtoupper($product->sku ?? '');
                                        $qtyAdded = (float)($batch->quantity_added ?? 0);
                                        
                                        if (str_starts_with($sku, 'KILO') || $qtyAdded == 0) continue;

                                        $costPrice = (float)($batch->cost_price ?? ($product->cost_price ?? 0));
                                        $isAuditCorrection = ($qtyAdded < 0 || (is_null($batch->num_boxes) && is_null($batch->items_per_box) && $qtyAdded <= 0));
                                    @endphp
                                    <tr class="hover:bg-gray-50/80 transition-colors">
                                        <td class="p-3 text-xs text-gray-900 font-mono font-bold">
                                            {{ $batch->delivery_date ? \Carbon\Carbon::parse($batch->delivery_date)->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="p-3">
                                            <p class="font-extrabold text-gray-900">{{ $product->name ?? 'Unknown Product' }}</p>
                                            <span class="text-[10px] text-gray-400 font-mono uppercase">{{ $product->sku ?? 'NO-SKU' }}</span>
                                        </td>
                                        
                                        <td class="p-3">
                                            @if($qtyAdded < 0)
                                                <span class="inline-flex items-center text-[10px] bg-rose-50 text-rose-800 font-extrabold px-2 py-0.5 rounded-lg uppercase tracking-wide border border-rose-200/60">
                                                    ⚠️ Stock Shrinkage / Audit Loss
                                                </span>
                                            @elseif($isAuditCorrection)
                                                <span class="inline-flex items-center text-[10px] bg-amber-50 text-amber-800 font-extrabold px-2 py-0.5 rounded-lg uppercase tracking-wide border border-amber-200/60">
                                                    ⚙️ Audit Adjustment
                                                </span>
                                            @elseif(!empty($batch->supplier))
                                                <span class="inline-flex items-center text-[11px] bg-slate-100 text-slate-800 font-extrabold px-2.5 py-1 rounded-xl uppercase tracking-wide">
                                                    🏢 {{ $batch->supplier->name }}
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400 italic">No Vendor Assigned</span>
                                            @endif
                                        </td>

                                        <td class="p-3 text-center font-extrabold font-mono">
                                            <span class="{{ $qtyAdded > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                                {{ $qtyAdded > 0 ? '+' : '' }}{{ number_format($qtyAdded) }}
                                            </span>
                                        </td>
                                        
                                        <td class="p-3 text-right font-mono text-xs text-gray-400">
                                            ₱{{ number_format($costPrice, 2) }}
                                        </td>
                                        
                                        <td class="p-3 text-right font-mono text-sm font-bold">
                                            @if($qtyAdded < 0)
                                                <span class="text-rose-500 font-medium italic">(-₱{{ number_format(abs($qtyAdded * $costPrice), 2) }})</span>
                                            @else
                                                <span class="text-gray-900 font-black">₱{{ number_format($qtyAdded * $costPrice, 2) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="p-8 text-center text-sm text-gray-400 italic">
                                            No batch purchases found for this filtered perspective matrix setup.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $loggedBatches->links() }}
                    </div>
                </div>

            </div>
        </div>
    @endif

    @if($weighedProduct)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-xs w-full border border-gray-100 text-center">
                
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-3 text-xl">
                    ⚖️
                </div>
                
                <h3 class="font-black text-gray-900 text-base uppercase tracking-tight">{{ $weighedProduct->name }}</h3>
                <p class="text-xs text-gray-500 mt-0.5 mb-4">Base Rate: ₱{{ number_format($weighedProduct->selling_price, 2) }} / kilo</p>
                
                <div class="mb-5 text-left">
                    <label class="block text-[11px] font-black text-gray-700 uppercase tracking-wider mb-1">Enter Scale Weight Reading (KG):</label>
                    <input 
                        id="weight-input"
                        wire:model.defer="manualWeight"
                        @keydown.enter="$wire.confirmWeighedItem()"
                        type="number" 
                        step="0.001" 
                        placeholder="0.000"
                        class="w-full text-center text-2xl font-black bg-gray-50 border-2 border-gray-200 rounded-xl p-2.5 focus:border-blue-600 focus:bg-white text-gray-900 shadow-inner outline-none transition-all"
                    />
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs font-bold uppercase tracking-wider">
                    <button 
                        wire:click="$set('weighedProduct', null)" 
                        type="button" 
                        class="py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="confirmWeighedItem" 
                        type="button" 
                        class="py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow transition-colors"
                    >
                        Add (Enter)
                    </button>
                </div>
            </div>
        </div>

        <script>
            window.addEventListener('focus-weight-prompt', event => {
                setTimeout(() => {
                    const el = document.getElementById('weight-input');
                    if(el) el.focus();
                }, 50);
            });
        </script>
    @endif

    @if($activeTab === 'settings')
        <div class="space-y-6 animate-in fade-in duration-150">
            
            @if (session()->has('settings_success'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 text-xs font-bold rounded-xl shadow-sm">
                    ✅ {{ session('settings_success') }}
                </div>
            @endif
            @if (session()->has('settings_error'))
                <div class="p-4 bg-red-50 border border-red-200 text-red-700 text-xs font-bold rounded-xl shadow-sm">
                    ❌ {{ session('settings_error') }}
                </div>
            @endif

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">System Settings Library</h2>
                    <p class="text-xs font-semibold text-gray-400 mt-0.5">Manage entity attributes across product dependencies</p>
                </div>
                <div>
                    @if($settings_sub_tab === 'categories')
                        <button wire:click="openCategoryModal" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold uppercase tracking-wide rounded-lg shadow-sm transition-all flex items-center gap-1">
                            ➕ Add New Category
                        </button>
                    @else
                        <button wire:click="openSupplierModal" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold uppercase tracking-wide rounded-lg shadow-sm transition-all flex items-center gap-1">
                            ➕ Add New Supplier
                        </button>
                    @endif
                </div>
            </div>

            <div class="border-b border-gray-200">
                <nav class="flex gap-2" aria-label="Tabs">
                    <button type="button" wire:click="$set('settings_sub_tab', 'categories')"
                        class="px-5 py-3 font-black text-xs uppercase tracking-wider border-b-2 rounded-t-xl transition-all flex items-center gap-2
                        {{ $settings_sub_tab === 'categories' ? 'border-blue-600 bg-blue-50 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        📁 Categories ({{ $categories->count() }})
                    </button>
                    <button type="button" wire:click="$set('settings_sub_tab', 'suppliers')"
                        class="px-5 py-3 font-black text-xs uppercase tracking-wider border-b-2 rounded-t-xl transition-all flex items-center gap-2
                        {{ $settings_sub_tab === 'suppliers' ? 'border-purple-600 bg-purple-50 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        🏢 Suppliers Library ({{ $suppliers->count() }})
                    </button>
                </nav>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                @if($settings_sub_tab === 'categories')
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200 text-gray-400 text-xs font-bold uppercase tracking-wider">
                                    <th class="p-3">ID</th>
                                    <th class="p-3">Category Name</th>
                                    <th class="p-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-700">
                                @forelse($categories as $cat)
                                    <tr class="hover:bg-gray-50/60 transition-all">
                                        <td class="p-3 font-mono text-xs text-gray-400">#{{ $cat->id }}</td>
                                        <td class="p-3 font-bold text-gray-900">{{ $cat->name }}</td>
                                        <td class="p-3 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button wire:click="openCategoryModal({{ $cat->id }})" class="px-2 py-1 text-xs font-bold border rounded-md text-blue-600 hover:bg-blue-50 transition-all">Edit ✏️</button>
                                                <button wire:click="deleteCategory({{ $cat->id }})" wire:confirm="Are you sure you want to delete this category?" class="px-2 py-1 text-xs font-bold border rounded-md text-red-600 hover:bg-red-50 transition-all">Delete 🗑️</button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-8 text-gray-400 text-xs">No categories listed in the library.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200 text-gray-400 text-xs font-bold uppercase tracking-wider">
                                    <th class="p-3">Supplier Name</th>
                                    <th class="p-3">Contact Person</th>
                                    <th class="p-3">Phone Line</th>
                                    <th class="p-3">Address Location</th>
                                    <th class="p-3">Remarks</th> <th class="p-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-700">
                                @forelse($suppliers as $sup)
                                    <tr class="hover:bg-gray-50/60 transition-all">
                                        <td class="p-3 font-bold text-gray-900">{{ $sup->name }}</td>
                                        <td class="p-3 text-xs text-gray-600">{{ $sup->contact_person ?? '—' }}</td>
                                        <td class="p-3 font-mono text-xs text-gray-600">{{ $sup->phone ?? '—' }}</td>
                                        <td class="p-3 text-xs text-gray-500 max-w-xs truncate">{{ $sup->address ?? '—' }}</td>
                                        <td class="p-3 text-xs text-gray-400 max-w-xs truncate italic">
                                            {{ $sup->remarks ?? 'No notes attached' }}
                                        </td>
                                        <td class="p-3 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button wire:click="openSupplierModal({{ $sup->id }})" class="px-2 py-1 text-xs font-bold border rounded-md text-purple-600 hover:bg-purple-50 transition-all">Edit ✏️</button>
                                                <button wire:click="deleteSupplier({{ $sup->id }})" wire:confirm="Are you sure you want to delete this supplier profile?" class="px-2 py-1 text-xs font-bold border rounded-md text-red-600 hover:bg-red-50 transition-all">Delete 🗑️</button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-8 text-gray-400 text-xs">No vendor profiles registered in system library database.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <h3 class="text-xs font-black uppercase text-gray-400 tracking-wider flex items-center gap-1">
                            🛡️ System Administration & Recovery
                        </h3>
                        <h4 class="text-base font-bold text-gray-900">
                            On-Demand Database Backup
                        </h4>
                        <p class="text-xs text-gray-500 leading-relaxed max-w-2xl">
                            Download a standalone SQL export structure copy of your application's transaction history logs, supplier entities, and product tracking metrics to safely secure cold-storage local backups.
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <button type="button" 
                                wire:click="downloadDatabaseBackup" 
                                wire:loading.attr="disabled"
                                class="w-full md:w-auto px-5 py-3 bg-slate-900 hover:bg-slate-800 text-white text-xs font-extrabold uppercase tracking-wider rounded-xl shadow-sm transition-all flex items-center justify-center gap-2 disabled:opacity-50">
                            <span wire:loading.remove wire:target="downloadDatabaseBackup">📥 Export SQL Backup</span>
                            <span wire:loading wire:target="downloadDatabaseBackup" class="inline-flex items-center gap-2">
                                <svg class="animate-spin h-3 w-3 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Bundling Assets...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

        </div>

        @if($show_category_modal)
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 animate-in fade-in duration-200">
                <div class="bg-white p-6 rounded-2xl border shadow-xl w-full max-w-md mx-4 animate-in zoom-in-95 duration-150">
                    <h3 class="text-sm font-black uppercase text-gray-900 border-b pb-2 mb-4">{{ $category_id ? 'Update Category' : 'Create New Category' }}</h3>
                    <form wire:submit.prevent="saveCategory" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Category Name</label>
                            <input type="text" wire:model="category_name" class="w-full px-3 py-2 border rounded-xl text-sm font-semibold text-gray-900 focus:outline-none focus:border-blue-500" placeholder="e.g., Canned Goods, Beverages">
                            @error('category_name') <span class="text-[11px] font-bold text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="button" wire:click="$set('show_category_modal', false)" class="px-4 py-2 text-xs font-bold bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200">Cancel</button>
                            <button type="submit" class="px-4 py-2 text-xs font-bold bg-blue-600 text-white rounded-xl hover:bg-blue-700 shadow-sm">Save Segment</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        @if($show_supplier_modal)
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 animate-in fade-in duration-200">
                <div class="bg-white p-6 rounded-2xl border shadow-xl w-full max-w-lg mx-4 animate-in zoom-in-95 duration-150">
                    <h3 class="text-sm font-black uppercase text-gray-900 border-b pb-2 mb-4">
                        {{ $supplier_id ? 'Modify Supplier Profile' : 'Register Supplier Entity' }}
                    </h3>
                    <form wire:submit.prevent="saveSupplier" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Supplier / Vendor Name</label>
                                <input type="text" wire:model="supplier_name" 
                                    class="w-full px-3 py-2 border rounded-xl text-sm font-semibold text-gray-900 focus:outline-none focus:border-purple-500" 
                                    placeholder="e.g., San Miguel Corp, Alaska Milk">
                                @error('supplier_name') 
                                    <span class="text-[11px] font-bold text-red-500 mt-1 block">⚠️ {{ $message }}</span> 
                                @enderror
                            </div>
                            
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Contact Agent Name</label>
                                <input type="text" wire:model="supplier_contact" class="w-full px-3 py-2 border rounded-xl text-sm font-medium text-gray-900 focus:outline-none focus:border-purple-500" placeholder="e.g., Juan Dela Cruz">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Active Phone Line</label>
                                <input type="text" wire:model="supplier_phone" class="w-full px-3 py-2 border rounded-xl text-sm font-mono text-gray-900 focus:outline-none focus:border-purple-500" placeholder="e.g., 0917XXXXXXX">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Office / Warehouse Address</label>
                                <textarea wire:model="supplier_address" rows="2" class="w-full px-3 py-2 border rounded-xl text-sm font-medium text-gray-900 focus:outline-none focus:border-purple-500" placeholder="Street layout details..."></textarea>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Internal Remarks / Terms (Optional)</label>
                                <textarea wire:model="supplier_remarks" rows="2" class="w-full px-3 py-2 border rounded-xl text-sm font-medium text-gray-900 focus:outline-none focus:border-purple-500" placeholder="e.g., Delivery scheduled every Tuesday; accepts post-dated checks..."></textarea>
                                @error('supplier_remarks') 
                                    <span class="text-[11px] font-bold text-red-500 mt-1 block">⚠️ {{ $message }}</span> 
                                @enderror
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="button" wire:click="$set('show_supplier_modal', false)" class="px-4 py-2 text-xs font-bold bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200">Cancel</button>
                            <button type="submit" class="px-4 py-2 text-xs font-bold bg-blue-600 text-white rounded-xl hover:bg-blue-700 shadow-sm">Save Record</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

    @endif

    @if($activeTab === 'digital_services')
        <div class="space-y-6">
            
            <!-- 💼 CONFIGURATION CONTROL & TIMEFRAME SCOPE FILTER BAR -->
            <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="text-sm font-black text-gray-800 uppercase tracking-wide">💼 Digital Capital Desk</h2>
                    <p class="text-xs text-gray-400">Track and monitor your actual cash drawer vs. digital phone bank funds live.</p>
                </div>
                
                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                    <select wire:model.live="filter_service" class="text-xs font-bold p-2.5 bg-gray-50 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="">💼 All Services</option>
                        
                        <optgroup label="Customer Services">
                            <option value="gcash_cash_in">📱 GCash - Cash In</option>
                            <option value="gcash_cash_out">📤 GCash - Cash Out</option>
                            <option value="eload">⚡ E-Loading Platform</option>
                        </optgroup>

                        <optgroup label="Store Balance Refilling">
                            <option value="drawer_to_gcash_refill">🔄 Drawer Cash ➡️ GCash</option>
                            <option value="gcash_to_drawer_refill">💰 GCash ➡️ Cash Drawer</option>
                        </optgroup>
                    </select>

                    <select wire:model.live="filter_type" class="text-xs font-bold p-2.5 bg-gray-50 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="daily">📅 Daily</option>
                        <option value="monthly">🗓️ Monthly</option>
                    </select>

                    @if($filter_type === 'daily')
                        <input type="date" wire:model.live="filter_date" class="text-xs font-bold p-2.5 bg-gray-50 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
                    @else
                        <input type="month" wire:model.live="filter_month" class="text-xs font-bold p-2.5 bg-gray-50 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" />
                    @endif

                    <button type="button" wire:click="$toggle('show_capital_form')" class="text-xs bg-slate-900 hover:bg-slate-800 text-white font-bold px-4 py-2.5 rounded-xl transition-all uppercase tracking-wider shadow-sm ml-auto md:ml-0">
                        {{ $show_capital_form ? '🙈 Close Tool' : '➕ Add/Replenish Funds' }}
                    </button>
                </div>
            </div>

            <!-- 💰 ADD/REPLENISH CAPITAL INJECTION FORM -->
            @if($show_capital_form)
                <div class="bg-blue-50/50 p-5 rounded-2xl border border-blue-200">
                    <h4 class="text-xs font-black uppercase text-blue-900 tracking-wider mb-3">💰 Record Fund Replenishment</h4>
                    
                    @if (session()->has('capital_success'))
                        <div class="p-3 mb-3 text-xs text-emerald-800 bg-emerald-50 rounded-xl border border-emerald-200 font-medium">
                            {{ session('capital_success') }}
                        </div>
                    @endif

                    <form wire:submit.prevent="submitCapitalInjection" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                        <div>
                            <label class="text-[10px] font-black uppercase text-gray-500 block mb-1">Target Pool Destination</label>
                            <select wire:model="inject_type" class="w-full p-2.5 bg-white border border-gray-300 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="cash">💵 Add to Cash-on-Hand Drawer</option>
                                <option value="gcash">📱 Add to GCash Wallet Balance</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-gray-500 block mb-1">Transaction Date</label>
                            <input type="date" wire:model="digital_date" class="w-full p-2.5 bg-white border border-gray-300 rounded-xl text-sm font-bold text-gray-900 outline-none focus:ring-2 focus:ring-blue-500" required />
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-gray-500 block mb-1">Amount to Add (₱)</label>
                            <input type="number" step="any" wire:model="inject_amount" class="w-full p-2.5 bg-white border border-gray-300 rounded-xl text-sm font-mono font-bold text-gray-900 outline-none focus:ring-2 focus:ring-blue-500" placeholder="₱0.00" required />
                        </div>
                        <div>
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold p-2.5 rounded-xl transition-all uppercase tracking-wide text-xs shadow-sm">
                                Save Log Entry
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <!-- 📊 REAL-TIME OVERVIEW METRICS BLOCK (MATCHING THE FIRST DISPLAY ROW IN THE ATTACHMENT) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Card 1: Cash Drawer -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
                    <p class="text-xs uppercase font-bold text-gray-400 tracking-wider">
                        💵 {{ $filter_type === 'daily' && $filter_date !== now()->format('Y-m-d') ? 'Historical Snapshot' : 'Total Running' }} Cash Drawer
                    </p>
                    <p class="text-2xl font-black mt-1 text-gray-900 font-mono tracking-tight">
                        ₱{{ number_format($runningCashOnHand, 2) }}
                    </p>
                    <span class="text-[10px] text-gray-400 block mt-1">
                        @if($filter_type === 'daily')
                            Cash balance at end of day: <span class="font-semibold text-gray-600">{{ \Carbon\Carbon::parse($filter_date)->format('M d, Y') }}</span>
                        @else
                            Cash balance at end of month: <span class="font-semibold text-gray-600">{{ \Carbon\Carbon::parse($filter_month . '-01')->format('F Y') }}</span>
                        @endif
                    </span>
                </div>

                <!-- Card 2: GCash Wallet -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
                    <p class="text-xs uppercase font-bold text-gray-400 tracking-wider">
                        📱 {{ $filter_type === 'daily' && $filter_date !== now()->format('Y-m-d') ? 'Historical Snapshot' : 'Total Running' }} GCash Wallet
                    </p>
                    <p class="text-2xl font-black mt-1 text-blue-600 font-mono tracking-tight">
                        ₱{{ number_format($runningGCashBalance, 2) }}
                    </p>
                    <span class="text-[10px] text-gray-400 block mt-1">
                        @if($filter_type === 'daily')
                            Digital balance at end of day: <span class="font-semibold text-blue-600">{{ \Carbon\Carbon::parse($filter_date)->format('M d, Y') }}</span>
                        @else
                            Digital balance at end of month: <span class="font-semibold text-blue-600">{{ \Carbon\Carbon::parse($filter_month . '-01')->format('F Y') }}</span>
                        @endif
                    </span>
                </div>

                <!-- Card 3: Earnings Scope -->
                <div class="bg-slate-900 p-5 rounded-2xl text-white shadow-sm">
                    <p class="text-xs uppercase font-bold text-slate-400 tracking-wider">✨ Total Fees Collected</p>
                    <p class="text-2xl font-black mt-1 text-cyan-400 font-mono tracking-tight">
                        ₱{{ number_format($totalFeesEarned, 2) }}
                    </p>
                    <span class="text-[10px] text-slate-400 block mt-1">
                        Calculated for: <span class="font-bold underline text-white">
                            {{ $filter_type === 'daily' ? 'Date '. \Carbon\Carbon::parse($filter_date)->format('M d, Y') : 'Month '. \Carbon\Carbon::parse($filter_month.'-01')->format('F Y') }}
                        </span>
                    </span>
                </div>
            </div>

            <!-- 📈 RE-ENGINEERED ELEMENT CARDS: ALIGNED CARD LAYOUT MATRICES FOR VOLUME BREAKDOWNS -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Cash In Module Card -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-between relative overflow-hidden">
                    <div>
                        <div class="flex justify-between items-start">
                            <p class="text-xs uppercase font-bold text-gray-400 tracking-wider">🔵 Total Cash-In</p>
                            <span class="text-[10px] font-black text-blue-700 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-md uppercase">In</span>
                        </div>
                        <p class="text-2xl font-black mt-2 text-gray-900 font-mono tracking-tight">
                            ₱{{ number_format($totalCashInVolume, 2) }}
                        </p>
                    </div>
                    <span class="text-[10px] text-gray-400 block mt-3 pt-2 border-t border-gray-50 italic">
                        Summed volume for active {{ $filter_type }} filter
                    </span>
                </div>

                <!-- Total Cash Out Module Card -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-between relative overflow-hidden">
                    <div>
                        <div class="flex justify-between items-start">
                            <p class="text-xs uppercase font-bold text-gray-400 tracking-wider">🟣 Total Cash-Out</p>
                            <span class="text-[10px] font-black text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-md uppercase">Out</span>
                        </div>
                        <p class="text-2xl font-black mt-2 text-gray-900 font-mono tracking-tight">
                            ₱{{ number_format($totalCashOutVolume, 2) }}
                        </p>
                    </div>
                    <span class="text-[10px] text-gray-400 block mt-3 pt-2 border-t border-gray-50 italic">
                        Summed volume for active {{ $filter_type }} filter
                    </span>
                </div>

                <!-- Total E-Load Dispended Module Card -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex flex-col justify-between relative overflow-hidden">
                    <div>
                        <div class="flex justify-between items-start">
                            <p class="text-xs uppercase font-bold text-gray-400 tracking-wider">📱 Total E-Load Dispensed</p>
                            <span class="text-[10px] font-black text-purple-700 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded-md uppercase">Load</span>
                        </div>
                        <p class="text-2xl font-black mt-2 text-gray-900 font-mono tracking-tight">
                            ₱{{ number_format($totalELoadVolume, 2) }}
                        </p>
                    </div>
                    <span class="text-[10px] text-gray-400 block mt-3 pt-2 border-t border-gray-50 italic">
                        Summed volume for active {{ $filter_type }} filter
                    </span>
                </div>
            </div>

            <!-- 🛠️ LOGGING TERMINAL & TABLE LAYOUT -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Contextual Console Form Component -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border h-fit {{ $is_edit_mode ? 'border-amber-300 ring-2 ring-amber-100/50' : 'border-gray-200' }}">
                    <div class="flex justify-between items-center mb-4 pb-2 border-b">
                        <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">
                            {{ $is_edit_mode ? '✏️ Modify Action Log' : '📝 Log Digital Action' }}
                        </h3>
                        @if($is_edit_mode)
                            <span class="text-[10px] bg-amber-100 text-amber-800 font-bold px-2 py-0.5 rounded-md uppercase">Edit Mode</span>
                        @endif
                    </div>
                    
                    @if (session()->has('digital_success'))
                        <div class="p-3 mb-4 text-xs text-emerald-800 bg-emerald-50 rounded-xl border border-emerald-200 font-medium">
                            {{ session('digital_success') }}
                        </div>
                    @endif

                    <form wire:submit.prevent="saveDigitalTransaction" class="space-y-4">
                        <div>
                            <label class="text-xs font-bold uppercase text-gray-500 block mb-1">Service Platform</label>
                            <select wire:model="digital_service_type" class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-xl text-sm font-medium focus:ring-2 focus:ring-blue-500 outline-none">
                                <optgroup label="Customer Services">
                                    <option value="gcash_cash_in">GCash - Cash In</option>
                                    <option value="gcash_cash_out">GCash - Cash Out</option>
                                    <option value="eload">E-Loading Platform</option>
                                </optgroup>

                                <optgroup label="Store Balance Refilling">
                                    <option value="drawer_to_gcash_refill">🔄 Drawer Cash ➡️ Refill GCash Wallet (Internal)</option>
                                    <option value="gcash_to_drawer_refill">💰 GCash Wallet ➡️ Refill Cash Drawer (Internal)</option>
                                </optgroup>
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-bold uppercase text-gray-500 block mb-1">Transaction Date (Backtrack Option)</label>
                            <input type="date" wire:model="digital_date" class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-xl text-sm font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 outline-none" required />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-bold uppercase text-gray-500 block mb-1">Principal Amount(₱)</label>
                                <input type="number" step="any" wire:model="digital_amount" class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-xl text-sm font-mono font-bold focus:ring-2 focus:ring-blue-500 outline-none" required />
                            </div>
                            <div>
                                <label class="text-xs font-bold uppercase text-gray-500 block mb-1">Fee Collected (₱)</label>
                                <input type="number" step="any" wire:model="digital_fee" class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-xl text-sm font-mono font-bold focus:ring-2 focus:ring-blue-500 outline-none" required />
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-bold uppercase text-gray-500 block mb-1">Customer Target Number</label>
                            <input type="text" wire:model="digital_phone" placeholder="09XXXXXXXXX" class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
                        </div>

                        <div>
                            <label class="text-xs font-bold uppercase text-gray-500 block mb-1">Ref Number / Package Promo</label>
                            <input type="text" wire:model="digital_ref" placeholder="Instapay Ref or Load Combo Code" class="w-full p-2.5 bg-gray-50 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
                        </div>

                        <div class="flex gap-3">
                            @if($is_edit_mode)
                                <button type="button" wire:click="cancelTxEdit" class="w-1/3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold p-3 rounded-xl transition-all uppercase tracking-wide text-xs">
                                    Cancel
                                </button>
                            @endif
                            <button type="submit" class="w-full font-bold p-3 rounded-xl transition-all uppercase tracking-wide text-xs shadow-sm {{ $is_edit_mode ? 'bg-amber-500 hover:bg-amber-600 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                                {{ $is_edit_mode ? 'Update Transaction' : 'Add Transaction' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Standalone Ledger Display Grid Matrix -->
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="flex justify-between items-center mb-4 pb-2 border-b">
                        <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">📜 History Log Matrix</h3>
                        <span class="text-xs font-bold text-gray-400 bg-gray-100 px-2.5 py-1 rounded-lg">
                            Showing: {{ $filter_type === 'daily' ? 'Selected Day' : 'Selected Month' }}
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200 text-gray-400 text-xs font-bold uppercase tracking-wider">
                                    <th class="p-3">Timestamp</th>
                                    <th class="p-3">Ref Number</th>
                                    <th class="p-3">Service Action</th>
                                    <th class="p-3 text-right">Principal</th>
                                    <th class="p-3 text-right">Fee Margin</th>
                                    <th class="p-3 text-right">Drawer Cash</th>
                                    <th class="p-3 text-right">GCash App</th>
                                    <th class="p-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-700">
                                @forelse($digitalTransactions as $tx)
                                    <tr class="hover:bg-gray-50/80 transition-colors {{ $editing_tx_id === $tx->id ? 'bg-amber-50/60' : '' }}">
                                        <td class="p-3 text-xs font-mono text-gray-400">
                                            {{ \Carbon\Carbon::parse($tx->logged_at)->format('h:i A') }}
                                            <span class="block text-[9px] text-gray-600 font-sans font-bold mt-0.5">🗓️ {{ \Carbon\Carbon::parse($tx->logged_at)->format('M d, Y') }}</span>
                                        </td>
                                        <td class="p-3 text-xs font-mono">
                                            @if($tx->reference_number)
                                                <span class="font-bold text-gray-700 bg-gray-100 px-2 py-1 rounded-md">{{ $tx->reference_number }}</span>
                                                @if($tx->customer_phone)
                                                    <span class="block text-[9px] text-gray-400 font-sans mt-0.5">📱 {{ $tx->customer_phone }}</span>
                                                @endif
                                            @else
                                                <span class="text-gray-300 italic">—</span>
                                            @endif
                                        </td>
                                        <td class="p-3">
                                            @if($tx->service_type === 'gcash_cash_in')
                                                <span class="inline-flex items-center text-[10px] bg-blue-50 text-blue-700 font-extrabold px-2 py-0.5 rounded-lg uppercase border border-blue-200">🔵 Cash-In</span>
                                            @elseif($tx->service_type === 'gcash_cash_out')
                                                <span class="inline-flex items-center text-[10px] bg-indigo-50 text-indigo-700 font-extrabold px-2 py-0.5 rounded-lg uppercase border border-indigo-200">🟣 Cash-Out</span>
                                            @elseif($tx->service_type === 'eload')
                                                <span class="inline-flex items-center text-[10px] bg-purple-50 text-purple-700 font-extrabold px-2 py-0.5 rounded-lg uppercase border border-purple-200">📱 E-Load</span>
                                            @elseif($tx->service_type === 'drawer_to_gcash_refill')
                                                <span class="inline-flex items-center text-[10px] bg-amber-50 text-amber-800 font-extrabold px-2 py-0.5 rounded-lg uppercase border border-amber-300">🔄 Wallet Refill</span>
                                            @elseif($tx->service_type === 'gcash_to_drawer_refill')
                                                <span class="inline-flex items-center text-[10px] bg-orange-50 text-orange-800 font-extrabold px-2 py-0.5 rounded-lg uppercase border border-orange-300">💰 Drawer Refill</span>
                                            @elseif($tx->service_type === 'capital_inject_cash')
                                                <span class="inline-flex items-center text-[10px] bg-emerald-100 text-emerald-800 font-black px-2 py-0.5 rounded-lg uppercase border border-emerald-300">💵 Added Cash Capital</span>
                                            @elseif($tx->service_type === 'capital_inject_gcash')
                                                <span class="inline-flex items-center text-[10px] bg-sky-100 text-sky-800 font-black px-2 py-0.5 rounded-lg uppercase border border-sky-300">📱 Added GCash Capital</span>
                                            @endif
                                        </td>
                                        <td class="p-3 text-right font-mono text-sm font-semibold text-gray-900">
                                            ₱{{ number_format($tx->amount, 2) }}
                                        </td>
                                        <td class="p-3 text-right font-mono text-sm font-black text-emerald-600">
                                            {{ $tx->service_fee > 0 ? '+₱' . number_format($tx->service_fee, 2) : '—' }}
                                        </td>
                                        <td class="p-3 text-right font-mono text-xs font-bold {{ $tx->cash_on_hand_change >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                            {{ $tx->cash_on_hand_change == 0 ? '—' : ($tx->cash_on_hand_change > 0 ? '+' : '') . '₱' . number_format($tx->cash_on_hand_change, 2) }}
                                        </td>
                                        <td class="p-3 text-right font-mono text-xs font-bold {{ $tx->gcash_balance_change >= 0 ? 'text-blue-600' : 'text-amber-600' }}">
                                            {{ $tx->gcash_balance_change == 0 ? '—' : ($tx->gcash_balance_change > 0 ? '+' : '') . '₱' . number_format($tx->gcash_balance_change, 2) }}
                                        </td>
                                        <td class="p-3 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                @if(in_array($tx->service_type, ['gcash_cash_in', 'gcash_cash_out', 'eload', 'drawer_to_gcash_refill', 'gcash_to_drawer_refill']))
                                                    <button type="button" wire:click="editDigitalTransaction({{ $tx->id }})" class="text-[11px] bg-gray-100 hover:bg-amber-100 text-gray-600 hover:text-amber-800 px-2.5 py-1 rounded-lg font-bold transition-colors uppercase tracking-wide border border-gray-200 hover:border-amber-300">
                                                        ✏️ Edit
                                                    </button>
                                                    <button type="button" onclick="confirm('Are you sure you want to delete this entry? This will reverse its balance changes.') || event.stopImmediatePropagation()" wire:click="archiveDigitalTransaction({{ $tx->id }})" class="text-[11px] bg-red-100 hover:bg-rose-100 text-gray-600 hover:text-rose-700 px-2.5 py-1 rounded-lg font-bold transition-colors uppercase tracking-wide border border-gray-200 hover:border-rose-200">
                                                        🗑️ Delete
                                                    </button>
                                                @else
                                                    <span class="text-[9px] text-gray-400 italic">🔒 System Record</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="p-8 text-center text-sm text-gray-400 italic">No transactions captured within this timeframe context.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $digitalTransactions->links() }}
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    // Listen for the focus events
    window.addEventListener('focus-weight-prompt', event => {
        const input = document.getElementById('weight-input-field'); // Ensure your input has this ID
        if (input) input.focus();
    });

    window.addEventListener('focus-barcode', event => {
        const input = document.querySelector('[x-ref="barcodeField"]');
        if (input) input.focus();
    });

    // window.addEventListener('open-cash-drawer', event => {
    //     // This is the ESC/POS command for a standard Cash Drawer Kick
    //     // 27, 112, 0, 60, 240 is the standard sequence
    //     const drawerKickCommand = new Uint8Array([0x1B, 0x70, 0x00, 0x3C, 0xF0]);
        
    //     // Use the Web Serial API or standard print workflow
    //     // The most reliable way for browser-based POS is via a silent print job
    //     // or a dedicated print server.
    //     console.log("Triggering Cash Drawer...");
        
    //     // Simple hack: Trigger a tiny print job that only contains the kick command
    //     // Note: This requires the browser to have a default printer set to your POS printer
    //     var printWindow = window.open('', '', 'width=0,height=0');
    //     printWindow.document.write(String.fromCharCode(0x1B, 0x70, 0x00, 0x3C, 0xF0));
    //     printWindow.document.close();
    //     printWindow.print();
    //     printWindow.close();
    // }); 

    // Keep track of our hardware connection globally
    let authorizedDrawerPort = null;

    /**
     * 🔌 Attempts to silently re-establish a link with a previously 
     * authorized USB Serial Trigger box on application boot.
     */
    async function initializePersistentHardware() {
        if ('serial' in navigator) {
            try {
                const allowedPorts = await navigator.serial.getPorts();
                if (allowedPorts.length > 0) {
                    // Auto-bind to the first active authorized device slot found
                    authorizedDrawerPort = allowedPorts[0];
                    console.log("Persistent cash drawer hardware port recovered:", authorizedDrawerPort);
                }
            } catch (err) {
                console.error("Failed to recover persistent serial profiles:", err);
            }
        }
    }

    // Automatically scan for existing hardware permissions when page loads
    document.addEventListener("DOMContentLoaded", initializePersistentHardware);

    function cashDrawerHardware() {
        return {
            async triggerDrawerRelay() {
                if (!('serial' in navigator)) {
                    console.warn("Web Serial API is unavailable on this browser browser engine.");
                    return;
                }

                try {
                    // If we don't have a cached port handle, check authorized list again or prompt once
                    if (!authorizedDrawerPort) {
                        const allowedPorts = await navigator.serial.getPorts();
                        
                        if (allowedPorts.length > 0) {
                            authorizedDrawerPort = allowedPorts[0];
                        } else {
                            // This triggers the browser prompt window ONLY if no device is authorized yet
                            authorizedDrawerPort = await navigator.serial.requestPort();
                        }
                    }

                    // Execute hardware session communication
                    if (authorizedDrawerPort) {
                        // Open connection if it closed unexpectedly or hasn't been initialized
                        if (!authorizedDrawerPort.readable) {
                            await authorizedDrawerPort.open({ baudRate: 9600 });
                        }
                        
                        const writer = authorizedDrawerPort.writable.getWriter();
                        const openPulse = new Uint8Array([0x01]); // Relay trigger pulse byte
                        
                        await writer.write(openPulse);
                        writer.releaseLock();
                        await authorizedDrawerPort.close();
                        
                        console.log("Cash drawer relay triggered silently.");
                    }
                } catch (err) {
                    console.error("Hardware transaction failure:", err);
                    // Clear out stale reference context if device was unplugged
                    authorizedDrawerPort = null; 
                }
            }
        }
    }
</script>

