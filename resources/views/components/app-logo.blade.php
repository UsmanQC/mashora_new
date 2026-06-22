@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand :name="config('app.name')" {{ $attributes }} />
@else
    <flux:brand :name="config('app.name')" {{ $attributes }} />
@endif
