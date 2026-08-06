(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        var modalElement = document.getElementById("doctorPhotoCropModal");
        var stage = document.querySelector("[data-crop-stage]");
        var image = document.querySelector("[data-crop-image]");
        var preview = document.querySelector("[data-crop-preview]");
        var zoom = document.querySelector("[data-crop-zoom]");
        var resetButton = document.querySelector("[data-crop-reset]");
        var saveButton = document.querySelector("[data-crop-save]");
        var modal = modalElement && window.bootstrap ? new window.bootstrap.Modal(modalElement) : null;
        var activeInput = null;
        var activeForm = null;
        var activeCroppedInput = null;
        var state = {
            baseScale: 1,
            dragging: false,
            loaded: false,
            naturalHeight: 0,
            naturalWidth: 0,
            objectUrl: null,
            offsetX: 0,
            offsetY: 0,
            pointerId: null,
            stageSize: 0,
            startOffsetX: 0,
            startOffsetY: 0,
            startX: 0,
            startY: 0,
            submitting: false,
            zoom: 1
        };

        function clamp(value, min, max) {
            return Math.min(Math.max(value, min), max);
        }

        function showError(message) {
            window.alert(message);
        }

        function releaseObjectUrl() {
            if (state.objectUrl && window.URL) {
                window.URL.revokeObjectURL(state.objectUrl);
            }

            state.objectUrl = null;
        }

        function resetPicker() {
            if (!state.submitting && activeInput) {
                activeInput.value = "";
            }

            if (!state.submitting && activeCroppedInput) {
                activeCroppedInput.value = "";
            }

            releaseObjectUrl();
            state.dragging = false;
            state.loaded = false;
            state.pointerId = null;
            stage && stage.classList.remove("is-dragging");
            image && image.removeAttribute("src");
            image && image.removeAttribute("style");

            if (saveButton) {
                saveButton.disabled = true;
            }

            if (!state.submitting) {
                activeInput = null;
                activeForm = null;
                activeCroppedInput = null;
            }
        }

        function validFile(file) {
            if (["image/jpeg", "image/png", "image/webp"].indexOf(file.type) === -1) {
                showError("Foto dokter harus berformat JPG, JPEG, PNG, atau WEBP.");
                return false;
            }

            if (file.size > 2 * 1024 * 1024) {
                showError("Ukuran foto dokter maksimal 2 MB.");
                return false;
            }

            return true;
        }

        function cropRectangle() {
            var scale = state.baseScale * state.zoom;
            var displayWidth = state.naturalWidth * scale;
            var displayHeight = state.naturalHeight * scale;
            var displayLeft = state.stageSize / 2 + state.offsetX - displayWidth / 2;
            var displayTop = state.stageSize / 2 + state.offsetY - displayHeight / 2;
            var cropWidth = Math.min(state.naturalWidth, state.stageSize / scale);
            var cropHeight = Math.min(state.naturalHeight, state.stageSize / scale);

            return {
                height: cropHeight,
                width: cropWidth,
                x: clamp((0 - displayLeft) / scale, 0, state.naturalWidth - cropWidth),
                y: clamp((0 - displayTop) / scale, 0, state.naturalHeight - cropHeight)
            };
        }

        function renderPreview() {
            if (!preview || !image || !state.loaded) {
                return;
            }

            var context = preview.getContext("2d");

            if (!context) {
                return;
            }

            var crop = cropRectangle();
            context.clearRect(0, 0, preview.width, preview.height);
            context.fillStyle = "#ffffff";
            context.fillRect(0, 0, preview.width, preview.height);
            context.drawImage(image, crop.x, crop.y, crop.width, crop.height, 0, 0, preview.width, preview.height);
        }

        function updateCrop() {
            if (!state.loaded || !stage || !image) {
                return;
            }

            var zoomValue = zoom ? parseFloat(zoom.value) : state.zoom;
            state.zoom = clamp(isNaN(zoomValue) ? 1 : zoomValue, 1, 3);
            var baseWidth = state.naturalWidth * state.baseScale;
            var baseHeight = state.naturalHeight * state.baseScale;
            var maxOffsetX = Math.max((baseWidth * state.zoom - state.stageSize) / 2, 0);
            var maxOffsetY = Math.max((baseHeight * state.zoom - state.stageSize) / 2, 0);
            state.offsetX = clamp(state.offsetX, -maxOffsetX, maxOffsetX);
            state.offsetY = clamp(state.offsetY, -maxOffsetY, maxOffsetY);
            image.style.height = baseHeight + "px";
            image.style.left = "calc(50% + " + state.offsetX + "px)";
            image.style.top = "calc(50% + " + state.offsetY + "px)";
            image.style.transform = "translate(-50%, -50%) scale(" + state.zoom + ")";
            image.style.width = baseWidth + "px";
            renderPreview();
        }

        function prepareCrop(resetPosition) {
            if (!state.loaded || !stage) {
                return;
            }

            var rect = stage.getBoundingClientRect();
            state.stageSize = Math.max(1, Math.round(Math.min(rect.width, rect.height)));
            state.baseScale = Math.max(state.stageSize / state.naturalWidth, state.stageSize / state.naturalHeight);

            if (resetPosition) {
                state.offsetX = 0;
                state.offsetY = 0;
                state.zoom = 1;
                zoom && (zoom.value = "1");
            }

            updateCrop();
            saveButton && (saveButton.disabled = false);
        }

        function openCrop(file) {
            if (!modal || !stage || !image || !preview || !zoom || !activeForm || !activeCroppedInput || !window.URL) {
                activeForm && activeForm.requestSubmit();
                return;
            }

            releaseObjectUrl();
            activeCroppedInput.value = "";
            state.loaded = false;
            state.submitting = false;
            saveButton.disabled = true;
            state.objectUrl = window.URL.createObjectURL(file);
            image.onload = function () {
                state.loaded = true;
                state.naturalHeight = image.naturalHeight;
                state.naturalWidth = image.naturalWidth;

                if (modalElement.classList.contains("show")) {
                    prepareCrop(true);
                }
            };
            image.onerror = function () {
                showError("Foto dokter tidak dapat dibaca.");
                modal.hide();
            };
            image.src = state.objectUrl;
            modal.show();
        }

        function saveCrop() {
            if (!state.loaded || !image || !activeForm || !activeInput || !activeCroppedInput) {
                return;
            }

            var crop = cropRectangle();
            var output = document.createElement("canvas");
            var context = output.getContext("2d");

            if (!context) {
                showError("Browser belum dapat memproses crop foto.");
                return;
            }

            output.width = 640;
            output.height = 640;
            context.fillStyle = "#ffffff";
            context.fillRect(0, 0, output.width, output.height);
            context.drawImage(image, crop.x, crop.y, crop.width, crop.height, 0, 0, output.width, output.height);
            var dataUrl = output.toDataURL("image/jpeg", 0.9);

            if (dataUrl.length > 3145728) {
                showError("Hasil crop foto dokter maksimal 2 MB.");
                return;
            }

            activeCroppedInput.value = dataUrl;
            activeInput.value = "";
            state.submitting = true;
            saveButton.disabled = true;
            activeForm.submit();
        }

        document.querySelectorAll("[data-photo-picker]").forEach(function (button) {
            button.addEventListener("click", function () {
                var form = button.closest(".doctor-photo-upload-form");
                var input = form && form.querySelector(".doctor-photo-file-input");
                input && input.click();
            });
        });

        document.querySelectorAll(".doctor-photo-file-input").forEach(function (input) {
            input.addEventListener("change", function () {
                var file = input.files && input.files[0];

                if (!file) {
                    return;
                }

                if (!validFile(file)) {
                    input.value = "";
                    return;
                }

                activeInput = input;
                activeForm = input.closest(".doctor-photo-upload-form");
                activeCroppedInput = activeForm && activeForm.querySelector(".doctor-photo-cropped-input");
                openCrop(file);
            });
        });

        modalElement && modalElement.addEventListener("shown.bs.modal", function () {
            prepareCrop(true);
        });
        modalElement && modalElement.addEventListener("hidden.bs.modal", resetPicker);

        stage && stage.addEventListener("pointerdown", function (event) {
            if (!state.loaded) {
                return;
            }

            state.dragging = true;
            state.pointerId = event.pointerId;
            state.startOffsetX = state.offsetX;
            state.startOffsetY = state.offsetY;
            state.startX = event.clientX;
            state.startY = event.clientY;
            stage.classList.add("is-dragging");
            stage.setPointerCapture(event.pointerId);
            event.preventDefault();
        });

        stage && stage.addEventListener("pointermove", function (event) {
            if (!state.dragging || state.pointerId !== event.pointerId) {
                return;
            }

            state.offsetX = state.startOffsetX + event.clientX - state.startX;
            state.offsetY = state.startOffsetY + event.clientY - state.startY;
            updateCrop();
        });

        ["pointerup", "pointercancel"].forEach(function (eventName) {
            stage && stage.addEventListener(eventName, function (event) {
                if (state.pointerId !== event.pointerId) {
                    return;
                }

                state.dragging = false;
                state.pointerId = null;
                stage.classList.remove("is-dragging");
            });
        });

        stage && stage.addEventListener("wheel", function (event) {
            if (!state.loaded || !zoom) {
                return;
            }

            event.preventDefault();
            zoom.value = clamp(state.zoom + (event.deltaY > 0 ? -0.08 : 0.08), 1, 3);
            updateCrop();
        }, { passive: false });

        zoom && zoom.addEventListener("input", updateCrop);
        resetButton && resetButton.addEventListener("click", function () {
            state.offsetX = 0;
            state.offsetY = 0;
            zoom.value = "1";
            updateCrop();
        });
        saveButton && saveButton.addEventListener("click", saveCrop);

        document.querySelectorAll("[data-photo-delete-form]").forEach(function (form) {
            form.addEventListener("submit", function (event) {
                if (!window.confirm("Hapus foto dokter ini? Tampilan akan kembali menggunakan inisial.")) {
                    event.preventDefault();
                }
            });
        });

        window.addEventListener("resize", function () {
            if (modalElement && modalElement.classList.contains("show")) {
                prepareCrop(false);
            }
        });
    });
})();
