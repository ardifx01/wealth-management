document.addEventListener('livewire:init', () => {
    Livewire.on('currencyChanged', (event) => {
        window.location.reload();
    });
});
