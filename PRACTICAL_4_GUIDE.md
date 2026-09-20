# Practical 4 — StudentHub

## Open the project

Extract the ZIP into a new folder. Open that folder in VS Code, then open `home.html` using Live Server. Keep using the same address and port when demonstrating the saved theme.

Alternatively, if Python is installed, run `python -m http.server 8000` inside the project folder, then open `http://localhost:8000/home.html`.

## Demonstrate the six features

1. **Theme:** click Dark mode in the header. Refresh, then visit another page: dark mode should remain selected. Click Light mode to switch back.
2. **Mobile menu:** narrow the browser below 768 pixels. Click Menu to open or close navigation. Open it, focus a navigation link and press Escape to close it.
3. **FAQ accordion:** open FAQ. Click each question to expand/collapse its answer. Tab to a question and use Enter or Space.
4. **Modal popup:** on Home, click Quick guide. Close with Close, Escape, or by clicking outside the popup. Keyboard focus returns to Quick guide.
5. **Notification banner:** on Home, click the notice's dismiss button. The notice disappears and returns after refreshing.
6. **Content slider:** on Home, use Previous and Next under Campus highlights. Three slides wrap around, and only the current slide is visible.

Open the browser developer tools Console while demonstrating and check for JavaScript errors. Try the features at desktop and phone widths.

## Source-code explanation

- `js/script.js` contains the six numbered sections. All ten HTML pages load it using `defer`, so their HTML is parsed before the script runs.
- `querySelector`, `querySelectorAll` and `getElementById` select DOM elements. `textContent`, `hidden`, `dataset` and `setAttribute` update the page and accessible control states.
- `addEventListener` attaches click, keydown, change and close handlers. Checks around page-specific elements let the same script run on every page.
- `applyTheme()` sets the root element's `data-theme`. CSS selectors apply dark colours. `localStorage` saves `studenthub-theme`; guarded storage calls keep the UI working when storage is unavailable.
- `matchMedia()` detects mobile width. The menu updates both navigation visibility and `aria-expanded`, including after a resize.
- Each FAQ button refers to its answer through `aria-controls`. Its click handler synchronizes the answer's visibility, expanded state and plus/minus symbol.
- The native `dialog` uses `showModal()` and `close()`. The browser manages modal focus containment and Escape. A close handler restores focus to the opener.
- The notification's click handler sets `hidden = true` and moves focus to a visible control.
- `showSlide()` uses an index and modulo arithmetic to wrap between three slides. An `aria-live` status announces the current slide. There is no automatic rotation.
- The Practical 4 section at the end of `css/style.css` styles these components and supplies dark-mode equivalents of existing page colours. The same stylesheet contains the existing layouts and transitions. Reduced-motion settings disable animation and transitions.

## What to submit

Your sheet asks for a live demo and source-code explanation. Demonstrate the site using the steps above and explain `js/script.js` using the notes. Include the project source files in your submission. If a report is also requested, capture the open FAQ, modal, slider, dismissed notice, mobile menu, and both themes.

## Practical 5

The registration page remains ready for the next practical. Practical 5's validation and error-handling features have not been added in this update.

## Verification status

JavaScript syntax checks and HTML ID/ARIA-reference checks passed on all ten pages. Simulated DOM tests passed for all six component handlers, slider wrapping, menu resizing and theme-storage failure handling. A real-browser test could not run because the browser download failed in the editing environment. Visual layout, native dialog focus containment and native keyboard activation still need the browser demo checks listed above.
