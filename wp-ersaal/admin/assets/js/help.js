document.addEventListener('DOMContentLoaded', function() {
    // Accordion
    const acc = document.getElementsByClassName("ersaal-accordion-btn");
    for (let i = 0; i < acc.length; i++) {
        acc[i].addEventListener("click", function() {
            this.classList.toggle("active");
            let panel = this.nextElementSibling;
            if (panel.style.maxHeight) {
                panel.style.maxHeight = null;
                panel.classList.remove('show');
                this.setAttribute('aria-expanded', 'false');
            } else {
                panel.style.maxHeight = panel.scrollHeight + "px";
                panel.classList.add('show');
                this.setAttribute('aria-expanded', 'true');
            }
        });
    }

    // Search
    const searchInput = document.getElementById('ersaal-help-search');
    const sections = document.querySelectorAll('.ersaal-help-section');
    const links = document.querySelectorAll('.ersaal-help-sidebar a');
    const noResults = document.getElementById('ersaal-help-no-results');

    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            let visibleCount = 0;

            sections.forEach(section => {
                const text = section.innerText.toLowerCase();
                if (text.includes(query)) {
                    section.style.display = 'block';
                    visibleCount++;
                } else {
                    section.style.display = 'none';
                }
            });
            
            if (visibleCount === 0 && query !== '') {
                noResults.style.display = 'block';
            } else {
                noResults.style.display = 'none';
            }
        });
    }

    // ScrollSpy for sidebar active state
    window.addEventListener('scroll', function() {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            if (pageYOffset >= sectionTop - 60) {
                current = section.getAttribute('id');
            }
        });

        links.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + current) {
                link.classList.add('active');
            }
        });
    });
});
