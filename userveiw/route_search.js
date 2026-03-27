document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('routeSearchInput');
    const searchResults = document.getElementById('routeSearchResults');

    if (!searchInput || !searchResults) return;

    let debounceTimer;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();

        if (query.length < 2) {
            searchResults.innerHTML = '';
            searchResults.style.display = 'none';
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`search_routes_api.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        renderResults(data.routes);
                    } else {
                        searchResults.innerHTML = '<div class="search-item">No routes found</div>';
                        searchResults.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error fetching routes:', error);
                });
        }, 300);
    });

    function renderResults(routes) {
        if (routes.length === 0) {
            searchResults.innerHTML = '<div class="search-item">No routes found</div>';
            searchResults.style.display = 'block';
            return;
        }

        let html = '';
        routes.forEach(route => {
            const stagesText = route.stages.join(' → ');
            const fareText = route.min_fare ? `From Ksh ${parseInt(route.min_fare)}` : '';
            
            // Format labels for trip types
            let typesHtml = '';
            if (route.trip_types.includes('short')) {
                typesHtml += `<span class="type-tag type-short">Local</span>`;
            }
            if (route.trip_types.includes('long')) {
                typesHtml += `<span class="type-tag type-long">Long Dist.</span>`;
            }
            
            html += `
                <div class="search-item" onclick="selectRoute(${route.route_id}, '${route.route_name.replace(/'/g, "\\'")}')">
                    <div class="route-info">
                        <div class="route-header">
                            <span class="route-name">${route.route_name}</span>
                            ${typesHtml}
                        </div>
                        <div class="route-sacco">${route.sacco_name}</div>
                        <div class="route-stages">${stagesText}</div>
                    </div>
                    <div class="route-side">
                        <div class="route-fare">${fareText}</div>
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
            `;
        });

        searchResults.innerHTML = html;
        searchResults.style.display = 'block';
    }

    // Hide search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
});

function selectRoute(routeId, routeName) {
    // Redirect to book.php which will now handle both trip types when searching
    window.location.href = `book.php?search=${encodeURIComponent(routeName)}`;
}
