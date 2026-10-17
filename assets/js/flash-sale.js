const galleryImage = document.querySelector(".flash-product-image > img");
const galleryThumbnails = document.querySelectorAll(".flash-thumbnail");

if (galleryImage && galleryThumbnails.length) {
    galleryThumbnails.forEach((thumbnail) => {
        thumbnail.addEventListener("click", () => {
            galleryImage.src = thumbnail.dataset.gallerySrc;
            galleryImage.alt = thumbnail.dataset.galleryAlt;

            galleryThumbnails.forEach((item) => {
                const isSelected = item === thumbnail;
                item.classList.toggle("is-selected", isSelected);
                item.setAttribute("aria-pressed", String(isSelected));
            });
        });
    });
}
