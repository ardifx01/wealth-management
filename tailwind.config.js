module.exports = {
    presets: [require('./vendor/filament/support/tailwind.config.preset')],
    content: [
        './app/Filament/**/*.php',
        './resources/views/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    plugins: [
        require('postcss-nesting'),
        require('tailwindcss'),
        require('autoprefixer'),
    ],
}
