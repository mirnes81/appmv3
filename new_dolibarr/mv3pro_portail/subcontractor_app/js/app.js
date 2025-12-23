const App = {
    init() {
        Auth.init();
        Reports.init();
        SignatureManager.init();
        this.setupBottomNav();
        this.registerServiceWorker();
    },

    setupBottomNav() {
        const navItems = document.querySelectorAll('.nav-item');

        navItems.forEach(item => {
            item.addEventListener('click', () => {
                navItems.forEach(nav => nav.classList.remove('active'));
                item.classList.add('active');
            });
        });
    },

    showLoader() {
        document.getElementById('loader').classList.add('show');
    },

    hideLoader() {
        document.getElementById('loader').classList.remove('show');
    },

    showToast(message, duration = 3000) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.classList.add('show');

        setTimeout(() => {
            toast.classList.remove('show');
        }, duration);
    },

    registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('service-worker.js')
                .then(() => console.log('Service Worker registered'))
                .catch(err => console.log('Service Worker registration failed:', err));
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    App.init();
});
