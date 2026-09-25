/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SEARCH.JS
|--------------------------------------------------------------------------
| File:
| js/search.js
|
| Handles:
| - AJAX live product search
| - Desktop navbar search
| - Mobile navbar search
| - Search suggestions
| - Debounce
| - Request cancellation
| - Loading state
| - Empty state
| - Error state
| - Keyboard navigation
| - Search form submit
| - Clear search
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {

    /*
    |--------------------------------------------------------------------------
    | CONFIGURATION
    |--------------------------------------------------------------------------
    */

    const baseUrl =
        typeof window.HOCHIPOHUB_BASE_URL === "string"
            ? window.HOCHIPOHUB_BASE_URL
            : "/";

    const ajaxSearchUrl =
        baseUrl +
        "ajax/search_product.php";

    const searchPageUrl =
        baseUrl +
        "search.php";

    const productPageUrl =
        baseUrl +
        "product_details.php";

    const minimumCharacters = 1;

    const debounceDelay = 350;

    const suggestionLimit = 8;


    /*
    |--------------------------------------------------------------------------
    | SEARCH FORMS
    |--------------------------------------------------------------------------
    |
    | Desktop:
    | .navbar-search
    |
    | Mobile:
    | .mobile-search
    |
    |--------------------------------------------------------------------------
    */

    const searchForms =
        document.querySelectorAll(
            ".navbar-search, .mobile-search, .search-form"
        );


    searchForms.forEach(function (form) {

        initializeSearchForm(form);

    });


    /*
    |--------------------------------------------------------------------------
    | INITIALIZE SEARCH FORM
    |--------------------------------------------------------------------------
    */

    function initializeSearchForm(form) {

        const input =
            form.querySelector(
                'input[name="q"], input[name="search"], input[type="search"]'
            );


        if (!input) {
            return;
        }


        let suggestionContainer =
            form.querySelector(
                ".search-suggestions"
            );


        /*
        |--------------------------------------------------------------------------
        | CREATE SUGGESTION CONTAINER IF MISSING
        |--------------------------------------------------------------------------
        */

        if (!suggestionContainer) {

            suggestionContainer =
                document.createElement("div");

            suggestionContainer.className =
                "search-suggestions";

            suggestionContainer.setAttribute(
                "role",
                "listbox"
            );

            suggestionContainer.setAttribute(
                "aria-label",
                "Product search suggestions"
            );

            form.appendChild(
                suggestionContainer
            );

        }


        /*
        |--------------------------------------------------------------------------
        | FORM STATE
        |--------------------------------------------------------------------------
        */

        let debounceTimer = null;

        let currentController = null;

        let currentKeyword = "";

        let activeSuggestionIndex = -1;


        /*
        |--------------------------------------------------------------------------
        | INPUT EVENT
        |--------------------------------------------------------------------------
        */

        input.addEventListener(
            "input",
            function () {

                const keyword =
                    input.value.trim();


                currentKeyword =
                    keyword;


                activeSuggestionIndex = -1;


                toggleClearButton(
                    input,
                    keyword
                );


                /*
                |--------------------------------------------------------------------------
                | CANCEL DEBOUNCE
                |--------------------------------------------------------------------------
                */

                if (debounceTimer) {

                    clearTimeout(
                        debounceTimer
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | CANCEL PREVIOUS AJAX REQUEST
                |--------------------------------------------------------------------------
                */

                if (currentController) {

                    currentController.abort();

                    currentController = null;

                }


                /*
                |--------------------------------------------------------------------------
                | EMPTY SEARCH
                |--------------------------------------------------------------------------
                */

                if (
                    keyword.length <
                    minimumCharacters
                ) {

                    hideSuggestions(
                        form
                    );

                    suggestionContainer.innerHTML =
                        "";

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | LOADING
                |--------------------------------------------------------------------------
                */

                showLoading(
                    suggestionContainer
                );


                showSuggestions(
                    suggestionContainer
                );


                /*
                |--------------------------------------------------------------------------
                | DEBOUNCE
                |--------------------------------------------------------------------------
                */

                debounceTimer =
                    setTimeout(
                        function () {

                            performAjaxSearch(
                                keyword,
                                form,
                                input,
                                suggestionContainer
                            );

                        },
                        debounceDelay
                    );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | INPUT FOCUS
        |--------------------------------------------------------------------------
        */

        input.addEventListener(
            "focus",
            function () {

                const keyword =
                    input.value.trim();


                if (
                    keyword !== ""
                    &&
                    suggestionContainer.innerHTML.trim() !== ""
                ) {

                    showSuggestions(
                        suggestionContainer
                    );

                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | KEYBOARD NAVIGATION
        |--------------------------------------------------------------------------
        */

        input.addEventListener(
            "keydown",
            function (event) {

                const suggestions =
                    suggestionContainer.querySelectorAll(
                        ".search-suggestion"
                    );


                /*
                |--------------------------------------------------------------------------
                | ESCAPE
                |--------------------------------------------------------------------------
                */

                if (
                    event.key === "Escape"
                ) {

                    hideSuggestions(
                        form
                    );

                    activeSuggestionIndex = -1;

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | ARROW DOWN
                |--------------------------------------------------------------------------
                */

                if (
                    event.key === "ArrowDown"
                    &&
                    suggestions.length > 0
                ) {

                    event.preventDefault();


                    activeSuggestionIndex++;


                    if (
                        activeSuggestionIndex >=
                        suggestions.length
                    ) {

                        activeSuggestionIndex = 0;

                    }


                    updateKeyboardSelection(
                        suggestions,
                        activeSuggestionIndex
                    );


                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | ARROW UP
                |--------------------------------------------------------------------------
                */

                if (
                    event.key === "ArrowUp"
                    &&
                    suggestions.length > 0
                ) {

                    event.preventDefault();


                    activeSuggestionIndex--;


                    if (
                        activeSuggestionIndex < 0
                    ) {

                        activeSuggestionIndex =
                            suggestions.length - 1;

                    }


                    updateKeyboardSelection(
                        suggestions,
                        activeSuggestionIndex
                    );


                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | ENTER ON SELECTED PRODUCT
                |--------------------------------------------------------------------------
                */

                if (
                    event.key === "Enter"
                    &&
                    activeSuggestionIndex >= 0
                    &&
                    suggestions[
                        activeSuggestionIndex
                    ]
                ) {

                    event.preventDefault();


                    const selectedSuggestion =
                        suggestions[
                            activeSuggestionIndex
                        ];


                    const productUrl =
                        selectedSuggestion.dataset.url;


                    if (productUrl) {

                        window.location.href =
                            productUrl;

                    }


                    return;
                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | NORMAL SEARCH FORM SUBMIT
        |--------------------------------------------------------------------------
        |
        | AJAX is only used for live suggestions.
        |
        | Pressing Search / Enter normally still opens:
        |
        | search.php?q=keyword
        |
        |--------------------------------------------------------------------------
        */

        form.addEventListener(
            "submit",
            function (event) {

                const keyword =
                    input.value.trim();


                if (keyword === "") {

                    event.preventDefault();

                    input.focus();

                    hideSuggestions(
                        form
                    );

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | PREVENT OLD "search" PARAMETER
                |--------------------------------------------------------------------------
                */

                input.name = "q";

                input.value =
                    keyword;


                /*
                |--------------------------------------------------------------------------
                | ENSURE CORRECT SEARCH PAGE
                |--------------------------------------------------------------------------
                */

                form.action =
                    searchPageUrl;

                form.method =
                    "GET";

            }
        );


        /*
        |--------------------------------------------------------------------------
        | SUGGESTION CLICK
        |--------------------------------------------------------------------------
        */

        suggestionContainer.addEventListener(
            "click",
            function (event) {

                const suggestion =
                    event.target.closest(
                        ".search-suggestion"
                    );


                if (!suggestion) {
                    return;
                }


                const productUrl =
                    suggestion.dataset.url;


                if (!productUrl) {
                    return;
                }


                window.location.href =
                    productUrl;

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | AJAX SEARCH
    |--------------------------------------------------------------------------
    */

    async function performAjaxSearch(
        keyword,
        form,
        input,
        suggestionContainer
    ) {

        /*
        |--------------------------------------------------------------------------
        | CREATE NEW REQUEST CONTROLLER
        |--------------------------------------------------------------------------
        */

        const controller =
            new AbortController();


        form._searchController =
            controller;


        const requestKeyword =
            keyword;


        try {

            const url =
                ajaxSearchUrl +
                "?keyword=" +
                encodeURIComponent(
                    requestKeyword
                ) +
                "&limit=" +
                encodeURIComponent(
                    suggestionLimit
                );


            const response =
                await fetch(
                    url,
                    {
                        method: "GET",

                        headers: {
                            "Accept":
                                "application/json",

                            "X-Requested-With":
                                "XMLHttpRequest"
                        },

                        cache:
                            "no-store",

                        signal:
                            controller.signal
                    }
                );


            /*
            |--------------------------------------------------------------------------
            | HTTP ERROR
            |--------------------------------------------------------------------------
            */

            if (!response.ok) {

                throw new Error(
                    "Search request failed."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | JSON RESPONSE
            |--------------------------------------------------------------------------
            */

            const data =
                await response.json();


            /*
            |--------------------------------------------------------------------------
            | IGNORE OLD RESULT
            |--------------------------------------------------------------------------
            */

            if (
                input.value.trim()
                    !== requestKeyword
            ) {

                return;

            }


            /*
            |--------------------------------------------------------------------------
            | API ERROR
            |--------------------------------------------------------------------------
            */

            if (
                !data
                ||
                data.success !== true
            ) {

                showError(
                    suggestionContainer,
                    data &&
                    data.message
                        ? data.message
                        : "Unable to search products."
                );

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | PRODUCTS
            |--------------------------------------------------------------------------
            */

            const products =
                Array.isArray(
                    data.products
                )
                    ? data.products
                    : [];


            /*
            |--------------------------------------------------------------------------
            | NO RESULTS
            |--------------------------------------------------------------------------
            */

            if (
                products.length === 0
            ) {

                showNoResults(
                    suggestionContainer,
                    requestKeyword
                );

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | DISPLAY RESULTS
            |--------------------------------------------------------------------------
            */

            renderSuggestions(
                products,
                suggestionContainer
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
                "HochipoHub AJAX Search Error:",
                error
            );


            showError(
                suggestionContainer,
                "Unable to load search suggestions."
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | RENDER SEARCH SUGGESTIONS
    |--------------------------------------------------------------------------
    */

    function renderSuggestions(
        products,
        suggestionContainer
    ) {

        suggestionContainer.innerHTML =
            "";


        products.forEach(
            function (product) {

                const productId =
                    parseInt(
                        product.product_id,
                        10
                    );


                if (
                    !productId
                    ||
                    productId <= 0
                ) {

                    return;

                }


                /*
                |--------------------------------------------------------------------------
                | PRODUCT URL
                |--------------------------------------------------------------------------
                */

                const productUrl =
                    productPageUrl +
                    "?id=" +
                    encodeURIComponent(
                        productId
                    );


                /*
                |--------------------------------------------------------------------------
                | PRODUCT IMAGE
                |--------------------------------------------------------------------------
                */

                let imageUrl =
                    String(
                        product.image_url
                        || ""
                    ).trim();


                if (
                    imageUrl === ""
                ) {

                    imageUrl =
                        baseUrl +
                        "image/logo.jpeg";

                }


                /*
                |--------------------------------------------------------------------------
                | PRODUCT DETAILS
                |--------------------------------------------------------------------------
                */

                const productName =
                    String(
                        product.product_name
                        || "Product"
                    );


                const businessName =
                    String(
                        product.business_name
                        || ""
                    );


                const categoryName =
                    String(
                        product.category_name
                        || ""
                    );


                let price =
                    String(
                        product.formatted_price
                        || ""
                    );


                /*
                |--------------------------------------------------------------------------
                | NORMALIZE PRICE
                |--------------------------------------------------------------------------
                */

                if (
                    price !== ""
                    &&
                    !price
                        .toUpperCase()
                        .startsWith("RM")
                ) {

                    price =
                        "RM " +
                        price;

                }


                /*
                |--------------------------------------------------------------------------
                | CREATE ITEM
                |--------------------------------------------------------------------------
                */

                const suggestion =
                    document.createElement(
                        "div"
                    );


                suggestion.className =
                    "search-suggestion";


                suggestion.setAttribute(
                    "role",
                    "option"
                );


                suggestion.setAttribute(
                    "tabindex",
                    "-1"
                );


                suggestion.dataset.url =
                    productUrl;


                /*
                |--------------------------------------------------------------------------
                | IMAGE
                |--------------------------------------------------------------------------
                */

                const image =
                    document.createElement(
                        "img"
                    );


                image.className =
                    "search-suggestion-image";


                image.src =
                    imageUrl;


                image.alt =
                    productName;


                image.loading =
                    "lazy";


                image.addEventListener(
                    "error",
                    function () {

                        this.onerror =
                            null;

                        this.src =
                            baseUrl +
                            "image/logo.jpeg";

                    }
                );


                /*
                |--------------------------------------------------------------------------
                | INFO
                |--------------------------------------------------------------------------
                */

                const info =
                    document.createElement(
                        "div"
                    );


                info.className =
                    "search-suggestion-info";


                const name =
                    document.createElement(
                        "span"
                    );


                name.className =
                    "search-suggestion-name";


                name.textContent =
                    productName;


                const meta =
                    document.createElement(
                        "span"
                    );


                meta.className =
                    "search-suggestion-meta";


                const metaParts = [];


                if (
                    businessName !== ""
                ) {

                    metaParts.push(
                        businessName
                    );

                }


                if (
                    categoryName !== ""
                ) {

                    metaParts.push(
                        categoryName
                    );

                }


                meta.textContent =
                    metaParts.join(
                        " • "
                    );


                info.appendChild(
                    name
                );


                if (
                    metaParts.length > 0
                ) {

                    info.appendChild(
                        meta
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | PRICE
                |--------------------------------------------------------------------------
                */

                const priceElement =
                    document.createElement(
                        "span"
                    );


                priceElement.className =
                    "search-suggestion-price";


                priceElement.textContent =
                    price;


                /*
                |--------------------------------------------------------------------------
                | APPEND
                |--------------------------------------------------------------------------
                */

                suggestion.appendChild(
                    image
                );


                suggestion.appendChild(
                    info
                );


                if (
                    price !== ""
                ) {

                    suggestion.appendChild(
                        priceElement
                    );

                }


                suggestionContainer.appendChild(
                    suggestion
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | FALLBACK IF ALL PRODUCTS INVALID
        |--------------------------------------------------------------------------
        */

        if (
            suggestionContainer.children.length
            === 0
        ) {

            showNoResults(
                suggestionContainer,
                ""
            );

            return;

        }


        showSuggestions(
            suggestionContainer
        );

    }


    /*
    |--------------------------------------------------------------------------
    | LOADING STATE
    |--------------------------------------------------------------------------
    */

    function showLoading(
        suggestionContainer
    ) {

        suggestionContainer.innerHTML =
            "";


        const state =
            document.createElement(
                "div"
            );


        state.className =
            "search-suggestion-state";


        state.textContent =
            "Searching products...";


        suggestionContainer.appendChild(
            state
        );

    }


    /*
    |--------------------------------------------------------------------------
    | NO RESULT STATE
    |--------------------------------------------------------------------------
    */

    function showNoResults(
        suggestionContainer,
        keyword
    ) {

        suggestionContainer.innerHTML =
            "";


        const state =
            document.createElement(
                "div"
            );


        state.className =
            "search-suggestion-state";


        if (
            keyword &&
            keyword.trim() !== ""
        ) {

            state.textContent =
                'No products found for "' +
                keyword +
                '".';

        } else {

            state.textContent =
                "No products found.";

        }


        suggestionContainer.appendChild(
            state
        );


        showSuggestions(
            suggestionContainer
        );

    }


    /*
    |--------------------------------------------------------------------------
    | ERROR STATE
    |--------------------------------------------------------------------------
    */

    function showError(
        suggestionContainer,
        message
    ) {

        suggestionContainer.innerHTML =
            "";


        const state =
            document.createElement(
                "div"
            );


        state.className =
            "search-suggestion-state";


        state.textContent =
            message ||
            "Unable to search products.";


        suggestionContainer.appendChild(
            state
        );


        showSuggestions(
            suggestionContainer
        );

    }


    /*
    |--------------------------------------------------------------------------
    | SHOW SUGGESTIONS
    |--------------------------------------------------------------------------
    */

    function showSuggestions(
        suggestionContainer
    ) {

        if (!suggestionContainer) {
            return;
        }


        suggestionContainer.classList.add(
            "active"
        );

    }


    /*
    |--------------------------------------------------------------------------
    | HIDE SUGGESTIONS
    |--------------------------------------------------------------------------
    */

    function hideSuggestions(
        form
    ) {

        if (!form) {
            return;
        }


        const suggestionContainer =
            form.querySelector(
                ".search-suggestions"
            );


        if (!suggestionContainer) {
            return;
        }


        suggestionContainer.classList.remove(
            "active"
        );


        suggestionContainer
            .querySelectorAll(
                ".search-suggestion.keyboard-active"
            )
            .forEach(
                function (item) {

                    item.classList.remove(
                        "keyboard-active"
                    );

                }
            );

    }


    /*
    |--------------------------------------------------------------------------
    | KEYBOARD SELECTION
    |--------------------------------------------------------------------------
    */

    function updateKeyboardSelection(
        suggestions,
        activeIndex
    ) {

        suggestions.forEach(
            function (
                suggestion,
                index
            ) {

                if (
                    index ===
                    activeIndex
                ) {

                    suggestion.classList.add(
                        "keyboard-active"
                    );


                    suggestion.scrollIntoView({
                        block:
                            "nearest"
                    });

                } else {

                    suggestion.classList.remove(
                        "keyboard-active"
                    );

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CLEAR BUTTON
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "click",
        function (event) {

            const clearButton =
                event.target.closest(
                    ".search-clear"
                );


            if (!clearButton) {
                return;
            }


            const form =
                clearButton.closest(
                    "form"
                );


            if (!form) {
                return;
            }


            const input =
                form.querySelector(
                    'input[name="q"], input[name="search"], input[type="search"]'
                );


            if (!input) {
                return;
            }


            input.value =
                "";


            input.focus();


            toggleClearButton(
                input,
                ""
            );


            hideSuggestions(
                form
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | CLICK OUTSIDE SEARCH
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "click",
        function (event) {

            const searchArea =
                event.target.closest(
                    ".navbar-search, .mobile-search, .search-form, .search-wrapper, .search-container, .search-box"
                );


            if (searchArea) {
                return;
            }


            document
                .querySelectorAll(
                    ".search-suggestions.active"
                )
                .forEach(
                    function (
                        suggestionContainer
                    ) {

                        suggestionContainer
                            .classList
                            .remove(
                                "active"
                            );

                    }
                );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | GLOBAL HELPER
    |--------------------------------------------------------------------------
    */

    window.submitSearch =
        function (keyword) {

            keyword =
                String(
                    keyword || ""
                ).trim();


            if (
                keyword === ""
            ) {

                return;

            }


            window.location.href =
                searchPageUrl +
                "?q=" +
                encodeURIComponent(
                    keyword
                );

        };


    /*
    |--------------------------------------------------------------------------
    | SEARCH BUTTON HELPER
    |--------------------------------------------------------------------------
    */

    window.performSearch =
        function (inputSelector) {

            const input =
                document.querySelector(
                    inputSelector
                );


            if (!input) {
                return;
            }


            const keyword =
                input.value.trim();


            if (
                keyword === ""
            ) {

                input.focus();

                return;
            }


            window.submitSearch(
                keyword
            );

        };

});


/*
|--------------------------------------------------------------------------
| TOGGLE CLEAR BUTTON
|--------------------------------------------------------------------------
*/

function toggleClearButton(
    input,
    keyword
) {

    if (!input) {
        return;
    }


    const form =
        input.closest(
            "form"
        );


    if (!form) {
        return;
    }


    const clearButton =
        form.querySelector(
            ".search-clear"
        );


    if (!clearButton) {
        return;
    }


    if (
        String(
            keyword || ""
        ).trim() !== ""
    ) {

        clearButton.classList.add(
            "active"
        );


        clearButton.style.display =
            "flex";

    } else {

        clearButton.classList.remove(
            "active"
        );


        clearButton.style.display =
            "none";

    }

}