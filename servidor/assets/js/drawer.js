function initDrawer() {

  console.log("INIT DRAWER");

  const fab = document.querySelector(".fab");
  const drawer = document.querySelector(".drawer");
  const overlay = document.querySelector(".drawer-overlay");
  const closeBtn = document.querySelector(".drawer-close");

  console.log({
    fab,
    drawer,
    overlay,
    closeBtn
  });

  if (!fab || !drawer || !overlay || !closeBtn) {

    console.error("Drawer: faltan elementos");

    return;
  }

  function openDrawer() {

    console.log("OPEN");

    drawer.classList.add("open");
    overlay.classList.add("open");

    document.body.style.overflow = "hidden";
  }

  function closeDrawer() {

    drawer.classList.remove("open");
    overlay.classList.remove("open");

    document.body.style.overflow = "";
  }

  fab.addEventListener("click", openDrawer);

  closeBtn.addEventListener("click", closeDrawer);

  overlay.addEventListener("click", closeDrawer);

}

// function initDrawer() {

//   const fab = document.querySelector(".fab");
//   const drawer = document.querySelector(".drawer");
//   const overlay = document.querySelector(".drawer-overlay");
//   const closeBtn = document.querySelector(".drawer-close");

//   if (!fab || !drawer || !overlay || !closeBtn) {

//     console.error("Drawer: faltan elementos");

//     return;
//   }

//   function openDrawer() {

//     drawer.classList.add("open");
//     overlay.classList.add("open");

//     document.body.style.overflow = "hidden";
//   }

//   function closeDrawer() {

//     drawer.classList.remove("open");
//     overlay.classList.remove("open");

//     document.body.style.overflow = "";
//   }

//   fab.addEventListener("click", openDrawer);

//   closeBtn.addEventListener("click", closeDrawer);

//   overlay.addEventListener("click", closeDrawer);

// }