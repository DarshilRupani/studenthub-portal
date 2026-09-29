// All DOM rendering lives here.
const MONTHS = ["JAN", "FEB", "MAR", "APR", "MAY", "JUN",
                "JUL", "AUG", "SEP", "OCT", "NOV", "DEC"];

export function escapeHtml(value) {
  return String(value).replace(/[&<>"']/g, (char) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;",
  }[char]));
}

export function showMessage(container, text, isError = false) {
  const role = isError ? "alert" : "status";
  const cls = isError ? "events-message error" : "events-message";
  container.innerHTML = `<p class="${cls}" role="${role}">${escapeHtml(text)}</p>`;
}

export function fillSelect(select, values) {
  values.forEach((value) => {
    const option = document.createElement("option");
    option.value = value;
    option.textContent = value;
    select.appendChild(option);
  });
}

export function renderEventCards(container, events, offset) {
  if (events.length === 0) {
    showMessage(container, "No events match your search.");
    return;
  }
  container.innerHTML = events
    .map((event, index) => {
      const [, month, day] = event.date.split("-");
      return `
        <article class="event-card">
          <div class="event-date">
            <strong>${day}</strong>
            <span>${MONTHS[Number(month) - 1]}</span>
          </div>
          <div class="event-content">
            <span class="event-type">${escapeHtml(event.category.toUpperCase())}</span>
            <h3>${escapeHtml(event.title)}</h3>
            <p>${escapeHtml(event.description)}</p>
            <div class="event-meta"><span>📍 ${escapeHtml(event.location)}</span></div>
          </div>
          <div class="event-number">${String(offset + index + 1).padStart(2, "0")}</div>
        </article>`;
    })
    .join("");
}

export function renderStudentCards(container, students, offset) {
  if (students.length === 0) {
    showMessage(container, "No students match your search.");
    return;
  }
  container.innerHTML = students
    .map((student, index) => {
      const initials = student.name
        .split(" ")
        .map((part) => part[0])
        .slice(0, 2)
        .join("");
      return `
        <article class="event-card">
          <div class="event-date">
            <strong>${escapeHtml(initials)}</strong>
            <span>SEM ${student.semester}</span>
          </div>
          <div class="event-content">
            <span class="event-type">${escapeHtml(student.department.toUpperCase())}</span>
            <h3>${escapeHtml(student.name)}</h3>
            <p>CGPA: ${student.cgpa.toFixed(2)}</p>
            <div class="event-meta"><span>✉️ ${escapeHtml(student.email)}</span></div>
          </div>
          <div class="event-number">${String(offset + index + 1).padStart(2, "0")}</div>
        </article>`;
    })
    .join("");
}

export function renderPagination(nav, page, totalPages) {
  if (totalPages <= 1) {
    nav.innerHTML = "";
    return;
  }
  let html = `<button type="button" data-page="${page - 1}" ${page === 1 ? "disabled" : ""}>Previous</button>`;
  for (let number = 1; number <= totalPages; number++) {
    html += `<button type="button" data-page="${number}" ${number === page ? 'aria-current="page" class="current"' : ""}>${number}</button>`;
  }
  html += `<button type="button" data-page="${page + 1}" ${page === totalPages ? "disabled" : ""}>Next</button>`;
  nav.innerHTML = html;
}