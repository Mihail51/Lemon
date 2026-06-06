// i18n.js — простой переключатель языка (RU / EN / HE)
// Работает без перезагрузки страниц

(function () {
  const SUPPORTED = ["ru", "en", "he"];
  const DEFAULT_LANG = "ru";

  function getLang() {
    const hash = location.hash.replace("#", "").toLowerCase();
    if (SUPPORTED.includes(hash)) return hash;

    const saved = localStorage.getItem("site_lang");
    if (SUPPORTED.includes(saved)) return saved;

    return DEFAULT_LANG;
  }

  function setActiveButtons(lang) {
    document.querySelectorAll("[data-lang]").forEach(el => {
      el.classList.toggle("is-active", el.dataset.lang === lang);
    });
  }

  function setCurrentLabel(lang){
    const el = document.querySelector("[data-lang-current]");
    if (el) el.textContent = lang.toUpperCase();
  }


  function applyLang(lang) {
    if (!window.LANG) return;

    document.documentElement.lang = lang;
    document.documentElement.dir = (lang === "he") ? "rtl" : "ltr";

    document.querySelectorAll("[data-i18n]").forEach(el => {
      const key = el.dataset.i18n;
      const text = window.LANG[key]?.[lang];
      if (text) el.textContent = text;
    });

    document.querySelectorAll("[data-i18n-placeholder]").forEach(el => {
      const key = el.dataset.i18nPlaceholder;
      const text = window.LANG[key]?.[lang];
      if (text) el.placeholder = text;
    });

    setActiveButtons(lang);
    localStorage.setItem("site_lang", lang);

    setCurrentLabel(lang);
  }

  document.addEventListener("click", e => {
  const langWrap = e.target.closest("#lang");

  // Клик по кнопке (toggle меню)
  const btn = e.target.closest("#lang .lang__btn");
  if (btn){
    e.preventDefault();
    const open = langWrap.classList.toggle("is-open");
    btn.setAttribute("aria-expanded", open ? "true" : "false");
    return;
  }

  // Клик по пункту языка
  const opt = e.target.closest("[data-lang]");
  if (opt){
    e.preventDefault();
    const lang = opt.dataset.lang;
    if (!SUPPORTED.includes(lang)) return;

    location.hash = lang;
    applyLang(lang);

    // закрыть меню после выбора
    if (langWrap){
      langWrap.classList.remove("is-open");
      const b = langWrap.querySelector(".lang__btn");
      if (b) b.setAttribute("aria-expanded", "false");
    }
    return;
  }

  // Клик вне меню — закрыть
  const anyOpen = document.querySelector("#lang.is-open");
  if (anyOpen && !langWrap){
    anyOpen.classList.remove("is-open");
    const b = anyOpen.querySelector(".lang__btn");
    if (b) b.setAttribute("aria-expanded", "false");
  }
});


  const lang = getLang();
  if (!location.hash) {
    history.replaceState(null, "", "#" + lang);
  }

  applyLang(lang);
})();
