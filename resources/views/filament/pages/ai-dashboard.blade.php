<x-filament-panels::page>
    @if ($this->hasHeaderWidgets())
        <x-filament-widgets::widgets
            :columns="$this->getHeaderWidgetsColumns()"
            :widgets="$this->getVisibleHeaderWidgets()"
        />
    @endif

    @if ($this->hasFooterWidgets())
        <x-filament-widgets::widgets
            :columns="$this->getFooterWidgetsColumns()"
            :widgets="$this->getVisibleFooterWidgets()"
        />
    @endif
</x-filament-panels::page>
