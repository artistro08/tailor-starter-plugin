function queryBlogElements(selector, callback) {
  const elements = document.querySelectorAll(selector);
  elements.forEach((element) => callback(element));
}

function observeBlog(selector, callback) {
  queryBlogElements(selector, callback);

  const observer = new MutationObserver(() => {
    queryBlogElements(selector, callback);
  });

  observer.observe(document.documentElement, {
    attributes: true,
    childList: true,
    characterData: true,
    subtree: true,
  });
}

observeBlog(".backend-dropdownmenu .item", (element) => {
  if (element.innerText.trim() === "Posts") {
    element.style.display = "none";
  }
});

observeBlog(".mainmenu-item a", (element) => {
  if (element.innerText.trim() === "Blog") {
    if (element.innerHTML.includes("icon-newspaper-o"))
      element.parentNode.style.display = "none";
  }
});