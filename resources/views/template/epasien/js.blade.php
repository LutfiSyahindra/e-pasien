 <!-- Bootstrap bundle JS -->
 <script src="{{ versioned_asset("epasien/assets/js/bootstrap.bundle.min.js") }}"></script>
 <!--plugins-->
 <script src="{{ versioned_asset("epasien/assets/js/jquery.min.js") }}"></script>
 @if (request()->routeIs("users.*", "roles.*", "roleConfiguration.*", "daftarOnline.index"))
     <script src="{{ versioned_asset("epasien/assets/plugins/select2/js/select2.min.js") }}"></script>
 @endif
 @if (request()->routeIs("profile.*", "users.*", "roles.*", "permissions.*", "daftarOnline.*"))
     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
     <script>
     (function() {
         if (!window.Swal) {
             return;
         }

         const defaults = {
             buttonsStyling: false,
             focusConfirm: false,
             heightAuto: false,
             reverseButtons: true,
             width: "min(360px, calc(100vw - 32px))",
             customClass: {
                 container: "ep-swal-container",
                 popup: "ep-swal-popup",
                 icon: "ep-swal-icon",
                 title: "ep-swal-title",
                 htmlContainer: "ep-swal-text",
                 actions: "ep-swal-actions",
                 confirmButton: "ep-swal-button ep-swal-confirm",
                 cancelButton: "ep-swal-button ep-swal-cancel",
                 denyButton: "ep-swal-button ep-swal-deny",
                 closeButton: "ep-swal-close",
                 timerProgressBar: "ep-swal-progress"
             }
         };

         const toastDefaults = {
             backdrop: false,
             position: "top-end",
             width: "min(360px, calc(100vw - 24px))"
         };

         const toastClasses = {
             container: "ep-swal-toast-container",
             popup: "ep-swal-toast-popup",
             icon: "ep-swal-toast-icon"
         };

         const iconHtml = {
             success: '<i class="bi bi-check2"></i>',
             error: '<i class="bi bi-x-lg"></i>',
             warning: '<i class="bi bi-exclamation-triangle"></i>',
             question: '<i class="bi bi-question-lg"></i>',
             info: '<i class="bi bi-info-lg"></i>'
         };

         const originalFire = Swal.fire.bind(Swal);

         function mergeClassValue(baseValue, customValue) {
             return [baseValue, customValue].filter(Boolean).join(" ");
         }

         function mergeCustomClass(customClass, isToast) {
             const merged = Object.assign({}, defaults.customClass);

             if (isToast) {
                 Object.keys(toastClasses).forEach(function(key) {
                     merged[key] = mergeClassValue(merged[key], toastClasses[key]);
                 });
             }

             if (typeof customClass === "string") {
                 merged.popup = mergeClassValue(merged.popup, customClass);
                 return merged;
             }

             Object.keys(customClass || {}).forEach(function(key) {
                 merged[key] = mergeClassValue(merged[key], customClass[key]);
             });

             return merged;
         }

         function buildOptions(options) {
             const isToast = options.toast === true;
             const normalized = Object.assign({}, defaults, isToast ? toastDefaults : {}, options, {
                 customClass: mergeCustomClass(options.customClass, isToast)
             });

             if (isToast) {
                 normalized.backdrop = false;
                 normalized.position = options.position || toastDefaults.position;
             }

             if (
                 normalized.icon &&
                 iconHtml[normalized.icon] &&
                 !Object.prototype.hasOwnProperty.call(options, "iconHtml")
             ) {
                 normalized.iconHtml = iconHtml[normalized.icon];
             }

             return normalized;
         }

         Swal.fire = function() {
             if (arguments.length === 1 && typeof arguments[0] === "object") {
                 return originalFire(buildOptions(arguments[0] || {}));
             }

             return originalFire.apply(Swal, arguments);
         };
     })();
     </script>
 @endif
 <script src="{{ versioned_asset("epasien/assets/plugins/simplebar/js/simplebar.min.js") }}"></script>
 <script src="{{ versioned_asset("epasien/assets/plugins/metismenu/js/metisMenu.min.js") }}"></script>
 @if (request()->routeIs("users.*", "roles.*", "permissions.*"))
     <script src="{{ versioned_asset("epasien/assets/plugins/datatable/js/jquery.dataTables.min.js") }}"></script>
     <script src="{{ versioned_asset("epasien/assets/plugins/datatable/js/dataTables.bootstrap5.min.js") }}"></script>
 @endif
 <!--app-->
 <script src="{{ versioned_asset("epasien/assets/js/app.js") }}"></script>
 {{-- <script src="{{ versioned_asset("epasien/assets/js/index.js") }}"></script> --}}
