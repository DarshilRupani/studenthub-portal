// Practical 4: shared UI behaviour. Loaded with defer after HTML is parsed.
'use strict';

// 1. Theme preference: storage failures must not break the other components.
const themeButton = document.querySelector('#theme-toggle');
function applyTheme(theme) {
    document.documentElement.dataset.theme = theme;
    themeButton.setAttribute('aria-pressed', String(theme === 'dark'));
    themeButton.textContent = theme === 'dark' ? 'Light mode' : 'Dark mode';
    themeButton.setAttribute('aria-label', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
}
if (themeButton) {
    let savedTheme = 'light';
    try {
        savedTheme = localStorage.getItem('studenthub-theme') === 'dark' ? 'dark' : 'light';
    } catch (error) { /* Continue with light mode if storage is unavailable. */ }
    applyTheme(savedTheme);
    themeButton.hidden = false;
    themeButton.addEventListener('click', () => {
        const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        applyTheme(theme);
        try { localStorage.setItem('studenthub-theme', theme); } catch (error) { /* Session-only theme. */ }
    });
}

// 2. Mobile menu: synchronize visibility and its accessible expanded state.
const menuButton = document.querySelector('#menu-toggle');
const navigation = document.querySelector('#main-navigation');
const mobileScreen = window.matchMedia('(max-width: 767px)');
if (menuButton && navigation) {
    function setMenu(open) {
        navigation.hidden = mobileScreen.matches && !open;
        menuButton.setAttribute('aria-expanded', String(open));
        menuButton.textContent = open ? 'Close menu' : '☰ Menu';
    }
    function updateMenuLayout() {
        menuButton.hidden = !mobileScreen.matches;
        setMenu(!mobileScreen.matches);
        if (navigation.hidden && navigation.contains(document.activeElement)) menuButton.focus();
    }
    updateMenuLayout();
    mobileScreen.addEventListener('change', updateMenuLayout);
    menuButton.addEventListener('click', () => setMenu(menuButton.getAttribute('aria-expanded') !== 'true'));
    navigation.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && mobileScreen.matches) { setMenu(false); menuButton.focus(); }
    });
    navigation.addEventListener('click', (event) => {
        if (event.target.closest('a') && mobileScreen.matches) setMenu(false);
    });
}

// 3. FAQ accordion: native buttons work with mouse, Enter and Space.
document.querySelectorAll('.faq-toggle').forEach((button) => {
    const answer = document.getElementById(button.getAttribute('aria-controls'));
    if (!answer) return;
    function setExpanded(expanded) {
        button.setAttribute('aria-expanded', String(expanded));
        answer.hidden = !expanded;
        button.querySelector('.faq-symbol').textContent = expanded ? '−' : '+';
    }
    setExpanded(false);
    button.addEventListener('click', () => setExpanded(button.getAttribute('aria-expanded') !== 'true'));
});

// 4. Modal: native dialog provides focus containment, Escape and inert background.
const guide = document.querySelector('#guide-dialog');
const openGuide = document.querySelector('#open-guide');
const closeGuide = document.querySelector('#close-guide');
if (guide && openGuide && closeGuide) {
    openGuide.hidden = false;
    openGuide.addEventListener('click', () => guide.showModal());
    closeGuide.addEventListener('click', () => guide.close());
    guide.addEventListener('click', (event) => {
        const bounds = guide.getBoundingClientRect();
        if (event.target === guide && (event.clientX < bounds.left || event.clientX > bounds.right ||
            event.clientY < bounds.top || event.clientY > bounds.bottom)) guide.close();
    });
    guide.addEventListener('close', () => openGuide.focus());
}

// 5. Dismissible notification. It returns on reload for easy demonstration.
const notice = document.querySelector('#welcome-notice');
const dismissNotice = document.querySelector('#dismiss-notice');
if (notice && dismissNotice) {
    dismissNotice.hidden = false;
    dismissNotice.addEventListener('click', () => {
        notice.hidden = true;
        if (openGuide) openGuide.focus();
    });
}

// 6. Content slider: manual controls avoid distracting automatic movement.
const slider = document.querySelector('.campus-slider');
if (slider) {
    const slides = Array.from(slider.querySelectorAll('.highlight-slide'));
    const status = slider.querySelector('#slide-status');
    let currentSlide = 0;
    function showSlide(index) {
        currentSlide = (index + slides.length) % slides.length;
        slides.forEach((slide, position) => { slide.hidden = position !== currentSlide; });
        status.textContent = `Slide ${currentSlide + 1} of ${slides.length}`;
    }
    slider.querySelector('.slider-controls').hidden = false;
    slider.querySelector('#previous-slide').addEventListener('click', () => showSlide(currentSlide - 1));
    slider.querySelector('#next-slide').addEventListener('click', () => showSlide(currentSlide + 1));
    showSlide(0);
}
