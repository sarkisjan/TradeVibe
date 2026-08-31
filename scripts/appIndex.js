const massDelete = document.querySelector(".mass_delete");
const list = document.querySelector(".productList");

let checkedProductIds = [];
let allProducts = [];
let currentImgArray = [];
let currentImgIndex = 0;

fetch("reading.php", {
  method: "GET",
  headers: {
    Accept: "application/json",
    "X-Requested-With": "XMLHttpRequest",
  },
})
  .then((response) => {
    // ENFORCED PRODUCTION REFACTOR: Pulling clean JSON stream objects directly from backend layers
    if (!response.ok) {
      throw new Error("Failed to load products.");
    }
    return response.json();
  })
  .then((productsData) => {
    // Assign verified array data directly to your global application framework variable
    allProducts = productsData;
    // Trigger your native layout rendering loops and UI click event handlers dynamically
    filterAndRenderProducts();
    setupFilterEventListeners();
  })
  .catch((error) => {
    console.error("Error loading products:", error);
  });

function filterAndRenderProducts() {
  list.innerHTML = "";

  const activeSubcatEl = document.querySelector(".subcat-list li.active");

  const selectedCategory =
    window.activeSelectedCategoryTracker ||
    (activeSubcatEl
      ? activeSubcatEl.parentElement.getAttribute("data-category")
      : "all");

  const selectedSubcategory =
    window.activeSelectedSubcategoryTracker ||
    (activeSubcatEl ? activeSubcatEl.getAttribute("data-sub") : "all");

  const selectedBrand = document.getElementById("brandFilter")?.value || "all";

  const selectedColor = document.getElementById("colorFilter")?.value || "all";

  const selectedSize = document.getElementById("sizeFilter")?.value || "all";

  const selectedGender =
    document.getElementById("genderFilter")?.value || "all";

  const activeSort = document.getElementById("sortFilter")?.value || "default";

  let filtered = allProducts.filter((row) => {
    // Admin users can only view their own products
    if (
      currentUserRole === "admin" &&
      parseInt(row.admin_id) !== currentUserId
    ) {
      return false;
    }

    // If "global-all" is selected, skip category filtering
    // This allows products from multiple categories to be displayed together
    if (selectedSubcategory !== "global-all") {
      // Apply category and subcategory filters
      const rowCatClean = row.category ? row.category.trim().toLowerCase() : "";

      const selCatClean = selectedCategory
        ? selectedCategory.trim().toLowerCase()
        : "";

      const rowSubClean = row.subcategory
        ? row.subcategory.trim().toLowerCase()
        : "";

      const selSubClean = selectedSubcategory
        ? selectedSubcategory.trim().toLowerCase()
        : "";

      if (selectedSubcategory === "all") {
        if (rowCatClean !== selCatClean) return false;
      } else {
        if (rowCatClean !== selCatClean || rowSubClean !== selSubClean) {
          return false;
        }
      }

      // Apply furniture room filter
      const activeRoom = window.selectedFurnitureRoom || "all";

      if (selSubClean === "furniture" && activeRoom !== "all") {
        const rowRoomClean = row.furniture_room
          ? row.furniture_room.trim().toLowerCase()
          : "";

        const selRoomClean = activeRoom.trim().toLowerCase();

        if (rowRoomClean !== selRoomClean) return false;
      }
    }

    // Apply additional sidebar filters
    if (selectedBrand !== "all" && row.brand !== selectedBrand) return false;

    if (selectedColor !== "all" && row.color !== selectedColor) return false;

    if (selectedSize !== "all" && row.size_attr !== selectedSize) return false;

    if (selectedGender !== "all" && row.gender !== selectedGender) return false;

    return true;
  });

  // Sort products based on the selected option
  if (activeSort === "price-low") {
    filtered.sort(
      (a, b) =>
        parseFloat(a.raw_price_converted) - parseFloat(b.raw_price_converted),
    );
  } else if (activeSort === "price-high") {
    filtered.sort(
      (a, b) =>
        parseFloat(b.raw_price_converted) - parseFloat(a.raw_price_converted),
    );
  } else if (activeSort === "discount-high") {
    filtered.sort((a, b) => parseInt(b.discount) - parseInt(a.discount));
  }

  if (filtered.length === 0) {
    list.innerHTML = `<p class="no-products-alert">No products match the selected criteria.</p>`;
    return;
  }

  let html = "";

  // Render product cards
  for (const row of filtered) {
    let rawImageString =
      row.image && typeof row.image === "string" && row.image.trim() !== ""
        ? row.image.trim()
        : "default-product.png";

    // Get the primary image from the image list
    let imagesArray = rawImageString.split(",");

    let finalMainImage = imagesArray
      ? imagesArray[0].trim()
      : "default-product.png";

    let bgImageRoute =
      finalMainImage === "default-product.png"
        ? "default-product.png"
        : finalMainImage.startsWith("uploads/")
          ? finalMainImage
          : "uploads/" + finalMainImage;

    let specificationLi = "";
    const cardSub = row.subcategory ? row.subcategory.toLowerCase() : "";

    // Display product-specific information based on the subcategory
    if (
      [
        "furniture",
        "souvenirs",
        "gradina",
        "kujna",
        "tehnika",
        "bedding",
        "equipment",
      ].includes(cardSub)
    ) {
      specificationLi = `
        <li class="specDisplay spec-badge-dimensions">
          Dimensions: ${row.height}x${row.width}x${row.length} cm
        </li>`;
    } else if (["book", "supplements"].includes(cardSub)) {
      specificationLi = `
        <li class="specDisplay spec-badge-weight">
          Weight: ${row.weight} g
        </li>`;
    } else if (cardSub === "shoes") {
      specificationLi = `<li class="specDisplay spec-badge-shoes">Footwear</li>`;
    } else if (["clothing", "summer", "winter"].includes(cardSub)) {
      specificationLi = `<li class="specDisplay spec-badge-clothing">Apparel</li>`;
    }

    // Show discount badge if a discount is available
    let discountBadge =
      parseInt(row.discount) > 0
        ? `<span class="discount-tag">-${row.discount}% OFF</span>`
        : "";

    // Show bulk action checkbox only for admins
    let checkboxHtml =
      currentUserRole === "admin"
        ? `<input type="checkbox" class="delete-checkbox" value="${row.id}">`
        : "";

    // Show the appropriate cart action for customers
    let cartButtonHtml = "";

    if (currentUserRole === "user") {
      if (["shoes", "clothing", "summer", "winter"].includes(cardSub)) {
        cartButtonHtml = `<p class="size-hint-text">Click product image to select size</p>`;
      } else {
        cartButtonHtml = `<button class="add-to-cart-btn direct-add-btn" data-id="${row.id}">Add to Cart</button>`;
      }
    }

    // Show edit controls only for admins
    let editButtonHtml =
      currentUserRole === "admin"
        ? `<button class="edit-price-btn"
             data-id="${row.id}"
             data-price="${row.price_raw || row.price}"
             data-discount="${row.discount}">
             ⚙ Edit Price & Discount
           </button>`
        : "";

    // Append the generated product card HTML
    html += `
      <div class="productItem" id="product-card-${row.id}">
          ${checkboxHtml}
          ${discountBadge}

          <div class="productImageBg open-gallery-trigger"
               data-id="${row.id}"
               style="background-image: url('${bgImageRoute}'); cursor: zoom-in;">
              <img src="${bgImageRoute}"
                   alt="${row.name} - ${row.sku} TradeVibe Product"
                   class="seo-hidden-img"
                   style="display:none;">

              ${
                rawImageString === "default-product.png"
                  ? "<span class='no-image-text'>No Image</span>"
                  : ""
              }
          </div>

          <div class="productInfo">
              <ul class="aboutProduct">
                  <li class="skuDisplay">${row.sku}</li>
                  <li class="nameDisplay"><strong>${row.name}</strong></li>
                  <li class="priceDisplay">${row.display_price}</li>
                  ${specificationLi}
              </ul>

              ${cartButtonHtml}
              ${editButtonHtml}
          </div>
      </div>`;
  }

  list.innerHTML = html;

  setupCheckboxListeners();
}

// Handle Add to Cart button clicks
document.addEventListener("click", function (event) {
  if (event.target && event.target.classList.contains("add-to-cart-btn")) {
    const productId = event.target.getAttribute("data-id");

    // Determine whether this is a direct-add product.
    // Direct-add products do NOT require size selection.
    const isDirectAdd = event.target.classList.contains("direct-add-btn");

    const originButton = event.target;

    const sizeTrackerCheck = document.getElementById(
      "modalSelectedSizeTracker",
    );

    const sizeWrapperCheck = document.getElementById("modalSizeWrapper");

    let selectedSizeValue = "Standard";

    /*
     * IMPORTANT:
     * Direct-add products must completely bypass the modal size validation.
     *
     * The modal size wrapper may still be display:block in the DOM after
     * closing a previous size-based product modal. Therefore, checking only
     * modalSizeWrapper visibility would incorrectly force size selection
     * for products that do not require a size.
     */
    if (!isDirectAdd) {
      // Products added from the modal may require a size.
      if (
        sizeWrapperCheck &&
        window.getComputedStyle(sizeWrapperCheck).display === "block"
      ) {
        if (!sizeTrackerCheck || sizeTrackerCheck.value.trim() === "") {
          alert(
            "Please select an available size configuration before adding this product to your cart!",
          );
          return;
        }

        selectedSizeValue = sizeTrackerCheck.value.trim();
      } else {
        // Modal product without size configuration.
        selectedSizeValue = "Standard";
      }
    } else {
      // Direct-add products always use the Standard configuration.
      selectedSizeValue = "Standard";
    }

    const req = new XMLHttpRequest();

    req.open("POST", "cart_handler.php", true);
    req.setRequestHeader("Content-Type", "application/json");

    req.onreadystatechange = function () {
      if (this.readyState === 4 && this.status === 200) {
        try {
          const res = JSON.parse(this.response);

          if (res.success) {
            const counterElement = document.getElementById("cart-counter");

            const cartIconWrapper =
              document.querySelector(".cart-icon-wrapper");

            // Play the cart notification and show the flying animation
            if (isDirectAdd && cartIconWrapper) {
              const audioNotification = new Audio("https://mixkit.co");

              audioNotification.volume = 0.4;

              audioNotification.play().catch(() => {
                console.log("Audio notification could not be played.");
              });

              // Get the position of the button and cart icon
              const btnRect = originButton.getBoundingClientRect();

              const cartRect = cartIconWrapper.getBoundingClientRect();

              // Create the animation element at the button position
              const ball = document.createElement("div");

              ball.className = "flying-cart-ball";

              ball.style.top = `${btnRect.top + btnRect.height / 2 - 8}px`;

              ball.style.left = `${btnRect.left + btnRect.width / 2 - 8}px`;

              document.body.appendChild(ball);

              // Move the animation element toward the cart icon
              setTimeout(() => {
                ball.style.top = `${cartRect.top + cartRect.height / 2 - 8}px`;

                ball.style.left = `${cartRect.left + cartRect.width / 2 - 8}px`;

                ball.style.transform = "scale(0.2)";
              }, 40);

              // Update the cart counter and animate the cart icon
              setTimeout(() => {
                if (counterElement) {
                  counterElement.innerText = res.cart_count;
                }

                cartIconWrapper.classList.add("cart-pulse-active");

                ball.remove();

                setTimeout(() => {
                  cartIconWrapper.classList.remove("cart-pulse-active");
                }, 400);
              }, 700);
            } else {
              if (counterElement) {
                counterElement.innerText = res.cart_count;
              }

              const viewModalToClose =
                document.getElementById("productViewModal");

              if (viewModalToClose) {
                viewModalToClose.style.display = "none";
              }

              // Reset the selected modal size after successful modal add.
              if (sizeTrackerCheck) {
                sizeTrackerCheck.value = "";
              }
            }
          } else {
            alert(res.message || "Unable to add the product to the cart.");
          }
        } catch (error) {
          console.error("Invalid server response:", this.response);
          console.error(error);

          alert("An unexpected server response was received.");
        }
      }
    };

    req.send(
      JSON.stringify({
        action: "add",
        product_id: productId,
        size: selectedSizeValue,
      }),
    );
  }
});

// Product view modal elements
const viewModal = document.getElementById("productViewModal");
const closeViewModalBtn = document.getElementById("closeViewModalBtn");
const frame = document.getElementById("modalImageFrame");
const prevBtn = document.getElementById("prevImgBtn");
const nextBtn = document.getElementById("nextImgBtn");

// Open the product gallery when a product image is clicked
document.addEventListener("click", function (e) {
  if (e.target && e.target.classList.contains("open-gallery-trigger")) {
    const prodId = e.target.getAttribute("data-id");

    const product = allProducts.find((p) => p.id == prodId);

    if (product) {
      const sizeTracker = document.getElementById("modalSelectedSizeTracker");

      if (sizeTracker) {
        sizeTracker.value = "";
      }

      currentImgArray = product.image
        ? product.image.split(",")
        : ["default-product.png"];

      currentImgIndex = 0;

      updateModalImage();

      document.getElementById("modalSku").innerText = "SKU: " + product.sku;

      document.getElementById("modalName").innerText = product.name;

      document.getElementById("modalPrice").innerHTML = product.display_price;

      let specsHtml = "";

      const sub = product.subcategory ? product.subcategory.toLowerCase() : "";

      // Display product specifications based on the subcategory
      if (
        [
          "furniture",
          "souvenirs",
          "gradina",
          "kujna",
          "tehnika",
          "bedding",
          "equipment",
        ].includes(sub)
      ) {
        specsHtml = `
          <span class="spec-badge-dimensions">
            Dimensions: ${product.height}x${product.width}x${product.length} cm
          </span>`;
      } else if (["book", "supplements"].includes(sub)) {
        specsHtml = `
          <span class="spec-badge-weight">
            Weight: ${product.weight} g
          </span>`;
      } else if (sub === "shoes") {
        specsHtml = `
          <span class="spec-badge-shoes">
            Category: Footwear / Shoes
          </span>`;
      } else if (["clothing", "summer", "winter"].includes(sub)) {
        specsHtml = `
          <span class="spec-badge-clothing">
            Category: Apparel / Clothing
          </span>`;
      }

      document.getElementById("modalSpecs").innerHTML = specsHtml;

      const descMenu = document.getElementById("modalDescription");

      const descWrapper = document.getElementById("modalDescWrapper");

      const arrow = document.getElementById("descArrow");

      descMenu.style.display = "none";
      arrow.innerText = "▼";

      // Show the description section only when a description exists
      if (product.description && product.description.trim() !== "") {
        descWrapper.style.display = "block";
        descMenu.innerText = product.description;
      } else {
        descWrapper.style.display = "none";
      }

      // Build the stock table for admin users
      const sellerStockSection = document.getElementById(
        "modalSellerStockSection",
      );

      const stockTableContainer = document.getElementById(
        "modalStockMatrixTableContainer",
      );

      if (sellerStockSection && stockTableContainer) {
        // Show the stock table only for admin users
        if (currentUserRole === "admin") {
          sellerStockSection.style.display = "block";
          stockTableContainer.innerHTML = "";

          let tableHtml = `
            <table class="stock-matrix-table">
              <thead>
                <tr>`;

          if (
            ["shoes", "clothing", "summer", "winter"].includes(sub) &&
            product.stock_summary
          ) {
            // Products with size-based inventory
            tableHtml += `
              <th>Size Configuration</th>
              <th>Current Qty</th>
              <th>Add New Pieces</th>
              <th>Action</th>
              </tr>
              </thead>
              <tbody>`;

            const stockItems = product.stock_summary.split(",");

            stockItems.forEach((item) => {
              const parts = item.split(":");
              const sizeName = parts[0];
              const sizeQty = parseInt(parts[1], 10) || 0;

              tableHtml += `
                <tr>
                  <td>
                    <span class="size-badge-history">
                      ${sizeName}
                    </span>
                  </td>

                  <td>
                    <strong>${sizeQty} pcs</strong>
                  </td>

                  <td>
                    <input
                      type="number"
                      min="1"
                      placeholder="+ Qty"
                      class="stock-add-input"
                      id="add_qty_input_${product.id}_${sizeName}"
                    >
                  </td>

                  <td>
                    <button
                      class="btn-add-stock-submit"
                      onclick="executeStockUpdatePHP(${product.id}, '${sizeName}')">
                      Add
                    </button>
                  </td>
                </tr>`;
            });
          } else {
            // Products without size-based inventory
            const totalGeneralQty = intval(product.total_qty);

            tableHtml += `
              <th>General Specifications</th>
              <th>Current Qty</th>
              <th>Add New Pieces</th>
              <th>Action</th>
              </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Default Standard Configuration</td>

                  <td>
                    <strong>${totalGeneralQty} pcs</strong>
                  </td>

                  <td>
                    <input
                      type="number"
                      min="1"
                      placeholder="+ Qty"
                      class="stock-add-input"
                      id="add_qty_input_${product.id}_Standard"
                    >
                  </td>

                  <td>
                    <button
                      class="btn-add-stock-submit"
                      onclick="executeStockUpdatePHP(${product.id}, 'Standard')">
                      Add
                    </button>
                  </td>
                </tr>`;
          }

          tableHtml += `
              </tbody>
            </table>`;

          stockTableContainer.innerHTML = tableHtml;
        } else {
          sellerStockSection.style.display = "none";
        }
      }

      // Prepare the size selector for customers
      const sizeWrapper = document.getElementById("modalSizeWrapper");

      const sizesContainer = document.getElementById("modalSizesContainer");

      if (sizesContainer) {
        sizesContainer.innerHTML = "";
      }

      if (
        sizeWrapper &&
        sizesContainer &&
        product.stock_summary &&
        product.stock_summary.trim() !== "" &&
        ["shoes", "clothing", "summer", "winter"].includes(sub)
      ) {
        sizeWrapper.style.display = "block";

        const stockItems = product.stock_summary.split(",");

        // Show the product availability information
        const totalInventoryPieces = intval(product.total_qty);

        if (totalInventoryPieces === 0) {
          document.getElementById("modalPrice").innerHTML =
            `<span class="modal-out-of-stock-price">OUT OF STOCK</span>`;

          const actionBtnZone = document.getElementById("modalActionContainer");

          if (actionBtnZone && currentUserRole === "user") {
            actionBtnZone.innerHTML = `
              <button
                class="btn btn-secondary"
                disabled
                style="width:100%; cursor:not-allowed;">
                Unavailable
              </button>`;
          }
        } else {
          document.getElementById("modalPrice").innerHTML =
            product.display_price;
        }

        // Create or reset the last-piece notification
        let noticeSpan = document.getElementById("modalLastPieceNoticeTracker");

        if (!noticeSpan) {
          noticeSpan = document.createElement("div");
          noticeSpan.id = "modalLastPieceNoticeTracker";

          sizesContainer.parentElement.appendChild(noticeSpan);
        }

        noticeSpan.innerText = "";
        noticeSpan.className = "";

        // Create size selection buttons
        stockItems.forEach((item) => {
          const parts = item.split(":");
          const sizeName = parts[0];
          const sizeQty = parseInt(parts[1], 10) || 0;

          const sizeBox = document.createElement("button");

          sizeBox.innerText = sizeName;
          sizeBox.className = "modal-size-box-item";

          sizeBox.setAttribute("data-size", sizeName);

          if (sizeQty === 0) {
            // Disable sizes that are out of stock
            sizeBox.classList.add("out-of-stock");
          } else if (sizeQty === 1) {
            // Highlight the last available item
            sizeBox.classList.add("last-piece");

            sizeBox.addEventListener("click", function () {
              document
                .querySelectorAll(".modal-size-box-item")
                .forEach((box) => box.classList.remove("selected"));

              this.classList.add("selected");

              if (sizeTracker) {
                sizeTracker.value = sizeName;
              }

              // Show a warning when only one item remains
              noticeSpan.className = "last-piece-notice-text";

              noticeSpan.innerText =
                "⚠ Last piece available! Order quickly before it sells out.";
            });
          } else {
            // Handle normal in-stock sizes
            sizeBox.addEventListener("click", function () {
              document
                .querySelectorAll(".modal-size-box-item")
                .forEach((box) => box.classList.remove("selected"));

              this.classList.add("selected");

              if (sizeTracker) {
                sizeTracker.value = sizeName;
              }

              noticeSpan.innerText = "";
              noticeSpan.className = "";
            });
          }

          sizesContainer.appendChild(sizeBox);
        });
      } else if (sizeWrapper) {
        sizeWrapper.style.display = "none";
      }

      // Show the appropriate modal action based on the user role
      const actionContainer = document.getElementById("modalActionContainer");

      if (actionContainer) {
        if (currentUserRole === "user") {
          actionContainer.innerHTML = `
            <button
              class="add-to-cart-btn dashboard-modal-cart-btn"
              data-id="${product.id}">
              Add to Cart
            </button>`;
        } else {
          actionContainer.innerHTML = `
            <span class="modal-viewing-status">
              Viewing as ${currentUserRole}
            </span>`;
        }
      }

      if (viewModal) {
        viewModal.style.display = "flex";
      }
    }
  }
});

// Update the main image displayed in the product modal
function updateModalImage() {
  if (!frame || !currentImgArray.length) {
    return;
  }

  const imgName = currentImgArray[currentImgIndex];

  const path =
    imgName === "default-product.png" ? imgName : `uploads/${imgName}`;

  frame.style.backgroundImage = `url('${path}')`;

  // Show navigation buttons only when multiple images exist
  if (currentImgArray.length <= 1) {
    if (prevBtn) prevBtn.style.display = "none";
    if (nextBtn) nextBtn.style.display = "none";
  } else {
    if (prevBtn) prevBtn.style.display = "block";
    if (nextBtn) nextBtn.style.display = "block";
  }
}

// Handle previous image navigation
if (prevBtn) {
  prevBtn.addEventListener("click", () => {
    currentImgIndex =
      currentImgIndex === 0 ? currentImgArray.length - 1 : currentImgIndex - 1;

    updateModalImage();
  });
}

// Handle next image navigation
if (nextBtn) {
  nextBtn.addEventListener("click", () => {
    currentImgIndex =
      currentImgIndex === currentImgArray.length - 1 ? 0 : currentImgIndex + 1;

    updateModalImage();
  });
}

// Toggle the product description
const toggleDescBtn = document.getElementById("toggleDescBtn");

if (toggleDescBtn) {
  toggleDescBtn.addEventListener("click", () => {
    const descMenu = document.getElementById("modalDescription");

    const arrow = document.getElementById("descArrow");

    if (!descMenu || !arrow) {
      return;
    }

    if (descMenu.style.display === "block") {
      descMenu.style.display = "none";
      arrow.innerText = "▼";
    } else {
      descMenu.style.display = "block";
      arrow.innerText = "▲";
    }
  });
}

// Close the product view modal
if (closeViewModalBtn) {
  closeViewModalBtn.addEventListener("click", () => {
    if (viewModal) {
      viewModal.style.display = "none";
    }
  });
}

// Set up product filter event listeners
function setupFilterEventListeners() {
  document.querySelectorAll(".subcat-list li").forEach((li) => {
    li.addEventListener("click", function () {
      document
        .querySelectorAll(".subcat-list li")
        .forEach((el) => el.classList.remove("active"));

      this.classList.add("active");

      filterAndRenderProducts();
    });
  });

  [
    "brandFilter",
    "colorFilter",
    "sizeFilter",
    "genderFilter",
    "sortFilter",
  ].forEach((id) => {
    document
      .getElementById(id)
      ?.addEventListener("change", filterAndRenderProducts);
  });

  // Reset all product filters
  document.getElementById("resetFiltersBtn")?.addEventListener("click", () => {
    document
      .querySelectorAll(".subcat-list li")
      .forEach((el) => el.classList.remove("active"));

    // Select all products after resetting the filters
    const globalAllLi = document.querySelector('[data-sub="global-all"]');

    if (globalAllLi) {
      globalAllLi.classList.add("active");
    }

    // Reset all dropdown filters
    ["brandFilter", "colorFilter", "sizeFilter", "genderFilter"].forEach(
      (id) => {
        const el = document.getElementById(id);

        if (el) {
          el.value = "all";
        }
      },
    );

    // Reset the price sorting option
    const sortEl = document.getElementById("sortFilter");

    if (sortEl) {
      sortEl.value = "default";
    }

    // Reset the furniture room filter
    window.selectedFurnitureRoom = "all";

    document
      .querySelectorAll(".room-dropdown-menu li")
      .forEach((el) => el.classList.remove("active-room"));

    filterAndRenderProducts();
  });
}

// Set up delete checkbox listeners
function setupCheckboxListeners() {
  const checkboxes = document.querySelectorAll(".delete-checkbox");

  checkboxes.forEach((checkbox) => {
    if (checkedProductIds.includes(checkbox.value)) {
      checkbox.checked = true;
    }

    checkbox.addEventListener("change", function () {
      const val = this.value;

      if (this.checked) {
        if (!checkedProductIds.includes(val)) {
          checkedProductIds.push(val);
        }
      } else {
        checkedProductIds = checkedProductIds.filter((id) => id !== val);
      }
    });
  });
}

// Handle bulk product deletion
if (massDelete) {
  massDelete.addEventListener("click", function () {
    if (checkedProductIds.length === 0) {
      alert("Please select at least one product to delete.");
      return;
    }

    const req = new XMLHttpRequest();

    req.open("POST", "deleting.php", true);

    req.setRequestHeader("Content-Type", "application/json");

    req.onreadystatechange = function () {
      if (this.readyState === 4 && this.status === 200) {
        window.location.href = "index.php";
      }
    };

    req.send(
      JSON.stringify({
        mass_delete: true,
        id: checkedProductIds,
      }),
    );
  });
}

// Convert a value to a floating-point number
function floatval(val) {
  return parseFloat(val) || 0;
}

// Convert a value to an integer
function intval(val) {
  return parseInt(val, 10) || 0;
}

// Open the edit price modal
document.addEventListener("click", function (e) {
  if (e.target && e.target.classList.contains("edit-price-btn")) {
    const prodId = e.target.getAttribute("data-id");

    const currentPrice = e.target.getAttribute("data-price");

    const currentDiscount = e.target.getAttribute("data-discount");

    const productIdInput = document.getElementById("edit_modal_product_id");

    const priceInput = document.getElementById("edit_modal_price");

    const discountInput = document.getElementById("edit_modal_discount");

    if (productIdInput && priceInput && discountInput) {
      productIdInput.value = prodId;

      priceInput.value = parseFloat(currentPrice) || 0;

      discountInput.value = parseInt(currentDiscount, 10) || 0;
    }

    const modal = document.getElementById("editPriceModal");

    if (modal) {
      modal.style.display = "flex";
    }
  }
});

// Close the edit price modal
document.getElementById("closeEditModalBtn")?.addEventListener("click", () => {
  const modal = document.getElementById("editPriceModal");

  if (modal) {
    modal.style.display = "none";
  }
});

// Save the updated product price and discount
const savePriceBtn = document.getElementById("savePriceBtn");

if (savePriceBtn) {
  savePriceBtn.addEventListener("click", function () {
    const prodId = document.getElementById("edit_modal_product_id").value;

    const newPrice = document.getElementById("edit_modal_price").value;

    const newDiscount = document.getElementById("edit_modal_discount").value;

    if (
      newPrice === "" ||
      Number(newPrice) < 0 ||
      newDiscount === "" ||
      Number(newDiscount) < 0
    ) {
      alert(
        "Please enter valid, non-negative values for price and discount fields!",
      );
      return;
    }

    const req = new XMLHttpRequest();

    req.open("POST", "update_price.php", true);

    req.setRequestHeader("Content-Type", "application/json");

    req.onreadystatechange = function () {
      if (this.readyState === 4 && this.status === 200) {
        const res = JSON.parse(this.response);

        if (res.success) {
          window.location.reload();
        } else {
          alert("Server Error: " + res.message);
        }
      }
    };

    req.send(
      JSON.stringify({
        action: "quick_edit",
        product_id: parseInt(prodId, 10),
        price: parseFloat(newPrice),
        discount: parseInt(newDiscount, 10),
      }),
    );
  });
}

// Make the stock update function globally available
// because it is called from dynamically generated HTML
window.executeStockUpdatePHP = function (productId, sizeName) {
  const inputElement = document.getElementById(
    `add_qty_input_${productId}_${sizeName}`,
  );

  const addedAmount = parseInt(inputElement ? inputElement.value : 0, 10);

  if (isNaN(addedAmount) || addedAmount <= 0) {
    alert(
      "Please enter a valid number of pieces to add to the inventory system!",
    );
    return;
  }

  // Send the stock update request to the server
  fetch("update_stock_matrix.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
    body: JSON.stringify({
      product_id: parseInt(productId, 10),
      size: sizeName,
      quantity_to_add: addedAmount,
    }),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error("Stock update request failed.");
      }

      return response.json();
    })
    .then((res) => {
      if (res.success) {
        alert("Inventory updated successfully.");

        window.location.reload();
      } else {
        alert("Server Error: " + res.message);
      }
    })
    .catch((error) => {
      console.error("Stock update error:", error);

      alert("Failed to synchronize the inventory with the server.");
    });
};

// Handle currency selection changes
document.addEventListener("DOMContentLoaded", function () {
  const currencyDropdownElement = document.getElementById("currencySelect");

  if (currencyDropdownElement) {
    currencyDropdownElement.addEventListener("change", function () {
      window.location.href = this.value;
    });
  }
});
// Handle sidebar, category, furniture, and profile menu actions
document.addEventListener("DOMContentLoaded", () => {
  // Set "All Products Marketplace" as the default active filter
  window.activeSelectedCategoryTracker = "all";
  window.activeSelectedSubcategoryTracker = "global-all";
  window.selectedFurnitureRoom = "all";

  // Remove the active state from all category items
  document
    .querySelectorAll(".subcat-list li")
    .forEach((li) => li.classList.remove("active"));

  // Set only "All Products Marketplace" as active
  const allProductsItem = document.querySelector(
    '.subcat-list li[data-sub="global-all"]',
  );

  if (allProductsItem) {
    allProductsItem.classList.add("active");
  }
  const hamburgerBtn = document.getElementById("hamburgerBtn");
  const closeMenuBtn = document.getElementById("closeMenuBtn");
  const sidebarMenu = document.getElementById("sidebarMenu");
  const menuOverlay = document.getElementById("menuOverlay");

  const profileBtn = document.getElementById("profileBtn");
  const profileMenu = document.getElementById("profileMenu");

  // Open and close the hamburger sidebar
  if (hamburgerBtn && sidebarMenu && menuOverlay) {
    hamburgerBtn.addEventListener("click", () => {
      sidebarMenu.classList.add("active");
      menuOverlay.classList.add("active");
    });

    const closeSidebar = () => {
      sidebarMenu.classList.remove("active");
      menuOverlay.classList.remove("active");
    };

    if (closeMenuBtn) closeMenuBtn.addEventListener("click", closeSidebar);
    menuOverlay.addEventListener("click", closeSidebar);
  }

  // Handle clicks on regular product categories
  document.querySelectorAll(".subcat-list > li").forEach((li) => {
    li.addEventListener("click", function (e) {
      // Ignore clicks inside the furniture room menu
      if (
        e.target.closest(".room-dropdown-menu") ||
        this.id === "furnitureParentLi"
      )
        return;

      // Remove active states from other categories
      document
        .querySelectorAll(".subcat-list li")
        .forEach((el) => el.classList.remove("active"));
      document
        .querySelectorAll(".room-dropdown-menu li")
        .forEach((el) => el.classList.remove("active-room"));

      // Store the selected category and subcategory
      window.selectedFurnitureRoom = "all";
      window.activeSelectedSubcategoryTracker = this.getAttribute("data-sub");
      window.activeSelectedCategoryTracker =
        this.parentElement.getAttribute("data-category");

      this.classList.add("active");

      // Update the product list
      if (typeof filterAndRenderProducts === "function") {
        filterAndRenderProducts();
      }

      // Close the sidebar after selecting a category
      if (sidebarMenu && menuOverlay) {
        sidebarMenu.classList.remove("active");
        menuOverlay.classList.remove("active");
      }
    });
  });

  // Handle furniture room selection
  document.querySelectorAll(".room-dropdown-menu li").forEach((roomLi) => {
    roomLi.addEventListener("click", function (e) {
      // Prevent the parent Furniture item from being triggered
      e.stopPropagation();

      // Update the selected room
      document
        .querySelectorAll(".room-dropdown-menu li")
        .forEach((el) => el.classList.remove("active-room"));
      this.classList.add("active-room");

      // Store the selected furniture filters
      window.selectedFurnitureRoom = this.getAttribute("data-room");
      window.activeSelectedSubcategoryTracker = "Furniture";
      window.activeSelectedCategoryTracker = "Home & Garden";

      // Set Furniture as the active category
      document
        .querySelectorAll(".subcat-list li")
        .forEach((el) => el.classList.remove("active"));
      document.getElementById("furnitureParentLi").classList.add("active");

      // Update the product list
      if (typeof filterAndRenderProducts === "function") {
        filterAndRenderProducts();
      }

      // Close the sidebar after selecting a room
      if (sidebarMenu && menuOverlay) {
        sidebarMenu.classList.remove("active");
        menuOverlay.classList.remove("active");
      }
    });
  });

  // Open and close the profile menu
  if (profileBtn && profileMenu) {
    profileBtn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      profileMenu.style.display =
        profileMenu.style.display === "block" ? "none" : "block";
    });
    // Close the profile menu when clicking outside it
    document.addEventListener("click", (e) => {
      if (!profileMenu.contains(e.target) && e.target !== profileBtn) {
        profileMenu.style.display = "none";
      }
    });
  }
});
