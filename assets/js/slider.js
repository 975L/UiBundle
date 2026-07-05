/*
 * (c) 2024: 975L <contact@975l.com>
 * (c) 2024: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        const sliderId = this.element.dataset.sliderId;
        if (sliderId) {
            this.slideIndex = 1;
            this.isPlaying = false;
            this.createLiveRegion(sliderId);
            this.preloadSliderImages(sliderId);
            this.initializeSlider(sliderId);
            this.resizeSlider(sliderId);
            this.setupAccessibility(sliderId);
            this.setupTouchGestures(sliderId);
            this.startAutoPlay(sliderId);
        }
    }

    disconnect() {
        if (this.autoPlayInterval) {
            clearInterval(this.autoPlayInterval);
        }
    }

    // Create ARIA live region for announcements
    createLiveRegion(sliderId) {
        const carousel = document.querySelector(`#${sliderId}`);
        let liveRegion = carousel.querySelector(".slider-liveregion");

        if (!liveRegion) {
            liveRegion = document.createElement("div");
            liveRegion.setAttribute("aria-live", "polite");
            liveRegion.setAttribute("aria-atomic", "true");
            liveRegion.className = "slider-liveregion visuallyhidden";
            carousel.appendChild(liveRegion);
        }
    }

    // Announce slide change to screen readers
    announceSlide(sliderId, current, total) {
        const liveRegion = document.querySelector(`#${sliderId} .slider-liveregion`);
        if (liveRegion) {
            liveRegion.textContent = `Item ${current} of ${total}`;
        }
    }

    // preloadSliderImages
    preloadSliderImages(sliderId) {
        const slides = document.querySelectorAll(`#${sliderId} .slider-item img`);

        slides.forEach((img, index) => {
            if (index === 0) {
                img.loading = "eager";
            } else {
                const preloadImg = new Image();
                preloadImg.src = img.src;
                preloadImg.onload = () => {
                    img.loading = "eager";
                    img.classList.add("preloaded");
                };
            }
        });
    }

    // setupAccessibility
    setupAccessibility(sliderId) {
        const carousel = document.querySelector(`#${sliderId}`);

        // Pause on mouse hover
        carousel.addEventListener("mouseenter", () => this.suspendAnimation());
        carousel.addEventListener("mouseleave", () => this.resumeAnimation(sliderId));

        // Pause on keyboard focus
        carousel.addEventListener("focusin", (e) => {
            if (!e.target.classList.contains("slider-item")) {
                this.suspendAnimation();
            }
        });
        carousel.addEventListener("focusout", (e) => {
            if (!e.target.classList.contains("slider-item")) {
                this.resumeAnimation(sliderId);
            }
        });

        // Play/Pause button
        const playPauseBtn = carousel.querySelector(".slider-play-pause");
        if (playPauseBtn) {
            playPauseBtn.addEventListener("click", () => this.togglePlayPause(sliderId, playPauseBtn));
        }
    }

    // Touch gestures: swipe left/right to navigate, press-and-hold to pause.
    // A plain tap (released quickly, without moving) falls through to the existing
    // "click on slide" listener set up in initializeSlider(), which advances to the next slide.
    setupTouchGestures(sliderId) {
        const longPressDelay = 500;
        const swipeThreshold = 50;
        const items = document.querySelectorAll(`#${sliderId} .slider-item`);

        items.forEach((item) => {
            let startX = 0;
            let startY = 0;
            let longPressTimer = null;
            let isLongPress = false;
            let isSwipe = false;

            item.addEventListener("touchstart", (e) => {
                const touch = e.touches[0];
                startX = touch.clientX;
                startY = touch.clientY;
                isLongPress = false;
                isSwipe = false;

                longPressTimer = setTimeout(() => {
                    isLongPress = true;
                    this.suspendAnimation();
                }, longPressDelay);
            }, { passive: true });

            item.addEventListener("touchmove", (e) => {
                const touch = e.touches[0];
                const deltaX = touch.clientX - startX;
                const deltaY = touch.clientY - startY;

                if (!isSwipe && Math.abs(deltaX) > swipeThreshold && Math.abs(deltaX) > Math.abs(deltaY)) {
                    isSwipe = true;
                    clearTimeout(longPressTimer);
                }

                if (isSwipe) {
                    e.preventDefault();
                }
            }, { passive: false });

            item.addEventListener("touchend", (e) => {
                clearTimeout(longPressTimer);

                if (isSwipe) {
                    e.preventDefault();
                    const deltaX = e.changedTouches[0].clientX - startX;
                    if (deltaX < 0) {
                        this.displaySlide(sliderId, ++this.slideIndex, "next");
                    } else {
                        this.displaySlide(sliderId, --this.slideIndex, "prev");
                    }
                } else if (isLongPress) {
                    e.preventDefault();
                    this.resumeAnimation(sliderId);
                }
            }, { passive: false });

            item.addEventListener("touchcancel", () => {
                clearTimeout(longPressTimer);
                if (isLongPress) {
                    this.resumeAnimation(sliderId);
                }
            });
        });
    }

    suspendAnimation() {
        if (this.autoPlayInterval) {
            clearInterval(this.autoPlayInterval);
            this.autoPlayInterval = null;
        }
    }

    resumeAnimation(sliderId) {
        if (this.isPlaying && !this.autoPlayInterval) {
            this.startAutoPlay(sliderId);
        }
    }

    togglePlayPause(sliderId, button) {
        const action = button.getAttribute("data-action");

        if (action === "stop") {
            this.suspendAnimation();
            this.isPlaying = false;
            button.setAttribute("data-action", "start");
            button.setAttribute("aria-label", button.getAttribute("aria-label").replace("Stop", "Start").replace("Arrêter", "Démarrer").replace("Detener", "Iniciar"));
            button.innerHTML = '<span aria-hidden="true">▶</span>';
        } else {
            this.isPlaying = true;
            this.startAutoPlay(sliderId);
            button.setAttribute("data-action", "stop");
            button.setAttribute("aria-label", button.getAttribute("aria-label").replace("Start", "Stop").replace("Démarrer", "Arrêter").replace("Iniciar", "Detener"));
            button.innerHTML = '<span aria-hidden="true">⏸</span>';
        }
    }

    // initializeSlider
    initializeSlider(sliderId) {
        const prevBtn = document.querySelector(`#${sliderId} .slider-prev`);
        const nextBtn = document.querySelector(`#${sliderId} .slider-next`);

        if (!prevBtn || !nextBtn) {
            return;
        }

        // Display the first slide
        this.displaySlide(sliderId, this.slideIndex, "none");

        // Previous slide
        prevBtn.addEventListener("click", () => {
            this.displaySlide(sliderId, --this.slideIndex, "prev");
        });

        // Next slide
        nextBtn.addEventListener("click", () => {
            this.displaySlide(sliderId, ++this.slideIndex, "next");
        });

        // Click on slide to go next
        const slides = document.querySelectorAll(`#${sliderId} .slider-item`);
        slides.forEach((slide) => {
            slide.addEventListener("click", () => {
                this.displaySlide(sliderId, ++this.slideIndex, "next");
            });
        });

        // Navigation dots
        const dots = document.querySelectorAll(`#${sliderId} .slider-dot`);
        dots.forEach((dot) => {
            dot.addEventListener("click", () => {
                const targetSlide = parseInt(dot.getAttribute("data-slide"), 10) + 1;
                const direction = targetSlide > this.slideIndex ? "next" : "prev";
                this.displaySlide(sliderId, targetSlide, direction);
            });
        });
    }

    // resizeSlider
    resizeSlider(sliderId) {
        const slider = document.querySelector(`#${sliderId}`);
        const slides = document.querySelectorAll(`#${sliderId} .slider-item`);

        if (slides.length === 0) {
            return;
        }

        // A fixed ratio (CSS aspect-ratio, via a "slider-ratio-*" class) already sizes the slider.
        // This JS height fallback is only needed in free/natural ratio mode, where slides are
        // absolutely positioned and stacked, so the container can't size itself from content alone.
        const hasFixedRatio = Array.from(slider.classList).some((c) => c.startsWith("slider-ratio-"));
        if (hasFixedRatio) {
            return;
        }

        const images = Array.from(slides)
            .map((slide) => slide.querySelector("img"))
            .filter((img) => img !== null);

        // Sets the slider height to the tallest image scaled to the slider's width, using the
        // width/height HTML attributes (Media entity dimensions) which are known synchronously -
        // no waiting on image load events, which used to grow the slider after the fact and shift
        // bottom-anchored text/credits down. "slider-sized" then lets every slide - including
        // smaller images - fill that height and get cropped via object-fit: cover.
        const applyMaxHeight = () => {
            const width = slider.clientWidth;
            const maxHeight = images.reduce((max, img) => {
                const naturalWidth = img.naturalWidth || img.width;
                const naturalHeight = img.naturalHeight || img.height;
                if (!naturalWidth || !naturalHeight) {
                    return max;
                }
                return Math.max(max, naturalHeight * (width / naturalWidth));
            }, 0);

            if (maxHeight > 0) {
                slider.style.height = `${maxHeight}px`;
                slider.classList.add("slider-sized");
            }
        };

        applyMaxHeight();

        // Fallback for medias missing stored width/height: refine once the real image loads
        images.forEach((img) => {
            if (!img.getAttribute("width") || !img.getAttribute("height")) {
                img.addEventListener("load", applyMaxHeight, { once: true });
            }
        });

        // Recalculates height in case of resizing the window, waits that resize is finished to avoid multiple calculations
        let resizeTimeout;
        window.addEventListener("resize", () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(applyMaxHeight, 300);
        });
    }

    // Display slide with ARIA support
    displaySlide(sliderId, number, direction = "next", announceChange = true) {
        const slides = document.querySelectorAll(`#${sliderId} .slider-item`);
        const dots = document.querySelectorAll(`#${sliderId} .slider-dot`);

        if (slides.length === 0) {
            return;
        }

        // Calculate correct index
        const index = this.calculateIndex(number, slides.length);

        // Find current active slide
        const currentSlide = Array.from(slides).find((slide) => slide.style.display === "block");
        const newSlide = slides[index - 1];

        // Remove animation classes
        slides.forEach((slide) => {
            slide.classList.remove("slide-in-right", "slide-in-left", "slide-out-right", "slide-out-left");
        });

        // Manage ARIA attributes
        slides.forEach((slide, idx) => {
            if (idx === index - 1) {
                slide.removeAttribute("aria-hidden");
            } else {
                slide.setAttribute("aria-hidden", "true");
            }
        });

        // Add animations if not initial display
        if (currentSlide && currentSlide !== newSlide && direction !== "none") {
            if (direction === "next") {
                currentSlide.classList.add("slide-out-left");
                newSlide.classList.add("slide-in-right");
            } else {
                currentSlide.classList.add("slide-out-right");
                newSlide.classList.add("slide-in-left");
            }

            setTimeout(() => {
                currentSlide.style.display = "none";
            }, 500);
        } else if (currentSlide && currentSlide !== newSlide) {
            currentSlide.style.display = "none";
        }

        // Update dots
        dots.forEach((dot, idx) => {
            if (idx === index - 1) {
                dot.classList.add("current", "active");
                dot.setAttribute("aria-label", dot.getAttribute("aria-label").replace(/\(.*?\)/, "") + " (current)");
            } else {
                dot.classList.remove("current", "active");
                dot.setAttribute("aria-label", dot.getAttribute("aria-label").replace(/\s*\(.*?\)/, ""));
            }
        });

        // Display new slide
        newSlide.style.display = "block";

        // Announce change
        if (announceChange && direction !== "none") {
            this.announceSlide(sliderId, index, slides.length);
        }

        // Update state
        this.slideIndex = index;
    }

    // Helper method to calculate valid index
    calculateIndex(number, length) {
        if (number > length) return 1;
        if (number < 1) return length;
        return number;
    }

    // Auto-play
    startAutoPlay(sliderId) {
        const duration = parseInt(this.element.dataset.sliderDuration, 10);

        if (!duration || duration <= 0) {
            return;
        }

        this.isPlaying = true;

        this.autoPlayInterval = setInterval(() => {
            this.slideIndex++;
            this.displaySlide(sliderId, this.slideIndex, "next", true);
        }, duration);
    }
}
