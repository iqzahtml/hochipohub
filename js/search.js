/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SEARCH.JS
|--------------------------------------------------------------------------
| Handles:
| - Live product search
| - Search suggestions
| - Search form
| - Clear search
| - Keyboard navigation
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {

    const searchForms = document.querySelectorAll(".search-form");

    searchForms.forEach(function (form) {

        const input = form.querySelector(
            'input[name="search"], input[type="search"]'
        );

        if (!input) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | SEARCH FORM SUBMIT
        |--------------------------------------------------------------------------
        */

        form.addEventListener("submit", function (event) {

            const keyword = input.value.trim();

            if (keyword === "") {

                event.preventDefault();

                input.focus();

                return;
            }

            input.value = keyword;

        });


        /*
        |--------------------------------------------------------------------------
        | SEARCH INPUT
        |--------------------------------------------------------------------------
        */

        input.addEventListener("input", function () {

            const keyword = this.value.trim();

            toggleClearButton(input, keyword);

        });


        /*
        |--------------------------------------------------------------------------
        | ESCAPE KEY
        |--------------------------------------------------------------------------
        */

        input.addEventListener("keydown", function (event) {

            if (event.key === "Escape") {

                this.value = "";

                toggleClearButton(this, "");

                hideSuggestions(form);

            }

        });

        toggleClearButton(input, input.value.trim());

    });


    /*
    |--------------------------------------------------------------------------
    | SEARCH CLEAR BUTTON
    |--------------------------------------------------------------------------
    */

    document.addEventListener("click", function (event) {

        const clearButton =
            event.target.closest(".search-clear");

        if (!clearButton) {
            return;
        }

        const form =
            clearButton.closest("form");

        if (!form) {
            return;
        }

        const input = form.querySelector(
            'input[name="search"], input[type="search"]'
        );

        if (!input) {
            return;
        }

        input.value = "";

        input.focus();

        toggleClearButton(input, "");

        hideSuggestions(form);

    });


    /*
    |--------------------------------------------------------------------------
    | SEARCH SUGGESTIONS
    |--------------------------------------------------------------------------
    */

    const suggestionContainers =
        document.querySelectorAll(
            ".search-suggestions"
        );

    suggestionContainers.forEach(function (container) {

        const form =
            container.closest("form");

        if (!form) {
            return;
        }

        const input = form.querySelector(
            'input[name="search"], input[type="search"]'
        );

        if (!input) {
            return;
        }

        input.addEventListener("focus", function () {

            if (this.value.trim() !== "") {
                container.classList.add("active");
            }

        });

    });


    /*
    |--------------------------------------------------------------------------
    | CLICK OUTSIDE SEARCH
    |--------------------------------------------------------------------------
    */

    document.addEventListener("click", function (event) {

        const searchArea =
            event.target.closest(
                ".search-wrapper, .search-container, .search-box"
            );

        if (searchArea) {
            return;
        }

        document
            .querySelectorAll(".search-suggestions.active")
            .forEach(function (element) {

                element.classList.remove("active");

            });

    });


    /*
    |--------------------------------------------------------------------------
    | SUGGESTION CLICK
    |--------------------------------------------------------------------------
    */

    document.addEventListener("click", function (event) {

        const suggestion =
            event.target.closest(
                ".search-suggestion"
            );

        if (!suggestion) {
            return;
        }

        const keyword =
            suggestion.dataset.search ||
            suggestion.textContent.trim();

        const form =
            suggestion.closest("form");

        if (!form) {
            return;
        }

        const input = form.querySelector(
            'input[name="search"], input[type="search"]'
        );

        if (!input) {
            return;
        }

        input.value = keyword;

        form.submit();

    });

});


/*
|--------------------------------------------------------------------------
| TOGGLE CLEAR BUTTON
|--------------------------------------------------------------------------
*/

function toggleClearButton(input, keyword) {

    const form =
        input.closest("form");

    if (!form) {
        return;
    }

    const clearButton =
        form.querySelector(".search-clear");

    if (!clearButton) {
        return;
    }

    if (keyword !== "") {

        clearButton.classList.add("active");

        clearButton.style.display = "flex";

    } else {

        clearButton.classList.remove("active");

        clearButton.style.display = "none";

    }

}


/*
|--------------------------------------------------------------------------
| HIDE SUGGESTIONS
|--------------------------------------------------------------------------
*/

function hideSuggestions(form) {

    const suggestions =
        form.querySelector(
            ".search-suggestions"
        );

    if (!suggestions) {
        return;
    }

    suggestions.classList.remove("active");

}


/*
|--------------------------------------------------------------------------
| GLOBAL SEARCH HELPER
|--------------------------------------------------------------------------
*/

function submitSearch(keyword) {

    keyword = String(keyword || "").trim();

    if (keyword === "") {
        return;
    }

    const encodedKeyword =
        encodeURIComponent(keyword);

    window.location.href =
        "search.php?search=" +
        encodedKeyword;

}


/*
|--------------------------------------------------------------------------
| SEARCH WITH ENTER
|--------------------------------------------------------------------------
*/

document.addEventListener("keydown", function (event) {

    if (
        event.key !== "Enter" ||
        event.target.tagName !== "INPUT"
    ) {
        return;
    }

    const input = event.target;

    if (
        !input.matches(
            'input[name="search"], input[type="search"]'
        )
    ) {
        return;
    }

    const keyword =
        input.value.trim();

    if (keyword === "") {
        return;
    }

});


/*
|--------------------------------------------------------------------------
| SEARCH BUTTON HELPER
|--------------------------------------------------------------------------
*/

function performSearch(inputSelector) {

    const input =
        document.querySelector(inputSelector);

    if (!input) {
        return;
    }

    const keyword =
        input.value.trim();

    if (keyword === "") {

        input.focus();

        return;
    }

    submitSearch(keyword);

}