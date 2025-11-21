<x-app-layout>
    <x-slot name="title">
        {{ __('Edit Category') }}
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-6">
                        {{ __('Edit Category') }}
                    </h2>

                    <form action="{{ route('categories.update', $category) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <x-label for="name" :value="__('Name*')" />
                            <x-input id="name" name="name" :value="old('name', $category->name)" type="text" class="block mt-1 w-full" maxlength="255" required autofocus placeholder="{{ __('Category name') }}"/>
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <x-label for="slug" :value="__('Slug*')" />
                            <x-input id="slug" name="slug" :value="old('slug', $category->slug)" type="text" class="block mt-1 w-full" maxlength="255" required placeholder="{{ __('category-slug') }}"/>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Use lowercase letters, numbers, and hyphens only') }}</p>
                            @error('slug') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-6">
                            <x-label for="description" :value="__('Description')" />
                            <x-textarea id="description" name="description" class="block mt-1 w-full" rows="4" placeholder="{{ __('Category description (optional)') }}">{{ old('description', $category->description) }}</x-textarea>
                            @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center justify-end space-x-3">
                            <a href="{{ route('categories.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-400 dark:hover:bg-gray-500">
                                {{ __('Cancel') }}
                            </a>
                            <x-button type="submit">
                                {{ __('Update Category') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Auto-generate slug from name
        document.getElementById('name').addEventListener('input', function(e) {
            const slug = e.target.value
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            document.getElementById('slug').value = slug;
        });
    </script>
    @endpush
</x-app-layout>

