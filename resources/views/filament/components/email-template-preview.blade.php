
<div class="p-4 overflow-hidden border border-gray-200 rounded-lg">
    <div class="p-4 text-center text-white rounded-t-lg" x-bind:style="'background-color: {{ $primary_color }}'">
        <div class="mb-0">
            <div class="flex justify-center">
                <img src="{{ $logo_path }}" alt="Logo" class="max-h-[75px]" />
            </div>
        </div>
    </div>

    <div class="p-6 bg-white border-gray-200 border-x">
        <div class="mb-4 text-sm text-gray-500">{{ now()->format('F j, Y') }}</div>

        <h2 class="mb-4 text-xl font-semibold" x-bind:style="'color: {{ $primary_color }}'">
            Test Email from ZZuper Starter
        </h2>

        <p class="mb-4 text-gray-800">
            This is a test email to verify your email configuration settings are working correctly.
        </p>

        <div class="my-4 border-t border-gray-100"></div>

        <p class="mb-2 text-gray-800">
            Email theme: <strong >{{ \Illuminate\Support\Str::upper($template_theme) }}</strong>
        </p>

        <div class="mt-4 mb-2">
            <a href="#" class="inline-block px-4 py-2 text-sm font-medium rounded"
                x-bind:style="'background-color: {{ $secondary_color }}; color: #000000;'">
                Example Button
            </a>
        </div>
    </div>

    <div class="p-4 text-sm text-center text-gray-600 border-b border-t border-gray-200 rounded-b-lg bg-gray-50 border-x">
        <span>{{ $footer_text }}</span>
    </div>
</div>

<div class="mt-2 text-xs text-gray-500">
    <p>This is a preview of how your emails will appear. The actual email may look slightly different depending on the email client.</p>
</div>

