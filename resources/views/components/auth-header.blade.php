@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <flux:heading size="xl" class="text-white">{{ $title }}</flux:heading>
    <flux:subheading class="text-white">{{ $description }}</flux:subheading>
</div>
