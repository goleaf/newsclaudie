@props([
    'colspan' => 1,
    'message' => null,
])

<tr>
    <td
        colspan="{{ $colspan }}"
        class="px-4 py-6 text-center text-slate-500 dark:text-slate-400"
    >
        {{ $message ?? $slot }}
    </td>
</tr>

