/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SEARCH JS
|--------------------------------------------------------------------------
| AJAX live product search
| Search suggestions
| Search results
| Debounce
| Keyboard navigation
| Clear search
| Loading state
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {

    "use strict";


    /* ==============================================================
       CONFIGURATION
    ============================================================== */

    const SEARCH_DELAY = 300;

    const MIN_SEARCH_LENGTH = 1;

    const SEARCH_ENDPOINT =
        "ajax/search_product.php";


    /* ==============================================================
       FIND SEARCH ELEMENTS
    ============================================================== */

    const searchForms = document.querySelectorAll(
        ".search-form, #searchForm, [data-search-form]"
    );


    const searchInputs = document.querySelectorAll(
        ".search-input, #searchInput, [data-search-input]"
    );


    if (!searchInputs.length) {
        return;
    }


    /* ==============================================================
       SEARCH INPUT INITIALIZATION
    ============================================================== */

    searchInputs.forEach(function (input) {

        setupSearchInput(input);

    });


    /* ==============================================================
       SETUP SEARCH INPUT
    ============================================================== */

    function setupSearchInput(input) {

        const wrapper =
            input.closest(
                ".search-wrapper, .search-box, .search-container"
            ) || input.parentElement;


        if (!wrapper) return;


        let resultsContainer =
            wrapper.querySelector(
                ".ajax-search-results"
            );


        /*
        |--------------------------------------------------------------------------
        | CREATE RESULT CONTAINER IF IT DOES NOT EXIST
        |--------------------------------------------------------------------------
        */

        if (!resultsContainer) {

            resultsContainer =
                document.createElement("div");

            resultsContainer.className =
                "ajax-search-results";


            wrapper.appendChild(
                resultsContainer
            );

        }


        let clearButton =
            wrapper.querySelector(
                ".search-clear, [data-search-clear]"
            );


        let searchTimeout = null;

        let currentController = null;

        let selectedIndex = -1;


        /* ==============================================================
           INPUT EVENT
        ============================================================== */

        input.addEventListener(
            "input",
            function () {

                const keyword =
                    input.value.trim();


                selectedIndex = -1;


                updateClearButton(
                    clearButton,
                    keyword
                );


                /*
                |--------------------------------------------------------------------------
                | EMPTY SEARCH
                |--------------------------------------------------------------------------
                */

                if (
                    keyword.length <
                    MIN_SEARCH_LENGTH
                ) {

                    hideResults(
                        resultsContainer
                    );

                    return;

                }


                /*
                |--------------------------------------------------------------------------
                | DEBOUNCE
                |--------------------------------------------------------------------------
                */

                clearTimeout(
                    searchTimeout
                );


                searchTimeout =
                    setTimeout(
                        function () {

                            performSearch(
                                keyword,
                                input,
                                resultsContainer
                            );

                        },
                        SEARCH_DELAY
                    );

            }
        );


        /* ==============================================================
           KEYBOARD NAVIGATION
        ============================================================== */

        input.addEventListener(
            "keydown",
            function (event) {

                const items =
                    resultsContainer.querySelectorAll(
                        ".ajax-search-result"
                    );


                if (!items.length) {

                    /*
                    |--------------------------------------------------------------------------
                    | ENTER WITH NO SUGGESTIONS
                    |--------------------------------------------------------------------------
                    */

                    if (
                        event.key === "Enter" &&
                        input.value.trim()
                    ) {

                        submitSearch(
                            input
                        );

                    }

                    return;

                }


                /*
                |--------------------------------------------------------------------------
                | ARROW DOWN
                |--------------------------------------------------------------------------
                */

                if (event.key === "ArrowDown") {

                    event.preventDefault();


                    selectedIndex++;

                    if (
                        selectedIndex >=
                        items.length
                    ) {

                        selectedIndex = 0;

                    }


                    updateSelectedItem(
                        items,
                        selectedIndex
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | ARROW UP
                |--------------------------------------------------------------------------
                */

                else if (
                    event.key === "ArrowUp"
                ) {

                    event.preventDefault();


                    selectedIndex--;


                    if (
                        selectedIndex < 0
                    ) {

                        selectedIndex =
                            items.length - 1;

                    }


                    updateSelectedItem(
                        items,
                        selectedIndex
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | ENTER
                |--------------------------------------------------------------------------
                */

                else if (
                    event.key === "Enter"
                ) {

                    event.preventDefault();


                    if (
                        selectedIndex >= 0 &&
                        items[selectedIndex]
                    ) {

                        items[
                            selectedIndex
                        ].click();

                    } else {

                        submitSearch(
                            input
                        );

                    }

                }


                /*
                |--------------------------------------------------------------------------
                | ESCAPE
                |--------------------------------------------------------------------------
                */

                else if (
                    event.key === "Escape"
                ) {

                    hideResults(
                        resultsContainer
                    );

                    input.blur();

                }

            }
        );


        /* ==============================================================
           CLEAR BUTTON
        ============================================================== */

        if (clearButton) {

            clearButton.addEventListener(
                "click",
                function (event) {

                    event.preventDefault();


                    input.value = "";


                    updateClearButton(
                        clearButton,
                        ""
                    );


                    hideResults(
                        resultsContainer
                    );


                    input.focus();

                }
            );

        }


        /* ==============================================================
           FOCUS
        ============================================================== */

        input.addEventListener(
            "focus",
            function () {

                const keyword =
                    input.value.trim();


                if (
                    keyword.length >=
                    MIN_SEARCH_LENGTH
                ) {

                    performSearch(
                        keyword,
                        input,
                        resultsContainer
                    );

                }

            }
        );


        /* ==============================================================
           CLICK OUTSIDE
        ============================================================== */

        document.addEventListener(
            "click",
            function (event) {

                if (
                    !wrapper.contains(
                        event.target
                    )
                ) {

                    hideResults(
                        resultsContainer
                    );

                }

            }
        );

    }


    /* ==============================================================
       PERFORM SEARCH
    ============================================================== */

    async function performSearch(
        keyword,
        input,
        resultsContainer
    ) {

        if (
            keyword.length <
            MIN_SEARCH_LENGTH
        ) {

            hideResults(
                resultsContainer
            );

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | CANCEL PREVIOUS REQUEST
        |--------------------------------------------------------------------------
        */

        if (input._searchController) {

            input._searchController.abort();

        }


        const controller =
            new AbortController();


        input._searchController =
            controller;


        /*
        |--------------------------------------------------------------------------
        | SHOW LOADING
        |--------------------------------------------------------------------------
        */

        showLoading(
            resultsContainer
        );


        try {

            const url =
                buildSearchURL(
                    keyword
                );


            const response =
                await fetch(
                    url,
                    {
                        method: "GET",

                        headers: {
                            "X-Requested-With":
                                "XMLHttpRequest",

                            "Accept":
                                "application/json"
                        },

                        signal:
                            controller.signal
                    }
                );


            if (!response.ok) {

                throw new Error(
                    `HTTP ${response.status}`
                );

            }


            const data =
                await response.json();


            /*
            |--------------------------------------------------------------------------
            | VERIFY RESPONSE
            |--------------------------------------------------------------------------
            */

            if (
                !data ||
                typeof data !== "object"
            ) {

                throw new Error(
                    "Invalid server response."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | RENDER RESULTS
            |--------------------------------------------------------------------------
            */

            renderResults(
                data,
                input,
                resultsContainer
            );


        } catch (error) {

            /*
            |--------------------------------------------------------------------------
            | ABORTED REQUEST
            |--------------------------------------------------------------------------
            */

            if (
                error.name ===
                "AbortError"
            ) {

                return;

            }


            console.error(
                "HochipoHub search error:",
                error
            );


            showError(
                resultsContainer
            );

        }

    }


    /* ==============================================================
       BUILD SEARCH URL
    ============================================================== */

    function buildSearchURL(keyword) {

        const params =
            new URLSearchParams();


        params.set(
            "q",
            keyword
        );


        params.set(
            "limit",
            "8"
        );


        return (
            SEARCH_ENDPOINT +
            "?" +
            params.toString()
        );

    }


    /* ==============================================================
       RENDER RESULTS
    ============================================================== */

    function renderResults(
        data,
        input,
        resultsContainer
    ) {

        resultsContainer.innerHTML = "";


        /*
        |--------------------------------------------------------------------------
        | NORMALIZE RESULT DATA
        |--------------------------------------------------------------------------
        */

        let products = [];


        if (
            Array.isArray(
                data.products
            )
        ) {

            products =
                data.products;

        }


        /*
        |--------------------------------------------------------------------------
        | NO RESULTS
        |--------------------------------------------------------------------------
        */

        if (!products.length) {

            showNoResults(
                resultsContainer,
                input.value.trim()
            );

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | CREATE RESULT ITEMS
        |--------------------------------------------------------------------------
        */

        products.forEach(
            function (product) {

                const item =
                    createResultItem(
                        product
                    );


                resultsContainer.appendChild(
                    item
                );

            }
        );


        resultsContainer.classList.add(
            "active"
        );

    }


    /* ==============================================================
       CREATE RESULT ITEM
    ============================================================== */

    function createResultItem(product) {

        const item =
            document.createElement("a");


        item.className =
            "ajax-search-result";


        /*
        |--------------------------------------------------------------------------
        | PRODUCT ID
        |--------------------------------------------------------------------------
        */

        const productID =
            product.product_id ||
            product.id ||
            "";


        /*
        |--------------------------------------------------------------------------
        | PRODUCT NAME
        |--------------------------------------------------------------------------
        */

        const productName =
            product.product_name ||
            product.name ||
            "Unnamed Product";


        /*
        |--------------------------------------------------------------------------
        | PRICE
        |--------------------------------------------------------------------------
        */

        const price =
            parseFloat(
                product.price || 0
            );


        /*
        |--------------------------------------------------------------------------
        | IMAGE
        |--------------------------------------------------------------------------
        */

        let image =
            product.image || "";


        if (!image) {

            image =
                "image/products/default-product.jpg";

        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCT URL
        |--------------------------------------------------------------------------
        */

        const productURL =
            product.url ||
            (
                "product_details.php?id=" +
                encodeURIComponent(
                    productID
                )
            );


        item.href =
            productURL;


        /*
        |--------------------------------------------------------------------------
        | IMAGE CONTAINER
        |--------------------------------------------------------------------------
        */

        const imageWrapper =
            document.createElement("div");


        imageWrapper.className =
            "ajax-search-result-image";


        const imageElement =
            document.createElement("img");


        imageElement.src =
            image;


        imageElement.alt =
            productName;


        imageElement.loading =
            "lazy";


        imageElement.onerror =
            function () {

                this.src =
                    "image/products/default-product.jpg";

            };


        imageWrapper.appendChild(
            imageElement
        );


        /*
        |--------------------------------------------------------------------------
        | INFO
        |--------------------------------------------------------------------------
        */

        const info =
            document.createElement("div");


        info.className =
            "ajax-search-result-info";


        const name =
            document.createElement("strong");


        name.textContent =
            productName;


        info.appendChild(
            name
        );


        /*
        |--------------------------------------------------------------------------
        | CATEGORY
        |--------------------------------------------------------------------------
        */

        if (
            product.category_name
        ) {

            const category =
                document.createElement("span");


            category.textContent =
                product.category_name;


            info.appendChild(
                category
            );

        }


        /*
        |--------------------------------------------------------------------------
        | PRICE
        |--------------------------------------------------------------------------
        */

        const priceElement =
            document.createElement("span");


        priceElement.className =
            "ajax-search-result-price";


        priceElement.textContent =
            formatCurrency(
                price
            );


        item.appendChild(
            imageWrapper
        );


        item.appendChild(
            info
        );


        item.appendChild(
            priceElement
        );


        return item;

    }


    /* ==============================================================
       LOADING STATE
    ============================================================== */

    function showLoading(
        resultsContainer
    ) {

        resultsContainer.innerHTML = `
            <div class="ajax-search-loading">
                <span class="search-loading-spinner"></span>
                <span>Searching HochipoHub...</span>
            </div>
        `;


        resultsContainer.classList.add(
            "active"
        );

    }


    /* ==============================================================
       NO RESULTS
    ============================================================== */

    function showNoResults(
        resultsContainer,
        keyword
    ) {

        resultsContainer.innerHTML = "";


        const box =
            document.createElement("div");


        box.className =
            "ajax-search-no-results";


        box.innerHTML = `
            <i class="fa-solid fa-magnifying-glass"></i>
            <strong>No products found</strong>
            <span>Try searching for something else.</span>
        `;


        resultsContainer.appendChild(
            box
        );


        resultsContainer.classList.add(
            "active"
        );

    }


    /* ==============================================================
       ERROR STATE
    ============================================================== */

    function showError(
        resultsContainer
    ) {

        resultsContainer.innerHTML = `
            <div class="ajax-search-no-results">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <strong>Search unavailable</strong>
                <span>Please try again.</span>
            </div>
        `;


        resultsContainer.classList.add(
            "active"
        );

    }


    /* ==============================================================
       HIDE RESULTS
    ============================================================== */

    function hideResults(
        resultsContainer
    ) {

        if (!resultsContainer) return;


        resultsContainer.classList.remove(
            "active"
        );


        /*
        |--------------------------------------------------------------------------
        | RESET KEYBOARD INDEX
        |--------------------------------------------------------------------------
        */

        resultsContainer
            .querySelectorAll(
                ".ajax-search-result.selected"
            )
            .forEach(
                function (item) {

                    item.classList.remove(
                        "selected"
                    );

                }
            );

    }


    /* ==============================================================
       UPDATE SELECTED ITEM
    ============================================================== */

    function updateSelectedItem(
        items,
        selectedIndex
    ) {

        items.forEach(
            function (item, index) {

                item.classList.toggle(
                    "selected",
                    index === selectedIndex
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | SCROLL SELECTED ITEM INTO VIEW
        |--------------------------------------------------------------------------
        */

        if (
            items[selectedIndex]
        ) {

            items[
                selectedIndex
            ].scrollIntoView({
                block: "nearest"
            });

        }

    }


    /* ==============================================================
       SUBMIT SEARCH
    ============================================================== */

    function submitSearch(input) {

        const keyword =
            input.value.trim();


        if (
            keyword.length <
            MIN_SEARCH_LENGTH
        ) {

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | FIND FORM
        |--------------------------------------------------------------------------
        */

        const form =
            input.closest("form");


        if (form) {

            form.submit();

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | FALLBACK SEARCH PAGE
        |--------------------------------------------------------------------------
        */

        window.location.href =
            "search.php?q=" +
            encodeURIComponent(
                keyword
            );

    }


    /* ==============================================================
       CLEAR BUTTON STATE
    ============================================================== */

    function updateClearButton(
        button,
        keyword
    ) {

        if (!button) return;


        if (keyword.length > 0) {

            button.classList.add(
                "active"
            );


            button.style.display =
                "flex";

        } else {

            button.classList.remove(
                "active"
            );


            button.style.display =
                "none";

        }

    }


    /* ==============================================================
       FORMAT CURRENCY
    ============================================================== */

    function formatCurrency(
        amount
    ) {

        const number =
            Number(amount);


        if (
            Number.isNaN(number)
        ) {

            return "RM 0.00";

        }


        return (
            "RM " +
            number.toLocaleString(
                "en-MY",
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            )
        );

    }


    /* ==============================================================
       SEARCH FORM SUBMISSION
    ============================================================== */

    searchForms.forEach(
        function (form) {

            form.addEventListener(
                "submit",
                function (event) {

                    const input =
                        form.querySelector(
                            ".search-input, #searchInput, [data-search-input]"
                        );


                    if (!input) return;


                    const keyword =
                        input.value.trim();


                    if (
                        keyword.length <
                        MIN_SEARCH_LENGTH
                    ) {

                        event.preventDefault();


                        input.focus();

                    }

                }
            );

        }
    );


    /* ==============================================================
       EXPOSE SEARCH API
    ============================================================== */

    window.HochipoHubSearch = {

        search: function (
            keyword,
            input,
            resultsContainer
        ) {

            if (
                input &&
                resultsContainer
            ) {

                performSearch(
                    keyword,
                    input,
                    resultsContainer
                );

            }

        },

        clear: function (
            resultsContainer
        ) {

            hideResults(
                resultsContainer
            );

        }

    };

});