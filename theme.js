/* Villa Rixdorf — theme toggle + mobile navigation + form submit guard.
   Loaded synchronously in <head> so the saved theme is applied before first
   paint (no flash). Uses only functional localStorage (DSGVO: no consent). */
(function () {
  "use strict";

  // 1) Apply saved theme immediately (no DOM needed).
  var saved = null;
  try { saved = localStorage.getItem("vr-theme"); } catch (e) {}
  if (saved === "light" || saved === "dark") {
    document.documentElement.setAttribute("data-theme", saved);
  }

  function effectiveTheme() {
    var attr = document.documentElement.getAttribute("data-theme");
    if (attr === "light" || attr === "dark") return attr;
    return (window.matchMedia &&
            window.matchMedia("(prefers-color-scheme: dark)").matches) ? "dark" : "light";
  }

  function hasManualChoice() {
    try { return !!localStorage.getItem("vr-theme"); } catch (e) { return false; }
  }

  function init() {
    // ----- Theme toggle -----
    var toggle = document.querySelector(".theme-toggle");
    if (toggle) {
      var sync = function () {
        var t = effectiveTheme();
        toggle.dataset.theme = t;
        toggle.setAttribute("aria-pressed", String(t === "dark"));
        var label = t === "dark" ? "Zu hellem Design wechseln" : "Zu dunklem Design wechseln";
        toggle.setAttribute("aria-label", label);
        toggle.title = label;
      };
      sync();
      toggle.addEventListener("click", function () {
        var next = effectiveTheme() === "dark" ? "light" : "dark";
        document.documentElement.setAttribute("data-theme", next);
        try { localStorage.setItem("vr-theme", next); } catch (e) {}
        sync();
      });
      if (window.matchMedia) {
        window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", function () {
          if (!hasManualChoice()) sync();
        });
      }
    }

    // ----- Mobile navigation (with focus management) -----
    var navToggle = document.querySelector(".nav-toggle");
    var nav = document.getElementById("primary-nav");
    if (navToggle && nav) {
      var firstLink = nav.querySelector("a");
      var open = function () {
        nav.classList.add("is-open");
        navToggle.setAttribute("aria-expanded", "true");
        if (firstLink) firstLink.focus();
      };
      var close = function (returnFocus) {
        nav.classList.remove("is-open");
        navToggle.setAttribute("aria-expanded", "false");
        if (returnFocus) navToggle.focus();
      };
      navToggle.addEventListener("click", function () {
        if (nav.classList.contains("is-open")) close(false); else open();
      });
      nav.addEventListener("click", function (e) {
        if (e.target.closest("a")) close(false);
      });
      document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && nav.classList.contains("is-open")) close(true);
      });
    }

    // ----- Double-submit guard (forms marked data-guard) -----
    // Runs on the 'submit' event, which only fires after the browser's native
    // validation passes — so it never blocks a user who still has a field to fix.
    var guarded = document.querySelectorAll("form[data-guard]");
    Array.prototype.forEach.call(guarded, function (form) {
      var submitting = false;
      form.addEventListener("submit", function (ev) {
        if (submitting) { ev.preventDefault(); return; }
        submitting = true;
        var btn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (btn) {
          btn.setAttribute("aria-disabled", "true");
          if (btn.tagName === "BUTTON") btn.textContent = "Senden…";
        }
      });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
