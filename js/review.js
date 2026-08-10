/*
|--------------------------------------------------------------------------
| HOCHIPO HUB - REVIEW JAVASCRIPT
|--------------------------------------------------------------------------
| File:
| js/review.js
|
| Functions:
| - Star rating selection
| - Rating preview
| - Review form validation
| - Character counter
| - Review modal
| - Review confirmation
| - Prevent double submission
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {

    initReviewRating();
    initReviewCharacterCounter();
    initReviewForm();
    initReviewModal();
    initReviewDeleteConfirmation();

});


/*
|--------------------------------------------------------------------------
| STAR RATING
|--------------------------------------------------------------------------
*/

function initReviewRating() {

    const ratingContainers =
        document.querySelectorAll(
            ".rating-input, .review-rating-input, .star-rating"
        );

    ratingContainers.forEach(function (container) {

        const stars =
            container.querySelectorAll(
                "input[type='radio']"
            );

        const labels =
            container.querySelectorAll(
                "label"
            );

        /*
        |--------------------------------------------------------------------------
        | Mouse hover
        |--------------------------------------------------------------------------
        */

        labels.forEach(function (label, index) {

            label.addEventListener(
                "mouseenter",
                function () {

                    highlightStars(
                        labels,
                        index + 1
                    );

                }
            );

        });


        /*
        |--------------------------------------------------------------------------
        | Mouse leave
        |--------------------------------------------------------------------------
        */

        container.addEventListener(
            "mouseleave",
            function () {

                let selectedRating = 0;

                stars.forEach(function (radio) {

                    if (radio.checked) {

                        selectedRating =
                            parseInt(
                                radio.value,
                                10
                            );

                    }

                });

                highlightStars(
                    labels,
                    selectedRating
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Click rating
        |--------------------------------------------------------------------------
        */

        stars.forEach(function (radio) {

            radio.addEventListener(
                "change",
                function () {

                    const rating =
                        parseInt(
                            radio.value,
                            10
                        );

                    highlightStars(
                        labels,
                        rating
                    );

                    updateRatingText(
                        container,
                        rating
                    );

                }
            );

        });

    });

}


/*
|--------------------------------------------------------------------------
| HIGHLIGHT STARS
|--------------------------------------------------------------------------
*/

function highlightStars(
    labels,
    rating
) {

    labels.forEach(function (label, index) {

        if (index < rating) {

            label.classList.add(
                "active"
            );

            label.classList.add(
                "selected"
            );

        } else {

            label.classList.remove(
                "active"
            );

            label.classList.remove(
                "selected"
            );

        }

    });

}


/*
|--------------------------------------------------------------------------
| RATING TEXT
|--------------------------------------------------------------------------
*/

function updateRatingText(
    container,
    rating
) {

    const ratingText =
        container.querySelector(
            ".rating-text, .review-rating-text"
        );

    if (!ratingText) {
        return;
    }


    const ratingLabels = {

        1: "Very Poor",

        2: "Poor",

        3: "Average",

        4: "Good",

        5: "Excellent"

    };


    ratingText.textContent =
        ratingLabels[rating] ||
        "Select a rating";

}


/*
|--------------------------------------------------------------------------
| CHARACTER COUNTER
|--------------------------------------------------------------------------
*/

function initReviewCharacterCounter() {

    const textareas =
        document.querySelectorAll(
            ".review-textarea, textarea[name='review'], textarea[name='comment']"
        );


    textareas.forEach(function (textarea) {

        const counter =
            findCharacterCounter(
                textarea
            );


        updateCharacterCounter(
            textarea,
            counter
        );


        textarea.addEventListener(
            "input",
            function () {

                updateCharacterCounter(
                    textarea,
                    counter
                );

            }
        );

    });

}


/*
|--------------------------------------------------------------------------
| FIND CHARACTER COUNTER
|--------------------------------------------------------------------------
*/

function findCharacterCounter(
    textarea
) {

    const parent =
        textarea.parentElement;

    if (!parent) {
        return null;
    }


    return parent.querySelector(
        ".character-count, .review-character-count, [data-character-count]"
    );

}


/*
|--------------------------------------------------------------------------
| UPDATE CHARACTER COUNTER
|--------------------------------------------------------------------------
*/

function updateCharacterCounter(
    textarea,
    counter
) {

    if (!counter) {
        return;
    }


    const currentLength =
        textarea.value.length;

    const maxLength =
        textarea.maxLength > 0
            ? textarea.maxLength
            : 1000;


    counter.textContent =
        currentLength +
        " / " +
        maxLength;


    /*
    |--------------------------------------------------------------------------
    | Warning state
    |--------------------------------------------------------------------------
    */

    if (
        currentLength >=
        maxLength * 0.8
    ) {

        counter.classList.add(
            "warning"
        );

    } else {

        counter.classList.remove(
            "warning"
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Limit state
    |--------------------------------------------------------------------------
    */

    if (
        currentLength >=
        maxLength
    ) {

        counter.classList.add(
            "limit"
        );

    } else {

        counter.classList.remove(
            "limit"
        );

    }

}


/*
|--------------------------------------------------------------------------
| REVIEW FORM
|--------------------------------------------------------------------------
*/

function initReviewForm() {

    const forms =
        document.querySelectorAll(
            ".review-form, form[data-review-form]"
        );


    forms.forEach(function (form) {

        form.addEventListener(
            "submit",
            function (event) {

                if (
                    !validateReviewForm(
                        form
                    )
                ) {

                    event.preventDefault();

                    return;

                }


                /*
                |--------------------------------------------------------------------------
                | Prevent double submission
                |--------------------------------------------------------------------------
                */

                if (
                    form.dataset.submitting ===
                    "true"
                ) {

                    event.preventDefault();

                    return;

                }


                form.dataset.submitting =
                    "true";


                const submitButton =
                    form.querySelector(
                        "button[type='submit'], input[type='submit']"
                    );


                if (submitButton) {

                    submitButton.disabled =
                        true;


                    if (
                        submitButton.tagName ===
                        "BUTTON"
                    ) {

                        submitButton.dataset.originalText =
                            submitButton.innerHTML;

                        submitButton.innerHTML =
                            "SUBMITTING...";

                    }

                }

            }
        );

    });

}


/*
|--------------------------------------------------------------------------
| VALIDATE REVIEW FORM
|--------------------------------------------------------------------------
*/

function validateReviewForm(
    form
) {

    /*
    |--------------------------------------------------------------------------
    | Rating
    |--------------------------------------------------------------------------
    */

    const rating =
        form.querySelector(
            "input[name='rating']:checked"
        );


    if (!rating) {

        showReviewError(
            form,
            "Please select a rating."
        );

        return false;

    }


    /*
    |--------------------------------------------------------------------------
    | Review text
    |--------------------------------------------------------------------------
    */

    const textarea =
        form.querySelector(
            "textarea[name='review'], textarea[name='comment'], .review-textarea"
        );


    if (textarea) {

        const reviewText =
            textarea.value.trim();


        if (
            reviewText.length === 0
        ) {

            showReviewError(
                form,
                "Please write a review."
            );

            textarea.focus();

            return false;

        }


        if (
            reviewText.length < 5
        ) {

            showReviewError(
                form,
                "Your review is too short."
            );

            textarea.focus();

            return false;

        }


        if (
            textarea.maxLength > 0 &&
            reviewText.length >
            textarea.maxLength
        ) {

            showReviewError(
                form,
                "Your review is too long."
            );

            textarea.focus();

            return false;

        }

    }


    clearReviewError(form);

    return true;

}


/*
|--------------------------------------------------------------------------
| SHOW REVIEW ERROR
|--------------------------------------------------------------------------
*/

function showReviewError(
    form,
    message
) {

    let error =
        form.querySelector(
            ".review-form-error"
        );


    if (!error) {

        error =
            document.createElement(
                "div"
            );

        error.className =
            "review-form-error";

        form.prepend(error);

    }


    error.textContent =
        message;

    error.classList.add(
        "show"
    );


    error.scrollIntoView({
        behavior: "smooth",
        block: "center"
    });

}


/*
|--------------------------------------------------------------------------
| CLEAR REVIEW ERROR
|--------------------------------------------------------------------------
*/

function clearReviewError(
    form
) {

    const error =
        form.querySelector(
            ".review-form-error"
        );


    if (error) {

        error.textContent = "";

        error.classList.remove(
            "show"
        );

    }

}


/*
|--------------------------------------------------------------------------
| REVIEW MODAL
|--------------------------------------------------------------------------
*/

function initReviewModal() {

    const openButtons =
        document.querySelectorAll(
            "[data-review-modal], .open-review-modal"
        );


    openButtons.forEach(function (button) {

        button.addEventListener(
            "click",
            function (event) {

                event.preventDefault();


                const modalId =
                    button.dataset.reviewModal ||
                    "reviewModal";


                const modal =
                    document.getElementById(
                        modalId
                    );


                if (modal) {

                    openReviewModal(
                        modal
                    );

                }

            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | Close buttons
    |--------------------------------------------------------------------------
    */

    const closeButtons =
        document.querySelectorAll(
            ".review-modal-close, [data-review-modal-close]"
        );


    closeButtons.forEach(function (button) {

        button.addEventListener(
            "click",
            function () {

                const modal =
                    button.closest(
                        ".review-modal, .modal"
                    );


                if (modal) {

                    closeReviewModal(
                        modal
                    );

                }

            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | Click outside
    |--------------------------------------------------------------------------
    */

    const modals =
        document.querySelectorAll(
            ".review-modal"
        );


    modals.forEach(function (modal) {

        modal.addEventListener(
            "click",
            function (event) {

                if (
                    event.target === modal
                ) {

                    closeReviewModal(
                        modal
                    );

                }

            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | ESC
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape"
            ) {

                const activeModal =
                    document.querySelector(
                        ".review-modal.active"
                    );


                if (activeModal) {

                    closeReviewModal(
                        activeModal
                    );

                }

            }

        }
    );

}


/*
|--------------------------------------------------------------------------
| OPEN REVIEW MODAL
|--------------------------------------------------------------------------
*/

function openReviewModal(
    modal
) {

    if (!modal) {
        return;
    }


    modal.classList.add(
        "active"
    );

    modal.classList.add(
        "show"
    );

    modal.style.display =
        "flex";


    document.body.classList.add(
        "modal-open"
    );

    document.body.style.overflow =
        "hidden";


    setTimeout(function () {

        const input =
            modal.querySelector(
                "input, textarea, select"
            );


        if (input) {

            input.focus();

        }

    }, 150);

}


/*
|--------------------------------------------------------------------------
| CLOSE REVIEW MODAL
|--------------------------------------------------------------------------
*/

function closeReviewModal(
    modal
) {

    if (!modal) {
        return;
    }


    modal.classList.remove(
        "active"
    );

    modal.classList.remove(
        "show"
    );


    setTimeout(function () {

        if (
            !modal.classList.contains(
                "active"
            )
        ) {

            modal.style.display =
                "none";

        }

    }, 200);


    document.body.classList.remove(
        "modal-open"
    );

    document.body.style.overflow =
        "";

}


/*
|--------------------------------------------------------------------------
| DELETE REVIEW CONFIRMATION
|--------------------------------------------------------------------------
*/

function initReviewDeleteConfirmation() {

    const deleteButtons =
        document.querySelectorAll(
            ".delete-review, [data-delete-review]"
        );


    deleteButtons.forEach(function (button) {

        button.addEventListener(
            "click",
            function (event) {

                const confirmed =
                    window.confirm(
                        "Are you sure you want to delete this review?"
                    );


                if (!confirmed) {

                    event.preventDefault();

                }

            }
        );

    });

}


/*
|--------------------------------------------------------------------------
| EXPOSE FUNCTIONS
|--------------------------------------------------------------------------
*/

window.openReviewModal =
    openReviewModal;

window.closeReviewModal =
    closeReviewModal;

window.validateReviewForm =
    validateReviewForm;