function queryEventElements(selector, callback) {
  const elements = document.querySelectorAll(selector);
  elements.forEach((element) => callback(element));
}

function observeEvents(selector, callback) {

  queryEventElements(selector, callback);

  const observer = new MutationObserver(() => {
    queryEventElements(selector, callback);
  });

  observer.observe(document.documentElement, {
    attributes: true,
    childList: true,
    characterData: true,
    subtree: true,
  });
}

observeEvents(".backend-dropdownmenu .item", (element) => {
  if(element.innerText.trim() === "Events") {
    element.style.display = 'none';
  }
});

observeEvents(".mainmenu-item a", (element) => {
  if (element.innerText.trim() === "Events") {
    element.style.display = "none";
  }
});

observeEvents(".mainmenu-item a", (element) => {
  if (element.innerText.trim() === "Events") {
    element.parentElement.style.display = "none";
  }
});