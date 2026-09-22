{{--
    Flash-sale manager — core 3.x port (Livewire + TailAdmin, two-panel: schedule /
    edit form on the left, running sales on the right) on the core ResourcePanel
    base. Replaces the AdminLTE view that extended the removed `gp247-core::layout`
    and drove its list with jQuery pjax + select2 + SweetAlert.

    Product picking uses the x-gp247::searchable-select component (not select2), the
    window uses the TailAdmin flatpickr through x-gp247::input with type=datetime,
    and the price is entered in the base currency (gp247_money_hint) like every
    other plugin money field.

    WHY no angle-bracketed component tag appears in this comment: the component tag
    compiler runs BEFORE Blade comments are stripped, so a tag written inside a
    comment is parsed as a real opening tag and silently breaks the rest of the file
    (it cost this screen a "syntax error, unexpected endforeach" once already).

    Variables: $rows (PluginModel paginator, with product+promotion eager-loaded);
    $form, $editingId, $sortField, $sortDir (component state).

    @aidlc-unit plugin-product-flash-sale
    @aidlc-story US-product-flash-sale-core3-port
    @aidlc-adr ADR-005, ADR-007
--}}
{{-- WHY a php BLOCK here and not the one-line parenthesised form: Blade pairs the
     php directive with the first closing one in the file using a single greedy
     pattern, so the short form up here would be read as the opening of the block
     that the row loop below closes, and every component tag in between would be
     left uncompiled ("unexpected endforeach"). For the same reason this comment
     spells the directive out in words: the raw-block pass runs BEFORE comments are
     stripped, so writing it with its @ prefix inside a comment breaks the file
     just as badly. --}}
@php
    $appPath = 'Plugins/ProductFlashSale';
@endphp

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    {{-- Left: schedule / edit --}}
    <x-gp247::card :title="gp247_language_render($editingId ? $appPath.'::lang.admin.edit' : $appPath.'::lang.admin.add_new_title')">
        <form wire:submit="save" class="space-y-4">

            <x-gp247::searchable-select
                model="form.product_id"
                :label="gp247_language_render($appPath.'::lang.admin.select_product')"
                :options="$this->productSelectOptions()"
                :error="$errors->first('form.product_id')"
                required
                data-testid="admin-product-flash-sale-form-product"
            />

            <x-gp247::input type="number" min="1" step="1"
                :label="gp247_language_render($appPath.'::lang.admin.stock')"
                name="stock" wire:model="form.stock"
                :error="$errors->first('form.stock')"
                :help="gp247_language_render($appPath.'::lang.admin.stock_helper')"
                required data-testid="admin-product-flash-sale-form-stock" />

            <x-gp247::input type="number" min="0" step="0.01"
                :label="gp247_language_render($appPath.'::lang.admin.price_promotion')"
                name="price_promotion" wire:model="form.price_promotion"
                :error="$errors->first('form.price_promotion')"
                :help="function_exists('gp247_money_hint') ? gp247_money_hint() : null"
                required data-testid="admin-product-flash-sale-form-price" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-gp247::input type="datetime"
                    :label="gp247_language_render($appPath.'::lang.admin.date_start')"
                    name="date_start" wire:model="form.date_start"
                    :error="$errors->first('form.date_start')"
                    required data-testid="admin-product-flash-sale-form-date-start" />

                <x-gp247::input type="datetime"
                    :label="gp247_language_render($appPath.'::lang.admin.date_end')"
                    name="date_end" wire:model="form.date_end"
                    :error="$errors->first('form.date_end')"
                    required data-testid="admin-product-flash-sale-form-date-end" />
            </div>

            <x-gp247::input type="number" min="0" step="1"
                :label="gp247_language_render($appPath.'::lang.admin.sort')"
                name="sort" wire:model="form.sort"
                :error="$errors->first('form.sort')"
                :help="gp247_language_render($appPath.'::lang.admin.sort_helper')"
                required data-testid="admin-product-flash-sale-form-sort" />

            <x-gp247::checkbox :label="gp247_language_render($appPath.'::lang.admin.status_promotion')"
                wire:model="form.status_promotion" value="1"
                data-testid="admin-product-flash-sale-form-status" />

            <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
                <x-gp247::button variant="secondary" wire:click="cancelEdit" data-testid="admin-product-flash-sale-form-cancel">
                    {{ gp247_language_render($editingId ? 'admin.cancel' : 'admin.reset') }}
                </x-gp247::button>
                <x-gp247::button type="submit" wire:loading.attr="disabled" data-testid="admin-product-flash-sale-form-submit">
                    <i class="fas fa-save"></i> {{ gp247_language_render($editingId ? 'admin.update' : 'admin.submit') }}
                </x-gp247::button>
            </div>
        </form>
    </x-gp247::card>

    {{-- Right: running sales --}}
    <x-gp247::card :title="gp247_language_render($appPath.'::lang.admin.list')">
        <x-gp247::table :empty="$rows->isEmpty() ? gp247_language_render('admin.no_records') : null">
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render($appPath.'::lang.admin.product') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render($appPath.'::lang.admin.price_promotion') }}</th>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('sold')">
                        {{ gp247_language_render($appPath.'::lang.admin.quota') }} @if ($sortField === 'sold')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render($appPath.'::lang.admin.window') }}</th>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('sort')">
                        {{ gp247_language_render($appPath.'::lang.admin.sort') }} @if ($sortField === 'sort')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render($appPath.'::lang.admin.status_promotion') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render($appPath.'::lang.admin.action') }}</th>
                </tr>
            </x-slot:head>

            @foreach ($rows as $row)
                @php
                    $promotion = $row->promotion;
                    $sold = (int) $row->sold;
                    $quota = (int) $row->stock;
                    $percent = $quota > 0 ? min(100, (int) round($sold / $quota * 100)) : 0;
                    $baseCode = function_exists('gp247_base_currency_code') ? gp247_base_currency_code() : null;
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ (string) $row->id === (string) $editingId ? 'bg-blue-100 border-l-4 border-blue-500 dark:bg-blue-900 dark:border-blue-500' : '' }}" wire:key="flash-{{ $row->id }}" data-testid="admin-product-flash-sale-list-item">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <a href="{{ $this->productUrl($row) }}" target="_blank" rel="noopener">
                                {!! $row->product ? gp247_image_render(gp247_file($row->product->image), '40px', '40px') : '' !!}
                            </a>
                            <div>
                                <span class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $row->product ? $row->product->getName() : $row->product_id }}</span>
                                @php($storeLabel = $this->storeLabelFor($row))
                                @if ($storeLabel !== '')
                                    {{-- Which shop is running this sale; shown only on multi-store /
                                         marketplace installs, where the list spans several of them. --}}
                                    <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">
                                        <i class="fas fa-store"></i> {{ $storeLabel }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                        @if ($promotion && $baseCode)
                            {{ gp247_currency_render_symbol((float) $promotion->price_promotion, $baseCode) }}
                        @else
                            {{ $promotion->price_promotion ?? '—' }}
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm text-gray-600 dark:text-gray-300">{{ $sold }} / {{ $quota }}</div>
                        {{-- Same "track + fill" pair the storefront strip uses, so a sale that
                             looks nearly gone in admin looks nearly gone to shoppers too. --}}
                        <div class="mt-1 h-1.5 w-24 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-full rounded-full bg-blue-600" style="width: {{ $percent }}%"></div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                        @if ($promotion && $promotion->date_start && $promotion->date_end)
                            {{ \Illuminate\Support\Carbon::parse($promotion->date_start)->format('Y-m-d H:i') }}
                            <span class="text-gray-400">→</span>
                            {{ \Illuminate\Support\Carbon::parse($promotion->date_end)->format('Y-m-d H:i') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row->sort }}</td>
                    <td class="px-4 py-3">
                        <x-gp247::badge :color="$promotion && $promotion->status_promotion ? 'green' : 'gray'">
                            {{ $promotion && $promotion->status_promotion ? gp247_language_render('admin.active') : gp247_language_render('admin.inactive') }}
                        </x-gp247::badge>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <x-gp247::button size="sm" variant="ghost" wire:click="editRow('{{ $row->id }}')" data-testid="admin-product-flash-sale-list-edit"><i class="fas fa-edit"></i></x-gp247::button>
                            <x-gp247::button size="sm" variant="ghost" wire:click="delete('{{ $row->id }}')" wire:confirm="{{ gp247_language_render('action.delete_confirm') }}" data-testid="admin-product-flash-sale-list-delete"><i class="fas fa-trash-alt text-red-600"></i></x-gp247::button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-gp247::table>

        <div class="mt-4">{{ $rows->links('gp247-admin::partials.pagination') }}</div>
    </x-gp247::card>
</div>
