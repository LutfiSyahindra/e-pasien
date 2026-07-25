 <!-- Bootstrap bundle JS -->
 <script src="{{ asset("epasien/assets/js/bootstrap.bundle.min.js") }}"></script>
 <!--plugins-->
 <script src="{{ asset("epasien/assets/js/jquery.min.js") }}"></script>
 <script src="{{ asset("epasien/assets/plugins/select2/js/select2.min.js") }}"></script>
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

         const originalFire = Swal.fire.bind(Swal);

         function mergeClassValue(baseValue, customValue) {
             return [baseValue, customValue].filter(Boolean).join(" ");
         }

         function mergeCustomClass(customClass) {
             const merged = Object.assign({}, defaults.customClass);

             if (typeof customClass === "string") {
                 merged.popup = mergeClassValue(merged.popup, customClass);
                 return merged;
             }

             Object.keys(customClass || {}).forEach(function(key) {
                 merged[key] = mergeClassValue(merged[key], customClass[key]);
             });

             return merged;
         }

         Swal.fire = function() {
             if (arguments.length === 1 && typeof arguments[0] === "object") {
                 const options = arguments[0] || {};

                 return originalFire(Object.assign({}, defaults, options, {
                     customClass: mergeCustomClass(options.customClass)
                 }));
             }

             return originalFire.apply(Swal, arguments);
         };
     })();
 </script>
 <script src="{{ asset("epasien/assets/plugins/simplebar/js/simplebar.min.js") }}"></script>
 <script src="{{ asset("epasien/assets/plugins/metismenu/js/metisMenu.min.js") }}"></script>
 <script src="{{ asset("epasien/assets/plugins/easyPieChart/jquery.easypiechart.js") }}"></script>
 <script src="{{ asset("epasien/assets/plugins/peity/jquery.peity.min.js") }}"></script>
 <script src="{{ asset("epasien/assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js") }}"></script>
 <script src="{{ asset("epasien/assets/js/pace.min.js") }}"></script>
 <script src="{{ asset("epasien/assets/plugins/vectormap/jquery-jvectormap-2.0.2.min.js") }}"></script>
 <script src="{{ asset("epasien/assets/plugins/vectormap/jquery-jvectormap-world-mill-en.js") }}"></script>
 {{-- <script src="{{ asset("epasien/assets/plugins/apexcharts-bundle/js/apexcharts.min.js") }}"></script> --}}
 <script src="{{ asset("epasien/assets/plugins/datatable/js/jquery.dataTables.min.js") }}"></script>
 <script src="{{ asset("epasien/assets/plugins/datatable/js/dataTables.bootstrap5.min.js") }}"></script>
 <!--app-->
 <script src="{{ asset("epasien/assets/js/app.js") }}"></script>
 {{-- <script src="{{ asset("epasien/assets/js/index.js") }}"></script> --}}

 {{-- <script>
     new PerfectScrollbar(".best-product")
     new PerfectScrollbar(".top-sellers-list")
 </script> --}}
