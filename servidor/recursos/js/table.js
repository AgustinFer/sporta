/* =========================
   BÚSQUEDA
========================= */

const searchInput =
  document.getElementById("tableSearch");

searchInput?.addEventListener(
  "input",
  function () {

    const term =
      this.value.toLowerCase();

    document
      .querySelectorAll(".table tbody tr")
      .forEach(row => {

        const text =
          row.innerText.toLowerCase();

        row.style.display =
          text.includes(term)
            ? ""
            : "none";

      });

  }
);

/* =========================
   ORDENAMIENTO
========================= */

document
  .querySelectorAll(
    ".table th[data-sort]"
  )
  .forEach(th => {

    th.style.cursor = "pointer";

    th.addEventListener(
      "click",
      () => sortTable(
        parseInt(
          th.dataset.sort
        )
      )
    );

  });

function sortTable(column) {

  const tbody =
    document.querySelector(
      ".table tbody"
    );

  const rows =
    Array.from(
      tbody.querySelectorAll("tr")
    );

  const asc =
    tbody.dataset.sortColumn != column ||
    tbody.dataset.sortOrder !== "asc";

  rows.sort((a, b) => {

    const aText =
      a.cells[column]
      .innerText
      .trim();

    const bText =
      b.cells[column]
      .innerText
      .trim();

    return asc
      ? aText.localeCompare(
          bText,
          undefined,
          { numeric: true }
        )
      : bText.localeCompare(
          aText,
          undefined,
          { numeric: true }
        );

  });

  rows.forEach(
    row => tbody.appendChild(row)
  );

  tbody.dataset.sortColumn =
    column;

  tbody.dataset.sortOrder =
    asc ? "asc" : "desc";

}
