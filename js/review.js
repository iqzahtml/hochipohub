/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - REVIEW.JS
|--------------------------------------------------------------------------
| Handles:
| - Star rating selection
| - Review form validation
| - Character counter
| - Review image preview
| - AJAX review submission
| - Review UI updates
| - Review sorting
|--------------------------------------------------------------------------
*/

"use strict";

document.addEventListener("DOMContentLoaded", function () {

    /* ==========================================================
       ELEMENTS
    ========================================================== */

    const reviewForm = document.querySelector("#reviewForm");

    const ratingInput = document.querySelector("#rating");
    const ratingButtons = document.querySelectorAll(
        ".review-rating-input button, .rating-star"
    );

    const reviewTextarea = document.querySelector(
        "#review, #reviewText, .review-textarea"
    );

    const characterCounter = document.querySelector(
        "#reviewCharacterCount, .review-character-count"
    );

    const imageInput = document.querySelector(
        "#reviewImage, #review_image"
    );

    const imagePreview = document.querySelector(
        "#reviewImagePreview, .review-image-preview"
    );

    const reviewSubmitButton = reviewForm
        ? reviewForm.querySelector(
            "button[type='submit'], .review-submit-btn"
        )
        : null;

    const reviewList = document.querySelector(
        ".review-list, #reviewList"
    );

    const reviewSort = document.querySelector(
        "#reviewSort, .review-sort"
    );


    /* ==========================================================
       CONFIGURATION
    ========================================================== */

    const MAX_REVIEW_LENGTH = 1000;

    let selectedRating = 0;


    /* ==========================================================
       HELPER - GET PRODUCT ID
    ========================================================== */

    function getProductId() {

        const productInput = document.querySelector(
            "#product_id"
        );

        if (productInput && productInput.value) {
            return productInput.value;
        }

        const productElement = document.querySelector(
            "[data-product-id]"
        );

        if (
            productElement &&
            productElement.dataset.productId
        ) {
            return productElement.dataset.productId;
        }

        const urlParams = new URLSearchParams(
            window.location.search
        );

        return urlParams.get("product_id") ||
               urlParams.get("id") ||
               "";
    }


    /* ==========================================================
       STAR RATING
    ========================================================== */

    function updateStars(rating) {

        ratingButtons.forEach(function (button) {

            const buttonRating = parseInt(
                button.dataset.rating ||
                button.dataset.value ||
                button.getAttribute("data-star") ||
                "0",
                10
            );

            if (buttonRating <= rating) {

                button.classList.add("active");

                button.setAttribute(
                    "aria-pressed",
                    "true"
                );

            } else {

                button.classList.remove("active");

                button.setAttribute(
                    "aria-pressed",
                    "false"
                );

            }

        });
    }


    function setRating(rating) {

        rating = parseInt(rating, 10);

        if (
            Number.isNaN(rating) ||
            rating < 1 ||
            rating > 5
        ) {
            return;
        }

        selectedRating = rating;

        if (ratingInput) {
            ratingInput.value = rating;
        }

        updateStars(rating);

        const ratingLabel = document.querySelector(
            "#ratingLabel, .rating-label"
        );

        if (ratingLabel) {

            const labels = {
                1: "Poor",
                2: "Fair",
                3: "Good",
                4: "Very Good",
                5: "Excellent"
            };

            ratingLabel.textContent =
                labels[rating] || "";
        }
    }


    ratingButtons.forEach(function (button) {

        button.addEventListener("click", function (event) {

            event.preventDefault();

            const rating = parseInt(
                button.dataset.rating ||
                button.dataset.value ||
                button.getAttribute("data-star") ||
                "0",
                10
            );

            setRating(rating);

        });


        /* ------------------------------------------------------
           Keyboard Accessibility
        ------------------------------------------------------ */

        button.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key === "Enter" ||
                    event.key === " "
                ) {

                    event.preventDefault();

                    button.click();
                }
            }
        );

    });


    /* ==========================================================
       INITIAL RATING
    ========================================================== */

    if (ratingInput && ratingInput.value) {

        const initialRating = parseInt(
            ratingInput.value,
            10
        );

        if (
            initialRating >= 1 &&
            initialRating <= 5
        ) {
            setRating(initialRating);
        }
    }


    /* ==========================================================
       TEXTAREA CHARACTER COUNTER
    ========================================================== */

    function updateCharacterCounter() {

        if (!reviewTextarea) {
            return;
        }

        const currentLength =
            reviewTextarea.value.length;

        if (characterCounter) {

            characterCounter.textContent =
                `${currentLength}/${MAX_REVIEW_LENGTH}`;

            if (
                currentLength >=
                MAX_REVIEW_LENGTH * 0.9
            ) {

                characterCounter.classList.add(
                    "near-limit"
                );

            } else {

                characterCounter.classList.remove(
                    "near-limit"
                );
            }

            if (
                currentLength >=
                MAX_REVIEW_LENGTH
            ) {

                characterCounter.classList.add(
                    "limit-reached"
                );

            } else {

                characterCounter.classList.remove(
                    "limit-reached"
                );
            }
        }

    }


    if (reviewTextarea) {

        reviewTextarea.setAttribute(
            "maxlength",
            MAX_REVIEW_LENGTH
        );

        reviewTextarea.addEventListener(
            "input",
            updateCharacterCounter
        );

        updateCharacterCounter();
    }


    /* ==========================================================
       IMAGE PREVIEW
    ========================================================== */

    if (imageInput) {

        imageInput.addEventListener(
            "change",
            function () {

                const file =
                    imageInput.files &&
                    imageInput.files[0];

                if (!file) {

                    if (imagePreview) {

                        imagePreview.innerHTML = "";

                        imagePreview.classList.remove(
                            "active"
                        );
                    }

                    return;
                }


                /* ------------------------------------------------
                   Validate image type
                ------------------------------------------------ */

                if (!file.type.startsWith("image/")) {

                    showMessage(
                        "Please select a valid image file.",
                        "error"
                    );

                    imageInput.value = "";

                    return;
                }


                /* ------------------------------------------------
                   Validate image size
                ------------------------------------------------ */

                const MAX_IMAGE_SIZE =
                    5 * 1024 * 1024;

                if (file.size > MAX_IMAGE_SIZE) {

                    showMessage(
                        "Image size must be 5MB or less.",
                        "error"
                    );

                    imageInput.value = "";

                    return;
                }


                if (!imagePreview) {
                    return;
                }


                const reader =
                    new FileReader();


                reader.onload = function (event) {

                    imagePreview.innerHTML = "";

                    const wrapper =
                        document.createElement("div");

                    wrapper.className =
                        "review-preview-wrapper";


                    const image =
                        document.createElement("img");

                    image.src =
                        event.target.result;

                    image.alt =
                        "Review image preview";

                    image.className =
                        "review-preview-image";


                    const removeButton =
                        document.createElement("button");

                    removeButton.type =
                        "button";

                    removeButton.className =
                        "review-preview-remove";

                    removeButton.innerHTML =
                        '<i class="fa-solid fa-xmark"></i> Remove';


                    removeButton.addEventListener(
                        "click",
                        function () {

                            imageInput.value = "";

                            imagePreview.innerHTML = "";

                            imagePreview.classList.remove(
                                "active"
                            );
                        }
                    );


                    wrapper.appendChild(image);

                    wrapper.appendChild(
                        removeButton
                    );

                    imagePreview.appendChild(
                        wrapper
                    );

                    imagePreview.classList.add(
                        "active"
                    );

                };


                reader.readAsDataURL(file);

            }
        );
    }


    /* ==========================================================
       FORM VALIDATION
    ========================================================== */

    function validateReviewForm() {

        clearFormErrors();


        /* ------------------------------------------------------
           Check rating
        ------------------------------------------------------ */

        if (
            !selectedRating &&
            ratingInput &&
            ratingInput.value
        ) {

            selectedRating =
                parseInt(
                    ratingInput.value,
                    10
                );
        }


        if (
            !selectedRating ||
            selectedRating < 1 ||
            selectedRating > 5
        ) {

            showFieldError(
                ".review-rating-input",
                "Please select a rating."
            );

            return false;
        }


        /* ------------------------------------------------------
           Check review text
        ------------------------------------------------------ */

        if (reviewTextarea) {

            const review =
                reviewTextarea.value.trim();

            if (!review) {

                showFieldError(
                    reviewTextarea,
                    "Please write a review."
                );

                reviewTextarea.focus();

                return false;
            }


            if (review.length < 5) {

                showFieldError(
                    reviewTextarea,
                    "Review must contain at least 5 characters."
                );

                reviewTextarea.focus();

                return false;
            }


            if (
                review.length >
                MAX_REVIEW_LENGTH
            ) {

                showFieldError(
                    reviewTextarea,
                    `Review cannot exceed ${MAX_REVIEW_LENGTH} characters.`
                );

                reviewTextarea.focus();

                return false;
            }
        }


        return true;
    }


    /* ==========================================================
       FORM ERROR
    ========================================================== */

    function showFieldError(
        element,
        message
    ) {

        let target = element;

        if (typeof element === "string") {

            target =
                document.querySelector(element);
        }


        if (!target) {
            return;
        }


        target.classList.add(
            "review-field-error"
        );


        let errorElement =
            target.parentElement
                ? target.parentElement.querySelector(
                    ".review-error-message"
                )
                : null;


        if (!errorElement) {

            errorElement =
                document.createElement("small");

            errorElement.className =
                "review-error-message";

            target.parentElement.appendChild(
                errorElement
            );
        }


        errorElement.textContent =
            message;
    }


    function clearFormErrors() {

        document
            .querySelectorAll(
                ".review-field-error"
            )
            .forEach(function (element) {

                element.classList.remove(
                    "review-field-error"
                );

            });


        document
            .querySelectorAll(
                ".review-error-message"
            )
            .forEach(function (element) {

                element.remove();

            });
    }


    /* ==========================================================
       MESSAGE SYSTEM
    ========================================================== */

    function showMessage(
        message,
        type = "info"
    ) {

        let container =
            document.querySelector(
                "#reviewMessage"
            );


        if (!container) {

            container =
                document.createElement("div");

            container.id =
                "reviewMessage";

            container.className =
                "review-message";

            if (reviewForm) {

                reviewForm.parentNode.insertBefore(
                    container,
                    reviewForm
                );

            } else {

                document.body.prepend(
                    container
                );
            }
        }


        container.className =
            `review-message ${type}`;

        container.textContent =
            message;


        container.scrollIntoView({
            behavior: "smooth",
            block: "nearest"
        });


        setTimeout(function () {

            if (container) {

                container.classList.add(
                    "fade-out"
                );

            }

        }, 4500);

    }


    /* ==========================================================
       SET BUTTON LOADING
    ========================================================== */

    function setButtonLoading(
        loading
    ) {

        if (!reviewSubmitButton) {
            return;
        }


        if (loading) {

            reviewSubmitButton.dataset.originalText =
                reviewSubmitButton.innerHTML;

            reviewSubmitButton.disabled =
                true;

            reviewSubmitButton.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';

        } else {

            reviewSubmitButton.disabled =
                false;

            if (
                reviewSubmitButton.dataset.originalText
            ) {

                reviewSubmitButton.innerHTML =
                    reviewSubmitButton.dataset.originalText;
            }
        }
    }


    /* ==========================================================
       AJAX SUBMIT REVIEW
    ========================================================== */

    async function submitReview() {

        const productId =
            getProductId();


        if (!productId) {

            showMessage(
                "Product information is missing.",
                "error"
            );

            return;
        }


        const formData =
            new FormData(reviewForm);


        formData.set(
            "product_id",
            productId
        );

        formData.set(
            "rating",
            selectedRating
        );


        /*
        ----------------------------------------------------------
        AJAX endpoint
        ----------------------------------------------------------
        review.php should process POST requests.
        ----------------------------------------------------------
        */

        try {

            setButtonLoading(true);


            const response =
                await fetch(
                    "review.php",
                    {
                        method: "POST",
                        body: formData,
                        headers: {
                            "X-Requested-With":
                                "XMLHttpRequest"
                        }
                    }
                );


            if (!response.ok) {

                throw new Error(
                    `HTTP ${response.status}`
                );
            }


            const contentType =
                response.headers.get(
                    "content-type"
                ) || "";


            let result;


            if (
                contentType.includes(
                    "application/json"
                )
            ) {

                result =
                    await response.json();

            } else {

                /*
                --------------------------------------------------
                If review.php returns normal HTML,
                reload page after submission.
                --------------------------------------------------
                */

                showMessage(
                    "Your review has been submitted.",
                    "success"
                );

                setTimeout(function () {

                    window.location.reload();

                }, 1000);

                return;
            }


            if (result.success) {

                showMessage(
                    result.message ||
                    "Your review has been submitted successfully.",
                    "success"
                );


                reviewForm.reset();

                selectedRating = 0;

                if (ratingInput) {
                    ratingInput.value = "";
                }

                updateStars(0);

                updateCharacterCounter();


                if (imagePreview) {

                    imagePreview.innerHTML = "";

                    imagePreview.classList.remove(
                        "active"
                    );
                }


                if (result.review) {

                    addReviewToList(
                        result.review
                    );
                }


            } else {

                showMessage(
                    result.message ||
                    "Unable to submit your review.",
                    "error"
                );
            }


        } catch (error) {

            console.error(
                "Review submission error:",
                error
            );


            showMessage(
                "Something went wrong while submitting your review.",
                "error"
            );

        } finally {

            setButtonLoading(false);

        }
    }


    /* ==========================================================
       REVIEW FORM SUBMIT
    ========================================================== */

    if (reviewForm) {

        reviewForm.addEventListener(
            "submit",
            function (event) {

                event.preventDefault();


                if (
                    !validateReviewForm()
                ) {
                    return;
                }


                submitReview();

            }
        );
    }


    /* ==========================================================
       ADD REVIEW TO LIST
    ========================================================== */

    function addReviewToList(
        review
    ) {

        if (!reviewList) {
            return;
        }


        const item =
            document.createElement("article");

        item.className =
            "review-item new-review";


        const avatar =
            document.createElement("div");

        avatar.className =
            "review-avatar";


        if (review.profile_image) {

            const img =
                document.createElement("img");

            img.src =
                review.profile_image;

            img.alt =
                review.name ||
                "Customer";

            avatar.appendChild(img);

        } else {

            avatar.textContent =
                getInitials(
                    review.name ||
                    "Customer"
                );
        }


        const content =
            document.createElement("div");

        content.className =
            "review-content";


        const header =
            document.createElement("div");

        header.className =
            "review-content-header";


        const username =
            document.createElement("span");

        username.className =
            "review-user-name";

        username.textContent =
            review.name ||
            "Customer";


        const date =
            document.createElement("span");

        date.className =
            "review-date";

        date.textContent =
            review.date ||
            "Just now";


        header.appendChild(username);

        header.appendChild(date);


        const stars =
            document.createElement("div");

        stars.className =
            "product-stars";


        const rating =
            parseInt(
                review.rating || 0,
                10
            );


        for (
            let i = 1;
            i <= 5;
            i++
        ) {

            const star =
                document.createElement("i");

            star.className =
                i <= rating
                    ? "fa-solid fa-star"
                    : "fa-regular fa-star";

            stars.appendChild(star);
        }


        const text =
            document.createElement("p");

        text.className =
            "review-text";

        text.textContent =
            review.review ||
            "";


        content.appendChild(header);

        content.appendChild(stars);

        content.appendChild(text);


        item.appendChild(avatar);

        item.appendChild(content);


        reviewList.prepend(item);

    }


    /* ==========================================================
       GET INITIALS
    ========================================================== */

    function getInitials(
        name
    ) {

        if (!name) {
            return "?";
        }


        const parts =
            name
                .trim()
                .split(/\s+/)
                .filter(Boolean);


        if (parts.length === 1) {

            return parts[0]
                .substring(0, 2)
                .toUpperCase();
        }


        return (
            parts[0][0] +
            parts[parts.length - 1][0]
        ).toUpperCase();
    }


    /* ==========================================================
       REVIEW SORTING
    ========================================================== */

    if (reviewSort) {

        reviewSort.addEventListener(
            "change",
            function () {

                const value =
                    reviewSort.value;


                if (!reviewList) {
                    return;
                }


                const reviews =
                    Array.from(
                        reviewList.querySelectorAll(
                            ".review-item"
                        )
                    );


                if (reviews.length < 2) {
                    return;
                }


                if (value === "newest") {

                    reviews.reverse();

                }


                if (value === "oldest") {

                    reviews.reverse();

                }


                if (value === "highest") {

                    reviews.sort(
                        function (a, b) {

                            return (
                                getReviewRating(b) -
                                getReviewRating(a)
                            );

                        }
                    );

                }


                if (value === "lowest") {

                    reviews.sort(
                        function (a, b) {

                            return (
                                getReviewRating(a) -
                                getReviewRating(b)
                            );

                        }
                    );

                }


                reviews.forEach(
                    function (review) {

                        reviewList.appendChild(
                            review
                        );

                    }
                );

            }
        );
    }


    function getReviewRating(
        reviewElement
    ) {

        const stars =
            reviewElement.querySelectorAll(
                ".product-stars .fa-star"
            );


        return stars.length;
    }


    /* ==========================================================
       REMOVE ERROR WHEN USER STARTS TYPING
    ========================================================== */

    if (reviewTextarea) {

        reviewTextarea.addEventListener(
            "input",
            function () {

                reviewTextarea.classList.remove(
                    "review-field-error"
                );

                const error =
                    reviewTextarea.parentElement
                        ? reviewTextarea.parentElement
                            .querySelector(
                                ".review-error-message"
                            )
                        : null;

                if (error) {
                    error.remove();
                }

            }
        );
    }


    /* ==========================================================
       PUBLIC REVIEW FUNCTIONS
    ========================================================== */

    window.HochipoReview = {

        setRating: setRating,

        validate:
            validateReviewForm,

        getRating:
            function () {
                return selectedRating;
            },

        getProductId:
            getProductId

    };

});