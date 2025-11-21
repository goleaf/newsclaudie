<x-app-layout>
    <x-slot name="title">
        {{ __('Categories') }}
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                            {{ __('Categories') }}
                        </h2>
                        @can('access-dashboards')
                        <a href="{{ route('categories.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            {{ __('Create Category') }}
                        </a>
                        @endcan
                    </div>

                    @if(session('success'))
                    <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-200 rounded">
                        {{ session('success') }}
                    </div>
                    @endif

                    @if($categories->count())
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($categories as $category)
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 shadow hover:shadow-lg transition-shadow duration-200">
                            <div class="flex justify-between items-start mb-3">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                    <a href="{{ route('categories.show', $category) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                        {{ $category->name }}
                                    </a>
                                </h3>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $category->posts_count }} {{ __('posts') }}
                                </span>
                            </div>
                            
                            @if($category->description)
                            <p class="text-gray-600 dark:text-gray-300 text-sm mb-4">
                                {{ Str::limit($category->description, 100) }}
                            </p>
                            @endif

                            <div class="flex justify-between items-center mt-4">
                                <a href="{{ route('categories.show', $category) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ __('View Posts') }}
                                </a>
                                @can('access-dashboards')
                                <div class="flex space-x-2">
                                    <a href="{{ route('categories.edit', $category) }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                                        {{ __('Edit') }}
                                    </a>
                                    <form action="{{ route('categories.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this category?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-200">
                                            {{ __('Delete') }}
                                        </button>
                                    </form>
                                </div>
                                @endcan
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $categories->links() }}
                    </div>
                    @else
                    <div class="text-center py-12">
                        <p class="text-gray-500 dark:text-gray-400 text-lg">
                            {{ __('No categories found.') }}
                        </p>
                        @can('access-dashboards')
                        <a href="{{ route('categories.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white">
                            {{ __('Create First Category') }}
                        </a>
                        @endcan
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

