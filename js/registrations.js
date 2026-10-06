const container = document.getElementById("registrations-container");
const countText = document.getElementById("registrations-count");

async function loadRegistrations() {
  container.innerHTML = "<p class=\"events-message\">Loading...</p>";
  try {
    const response = await fetch("php/get-registrations.php");
    if (!response.ok) throw new Error(`Server responded with ${response.status}`);
    const records = await response.json();

    if (records.length === 0) {
      countText.textContent = "No registrations yet.";
      container.innerHTML = "<p class=\"events-message\">Nobody has registered yet.</p>";
      return;
    }

    countText.textContent = `Showing ${records.length} registration${records.length === 1 ? "" : "s"}.`;
    container.innerHTML = records
      .map((r) => {
        const date = new Date(r.registered);
        return `
          <article class="event-card">
            <div class="event-date">
              <strong>${String(date.getDate()).padStart(2, "0")}</strong>
              <span>${date.toLocaleString("en-US", { month: "short" }).toUpperCase()}</span>
            </div>
            <div class="event-content">
              <span class="event-type">${r.course.toUpperCase()} · YEAR ${r.year}</span>
              <h3>${r.fullname}</h3>
              <p>${r.email} · ${r.mobile}</p>
              <div class="event-meta"><span>${r.role === "faculty" ? "Faculty" : "Student"} · ${r.gender}</span></div>
            </div>
          </article>`;
      })
      .join("");
  } catch (error) {
    console.error("Could not load registrations:", error);
    container.innerHTML = "<p class=\"events-message error\">Sorry, registrations could not be loaded.</p>";
  }
}

loadRegistrations();