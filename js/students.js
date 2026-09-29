import { fetchJson } from "./modules/api.js";
import { filterItems, sortItems, paginate } from "./modules/list-utils.js";
import { renderStudentCards, renderPagination, showMessage, fillSelect } from "./modules/render.js";

const STUDENTS_URL = "data/students.json";
const CACHE_KEY = "studenthub-students-cache";
const PAGE_SIZE = 6;

const container = document.getElementById("students-container");
const searchInput = document.getElementById("student-search");
const categorySelect = document.getElementById("student-department");
const sortSelect = document.getElementById("student-sort");
const countText = document.getElementById("students-count");
const notice = document.getElementById("data-notice");
const pagination = document.getElementById("pagination");

let allStudents = [];
let currentPage = 1;

async function init() {
  showMessage(container, "Loading students...");
  try {
    const { data, fromCache } = await fetchJson(STUDENTS_URL, CACHE_KEY);
    console.log("Fetched students:", data, fromCache ? "(from cache)" : "(from server)");
    allStudents = data;
    notice.textContent = fromCache
      ? "Could not reach the server. Showing your last saved copy of the students."
      : "";
    fillSelect(categorySelect, [...new Set(allStudents.map((student) => student.department))]);
    update();
  } catch (error) {
    console.error("Could not load students:", error);
    countText.textContent = "";
    pagination.innerHTML = "";
    showMessage(container, "Sorry, the student list could not be loaded. Please try again later.", true);
  }
}

function update() {
  const filtered = filterItems(
    allStudents,
    searchInput.value,
    ["name", "department", "email"],
    "department",
    categorySelect.value
  );
  const sorted = sortItems(filtered, sortSelect.value);
  const { pageItems, totalPages, page, start } = paginate(sorted, currentPage, PAGE_SIZE);
  currentPage = page;

  countText.textContent =
    `Showing ${pageItems.length} of ${sorted.length} students (page ${page} of ${totalPages})`;
  renderStudentCards(container, pageItems, start);
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