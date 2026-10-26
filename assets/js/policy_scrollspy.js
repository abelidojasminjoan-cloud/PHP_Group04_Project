
"use strict";

document.addEventListener("DOMContentLoaded", function () {

    const navigation = document.querySelector(".pv-privacy-nav");

    if (!navigation) return;

    const items = Array.from(
        navigation.querySelectorAll('a[href^="#"]')
    ).map(link => ({
        link,
        section: document.getElementById(
            link.getAttribute("href").substring(1)
        )
    })).filter(item => item.section);

    if (!items.length) return;

    let scheduled = false;

    function updateActiveSection() {
        scheduled = false;

        const readingLine = Math.min(
            180,
            window.innerHeight * 0.3
        );

        let activeIndex = 0;

        items.forEach((item, index) => {
            if (
                item.section.getBoundingClientRect().top
                <= readingLine
            ) {
                activeIndex = index;
            }
        });

        const atBottom =
            window.scrollY + window.innerHeight >=
            document.documentElement.scrollHeight - 4;

        if (atBottom) {
            activeIndex = items.length - 1;
        }

        items.forEach((item, index) => {
            const active = index === activeIndex;

            item.link.classList.toggle(
                "is-active",
                active
            );

            if (active) {
                item.link.setAttribute(
                    "aria-current",
                    "location"
                );
            } else {
                item.link.removeAttribute("aria-current");
            }
        });
    }

    function scheduleUpdate() {
        if (scheduled) return;

        scheduled = true;
        requestAnimationFrame(updateActiveSection);
    }

    window.addEventListener("scroll", scheduleUpdate, {
        passive: true
    });

    window.addEventListener("resize", scheduleUpdate);
    window.addEventListener("hashchange", scheduleUpdate);

    scheduleUpdate();
});
