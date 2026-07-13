@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500']) }}>
