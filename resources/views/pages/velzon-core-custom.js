document.addEventListener("DOMContentLoaded", function () {
  // 1) Feather ikonları düzelt (data-feather)
  if (window.feather && typeof window.feather.replace === "function") {
    window.feather.replace();
  }

  // 2) Tooltip / Popover (Bootstrap)
  if (window.bootstrap) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
      new bootstrap.Tooltip(el);
    });

    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
      new bootstrap.Popover(el);
    });
  }

  // 3) Horizontal menü "More" split (Velzon)
  // Not: Senin app.js’de horizontalMenuSplit = 7 idi
  var horizontalMenuSplit = 7;

  function updateHorizontalMenus() {
    var navbarMenuEl = document.querySelector(".navbar-menu");
    if (!navbarMenuEl) return;

    // navbar-menu içeriğini ilk halinden çek (1 kere)
    if (!window.__VELZON_NAVBAR_HTML__) {
      window.__VELZON_NAVBAR_HTML__ = navbarMenuEl.innerHTML;
    } else {
      navbarMenuEl.innerHTML = window.__VELZON_NAVBAR_HTML__;
    }

    var twoCol = document.getElementById("two-column-menu");
    if (twoCol) twoCol.innerHTML = "";

    // simplebar attribute’larını temizle (horizontal’da gerekmez)
    var scrollbar = document.getElementById("scrollbar");
    var nav = document.getElementById("navbar-nav");
    if (scrollbar) {
      scrollbar.removeAttribute("data-simplebar");
      scrollbar.classList.remove("h-100");
    }
    if (nav) nav.removeAttribute("data-simplebar");

    // menü split
    var items = document.querySelectorAll("ul.navbar-nav > li.nav-item");
    if (!items || items.length <= horizontalMenuSplit) return;

    var newMenus = "";
    var splitItem = null;

    Array.from(items).forEach(function (item, index) {
      if (index + 1 === horizontalMenuSplit) splitItem = item;

      if (index + 1 > horizontalMenuSplit) {
        newMenus += item.outerHTML;
        item.remove();
      }
    });

    if (splitItem && newMenus) {
      splitItem.insertAdjacentHTML(
        "afterend",
        '<li class="nav-item">\
          <a class="nav-link" href="#sidebarMore" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarMore">\
            <i class="ri-briefcase-2-line"></i> <span>More</span>\
          </a>\
          <div class="collapse menu-dropdown" id="sidebarMore">\
            <ul class="nav nav-sm flex-column">' + newMenus + '</ul>\
          </div>\
        </li>'
      );
    }
  }

  // 4) Aktif menüyü seç (navbar active)
  function initActiveMenu() {
    var nav = document.getElementById("navbar-nav");
    if (!nav) return;

    var currentPath = location.pathname === "/" ? "index" : location.pathname.substring(1);
    currentPath = currentPath.substring(currentPath.lastIndexOf("/") + 1);

    if (!currentPath) return;

    var a = nav.querySelector('[href="' + currentPath + '"]');
    if (!a) return;

    a.classList.add("active");

    var parentCollapseDiv = a.closest(".collapse.menu-dropdown");
    if (parentCollapseDiv) {
      parentCollapseDiv.classList.add("show");
      if (parentCollapseDiv.parentElement && parentCollapseDiv.parentElement.children[0]) {
        parentCollapseDiv.parentElement.children[0].classList.add("active");
        parentCollapseDiv.parentElement.children[0].setAttribute("aria-expanded", "true");
      }

      // üst collapse’ları da aç
      var parentCollapse = parentCollapseDiv.parentElement.closest(".collapse.menu-dropdown");
      if (parentCollapse) {
        parentCollapse.classList.add("show");
        if (parentCollapse.previousElementSibling) {
          parentCollapse.previousElementSibling.classList.add("active");
        }
      }
    }
  }

  // 5) Topbar shadow (scroll olunca)
  function windowScroll() {
    var pageTopbar = document.getElementById("page-topbar");
    if (!pageTopbar) return;
    (document.body.scrollTop >= 50 || document.documentElement.scrollTop >= 50)
      ? pageTopbar.classList.add("topbar-shadow")
      : pageTopbar.classList.remove("topbar-shadow");
  }

  // 6) Waves efekt (varsa)
  if (window.Waves && typeof window.Waves.init === "function") {
    window.Waves.init();
  }

  // Çalıştır
  if (document.documentElement.getAttribute("data-layout") === "horizontal") {
    updateHorizontalMenus();
  }
  initActiveMenu();
  windowScroll();

  // Eventler
  window.addEventListener("scroll", windowScroll);
  window.addEventListener("resize", function () {
    if (window.feather && typeof window.feather.replace === "function") {
      window.feather.replace();
    }
    if (document.documentElement.getAttribute("data-layout") === "horizontal") {
      updateHorizontalMenus();
      initActiveMenu();
    }
  });
});