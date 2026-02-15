<div>
    @if($this->node)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            {{-- Header --}}
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                    {{ $this->node->getDisplayName() }}
                </h3>
                <button
                    wire:click="close"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Content --}}
            <div class="p-4 space-y-4 max-h-[calc(100vh-200px)] overflow-y-auto">
                {{-- URL & Status --}}
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">URL</p>
                    <a
                        href="{{ $this->node->url }}"
                        target="_blank"
                        class="text-sm text-blue-600 dark:text-blue-400 hover:underline break-all"
                    >
                        {{ $this->node->url }}
                    </a>
                </div>

                {{-- Status Badge --}}
                <div class="flex items-center gap-2">
                    <span @class([
                        'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full',
                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $this->node->status->value === 'ok',
                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $this->node->status->value === 'redirect',
                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => in_array($this->node->status->value, ['client_error', 'server_error', 'timeout']),
                        'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' => $this->node->status->value === 'orphan',
                        'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' => $this->node->status->value === 'deep',
                    ])>
                        {{ ucfirst(str_replace('_', ' ', $this->node->status->value)) }}
                    </span>
                    @if($this->node->http_status)
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            HTTP {{ $this->node->http_status }}
                        </span>
                    @endif
                </div>

                {{-- Metrics Grid --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Depth</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $this->node->depth ?? 'N/A' }}
                        </p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Link Equity</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ number_format($this->node->link_equity_score * 100, 1) }}%
                        </p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Inbound Links</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $this->node->inbound_count }}
                        </p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Outbound Links</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $this->node->outbound_count }}
                        </p>
                    </div>
                </div>

                {{-- Page Title --}}
                @if($this->node->title)
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Page Title</p>
                        <p class="text-sm text-gray-900 dark:text-white">{{ $this->node->title }}</p>
                    </div>
                @endif

                {{-- Word Count --}}
                @if($this->node->word_count)
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Word Count</p>
                        <p class="text-sm text-gray-900 dark:text-white">{{ number_format($this->node->word_count) }}</p>
                    </div>
                @endif

                {{-- Issues --}}
                @if(count($this->issues) > 0)
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Issues ({{ count($this->issues) }})</p>
                        <div class="space-y-2">
                            @foreach($this->issues as $issue)
                                <div @class([
                                    'p-2 rounded-lg text-xs',
                                    'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300' => $issue['severity'] === 'critical',
                                    'bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300' => $issue['severity'] === 'serious',
                                    'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300' => $issue['severity'] === 'moderate',
                                    'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' => $issue['severity'] === 'minor',
                                ])>
                                    <p class="font-medium">{{ ucfirst(str_replace('_', ' ', $issue['issue_type'])) }}</p>
                                    <p class="mt-1">{{ $issue['message'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Inbound Links --}}
                @if(count($this->inboundLinks) > 0)
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                            Inbound Links ({{ count($this->inboundLinks) }}{{ $this->node->inbound_count > 20 ? '+' : '' }})
                        </p>
                        <div class="space-y-1 max-h-32 overflow-y-auto">
                            @foreach($this->inboundLinks as $link)
                                <div class="text-xs text-gray-600 dark:text-gray-300 truncate">
                                    <span class="text-gray-400">{{ $link['source_node']['path'] ?? 'Unknown' }}</span>
                                    @if($link['anchor_text'])
                                        <span class="text-gray-500">"{{ Str::limit($link['anchor_text'], 30) }}"</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Outbound Links --}}
                @if(count($this->outboundLinks) > 0)
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                            Outbound Links ({{ count($this->outboundLinks) }}{{ $this->node->outbound_count > 20 ? '+' : '' }})
                        </p>
                        <div class="space-y-1 max-h-32 overflow-y-auto">
                            @foreach($this->outboundLinks as $link)
                                <div class="text-xs text-gray-600 dark:text-gray-300 truncate">
                                    <span class="text-gray-400">{{ $link['target_node']['path'] ?? 'External' }}</span>
                                    @if($link['anchor_text'])
                                        <span class="text-gray-500">"{{ Str::limit($link['anchor_text'], 30) }}"</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Link Equity Flow --}}
                @if(!empty($this->equityFlow))
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Link Equity Flow</p>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 space-y-2">
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-600 dark:text-gray-300">Received</span>
                                <span class="font-medium text-green-600 dark:text-green-400">
                                    +{{ number_format($this->equityFlow['received'] ?? 0, 4) }}
                                </span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-600 dark:text-gray-300">Distributed</span>
                                <span class="font-medium text-orange-600 dark:text-orange-400">
                                    -{{ number_format($this->equityFlow['distributed'] ?? 0, 4) }}
                                </span>
                            </div>
                            <div class="flex justify-between text-xs border-t border-gray-200 dark:border-gray-600 pt-2">
                                <span class="text-gray-600 dark:text-gray-300">Net Score</span>
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    {{ number_format($this->equityFlow['score'] ?? 0, 4) }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex gap-2">
                <button
                    wire:click="viewPage"
                    class="flex-1 px-3 py-2 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"
                >
                    View Page
                </button>
                <button
                    wire:click="runScan"
                    class="flex-1 px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
                >
                    Run Scan
                </button>
            </div>
        </div>
    @endif
</div>
