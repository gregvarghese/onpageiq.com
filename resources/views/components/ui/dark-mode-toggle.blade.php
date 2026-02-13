<div x-data="darkModeToggle()" class="relative">
    <button
        type="button"
        @click="toggle"
        class="flex items-center justify-center rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
        :aria-label="currentMode === 'dark' ? 'Switch to light mode' : currentMode === 'light' ? 'Switch to system mode' : 'Switch to dark mode'"
    >
        <!-- Sun icon (light mode) -->
        <x-ui.icon
            name="sun"
            x-show="currentMode === 'light'"
            class="size-5"
        />
        <!-- Moon icon (dark mode) -->
        <x-ui.icon
            name="moon"
            x-show="currentMode === 'dark'"
            class="size-5"
        />
        <!-- Computer icon (system mode) -->
        <x-ui.icon
            name="computer-desktop"
            x-show="currentMode === 'system'"
            class="size-5"
        />
    </button>
</div>

<script>
function darkModeToggle() {
    return {
        currentMode: localStorage.theme || 'system',

        init() {
            this.applyTheme();
            // Listen for system preference changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (this.currentMode === 'system') {
                    this.applyTheme();
                }
            });
        },

        toggle() {
            // Cycle through: light -> dark -> system
            if (this.currentMode === 'light') {
                this.currentMode = 'dark';
                localStorage.theme = 'dark';
            } else if (this.currentMode === 'dark') {
                this.currentMode = 'system';
                localStorage.removeItem('theme');
            } else {
                this.currentMode = 'light';
                localStorage.theme = 'light';
            }
            this.applyTheme();
        },

        applyTheme() {
            if (this.currentMode === 'dark' || (this.currentMode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    }
}
</script>
