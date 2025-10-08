const toggle = document.getElementById("billing-toggle");
const monthlyLabel = document.getElementById("monthly-label");
const annualLabel = document.getElementById("annual-label");

toggle.addEventListener("change", () => {
    if (toggle.checked) {
        monthlyLabel.classList.remove("active");
        annualLabel.classList.add("active");
    } else {
        monthlyLabel.classList.add("active");
        annualLabel.classList.remove("active");
    }
});

