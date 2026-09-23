@php
    $depth = $depth ?? 0;
    $disabled = $disabled ?? false;
    $auto_expand = $auto_expand ?? false;
@endphp
@foreach ($categories as $category)
    @php
        $hasChildren = !empty($category->subcategories);
        $isOpen = $auto_expand && in_array($category->id, $selected_all_categories);
        $isChecked = in_array($category->id, $selected_categories);
        $icon = $hasChildren ? ($depth === 0 ? 'ph-folder-open' : 'ph-folder') : 'ph-tag';
    @endphp
    <div class="category cs-category {{ $isOpen ? 'open' : '' }}" data-depth="{{ $depth }}">
        <div class="category-header cs-row" style="padding-left: {{ 10 + ($depth * 28) }}px;">
            @if ($hasChildren)
                <button type="button" class="cs-toggle {{ $isOpen ? 'open' : '' }}" aria-label="{{ __('Toggle') }}">
                    <i class="ph ph-caret-down"></i>
                </button>
            @else
                <span class="cs-toggle-spacer"></span>
            @endif

            <label class="cs-check">
                <input type="checkbox"
                       name="{{ $checkbox_name ?? 'selected_categories[]' }}"
                       value="{{ $category->id }}"
                       class="category-checkbox"
                       {{ $isChecked ? 'checked' : '' }}
                       {{ $disabled ? 'disabled' : '' }}>
                <span class="cs-check-box"><i class="ph-bold ph-check cs-check-icon"></i></span>
            </label>

            <span class="cs-cat-icon"><i class="ph {{ $icon }}"></i></span>
            <span class="cs-cat-name">{{ $category->name }}</span>

            @if ($hasChildren)
                <span class="cs-badge" data-selected-badge>0 {{ __('Selected') }}</span>
            @endif
        </div>

        @if ($hasChildren)
            <div class="subcategories cs-subcategories" style="display: {{ $isOpen ? 'block' : 'none' }}; --line-left: {{ 22 + ($depth * 28) }}px;">
                @include('category.treeview', [
                    'categories' => $category->subcategories,
                    'selected_categories' => $selected_categories,
                    'selected_all_categories' => $selected_all_categories,
                    'checkbox_name' => $checkbox_name ?? 'selected_categories[]',
                    'depth' => $depth + 1,
                    'disabled' => $disabled,
                    'auto_expand' => $auto_expand,
                ])
            </div>
        @endif
    </div>
@endforeach
