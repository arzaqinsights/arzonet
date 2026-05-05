import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// ── Global Alpine stores ──
Alpine.store('toast', {
    show: false,
    message: '',
    type: 'success',

    fire(message, type = 'success') {
        this.message = message;
        this.type = type;
        this.show = true;
        setTimeout(() => { this.show = false; }, 4000);
    }
});

Alpine.store('sidebar', {
    open: window.innerWidth >= 1024,
    toggle() { this.open = !this.open; }
});

Alpine.start();
