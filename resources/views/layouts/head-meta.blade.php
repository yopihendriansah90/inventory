@php
    $pageMeta = config('page-meta.' . str_replace('.', '\.', '/' . trim(request()->path(), '/')));
    $inventoryBrand = request()->routeIs('inventory.*', 'signin');
    $metaTitle = $pageMeta['title'] ?? $pageMetaTitle ?? ($inventoryBrand
        ? 'inventory.title'
        : 'Laravel ' . ($title ?? 'Dashboard') . ' | TailAdmin - Laravel Dashboard Template');
    $metaDescription = $pageMeta['description'] ?? ($inventoryBrand
        ? 'inventory.dashboard.description'
        : 'This is Laravel ' . ($title ?? 'Dashboard') . ' page for TailAdmin - Laravel Tailwind CSS Admin Dashboard Template');
@endphp
<title>{{ __($metaTitle) }}</title>
<meta name="description" content="{{ __($metaDescription) }}">
