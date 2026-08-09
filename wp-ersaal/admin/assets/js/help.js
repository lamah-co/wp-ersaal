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

    document.querySelectorAll('.ersaal-accordion-btn').forEach(function(button, index) {
        const panel = button.nextElementSibling;
        if (!panel || !panel.classList.contains('ersaal-accordion-panel')) {
            return;
        }

        const buttonId = `ersaal-accordion-button-${index}`;
        const panelId = `ersaal-accordion-panel-${index}`;
        button.type = 'button';
        button.id = buttonId;
        button.setAttribute('aria-controls', panelId);
        button.setAttribute('aria-expanded', 'false');
        panel.id = panelId;
        panel.hidden = true;
        panel.setAttribute('role', 'region');
        panel.setAttribute('aria-labelledby', buttonId);

        const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        icon.setAttribute('class', 'ersaal-icon');
        icon.setAttribute('viewBox', '0 0 24 24');
        icon.setAttribute('fill', 'none');
        icon.setAttribute('stroke', 'currentColor');
        icon.setAttribute('stroke-width', '1.8');
        icon.setAttribute('stroke-linecap', 'round');
        icon.setAttribute('stroke-linejoin', 'round');
        icon.setAttribute('aria-hidden', 'true');
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', 'm9 18 6-6-6-6');
        icon.appendChild(path);
        button.appendChild(icon);

        button.addEventListener('click', function() {
            const expanded = button.getAttribute('aria-expanded') === 'true';
            button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            panel.hidden = expanded;
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', function(event) {
            const query = event.target.value.trim().toLocaleLowerCase();
            let visibleCount = 0;

            sections.forEach(function(section) {
                const matches = !query || section.textContent.toLocaleLowerCase().includes(query);
                section.hidden = !matches;
                if (matches) {
                    visibleCount++;
                }
            });

            noResults.hidden = visibleCount !== 0;
            searchStatus.textContent = ersaalHelp.i18n.results.replace('%d', String(visibleCount));
        });
    }
});
