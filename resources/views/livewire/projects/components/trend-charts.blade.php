<div>
    <!-- Controls -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        <!-- Scope Selector -->
        <div class="flex items-center gap-x-2">
            <select
                wire:model.live="scope"
                class="rounded-md border-0 py-1.5 pl-3 pr-8 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-600"
            >
                <option value="project">All URLs</option>
                <option value="url">Single URL</option>
            </select>

            @if($scope === 'url')
                <select
                    wire:model.live="selectedUrlId"
                    class="rounded-md border-0 py-1.5 pl-3 pr-8 text-sm text-gray-900 dark:text-white bg-white dark:bg-gray-700 ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-600 max-w-xs"
                >
                    <option value="">Select URL...</option>
                    @foreach($this->urls as $url)
                        <option value="{{ $url->id }}">{{ Str::limit($url->url, 50) }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        <!-- Date Range Selector -->
        <div class="flex items-center gap-x-1 rounded-lg bg-gray-100 dark:bg-gray-700 p-1">
            @foreach([
                '7' => '7D',
                '14' => '14D',
                '30' => '30D',
                '90' => '90D',
                'all' => 'All',
            ] as $value => $label)
                <button
                    wire:click="setDateRange('{{ $value }}')"
                    type="button"
                    class="px-3 py-1 text-xs font-medium rounded-md transition-colors {{ $dateRange === $value ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Score Chart -->
        <div class="rounded-lg bg-white dark:bg-gray-800 p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Score Over Time</h4>
            <div
                x-data="scoreChart(@js($this->scoreChartData))"
                x-init="initChart"
                wire:ignore
                class="h-64"
            >
                <div x-ref="chart" class="h-full"></div>
            </div>
        </div>

        <!-- Issues Chart -->
        <div class="rounded-lg bg-white dark:bg-gray-800 p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">Issues Over Time</h4>
            <div
                x-data="issueChart(@js($this->issueChartData))"
                x-init="initChart"
                wire:ignore
                class="h-64"
            >
                <div x-ref="chart" class="h-full"></div>
            </div>
        </div>
    </div>

    @if(empty($this->scoreChartData['labels']))
        <div class="mt-4 text-center py-8 text-gray-500 dark:text-gray-400">
            <x-ui.icon name="chart-bar" class="mx-auto size-12 text-gray-400" />
            <p class="mt-2 text-sm">No scan data available for the selected period.</p>
            <p class="text-xs">Run some scans to see trend charts.</p>
        </div>
    @endif
</div>

@script
<script>
    Alpine.data('scoreChart', (data) => ({
        chart: null,
        data: data,

        initChart() {
            if (typeof ApexCharts === 'undefined') {
                // Load ApexCharts dynamically if not available
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js';
                script.onload = () => this.renderChart();
                document.head.appendChild(script);
            } else {
                this.renderChart();
            }
        },

        renderChart() {
            if (this.data.labels.length === 0) return;

            const isDark = document.documentElement.classList.contains('dark');

            const options = {
                series: this.data.datasets.map(ds => ({
                    name: ds.name,
                    data: ds.data
                })),
                chart: {
                    type: 'area',
                    height: '100%',
                    toolbar: { show: false },
                    background: 'transparent',
                },
                colors: ['#10b981'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.1,
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 2,
                },
                xaxis: {
                    categories: this.data.labels,
                    labels: {
                        style: {
                            colors: isDark ? '#9ca3af' : '#6b7280',
                        }
                    }
                },
                yaxis: {
                    min: 0,
                    max: 100,
                    labels: {
                        style: {
                            colors: isDark ? '#9ca3af' : '#6b7280',
                        },
                        formatter: (val) => val + '%'
                    }
                },
                grid: {
                    borderColor: isDark ? '#374151' : '#e5e7eb',
                },
                tooltip: {
                    theme: isDark ? 'dark' : 'light',
                    y: {
                        formatter: (val) => val + '%'
                    }
                },
                dataLabels: { enabled: false },
            };

            this.chart = new ApexCharts(this.$refs.chart, options);
            this.chart.render();
        },

        destroy() {
            if (this.chart) {
                this.chart.destroy();
            }
        }
    }));

    Alpine.data('issueChart', (data) => ({
        chart: null,
        data: data,

        initChart() {
            if (typeof ApexCharts === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js';
                script.onload = () => this.renderChart();
                document.head.appendChild(script);
            } else {
                this.renderChart();
            }
        },

        renderChart() {
            if (this.data.labels.length === 0) return;

            const isDark = document.documentElement.classList.contains('dark');

            const options = {
                series: this.data.datasets.map(ds => ({
                    name: ds.name,
                    data: ds.data
                })),
                chart: {
                    type: 'bar',
                    height: '100%',
                    stacked: true,
                    toolbar: { show: false },
                    background: 'transparent',
                },
                colors: ['#ef4444', '#f59e0b', '#3b82f6'],
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        borderRadius: 2,
                    },
                },
                xaxis: {
                    categories: this.data.labels,
                    labels: {
                        style: {
                            colors: isDark ? '#9ca3af' : '#6b7280',
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: isDark ? '#9ca3af' : '#6b7280',
                        }
                    }
                },
                grid: {
                    borderColor: isDark ? '#374151' : '#e5e7eb',
                },
                tooltip: {
                    theme: isDark ? 'dark' : 'light',
                },
                legend: {
                    position: 'top',
                    labels: {
                        colors: isDark ? '#9ca3af' : '#6b7280',
                    }
                },
                dataLabels: { enabled: false },
            };

            this.chart = new ApexCharts(this.$refs.chart, options);
            this.chart.render();
        },

        destroy() {
            if (this.chart) {
                this.chart.destroy();
            }
        }
    }));
</script>
@endscript
