@props(['errors'])

@if ($errors->any())
    <x-ui.alert variant="danger" {{ $attributes }}>
        <p class="font-semibold">
            {{ __('Whoops! Something went wrong.') }}
        </p>
        <ul class="mt-2 list-disc space-y-1 ps-5 text-sm leading-relaxed">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif
