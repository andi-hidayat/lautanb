(function () {
    function getJakartaTime() {
        const d = new Date();
        const formatter = new Intl.DateTimeFormat('en-GB', {
            timeZone: 'Asia/Jakarta',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        });
        const parts = formatter.formatToParts(d);
        const map = {};
        for (const p of parts) {
            map[p.type] = p.value;
        }
        return `${map.year}-${map.month}-${map.day} ${map.hour}:${map.minute}:${map.second}`;
    }

    function createClockComponent() {
        return {
            timer: null,

            update() {
                const input = this.$el;
                if (!input || input.tagName !== 'INPUT') return;

                const val = getJakartaTime();
                input.value = val;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));

                try {
                    if (this.$wire && typeof this.$wire.set === 'function') {
                        this.$wire.set('data.needed_at', val, false);
                    } else if (window.Livewire && typeof window.Livewire.find === 'function') {
                        const root = input.closest('[wire\\:id]');
                        if (root) {
                            const id = root.getAttribute('wire:id');
                            if (id) {
                                const comp = window.Livewire.find(id);
                                if (comp && typeof comp.set === 'function') {
                                    comp.set('data.needed_at', val, false);
                                }
                            }
                        }
                    }
                } catch (e) {
                    // ignore
                }
            },

            init() {
                this.update();
                this.timer = setInterval(() => {
                    this.update();
                }, 1000);

                const form = this.$el?.closest('form');
                if (form) {
                    form.addEventListener('submit', () => {
                        this.update();
                    });
                }
            },

            destroy() {
                if (this.timer) {
                    clearInterval(this.timer);
                    this.timer = null;
                }
            },
        };
    }

    window.realtimeClock = createClockComponent;

    function register() {
        if (!window.Alpine) return;
        if (Alpine.data && Alpine.data('realtimeClock')) return;

        Alpine.data('realtimeClock', createClockComponent);
    }

    if (window.Alpine) {
        register();
    } else {
        document.addEventListener('alpine:init', register);
    }
})();
