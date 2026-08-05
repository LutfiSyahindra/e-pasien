

$(function() {
	"use strict";

  // Tooltops

    $(function () {
        $('[data-bs-toggle="tooltip"]').tooltip();
    })



    var $wrapper = $(".wrapper")
    var $sidebar = $(".sidebar-wrapper")
    var $sidebarToggle = $(".toggle-icon")
    var $mobileSidebarToggle = $(".mobile-toggle-icon")
    var $sidebarMenu = $("#menu")
    var $sidebarSearch = $("#ep-sidebar-search")
    var desktopSidebarQuery = window.matchMedia("(min-width: 1026px)")
    var sidebarPreferenceKey = "epasien.sidebar.collapsed"

    function readSidebarPreference() {
        try {
            return window.localStorage.getItem(sidebarPreferenceKey) === "true"
        } catch (error) {
            return false
        }
    }

    function writeSidebarPreference(isCollapsed) {
        try {
            window.localStorage.setItem(sidebarPreferenceKey, String(isCollapsed))
        } catch (error) {
            // Navigasi tetap berfungsi ketika penyimpanan browser dibatasi.
        }
    }

    function updateSidebarControl() {
        var isDesktop = desktopSidebarQuery.matches
        var isExpanded = isDesktop ? !$wrapper.hasClass("toggled") : $wrapper.hasClass("toggled")
        var controlLabel = isDesktop
            ? (isExpanded ? "Ciutkan sidebar" : "Perluas sidebar")
            : "Tutup sidebar"
        var mobileSidebarExpanded = !isDesktop && isExpanded
        var mobileControlLabel = mobileSidebarExpanded ? "Tutup menu utama" : "Buka menu utama"
        var $controlIcon = $sidebarToggle.find("i")

        $sidebarToggle
            .attr("aria-expanded", String(isExpanded))
            .attr("aria-label", controlLabel)
            .attr("title", controlLabel)

        $mobileSidebarToggle
            .attr("aria-expanded", String(mobileSidebarExpanded))
            .attr("aria-label", mobileControlLabel)
            .attr("title", mobileControlLabel)

        $controlIcon
            .toggleClass("bi-chevron-double-left", isDesktop)
            .toggleClass("bi-x-lg", !isDesktop)
    }

    function bindSidebarHover() {
        $sidebar.off(".epSidebarHover")

        if (desktopSidebarQuery.matches && $wrapper.hasClass("toggled")) {
            $sidebar
                .on("mouseenter.epSidebarHover", function() {
                    $wrapper.addClass("sidebar-hovered")
                })
                .on("mouseleave.epSidebarHover", function() {
                    $wrapper.removeClass("sidebar-hovered")
                })
        }
    }

    function setDesktopSidebarCollapsed(isCollapsed, persistPreference) {
        $wrapper.toggleClass("toggled", isCollapsed)

        if (!isCollapsed) {
            $wrapper.removeClass("sidebar-hovered")
        }

        bindSidebarHover()
        updateSidebarControl()

        if (persistPreference !== false) {
            writeSidebarPreference(isCollapsed)
        }
    }

    function syncSidebarForViewport() {
        $wrapper.removeClass("sidebar-hovered")

        if (desktopSidebarQuery.matches) {
            setDesktopSidebarCollapsed(readSidebarPreference(), false)
        } else {
            $wrapper.removeClass("toggled")
            $sidebar.off(".epSidebarHover")
            updateSidebarControl()
        }
    }

    $(".nav-toggle-icon").on("click", function() {
        if (!desktopSidebarQuery.matches) {
            $wrapper.removeClass("toggled")
            updateSidebarControl()
        }
    })

    $(".mobile-toggle-icon").on("click", function() {
        $wrapper.addClass("toggled")
        updateSidebarControl()
    })

    $sidebarToggle.on("click", function() {
        if (desktopSidebarQuery.matches) {
            setDesktopSidebarCollapsed(!$wrapper.hasClass("toggled"))
        } else {
            $wrapper.removeClass("toggled")
            updateSidebarControl()
        }
    })

    if (typeof desktopSidebarQuery.addEventListener === "function") {
        desktopSidebarQuery.addEventListener("change", syncSidebarForViewport)
    } else {
        desktopSidebarQuery.addListener(syncSidebarForViewport)
    }

    syncSidebarForViewport()

    $(function() {
        for (var e = window.location, o = $(".metismenu li a").filter(function() {
                return this.href == e
            }).parent().addClass("mm-active"); o.is("li");) {
            o = o.parent("").addClass("mm-show").parent("").addClass("mm-active")
        }
    })

    $(function() {
        $sidebarMenu.metisMenu()

        $sidebarMenu.children("li").children("a").each(function() {
            var menuTitle = $(this).find(".menu-title").text().trim()

            if (menuTitle && !this.hasAttribute("title")) {
                this.setAttribute("title", menuTitle)
            }
        })
    })

    var sidebarSearchActive = false

    function restoreSidebarSearch() {
        var $topItems = $sidebarMenu.children("li").not(".menu-label, .ep-sidebar-empty")

        $topItems.show().removeClass("ep-search-open")
        $topItems.find("li").show()
        $topItems.each(function() {
            var $item = $(this)
            var $submenu = $item.children("ul").first()
            var $trigger = $item.children("a.has-arrow").first()

            if (!$submenu.length) {
                return
            }

            if ($submenu.data("epSearchWasOpen")) {
                $submenu.addClass("mm-show")
                $trigger.attr("aria-expanded", "true")
            } else {
                $submenu.removeClass("mm-show")
                $trigger.attr("aria-expanded", "false")
            }

            $submenu.removeAttr("style").removeData("epSearchWasOpen")
        })

        $sidebarMenu.children(".menu-label").show()
        $sidebarMenu.children(".ep-sidebar-empty").removeClass("is-visible")
        sidebarSearchActive = false
    }

    function filterSidebarMenu(searchValue) {
        var query = String(searchValue || "").trim().toLocaleLowerCase("id-ID")
        var $topItems = $sidebarMenu.children("li").not(".menu-label, .ep-sidebar-empty")
        var visibleItemCount = 0

        if (!query) {
            restoreSidebarSearch()
            return
        }

        if (!sidebarSearchActive) {
            $topItems.children("ul").each(function() {
                $(this).data("epSearchWasOpen", $(this).hasClass("mm-show"))
            })
            sidebarSearchActive = true
        }

        $topItems.each(function() {
            var $item = $(this)
            var $trigger = $item.children("a").first()
            var $submenu = $item.children("ul").first()
            var title = $trigger.find(".menu-title").text().trim().toLocaleLowerCase("id-ID")
            var directMatch = title.indexOf(query) !== -1
            var hasMatchingChild = false

            if ($submenu.length) {
                $submenu.children("li").each(function() {
                    var $child = $(this)
                    var childMatches = directMatch || $child.text().trim().toLocaleLowerCase("id-ID").indexOf(query) !== -1

                    $child.toggle(childMatches)
                    hasMatchingChild = hasMatchingChild || childMatches
                })
            }

            var itemMatches = directMatch || hasMatchingChild
            $item.toggle(itemMatches)

            if (itemMatches) {
                visibleItemCount += 1
            }

            if ($submenu.length && itemMatches) {
                $item.addClass("ep-search-open")
                $submenu.addClass("mm-show").removeAttr("style")
                $trigger.attr("aria-expanded", "true")
            }
        })

        $sidebarMenu.children(".menu-label").each(function() {
            var hasVisibleItems = $(this)
                .nextUntil(".menu-label")
                .filter(":visible")
                .not(".ep-sidebar-empty")
                .length > 0

            $(this).toggle(hasVisibleItems)
        })

        $sidebarMenu.children(".ep-sidebar-empty").toggleClass("is-visible", visibleItemCount === 0)
    }

    $sidebarSearch.on("input", function() {
        filterSidebarMenu(this.value)
    })

    $sidebarSearch.on("keydown", function(event) {
        if (event.key === "Escape") {
            this.value = ""
            filterSidebarMenu("")
        }
    })

    $(document).on("keydown", function(event) {
        var target = event.target
        var isTyping = /^(INPUT|TEXTAREA|SELECT)$/.test(target.tagName) || target.isContentEditable

        if (event.key !== "/" || isTyping || event.ctrlKey || event.metaKey || event.altKey) {
            return
        }

        event.preventDefault()

        if (desktopSidebarQuery.matches) {
            setDesktopSidebarCollapsed(false)
        } else {
            $wrapper.addClass("toggled")
            updateSidebarControl()
        }

        window.setTimeout(function() {
            $sidebarSearch.trigger("focus")
        }, 180)
    })


	$(".chat-toggle-btn").on("click", function() {
		$(".chat-wrapper").toggleClass("chat-toggled")
	}), $(".chat-toggle-btn-mobile").on("click", function() {
		$(".chat-wrapper").removeClass("chat-toggled")
	}), $(".email-toggle-btn").on("click", function() {
		$(".email-wrapper").toggleClass("email-toggled")
	}), $(".email-toggle-btn-mobile").on("click", function() {
		$(".email-wrapper").removeClass("email-toggled")
	}), $(".compose-mail-btn").on("click", function() {
		$(".compose-mail-popup").show()
	}), $(".compose-mail-close").on("click", function() {
		$(".compose-mail-popup").hide()
	})


	$(document).ready(function() {
		$(window).on("scroll", function() {
			$(this).scrollTop() > 300 ? $(".back-to-top").fadeIn() : $(".back-to-top").fadeOut()
		}), $(".back-to-top").on("click", function() {
			return $("html, body").animate({
				scrollTop: 0
			}, 600), !1
		})
	})


	// switcher 

	$("#LightTheme").on("click", function() {
		$("html").attr("class", "light-theme")
	}),

	$("#DarkTheme").on("click", function() {
		$("html").attr("class", "dark-theme")
	}),

	$("#SemiDarkTheme").on("click", function() {
		$("html").attr("class", "semi-dark")
	}),

	$("#MinimalTheme").on("click", function() {
		$("html").attr("class", "minimal-theme")
	})


	$("#headercolor1").on("click", function() {
		$("html").addClass("color-header headercolor1"), $("html").removeClass("headercolor2 headercolor3 headercolor4 headercolor5 headercolor6 headercolor7 headercolor8")
	}), $("#headercolor2").on("click", function() {
		$("html").addClass("color-header headercolor2"), $("html").removeClass("headercolor1 headercolor3 headercolor4 headercolor5 headercolor6 headercolor7 headercolor8")
	}), $("#headercolor3").on("click", function() {
		$("html").addClass("color-header headercolor3"), $("html").removeClass("headercolor1 headercolor2 headercolor4 headercolor5 headercolor6 headercolor7 headercolor8")
	}), $("#headercolor4").on("click", function() {
		$("html").addClass("color-header headercolor4"), $("html").removeClass("headercolor1 headercolor2 headercolor3 headercolor5 headercolor6 headercolor7 headercolor8")
	}), $("#headercolor5").on("click", function() {
		$("html").addClass("color-header headercolor5"), $("html").removeClass("headercolor1 headercolor2 headercolor4 headercolor3 headercolor6 headercolor7 headercolor8")
	}), $("#headercolor6").on("click", function() {
		$("html").addClass("color-header headercolor6"), $("html").removeClass("headercolor1 headercolor2 headercolor4 headercolor5 headercolor3 headercolor7 headercolor8")
	}), $("#headercolor7").on("click", function() {
		$("html").addClass("color-header headercolor7"), $("html").removeClass("headercolor1 headercolor2 headercolor4 headercolor5 headercolor6 headercolor3 headercolor8")
	}), $("#headercolor8").on("click", function() {
		$("html").addClass("color-header headercolor8"), $("html").removeClass("headercolor1 headercolor2 headercolor4 headercolor5 headercolor6 headercolor7 headercolor3")
	})


});
