
// document.addEventListener('DOMContentLoaded', function () {
//   // FULLSCREEN
//   document.querySelectorAll('[data-toggle="fullscreen"]').forEach(function (btn) {
//     btn.addEventListener('click', function () {
//       if (!document.fullscreenElement) {
//         document.documentElement.requestFullscreen?.();
//       } else {
//         document.exitFullscreen?.();
//       }
//     });
//   });

//   // ===== THEME (Velzon + senin layout) =====
//   var KEY = 'theme'; // layout'un bunu okuyor
//   var toggleBtn = document.querySelector('.light-dark-mode');

//   function applyTheme(theme) {
//     // Senin layout script'in okuduğu yer burası
//     document.documentElement.setAttribute('data-bs-theme', theme);
//     localStorage.setItem(KEY, theme);

//     // Velzon topbar rengi (opsiyonel ama iyi)
//     // dark iken topbar="dark", light iken topbar="light"
//     document.body.setAttribute('data-topbar', theme === 'dark' ? 'dark' : 'light');

//     // ikon değişimi
//     if (toggleBtn) {
//       var icon = toggleBtn.querySelector('i');
//       if (icon) {
//         icon.classList.remove('bx-moon', 'bx-sun');
//         icon.classList.add(theme === 'dark' ? 'bx-sun' : 'bx-moon');
//       }
//     }
//   }

//   function currentTheme() {
//     return localStorage.getItem(KEY) || document.documentElement.getAttribute('data-bs-theme') || 'light';
//   }

//   // ilk yükleme
//   applyTheme(currentTheme());

//   // toggle
//   if (toggleBtn) {
//     toggleBtn.addEventListener('click', function () {
//       var next = currentTheme() === 'dark' ? 'light' : 'dark';
//       applyTheme(next);
//     });
//   }
// });








// console.log('Topbar custom loaded');

(function () {
  var KEY = 'theme';

  function currentTheme() {
    return localStorage.getItem(KEY) ||
      document.documentElement.getAttribute('data-bs-theme') ||
      'light';
  }

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    localStorage.setItem(KEY, theme);

    // topbar rengi
    if (document.body) {
      document.body.setAttribute('data-topbar', theme === 'dark' ? 'dark' : 'light');
    }

    // ikon güncelle (varsa)
    var btnEl = document.querySelector('.light-dark-mode');
    if (btnEl) {
      var icon = btnEl.querySelector('i');
      if (icon) {
        icon.classList.remove('bx-moon', 'bx-sun');
        icon.classList.add(theme === 'dark' ? 'bx-sun' : 'bx-moon');
      }
    }

    // console.log('[theme] applied:', theme);
  }

  function init() {
    // Sayfa açılınca mevcut temayı uygula
    applyTheme(currentTheme());

    // ✅ CAPTURE: stopPropagation olsa bile yakalar
    document.addEventListener('click', function (e) {
      // Fullscreen
      var fsBtn = e.target.closest && e.target.closest('[data-toggle="fullscreen"]');
      if (fsBtn) {
        // console.log('[click] fullscreen');
        if (!document.fullscreenElement) {
          document.documentElement.requestFullscreen?.();
        } else {
          document.exitFullscreen?.();
        }
        return;
      }

      // Theme toggle
      var themeBtn = e.target.closest && e.target.closest('.light-dark-mode');
      if (themeBtn) {
        // console.log('[click] theme toggle');
        var next = currentTheme() === 'dark' ? 'light' : 'dark';
        applyTheme(next);
      }
    }, true); // <-- capture
  }

  // DOM zaten hazırsa hemen çalıştır, değilse hazır olunca
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();