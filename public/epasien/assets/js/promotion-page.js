(function () {
    "use strict";

    var input = document.getElementById("promo-image");
    var preview = document.querySelector("[data-image-preview]");
    var placeholder = document.querySelector("[data-upload-placeholder]");

    if (input && preview) {
        input.addEventListener("change", function () {
            var file = input.files && input.files[0];
            if (!file) return;
            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
            if (placeholder) placeholder.hidden = true;
        });
    }

    ["title", "caption"].forEach(function (name) {
        var field = document.querySelector('[name="' + name + '"]');
        var counter = document.querySelector('[data-count-for="' + name + '"]');
        if (!field || !counter) return;
        var update = function () { counter.textContent = field.value.length; };
        field.addEventListener("input", update);
        update();
    });

    document.querySelectorAll("[data-promo-delete]").forEach(function (form) {
        form.addEventListener("submit", function (event) {
            event.preventDefault();
            if (window.Swal) {
                window.Swal.fire({
                    title: "Hapus promosi?",
                    text: "Konten dan gambar akan dihapus permanen.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Ya, hapus",
                    cancelButtonText: "Batal"
                }).then(function (result) { if (result.isConfirmed) form.submit(); });
                return;
            }
            if (window.confirm("Hapus promosi ini secara permanen?")) form.submit();
        });
    });
})();
