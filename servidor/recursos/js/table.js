function initTable() {

  const searchInput =
    document.getElementById("tableSearch");

  const showInactivos =
    document.getElementById("showInactivos");

  function filterRows() {
    const term = searchInput
      ? searchInput.value.toLowerCase()
      : "";

    const showAll = showInactivos
      ? showInactivos.checked
      : true;

    document
      .querySelectorAll(".table tbody tr")
      .forEach(row => {

        if (row.querySelector("td[colspan]")) return;

        const text =
          row.innerText.toLowerCase();

        const isInactive =
          row.querySelector(".status.inactive");

        const coincideBusqueda =
          text.includes(term);

        const coincideActivo =
          showAll || !isInactive;

        row.style.display =
          coincideBusqueda && coincideActivo
            ? ""
            : "none";

      });
  }

  if (searchInput && !searchInput.dataset.bound) {

    searchInput.dataset.bound = "true";

    searchInput.addEventListener(
      "input",
      filterRows
    );

  }

  if (showInactivos && !showInactivos.dataset.bound) {

    showInactivos.dataset.bound = "true";

    showInactivos.addEventListener(
      "change",
      filterRows
    );

  }

  filterRows();

  document
    .querySelectorAll(
      ".table th[data-sort]"
    )
    .forEach(th => {

      if (th.dataset.bound) return;

      th.dataset.bound = "true";

      th.style.cursor = "pointer";

      th.addEventListener(
        "click",
        () => sortTable(
          parseInt(th.dataset.sort)
        )
      );

    });

}

function sortTable(column) {

  const tbody =
    document.querySelector(
      ".table tbody"
    );

  if (!tbody) return;

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
        ?.innerText
        .trim() || "";

    const bText =
      b.cells[column]
        ?.innerText
        .trim() || "";

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

  document.querySelectorAll('.table th[data-sort]').forEach(function(th) {
    th.classList.remove('sort-asc', 'sort-desc');
    if (parseInt(th.dataset.sort) === column) {
      th.classList.add(asc ? 'sort-asc' : 'sort-desc');
    }
  });

}

/* ========================= */
/*  COLUMN PICKER */
/* ========================= */

function initColumnPicker() {

  const toolbar = document.querySelector(".table-toolbar");
  if (!toolbar) return;
  if (toolbar.dataset.columnPickerBound) return;
  toolbar.dataset.columnPickerBound = "true";

  const table = document.querySelector(".table");
  if (!table) return;

  const headers = table.querySelectorAll("thead th[data-column]");
  if (!headers.length) return;

  const page = document.body.dataset.page;
  if (!page) return;

  const storageKey = "sporta_columnVisibility_" + page.toLowerCase();

  const wrapper = document.createElement("div");
  wrapper.className = "column-picker-wrapper";

  const btn = document.createElement("button");
  btn.type = "button";
  btn.className = "column-picker-btn";
  btn.textContent = "Columnas \u25BC";

  const dropdown = document.createElement("div");
  dropdown.className = "column-picker-dropdown";

  headers.forEach(function(th) {
    const col = th.dataset.column;
    const label = th.textContent.trim();

    const item = document.createElement("label");
    item.className = "column-picker-item";

    const cb = document.createElement("input");
    cb.type = "checkbox";
    cb.dataset.column = col;
    cb.checked = true;

    cb.addEventListener("change", function() {
      toggleColumn(col, cb.checked);
      saveState();
    });

    item.appendChild(cb);
    item.appendChild(document.createTextNode(" " + label));
    dropdown.appendChild(item);
  });

  wrapper.appendChild(btn);
  wrapper.appendChild(dropdown);
  toolbar.appendChild(wrapper);

  loadState();

  btn.addEventListener("click", function(e) {
    e.stopPropagation();
    dropdown.classList.toggle("open");
  });

  document.addEventListener("click", function() {
    dropdown.classList.remove("open");
  });

  dropdown.addEventListener("click", function(e) {
    e.stopPropagation();
  });

  function toggleColumn(col, visible) {
    document.querySelectorAll('[data-column="' + col + '"]').forEach(function(el) {
      el.classList.toggle("column-hidden", !visible);
    });
  }

  function saveState() {
    var state = {};
    headers.forEach(function(th) {
      var col = th.dataset.column;
      var cb = dropdown.querySelector('[data-column="' + col + '"]');
      state[col] = cb.checked;
    });
    try {
      localStorage.setItem(storageKey, JSON.stringify(state));
    } catch (e) {}
  }

  function loadState() {
    try {
      var saved = localStorage.getItem(storageKey);
      if (saved) {
        var state = JSON.parse(saved);
        Object.keys(state).forEach(function(col) {
          var cb = dropdown.querySelector('[data-column="' + col + '"]');
          if (cb) {
            cb.checked = state[col];
            toggleColumn(col, state[col]);
          }
        });
      }
    } catch (e) {}
  }

}

document.addEventListener(
  "DOMContentLoaded",
  function() {
    initTable();
    initColumnPicker();
  }
);