import './bootstrap';

// Load only the Bootstrap widgets used by the admin shell.
import 'bootstrap/js/dist/collapse';
import 'bootstrap/js/dist/dropdown';

window.addEventListener('DOMContentLoaded', () => {
    const sidebarToggle = document.getElementById('sidebarToggle');

    if (!sidebarToggle) return;

    sidebarToggle.addEventListener('click', (event) => {
        event.preventDefault();
        document.body.classList.toggle('sb-sidenav-toggled');
    });
});
