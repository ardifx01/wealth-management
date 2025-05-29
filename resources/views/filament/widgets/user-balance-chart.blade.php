<div>
    {{ $this->form }}

    <x-filament::widget>
        <x-filament::card>
            <canvas id="chart-canvas" wire:ignore></canvas>
        </x-filament::card>
    </x-filament::widget>

    <script>
        document.addEventListener('livewire:load', function() {
            const ctx = document.getElementById('chart-canvas').getContext('2d');
            new Chart(ctx, {
                type: @js($chartType),
                data: @js($chartData),
                options: {},
            });
        });
    </script>
</div>
