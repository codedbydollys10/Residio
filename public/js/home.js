(function () {
    'use strict';

    const header = document.getElementById('homeHeader');
    const updateHeader = function () {
        if (header) header.classList.toggle('is-scrolled', window.scrollY > 8);
    };
    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });

    let collectionChart = null;
    const updateCollectionChartTheme = function () {
        if (!collectionChart) return;
        const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        collectionChart.options.scales.x.ticks.color = dark ? '#94a3b8' : '#64748b';
        collectionChart.update('none');
    };

    document.querySelectorAll('#homeThemeToggle, #homeThemeToggleMobile').forEach(function (button) {
        button.addEventListener('click', function () {
            const root = document.documentElement;
            const next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-bs-theme', next);
            try {
                localStorage.setItem('co-theme', next);
            } catch (error) {
                // The page theme still changes when browser storage is unavailable.
            }
            updateCollectionChartTheme();
        });
    });

    document.querySelectorAll('[data-module-tab]').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('[data-module-tab]').forEach(function (button) {
                button.classList.toggle('is-active', button === tab);
                button.setAttribute('aria-selected', button === tab ? 'true' : 'false');
            });
            document.querySelectorAll('[data-module-panel]').forEach(function (panel) {
                const active = panel.dataset.modulePanel === tab.dataset.moduleTab;
                panel.hidden = !active;
                panel.classList.toggle('is-active', active);
            });
        });
    });

    document.querySelectorAll('.home-mobile-links a').forEach(function (link) {
        link.addEventListener('click', function () {
            const menu = document.getElementById('homeMobileMenu');
            const instance = window.bootstrap && window.bootstrap.Offcanvas.getInstance(menu);
            if (instance) instance.hide();
        });
    });

    const revealItems = document.querySelectorAll('.home-reveal');
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
        revealItems.forEach(function (item) { item.classList.add('is-visible'); });
    } else {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        revealItems.forEach(function (item) { observer.observe(item); });
    }

    const canvas = document.getElementById('homeCollectionChart');
    if (canvas && window.Chart) {
        const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const context = canvas.getContext('2d');
        const fill = context.createLinearGradient(0, 0, 0, 150);
        fill.addColorStop(0, 'rgba(37, 99, 235, .24)');
        fill.addColorStop(1, 'rgba(37, 99, 235, 0)');
        collectionChart = new window.Chart(canvas, {
            type: 'line',
            data: {
                labels: ['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                datasets: [{
                    data: [52, 67, 61, 79, 73, 91],
                    borderColor: '#2563eb',
                    backgroundColor: fill,
                    fill: true,
                    tension: .38,
                    borderWidth: 2,
                    pointRadius: 2,
                    pointBackgroundColor: '#2563eb',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: {
                    y: { display: false, beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false }, border: { display: false }, ticks: { color: dark ? '#94a3b8' : '#64748b', font: { size: 9 } } },
                },
            },
        });
    }
})();
