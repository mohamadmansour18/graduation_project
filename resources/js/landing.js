const root = document.documentElement;
const themeToggle = document.querySelector('#theme-toggle');
const themeColorLight = document.querySelector('meta[name="theme-color"][media*="light"]');
const themeColorDark = document.querySelector('meta[name="theme-color"][media*="dark"]');

const readTheme = () => root.dataset.theme === 'dark' ? 'dark' : 'light';

const updateThemeControl = (theme) => {
    if (!themeToggle) {
        return;
    }

    const isDark = theme === 'dark';
    themeToggle.setAttribute('aria-pressed', String(isDark));
    themeToggle.setAttribute(
        'aria-label',
        isDark ? 'تفعيل الوضع النهاري' : 'تفعيل الوضع الليلي',
    );

    if (themeColorLight && themeColorDark) {
        const activeColor = isDark ? '#07101f' : '#f7f9ff';
        themeColorLight.content = activeColor;
        themeColorDark.content = activeColor;
    }
};

updateThemeControl(readTheme());

themeToggle?.addEventListener('click', () => {
    const nextTheme = readTheme() === 'dark' ? 'light' : 'dark';

    root.dataset.theme = nextTheme;
    root.style.colorScheme = nextTheme;

    try {
        localStorage.setItem('nerd-theme', nextTheme);
    } catch {
        // The theme still works for the current visit when storage is unavailable.
    }

    updateThemeControl(nextTheme);
});

const siteHeader = document.querySelector('#site-header');
const scrollProgressBar = document.querySelector('#scroll-progress-bar');

const updateScrollState = () => {
    const scrollTop = window.scrollY;
    const scrollableHeight = document.documentElement.scrollHeight - window.innerHeight;
    const progress = scrollableHeight > 0 ? Math.min(scrollTop / scrollableHeight, 1) : 0;

    siteHeader?.classList.toggle('is-scrolled', scrollTop > 16);

    if (scrollProgressBar) {
        scrollProgressBar.style.width = `${progress * 100}%`;
    }
};

updateScrollState();
window.addEventListener('scroll', updateScrollState, { passive: true });

const revealItems = document.querySelectorAll('.reveal');
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (prefersReducedMotion || !('IntersectionObserver' in window)) {
    revealItems.forEach((item) => item.classList.add('is-visible'));
} else {
    const revealObserver = new IntersectionObserver(
        (entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        },
        {
            threshold: 0.13,
            rootMargin: '0px 0px -45px',
        },
    );

    revealItems.forEach((item) => revealObserver.observe(item));
}

const navLinks = [...document.querySelectorAll('.desktop-nav a')];
const navSections = navLinks
    .map((link) => document.querySelector(link.getAttribute('href')))
    .filter(Boolean);

if ('IntersectionObserver' in window && navSections.length > 0) {
    const navigationObserver = new IntersectionObserver(
        (entries) => {
            const visibleEntry = entries
                .filter((entry) => entry.isIntersecting)
                .sort((first, second) => second.intersectionRatio - first.intersectionRatio)[0];

            if (!visibleEntry) {
                return;
            }

            navLinks.forEach((link) => {
                link.classList.toggle(
                    'is-active',
                    link.getAttribute('href') === `#${visibleEntry.target.id}`,
                );
            });
        },
        {
            threshold: [0.15, 0.35, 0.6],
            rootMargin: '-15% 0px -60%',
        },
    );

    navSections.forEach((section) => navigationObserver.observe(section));
}

const experienceTabs = [...document.querySelectorAll('.experience-tab')];
const experiencePhone = document.querySelector('#experience-phone');
const experienceImage = document.querySelector('#experience-image');
const experienceLabel = document.querySelector('#experience-label');
const experienceTitle = document.querySelector('#experience-title');
const experienceDescription = document.querySelector('#experience-description');
const experienceCurrent = document.querySelector('#experience-current');
const experienceCounter = document.querySelector('.experience-counter');
const experienceDots = [...document.querySelectorAll('#experience-dots span')];
const nextExperienceButton = document.querySelector('#experience-next');
const previousExperienceButton = document.querySelector('#experience-previous');
let activeExperienceIndex = 0;
let pointerStartX = null;

experienceTabs.forEach((tab) => {
    const screen = tab.dataset.screen;

    if (screen) {
        const image = new Image();
        image.src = screen;
    }
});

const formatCounter = (index) => String(index + 1).padStart(2, '0');

const updateExperience = (requestedIndex, shouldFocus = false) => {
    if (
        experienceTabs.length === 0
        || !experienceImage
        || !experiencePhone
        || !experienceLabel
        || !experienceTitle
        || !experienceDescription
    ) {
        return;
    }

    const normalizedIndex = (
        requestedIndex + experienceTabs.length
    ) % experienceTabs.length;
    const selectedTab = experienceTabs[normalizedIndex];

    activeExperienceIndex = normalizedIndex;
    experiencePhone.classList.add('is-switching');

    experienceTabs.forEach((tab, index) => {
        const isActive = index === normalizedIndex;
        tab.classList.toggle('is-active', isActive);
        tab.setAttribute('aria-selected', String(isActive));
        tab.tabIndex = isActive ? 0 : -1;
    });

    experienceDots.forEach((dot, index) => {
        dot.classList.toggle('is-active', index === normalizedIndex);
    });

    if (experienceCurrent) {
        experienceCurrent.textContent = formatCounter(normalizedIndex);
    }

    if (experienceCounter) {
        experienceCounter.style.setProperty(
            '--experience-progress',
            String((normalizedIndex + 1) / experienceTabs.length),
        );
    }

    const completeImageSwitch = () => {
        experiencePhone.classList.remove('is-switching');
    };

    experienceImage.addEventListener('load', completeImageSwitch, { once: true });
    window.setTimeout(completeImageSwitch, 520);

    experienceImage.src = selectedTab.dataset.screen ?? experienceImage.src;
    experienceImage.alt = selectedTab.dataset.alt ?? '';
    experienceLabel.textContent = selectedTab.dataset.label ?? '';
    experienceTitle.textContent = selectedTab.dataset.title ?? '';
    experienceDescription.textContent = selectedTab.dataset.description ?? '';

    if (shouldFocus) {
        selectedTab.focus({ preventScroll: true });
    }

    if (window.innerWidth <= 680) {
        selectedTab.scrollIntoView({
            behavior: prefersReducedMotion ? 'auto' : 'smooth',
            inline: 'center',
            block: 'nearest',
        });
    }
};

experienceTabs.forEach((tab, index) => {
    tab.addEventListener('click', () => updateExperience(index));

    tab.addEventListener('keydown', (event) => {
        const keyActions = {
            ArrowLeft: activeExperienceIndex + 1,
            ArrowRight: activeExperienceIndex - 1,
            Home: 0,
            End: experienceTabs.length - 1,
        };

        if (!(event.key in keyActions)) {
            return;
        }

        event.preventDefault();
        updateExperience(keyActions[event.key], true);
    });
});

nextExperienceButton?.addEventListener('click', () => {
    updateExperience(activeExperienceIndex + 1);
});

previousExperienceButton?.addEventListener('click', () => {
    updateExperience(activeExperienceIndex - 1);
});

experiencePhone?.addEventListener('pointerdown', (event) => {
    pointerStartX = event.clientX;
});

experiencePhone?.addEventListener('pointerup', (event) => {
    if (pointerStartX === null) {
        return;
    }

    const distance = event.clientX - pointerStartX;
    pointerStartX = null;

    if (Math.abs(distance) < 38) {
        return;
    }

    updateExperience(
        distance < 0
            ? activeExperienceIndex + 1
            : activeExperienceIndex - 1,
    );
});

experiencePhone?.addEventListener('pointercancel', () => {
    pointerStartX = null;
});

const heroVisual = document.querySelector('#hero-visual');

if (heroVisual && !prefersReducedMotion && window.matchMedia('(pointer: fine)').matches) {
    heroVisual.addEventListener('pointermove', (event) => {
        const bounds = heroVisual.getBoundingClientRect();
        const x = (event.clientX - bounds.left) / bounds.width - 0.5;
        const y = (event.clientY - bounds.top) / bounds.height - 0.5;

        heroVisual.style.setProperty('--phone-rotate-x', `${x * 5}deg`);
        heroVisual.style.setProperty('--phone-rotate-y', `${y * -4}deg`);
        heroVisual.style.setProperty('--phone-shift-x', `${x * 7}px`);
        heroVisual.style.setProperty('--phone-shift-y', `${y * 5}px`);
    });

    heroVisual.addEventListener('pointerleave', () => {
        heroVisual.style.removeProperty('--phone-rotate-x');
        heroVisual.style.removeProperty('--phone-rotate-y');
        heroVisual.style.removeProperty('--phone-shift-x');
        heroVisual.style.removeProperty('--phone-shift-y');
    });
}

const downloadShowcase = document.querySelector('#download-showcase');

if (
    downloadShowcase
    && !prefersReducedMotion
    && window.matchMedia('(pointer: fine)').matches
) {
    downloadShowcase.addEventListener('pointermove', (event) => {
        const bounds = downloadShowcase.getBoundingClientRect();
        const x = (event.clientX - bounds.left) / bounds.width - 0.5;
        const y = (event.clientY - bounds.top) / bounds.height - 0.5;

        downloadShowcase.style.setProperty('--download-shift-x', `${x * 10}px`);
        downloadShowcase.style.setProperty('--download-shift-y', `${y * 8}px`);
        downloadShowcase.style.setProperty('--download-float-x', `${x * -7}px`);
        downloadShowcase.style.setProperty('--download-float-y', `${y * -6}px`);
    });

    downloadShowcase.addEventListener('pointerleave', () => {
        downloadShowcase.style.removeProperty('--download-shift-x');
        downloadShowcase.style.removeProperty('--download-shift-y');
        downloadShowcase.style.removeProperty('--download-float-x');
        downloadShowcase.style.removeProperty('--download-float-y');
    });
}

const currentYear = document.querySelector('#current-year');

if (currentYear) {
    currentYear.textContent = String(new Date().getFullYear());
}
