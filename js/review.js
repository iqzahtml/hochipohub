/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - REVIEW JS
|--------------------------------------------------------------------------
| Handles:
| - Star rating
| - Review form
| - Character counter
| - Review image preview
| - Review validation
| - AJAX review submission
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {

    /* ==============================================================
       STAR RATING
    ============================================================== */

    const ratingContainer = document.querySelector(".review-rating-input");

    if (ratingContainer) {

        const ratingButtons = ratingContainer.querySelectorAll("button");
        const ratingInput = document.querySelector("#rating");

        ratingButtons.forEach((button, index) => {

            button.addEventListener("click", function () {

                const rating = index + 1;

                if (ratingInput) {
                    ratingInput.value = rating;
                }

                ratingButtons.forEach((btn, btnIndex) => {

                    if (btnIndex < rating) {
                        btn.classList.add("active");
                        btn.setAttribute("aria-pressed", "true");
                    } else {
                        btn.classList.remove("active");
                        btn.setAttribute("aria-pressed", "false");
                    }

                });

            });

            button.addEventListener("mouseenter", function () {

                const rating = index + 1;

                ratingButtons.forEach((btn, btnIndex) => {

                    if (btnIndex < rating) {
                        btn.classList.add("hover");
                    } else {
                        btn.classList.remove("hover");
                    }

                });

            });

        });


        ratingContainer.addEventListener("mouseleave", function () {

            ratingButtons.forEach(button => {
                button.classList.remove("hover");
            });

        });

    }


    /* ==============================================================
       REVIEW TEXT COUNTER
    ============================================================== */

    const reviewTextarea = document.querySelector(".review-textarea");
    const reviewCounter = document.querySelector(".review-character-count");

    if (reviewTextarea && reviewCounter) {

        const maxLength = reviewTextarea.getAttribute("maxlength") || 1000;

        function updateCounter() {

            const currentLength = reviewTextarea.value.length;

            reviewCounter.textContent =
                `${currentLength}/${maxLength}`;

        }

        reviewTextarea.addEventListener("input", updateCounter);

        updateCounter();

    }


    /* ==============================================================
       REVIEW IMAGE PREVIEW
    ============================================================== */

    const reviewImageInput = document.querySelector("#review-image");
    const reviewImagePreview = document.querySelector(".review-image-preview");

    if (reviewImageInput && reviewImagePreview) {

        reviewImageInput.addEventListener("change", function () {

            const file = this.files[0];

            if (!file) {
                reviewImagePreview.innerHTML = "";
                return;
            }

            if (!file.type.startsWith("image/")) {

                showReviewMessage(
                    "Please select a valid image.",
                    "error"
                );

                this.value = "";
                return;
            }

            const maxSize = 5 * 1024 * 1024;

            if (file.size > maxSize) {

                showReviewMessage(
                    "Image size must be less than 5MB.",
                    "error"
                );

                this.value = "";
                return;
            }

            const reader = new FileReader();

            reader.onload = function (event) {

                reviewImagePreview.innerHTML = `
                    <div class="review-preview-wrapper">
                        <img 
                            src="${event.target.result}"
                            alt="Review image preview"
                            class="review-preview-image"
                        >

                        <button 
                            type="button"
                            class="remove-review-image"
                            aria-label="Remove image"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                `;

                const removeButton =
                    reviewImagePreview.querySelector(
                        ".remove-review-image"
                    );

                if (removeButton) {

                    removeButton.addEventListener(
                        "click",
                        function () {

                            reviewImageInput.value = "";

                            reviewImagePreview.innerHTML = "";

                        }
                    );

                }

            };

            reader.readAsDataURL(file);

        });

    }


    /* ==============================================================
       REVIEW FORM
    ============================================================== */

    const reviewForm = document.querySelector("#review-form");

    if (reviewForm) {

        reviewForm.addEventListener("submit", async function (event) {

            event.preventDefault();

            const ratingInput =
                reviewForm.querySelector("#rating");

            const reviewInput =
                reviewForm.querySelector(
                    ".review-textarea"
                );

            if (!ratingInput || Number(ratingInput.value) < 1) {

                showReviewMessage(
                    "Please select a rating first.",
                    "error"
                );

                return;
            }

            if (
                reviewInput &&
                reviewInput.value.trim().length < 5
            ) {

                showReviewMessage(
                    "Your review must contain at least 5 characters.",
                    "error"
                );

                reviewInput.focus();

                return;
            }

            const submitButton =
                reviewForm.querySelector(
                    '[type="submit"]'
                );

            const originalText =
                submitButton
                    ? submitButton.innerHTML
                    : "";

            if (submitButton) {

                submitButton.disabled = true;

                submitButton.innerHTML = `
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    Submitting...
                `;

            }

            try {

                const formData =
                    new FormData(reviewForm);

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

                const result =
                    await response.json();

                if (result.success) {

                    showReviewMessage(
                        result.message ||
                        "Review submitted successfully.",
                        "success"
                    );

                    reviewForm.reset();

                    if (ratingInput) {
                        ratingInput.value = "";
                    }

                    if (ratingContainer) {

                        ratingContainer
                            .querySelectorAll("button")
                            .forEach(button => {
                                button.classList.remove("active");
                            });

                    }

                    if (reviewImagePreview) {
                        reviewImagePreview.innerHTML = "";
                    }

                } else {

                    showReviewMessage(
                        result.message ||
                        "Unable to submit review.",
                        "error"
                    );

                }

            } catch (error) {

                console.error(
                    "Review submission error:",
                    error
                );

                showReviewMessage(
                    "Something went wrong. Please try again.",
                    "error"
                );

            } finally {

                if (submitButton) {

                    submitButton.disabled = false;

                    submitButton.innerHTML =
                        originalText;

                }

            }

        });

    }


    /* ==============================================================
       REVIEW MESSAGE
    ============================================================== */

    function showReviewMessage(message, type) {

        let messageBox =
            document.querySelector(
                ".review-form-message"
            );

        if (!messageBox) {

            messageBox =
                document.createElement("div");

            messageBox.className =
                "review-form-message";

            if (reviewForm) {
                reviewForm.prepend(messageBox);
            }

        }

        messageBox.className =
            `review-form-message ${type}`;

        messageBox.innerHTML = `
            <i class="fa-solid ${
                type === "success"
                    ? "fa-circle-check"
                    : "fa-circle-exclamation"
            }"></i>

            <span>${message}</span>
        `;

        messageBox.scrollIntoView({
            behavior: "smooth",
            block: "nearest"
        });

        setTimeout(() => {

            messageBox.classList.add("fade-out");

        }, 4500);

    }


});