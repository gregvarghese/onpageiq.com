<div>
    <x-slot name="header">
        <div class="flex items-center gap-x-4">
            <a
                href="{{ route('projects.index') }}"
                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
            >
                <x-ui.icon name="arrow-left" class="size-5" />
            </a>
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Create Project</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Set up a new project to start scanning your URLs</p>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-2xl">
        <form wire:submit="create" class="space-y-8">
            <!-- Project Details -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-6">Project Details</h2>

                <div class="space-y-6">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-900 dark:text-white">
                            Project Name <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-2">
                            <input
                                type="text"
                                id="name"
                                wire:model="name"
                                placeholder="e.g., Company Website, Marketing Blog"
                                class="block w-full rounded-md border-0 px-3 py-2 text-gray-900 dark:text-white bg-white dark:bg-gray-900 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm"
                            />
                        </div>
                        @error('name')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-900 dark:text-white">
                            Description
                        </label>
                        <div class="mt-2">
                            <textarea
                                id="description"
                                wire:model="description"
                                rows="3"
                                placeholder="Brief description of the project (optional)"
                                class="block w-full rounded-md border-0 px-3 py-2 text-gray-900 dark:text-white bg-white dark:bg-gray-900 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm"
                            ></textarea>
                        </div>
                        @error('description')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Language -->
                    <div>
                        <label for="language" class="block text-sm font-medium text-gray-900 dark:text-white">
                            Content Language
                        </label>
                        <div class="mt-2">
                            <select
                                id="language"
                                wire:model="language"
                                class="block w-full rounded-md border-0 px-3 py-2 text-gray-900 dark:text-white bg-white dark:bg-gray-900 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm"
                            >
                                <option value="en">English</option>
                                <option value="es">Spanish</option>
                                <option value="fr">French</option>
                                <option value="de">German</option>
                                <option value="it">Italian</option>
                                <option value="pt">Portuguese</option>
                                <option value="nl">Dutch</option>
                            </select>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Select the primary language of your content for accurate analysis.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Analysis Options -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Analysis Options</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Choose which checks to run on your pages.
                </p>

                <div class="space-y-4">
                    <!-- Spelling -->
                    <label class="flex items-start gap-x-3 cursor-pointer">
                        <input
                            type="checkbox"
                            wire:model="checkSpelling"
                            {{ !$availableChecks['spelling'] ? 'disabled' : '' }}
                            class="mt-1 size-4 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-600 disabled:opacity-50"
                        />
                        <div class="flex-1">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Spelling Check</span>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Detect spelling errors and typos in your content.</p>
                        </div>
                    </label>

                    <!-- Grammar -->
                    <label class="flex items-start gap-x-3 cursor-pointer {{ !$availableChecks['grammar'] ? 'opacity-50' : '' }}">
                        <input
                            type="checkbox"
                            wire:model="checkGrammar"
                            {{ !$availableChecks['grammar'] ? 'disabled' : '' }}
                            class="mt-1 size-4 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-600 disabled:opacity-50"
                        />
                        <div class="flex-1">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Grammar Check</span>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Identify grammatical issues and suggest corrections.</p>
                            @if(!$availableChecks['grammar'])
                                <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Upgrade to Pro to unlock this feature.</p>
                            @endif
                        </div>
                    </label>

                    <!-- SEO -->
                    <label class="flex items-start gap-x-3 cursor-pointer {{ !$availableChecks['seo'] ? 'opacity-50' : '' }}">
                        <input
                            type="checkbox"
                            wire:model="checkSeo"
                            {{ !$availableChecks['seo'] ? 'disabled' : '' }}
                            class="mt-1 size-4 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-600 disabled:opacity-50"
                        />
                        <div class="flex-1">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">SEO Analysis</span>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Analyze meta tags, headings, and keyword usage.</p>
                            @if(!$availableChecks['seo'])
                                <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Upgrade to Pro to unlock this feature.</p>
                            @endif
                        </div>
                    </label>

                    <!-- Readability -->
                    <label class="flex items-start gap-x-3 cursor-pointer {{ !$availableChecks['readability'] ? 'opacity-50' : '' }}">
                        <input
                            type="checkbox"
                            wire:model="checkReadability"
                            {{ !$availableChecks['readability'] ? 'disabled' : '' }}
                            class="mt-1 size-4 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-600 disabled:opacity-50"
                        />
                        <div class="flex-1">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Readability Check</span>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Evaluate reading level, sentence length, and content clarity.</p>
                            @if(!$availableChecks['readability'])
                                <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Upgrade to Pro to unlock this feature.</p>
                            @endif
                        </div>
                    </label>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-x-4">
                <a
                    href="{{ route('projects.index') }}"
                    class="rounded-md px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
                >
                    Cancel
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center gap-x-2 rounded-md bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                >
                    <span wire:loading.remove wire:target="create">Create Project</span>
                    <span wire:loading wire:target="create">Creating...</span>
                </button>
            </div>
        </form>
    </div>
</div>
