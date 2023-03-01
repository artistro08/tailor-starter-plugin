
document.addEventListener("page:loaded", (event) => {
    let saveButton = document.querySelectorAll('[data-request="onSave"]');
    console.log(saveButton);
    saveButton.forEach((button) => {
        button.setAttribute("data-request-success", "window.location.reload()");
    });
});