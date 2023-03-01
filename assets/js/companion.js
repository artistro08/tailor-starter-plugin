document.addEventListener("page:loaded", (event) => {
    let saveButton = document.querySelectorAll('[data-request="onSave"]');
    saveButton.forEach((button) => {
        button.setAttribute("data-request-success", "window.location.reload()");
    });
});