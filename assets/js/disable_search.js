function querySearchElements(selector, callback) {
  const elements = document.querySelectorAll(selector);
  elements.forEach((element) => callback(element));
}

function observeSearch(selector, callback) {
  querySearchElements(selector, callback);

  const observer = new MutationObserver(() => {
    querySearchElements(selector, callback);
  });

  observer.observe(document.documentElement, {
    attributes: true,
    childList: true,
    characterData: true,
    subtree: true,
  });
}

observeSearch(".backend-dropdownmenu .item", (element) => {
  if (element.innerText.trim() === "Search") {
    element.style.display = "none";
  }
});