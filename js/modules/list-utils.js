// Pure array helpers: filter, sort, paginate. No DOM code here.
export function filterItems(items, text, fields, categoryField, category) {
  const query = text.trim().toLowerCase();
  return items.filter((item) => {
    const matchesText = fields.some((field) =>
      String(item[field]).toLowerCase().includes(query)
    );
    const matchesCategory = category === "all" || item[categoryField] === category;
    return matchesText && matchesCategory;
  });
}

// sortValue looks like "date-asc" or "cgpa-desc"
export function sortItems(items, sortValue) {
  const [field, direction] = sortValue.split("-");
  return [...items].sort((a, b) => {
    const av = a[field];
    const bv = b[field];
    const result =
      typeof av === "number" && typeof bv === "number"
        ? av - bv
        : String(av).localeCompare(String(bv));
    return direction === "desc" ? -result : result;
  });
}

export function paginate(items, page, pageSize) {
  const totalPages = Math.max(1, Math.ceil(items.length / pageSize));
  const safePage = Math.min(Math.max(page, 1), totalPages);
  const start = (safePage - 1) * pageSize;
  return {
    pageItems: items.slice(start, start + pageSize),
    totalPages,
    page: safePage,
    start,
  };
}