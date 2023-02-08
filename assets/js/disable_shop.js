function queryProductElements(selector, callback) {
  const elements = document.querySelectorAll(selector);
  elements.forEach((element) => callback(element));
}

function observeProducts(selector, callback) {
  queryProductElements(selector, callback);

  const observer = new MutationObserver(() => {
    queryProductElements(selector, callback);
  });

  observer.observe(document.documentElement, {
    attributes: true,
    childList: true,
    characterData: true,
    subtree: true,
  });
}

observeProducts(".backend-dropdownmenu .item", (element) => {
  if (element.innerText.trim() === "Products") {
    element.style.display = "none";
  }
});

observeProducts(".mainmenu-item a", (element) => {
  if (element.innerText.trim() === "Products") {
    element.style.display = "none";
  }
});

observeProducts(".mainmenu-item a", (element) => {
  if (element.innerText.trim() === "Product Categories") {
    element.style.display = "none";
  }
});

observeProducts(".mainmenu-item a", (element) => {
  if (element.innerText.trim() === "Product Properties") {
    element.style.display = "none";
  }
});
observeProducts(".mainmenu-item a", (element) => {
  if (element.innerText.trim() === "Orders") {
    element.style.display = "none";
  }
});

observeProducts(".mainmenu-item a", (element) => {
  if (element.innerText.trim() === "Shop") {
    element.parentElement.style.display = "none";
  }
});