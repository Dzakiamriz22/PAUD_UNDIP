import preset from "../../../../vendor/filament/filament/tailwind.config.preset";

export default {
    presets: [preset],
    content: [
        "./app/Filament/**/*.php",
        "./resources/views/filament/**/*.blade.php",
        "./resources/views/livewire/**/*.blade.php",
        "./resources/views/print/**/*.blade.php",
        "./vendor/filament/**/*.blade.php",
        "./vendor/diogogpinto/filament-auth-ui-enhancer/resources/**/*.blade.php",
        "./vendor/awcodes/filament-tiptap-editor/resources/**/*.blade.php",
        "./vendor/jaocero/radio-deck/resources/views/**/*.blade.php",
        "./vendor/codewithdennis/filament-simple-alert/resources/**/*.blade.php",
        "./vendor/awcodes/overlook/resources/**/*.blade.php",
        "./vendor/solution-forest/filament-tree/resources/**/*.blade.php",
    ],
    safelist: [
        'animate-spin',
        'animate-pulse',
        'animate-bounce'
    ],
    theme: {
        extend: {
            colors: {
                secondary: {
                    50: "rgba(var(--secondary-50), <alpha-value>)",
                    100: "rgba(var(--secondary-100), <alpha-value>)",
                    200: "rgba(var(--secondary-200), <alpha-value>)",
                    300: "rgba(var(--secondary-300), <alpha-value>)",
                    400: "rgba(var(--secondary-400), <alpha-value>)",
                    500: "rgba(var(--secondary-500), <alpha-value>)",
                    600: "rgba(var(--secondary-600), <alpha-value>)",
                    700: "rgba(var(--secondary-700), <alpha-value>)",
                    800: "rgba(var(--secondary-800), <alpha-value>)",
                    900: "rgba(var(--secondary-900), <alpha-value>)",
                },
            },
            fontFamily: {
                times: ['TimesNewRomanCustom', 'sans-serif'], // 'custom' is your utility class prefix
            },
        },
    },
};
