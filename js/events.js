import { fetchJson } from "./modules/api.js";
import { filterItems, sortItems, paginate } from "./modules/list-utils.js";
import { renderEventCards, renderPagination, showMessage, fillSelect } from "./modules/render.js";

const EVENTS_URL = "data/events.json";
const CACHE_KEY = "studenthub-events-cache";
const PAGE_SIZE = 6;

const container = document.getElementById("events-container");
const searchInput = document.getElementById("event-search");
const categorySelect = document.getElementById("event-category");
const sortSelect = document.getElementById("event-sort");
const countText = document.getElementById("events-count");
const notice = document.getElementById("data-notice");
const pagination = document.getElementById("pagination");

let allEvents = [];
let currentPage = 1;

async function init() {
  showMessage(container, "Loading events...");
  try {
    const { data, fromCache } = await fetchJson(EVENTS_URL, CACHE_KEY);
    console.log("Fetched events:", data, fromCache ? "(from cache)" : "(from server)");
    allEvents = data;
    notice.textContent = fromCache
      ? "Could not reach the server. Showing your last saved copy of the events."
      : "";
    fillSelect(categorySelect, [...new Set(allEvents.map((event) => event.category))]);
    update();
  } catch (error) {
    console.error("Could not load events:", error);
    countText.textContent = "";
    pagination.innerHTML = "";
    showMessage(container, "Sorry, the events could not be loaded. Please try again later.", true);
  }
}

function update() {
  const filtered = filterItems(
    allEvents,
    searchInput.value,
    ["title", "location", "description"],
    "category",
    categorySelect.value
  );
  const sorted = sortItems(filtered, sortSelect.value);
  const { pageItems, totalPages, page, start } = paginate(sorted, currentPage, PAGE_SIZE);
  currentPage = page;

  countText.textContent =
    `Showing ${pageItems.length} of ${sorted.length} events (page ${page} of ${totalPages})`;
  renderEventCards(container, pageItems, start);
  renderPagination(pagination, page, totalPages);
}

pagination.addEventListener("click", (event) => {
  const button = event.target.closest("button[data-page]");
  if (!button || button.disabled) return;
  currentPage = Number(button.dataset.page);
  update();
});

searchInput.addEventListener("input", () => { currentPage = 1; update(); });
categorySelect.addEventListener("change", () => { currentPage = 1; update(); });
sortSelect.addEventListener("change", () => { currentPage = 1; update(); });

init();