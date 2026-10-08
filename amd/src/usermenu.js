import $ from 'jquery';

export const init = () => {
    if (!window.customUsermenuTargetBlankInjected) {
        window.customUsermenuTargetBlankInjected = true;
        document.addEventListener('click', e => {
            const t = e.target.closest('a[target="_blank"]');
            if (t && t.closest('.usermenu')) {
                e.stopPropagation();
            }
        }, true);
    }

    const sources = document.querySelectorAll('template[data-region="local-extendednav-submenu"]');
    const carousel = document.querySelector('.usermenu #usermenu-carousel .carousel-inner');
    const main = document.querySelector('.usermenu #carousel-item-main');

    if (!carousel || !main) {
        return;
    }

    // Fix missing FontAwesome icons and new window targets for root items.
    const nodeFixes = document.querySelectorAll('template[data-region="local-extendednav-node-fix"]');
    nodeFixes.forEach(fix => {
        const url = fix.dataset.url;
        const title = fix.dataset.title;
        const icon = fix.dataset.icon;
        const newWindow = fix.dataset.newwindow;

        let link = null;
        const links = Array.from(main.querySelectorAll('a'));

        if (url) {
            try {
                link = main.querySelector(`a[href*="${url}"]`);
            } catch (e) {
                // Ignore invalid selectors.
            }
            if (!link) {
                link = links.find(l => l.href && l.href.includes(url));
            }
        }

        if (!link && title) {
            link = links.find(l => l.textContent.trim() === title);
        }

        if (link) {
            if (newWindow) {
                link.setAttribute('target', '_blank');
            }
            if (!icon) {
                return;
            }
            const existingIcon = link.querySelector('.icon');
            if (existingIcon) {
                if (existingIcon.tagName === 'IMG') {
                    const wrapper = existingIcon.closest('.carousel-navigation-link-icon');
                    if (wrapper) {
                        wrapper.innerHTML = `<i class="icon fa ${icon} fa-fw" aria-hidden="true"></i>`;
                    } else {
                        existingIcon.outerHTML = `<i class="icon fa ${icon} fa-fw" aria-hidden="true"></i>`;
                    }
                } else if (existingIcon.tagName === 'I') {
                    existingIcon.className = `icon fa ${icon} fa-fw`;
                }
            } else {
                const iconHtml = `<span class="carousel-navigation-link-icon">` +
                    `<i class="icon fa ${icon} fa-fw" aria-hidden="true"></i></span>`;
                link.innerHTML = iconHtml + link.innerHTML;
            }
        }
    });

    sources.forEach(source => {
        const triggerUrl = source.dataset.triggerUrl;
        const panelId = source.dataset.panelId;

        if (carousel.querySelector('#' + panelId)) {
            return;
        }

        let trigger = null;
        const allLinks = Array.from(main.querySelectorAll('a'));

        if (triggerUrl) {
            try {
                trigger = main.querySelector(`a[href*="${triggerUrl}"]`);
            } catch (e) {
                // Ignore invalid selectors.
            }
            if (!trigger) {
                trigger = allLinks.find(l => l.href && l.href.includes(triggerUrl));
            }
        }

        if (!trigger) {
            const triggerTitle = source.dataset.triggerTitle;
            if (triggerTitle) {
                trigger = allLinks.find(l => l.textContent.trim() === triggerTitle);
            }
        }

        if (!trigger) {
            return;
        }

        trigger.href = '#';
        trigger.classList.add('carousel-navigation-link');
        trigger.dataset.carouselTargetId = panelId;

        // Restore Moodle's native structure for submenus
        if (!trigger.querySelector('.carousel-navigation-link-arrow')) {
            const originalContent = trigger.innerHTML;
            trigger.innerHTML = `<div class="d-flex w-100 justify-content-between">` +
                `<span class="d-flex align-items-center w-100">` + originalContent + `</span>` +
                `<span class="carousel-navigation-link-arrow ml-auto">` +
                `<i class="icon fa fa-chevron-right fa-fw" aria-hidden="true"></i></span></div>`;
        }

        carousel.append(source.content.cloneNode(true));
        source.remove();

        const openSubmenu = event => {
            event.preventDefault();
            event.stopPropagation();
            const panel = carousel.querySelector('#' + panelId);
            const index = Array.from(carousel.children).indexOf(panel);
            $(carousel.closest('#usermenu-carousel')).carousel(index);
        };
        trigger.addEventListener('click', openSubmenu);
        trigger.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') {
                openSubmenu(event);
            }
        });
    });
};
