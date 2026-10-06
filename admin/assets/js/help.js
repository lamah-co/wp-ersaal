document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('ersaal-help-search');
    const sections = Array.from(document.querySelectorAll('.ersaal-help-section'));
    const links = Array.from(document.querySelectorAll('.ersaal-help-nav a'));
    const noResults = document.getElementById('ersaal-help-no-results');
    const searchStatus = document.getElementById('ersaal-help-search-status');
    const languageSelect = document.getElementById('ersaal-help-language');
    const languageForm = document.getElementById('ersaal-help-language-form');

    if (languageSelect && languageForm) {
        languageSelect.addEventListener('change', function() {
            languageForm.submit();
        });
    }

    function updateActiveLink(id) {
        links.forEach(function(link) {
            const active = link.getAttribute('href') === `#${id}`;
            link.classList.toggle('active', active);
            if (active) {
                link.setAttribute('aria-current', 'location');
            } else {
                link.removeAttribute('aria-current');
            }
        });
    }

    function showSection(id, shouldScroll) {
        const section = document.getElementById(id);
        if (!section || !section.classList.contains('ersaal-help-section')) {
            return;
        }

        section.hidden = false;
        section.open = true;
        updateActiveLink(id);

        if (shouldScroll) {
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    links.forEach(function(link) {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const id = link.getAttribute('href').slice(1);
            showSection(id, true);
            window.history.replaceState(null, '', `#${id}`);
        });
    });

    sections.forEach(function(section) {
        section.addEventListener('toggle', function() {
            if (section.open && !section.hidden) {
                updateActiveLink(section.id);
            }
        });
    });

    function updateResults(query) {
        let visibleCount = 0;

        sections.forEach(function(section) {
            const matches = !query || section.textContent.toLocaleLowerCase().includes(query);
            section.hidden = !matches;
            if (matches) {
                visibleCount++;
                if (query) {
                    section.open = true;
                }
            }
        });

        links.forEach(function(link) {
            const section = document.getElementById(link.getAttribute('href').slice(1));
            link.hidden = !section || section.hidden;
        });

        noResults.hidden = visibleCount !== 0;
        searchStatus.textContent = query ? ersaalHelp.i18n.results.replace('%d', String(visibleCount)) : '';

        const firstVisible = sections.find(function(section) { return !section.hidden; });
        updateActiveLink(firstVisible ? firstVisible.id : '');
    }

    if (searchInput) {
        searchInput.addEventListener('input', function(event) {
            updateResults(event.target.value.trim().toLocaleLowerCase());
        });
    }

    updateResults('');

    if (window.location.hash) {
        showSection(window.location.hash.slice(1), false);
    }
});
