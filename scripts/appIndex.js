const massDelete = document.querySelector(".mass_delete");
const list = document.querySelector(".productList");

let checkedProductIds = [];
let allProducts = [];
let currentImgArray = [];
let currentImgIndex = 0;

const xhttp = new XMLHttpRequest();
xhttp.open("GET", "reading.php", true);
xhttp.setRequestHeader("Content-Type", "application/json");
xhttp.onreadystatechange = function () {
  if (this.readyState == 4 && this.status == 200) {
    allProducts = JSON.parse(this.response);
    filterAndRenderProducts();
    setupFilterEventListeners();
  }
};
xhttp.send();

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
    // АКО Е ПРОДАНАВАЧ: Секогаш ги гледа само сопствените производи, независно од филтрите
    if (currentUserRole === "admin" && parseInt(row.admin_id) !== currentUserId)
      return false;

    // КРИТИЧЕН ФИКС: Ако е селектирано "global-all", ги прескокнуваме филтрите за категории
    // со што овозможуваме Home & Garden и Sports да се прикажат заедно на екранот!
    if (selectedSubcategory !== "global-all") {
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
        if (rowCatClean !== selCatClean || rowSubClean !== selSubClean)
          return false;
      }

      const activeRoom = window.selectedFurnitureRoom || "all";
      if (selSubClean === "furniture" && activeRoom !== "all") {
        const rowRoomClean = row.furniture_room
          ? row.furniture_room.trim().toLowerCase()
          : "";
        const selRoomClean = activeRoom.trim().toLowerCase();
        if (rowRoomClean !== selRoomClean) return false;
      }
    }

    // СТАНДАРДНИ СТРАНИЧНИ ПАЃАЧКИ МЕНИЈА (Овие филтри продолжуваат да важат и за All Products!)
    if (selectedBrand !== "all" && row.brand !== selectedBrand) return false;
    if (selectedColor !== "all" && row.color !== selectedColor) return false;
    if (selectedSize !== "all" && row.size_attr !== selectedSize) return false;
    if (selectedGender !== "all" && row.gender !== selectedGender) return false;

    return true;
  });

  // СОРТИРАЊЕ НА ЦЕНИТЕ И ПОПУСТИТЕ
  if (activeSort === "price-low") {
    filtered.sort(
      (a, b) =>
        floatval(a.raw_price_converted) - floatval(b.raw_price_converted),
    );
  } else if (activeSort === "price-high") {
    filtered.sort(
      (a, b) =>
        floatval(b.raw_price_converted) - floatval(a.raw_price_converted),
    );
  } else if (activeSort === "discount-high") {
    filtered.sort((a, b) => intval(b.discount) - intval(a.discount));
  }

  if (filtered.length === 0) {
    list.innerHTML = `<p class="no-products-alert">No products match the selected criteria.</p>`;
    return;
  }

  // ЦРТАЊЕ НА ЕЛЕГАНТНИТЕ КАРТИЧКИ ЗА ПРОИЗВОДИТЕ
  for (let row of filtered) {
    let rawImageString =
      row.image && typeof row.image === "string" && row.image.trim() !== ""
        ? row.image.trim()
        : "default-product.png";
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
      specificationLi = `<li class="specDisplay spec-badge-dimensions">Dimensions: ${row.height}x${row.width}x${row.length} cm</li>`;
    } else if (["book", "supplements"].includes(cardSub)) {
      specificationLi = `<li class="specDisplay spec-badge-weight">Weight: ${row.weight} g</li>`;
    } else if (cardSub === "shoes") {
      specificationLi = `<li class="specDisplay spec-badge-shoes">Footwear</li>`;
    } else if (["clothing", "summer", "winter"].includes(cardSub)) {
      specificationLi = `<li class="specDisplay spec-badge-clothing">Apparel</li>`;
    }

    let discountBadge =
      intval(row.discount) > 0
        ? `<span class="discount-tag">-${row.discount}% OFF</span>`
        : "";
    let checkboxHtml =
      currentUserRole === "admin"
        ? `<input type="checkbox" class="delete-checkbox" value="${row.id}">`
        : "";

    let cartButtonHtml = "";
    if (currentUserRole === "user") {
      if (["shoes", "clothing", "summer", "winter"].includes(cardSub)) {
        cartButtonHtml = `<p class="size-hint-text">Click product image to select size</p>`;
      } else {
        cartButtonHtml = `<button class='add-to-cart-btn direct-add-btn' data-id='${row.id}'>Add to Cart</button>`;
      }
    }

    let editButtonHtml =
      currentUserRole === "admin"
        ? `<button class="edit-price-btn" data-id="${row.id}" data-price="${row.price_raw || row.price}" data-discount="${row.discount}">⚙ Edit Price & Discount</button>`
        : "";

    list.innerHTML += `
      <div class="productItem" id="product-card-${row.id}">
          ${checkboxHtml}
          ${discountBadge}
          <div class="productImageBg open-gallery-trigger" data-id="${row.id}" style="background-image: url('${bgImageRoute}'); cursor: zoom-in;">
              <img src="${bgImageRoute}" alt="${row.name} - ${row.sku} TradeVibe Product" class="seo-hidden-img" style="display:none;">
              ${rawImageString === "default-product.png" ? "<span class='no-image-text'>No Image</span>" : ""}
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
  setupCheckboxListeners();
}

// ==========================================================================
// 3. НАСТАН ЗА ADD TO CART СО ОНЛАЈН ЗВУК И ЛЕТЕЧКА АНИМАЦИЈА
// ==========================================================================
document.addEventListener("click", function (event) {
  if (event.target && event.target.classList.contains("add-to-cart-btn")) {
    const productId = event.target.getAttribute("data-id");

    const sizeTrackerCheck = document.getElementById(
      "modalSelectedSizeTracker",
    );
    const sizeWrapperCheck = document.getElementById("modalSizeWrapper");

    if (
      sizeWrapperCheck &&
      window.getComputedStyle(sizeWrapperCheck).display === "block"
    ) {
      if (sizeTrackerCheck && sizeTrackerCheck.value.trim() === "") {
        alert(
          "Please select an available size configuration before adding this product to your cart!",
        );
        return;
      }
    }

    let selectedSizeValue = sizeTrackerCheck
      ? sizeTrackerCheck.value
      : "Standard";

    const isDirectAdd = event.target.classList.contains("direct-add-btn");
    let originButton = event.target;

    if (isDirectAdd) {
      selectedSizeValue = "Standard";
    }

    const req = new XMLHttpRequest();
    req.open("POST", "cart_handler.php", true);
    req.setRequestHeader("Content-Type", "application/json");

    req.onreadystatechange = function () {
      if (this.readyState == 4 && this.status == 200) {
        const res = JSON.parse(this.response);
        if (res.success) {
          const counterElement = document.getElementById("cart-counter");
          const cartIconWrapper = document.querySelector(".cart-icon-wrapper");

          if (isDirectAdd && cartIconWrapper) {
            // А) АКТИВИРАЊЕ НА ДИСКРЕТЕН Е-КОМЕРЦ ЗВУК
            const audioNotification = new Audio("https://mixkit.co");
            audioNotification.volume = 0.4;
            audioNotification
              .play()
              .catch((err) =>
                console.log("Audio badge alert preview blocked."),
              );

            // Б) МАКСИМАЛНО ПРЕЦИЗНА МАТЕМАТИКА ЗА КОРДИНАТИТЕ НА ЕКРАНОТ
            const btnRect = originButton.getBoundingClientRect();
            const cartRect = cartIconWrapper.getBoundingClientRect();

            // Креирање на топчето точно на почетната позиција на кликнатото копче
            const ball = document.createElement("div");
            ball.className = "flying-cart-ball";
            ball.style.top = `${btnRect.top + btnRect.height / 2 - 8}px`;
            ball.style.left = `${btnRect.left + btnRect.width / 2 - 8}px`;
            document.body.appendChild(ball);

            // В) АПСОЛУТЕН ЛЕТ: Му кажуваме на пребарувачот во истата секунда да го испушти топчето до кошничката
            setTimeout(() => {
              ball.style.top = `${cartRect.top + cartRect.height / 2 - 8}px`;
              ball.style.left = `${cartRect.left + cartRect.width / 2 - 8}px`;
              ball.style.transform = "scale(0.2)"; // Се смалува додека лета
            }, 40);

            // Г) КРАЕН ЕФЕКТ: Кога топчето ќе удри, бројката се зголемува и иконата пулсира
            setTimeout(() => {
              if (counterElement) counterElement.innerText = res.cart_count;
              cartIconWrapper.classList.add("cart-pulse-active");
              ball.remove();

              setTimeout(() => {
                cartIconWrapper.classList.remove("cart-pulse-active");
              }, 400);
            }, 700); // Летот трае точно 700 милисекунди
          } else {
            if (counterElement) counterElement.innerText = res.cart_count;
            const viewModalToClose =
              document.getElementById("productViewModal");
            if (viewModalToClose) viewModalToClose.style.display = "none";
          }
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

const viewModal = document.getElementById("productViewModal");
const closeViewModalBtn = document.getElementById("closeViewModalBtn");
const frame = document.getElementById("modalImageFrame");
const prevBtn = document.getElementById("prevImgBtn");
const nextBtn = document.getElementById("nextImgBtn");

document.addEventListener("click", function (e) {
  if (e.target && e.target.classList.contains("open-gallery-trigger")) {
    const prodId = e.target.getAttribute("data-id");
    const product = allProducts.find((p) => p.id == prodId);

    if (product) {
      const sizeTracker = document.getElementById("modalSelectedSizeTracker");
      if (sizeTracker) sizeTracker.value = "";

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
        specsHtml = `<span class="spec-badge-dimensions">Dimensions: ${product.height}x${product.width}x${product.length} cm</span>`;
      } else if (["book", "supplements"].includes(sub)) {
        specsHtml = `<span class="spec-badge-weight">Weight: ${product.weight} g</span>`;
      } else if (sub === "shoes") {
        specsHtml = `<span class="spec-badge-shoes">Category: Footwear / Shoes</span>`;
      } else if (["clothing", "summer", "winter"].includes(sub)) {
        specsHtml = `<span class="spec-badge-clothing">Category: Apparel / Clothing</span>`;
      }

      document.getElementById("modalSpecs").innerHTML = specsHtml;

      const descMenu = document.getElementById("modalDescription");
      const descWrapper = document.getElementById("modalDescWrapper");
      const arrow = document.getElementById("descArrow");

      descMenu.style.display = "none";
      arrow.innerText = "▼";

      if (product.description && product.description.trim() !== "") {
        descWrapper.style.display = "block";
        descMenu.innerText = product.description;
      } else {
        descWrapper.style.display = "none";
      }

      // ==========================================================================
      // ДИНАМИЧНО ГЕНЕРИРАЊЕ НА ИНВЕНТАРНАТА ТАБЕЛА ЗА ПРОДАНАВАЧОТ
      // ==========================================================================
      const sellerStockSection = document.getElementById(
        "modalSellerStockSection",
      );
      const stockTableContainer = document.getElementById(
        "modalStockMatrixTableContainer",
      );

      if (sellerStockSection && stockTableContainer) {
        // Оваа табела ќе се појави САМО ако на екранот е логиран Продавач (admin)
        if (currentUserRole === "admin") {
          sellerStockSection.style.display = "block";
          stockTableContainer.innerHTML = ""; // Чистење на претходната состојба

          let tableHtml = `<table class="stock-matrix-table"><thead><tr>`;

          if (
            ["shoes", "clothing", "summer", "winter"].includes(sub) &&
            product.stock_summary
          ) {
            // А) АКО ПРОДУКТОТ ИМА ГОЛЕМИНА (Патики, Облека)
            tableHtml += `<th>Size Configuration</th><th>Current Qty</th><th>Add New Pieces</th><th>Action</th></tr></thead><tbody>`;

            const stockItems = product.stock_summary.split(",");
            stockItems.forEach((item) => {
              const parts = item.split(":");
              const sizeName = parts[0];
              const sizeQty = parseInt(parts[1]) || 0;

              tableHtml += `
                            <tr>
                                <td><span class="size-badge-history">${sizeName}</span></td>
                                <td><strong>${sizeQty} pcs</strong></td>
                                <td><input type="number" min="1" placeholder="+ Qty" class="stock-add-input" id="add_qty_input_${product.id}_${sizeName}"></td>
                                <td><button class="btn-add-stock-submit" onclick="executeStockUpdatePHP(${product.id}, '${sizeName}')">Add</button></td>
                            </tr>`;
            });
          } else {
            // Б) АКО ПРОДУКТОТ НЕМА ГОЛЕМИНА (Мебел, Книги, Суплементи)
            // Го земаме вкупниот збир на сите парчиња од базата
            let totalGeneralQty = intval(product.total_qty);

            tableHtml += `<th>General Specifications</th><th>Current Qty</th><th>Add New Pieces</th><th>Action</th></tr></thead><tbody>
                        <tr>
                            <td>Default Standard Configuration</td>
                            <td><strong>${totalGeneralQty} pcs</strong></td>
                            <td><input type="number" min="1" placeholder="+ Qty" class="stock-add-input" id="add_qty_input_${product.id}_Standard"></td>
                            <td><button class="btn-add-stock-submit" onclick="executeStockUpdatePHP(${product.id}, 'Standard')">Add</button></td>
                        </tr>`;
          }

          tableHtml += `</tbody></table>`;
          stockTableContainer.innerHTML = tableHtml;
        } else {
          sellerStockSection.style.display = "none";
        }
      }
      // =======================================

      const sizeWrapper = document.getElementById("modalSizeWrapper");
      const sizesContainer = document.getElementById("modalSizesContainer");
      sizesContainer.innerHTML = "";

      if (
        product.stock_summary &&
        product.stock_summary.trim() !== "" &&
        ["shoes", "clothing", "summer", "winter"].includes(sub)
      ) {
        sizeWrapper.style.display = "block";
        const stockItems = product.stock_summary.split(",");

        // ==========================================================================
        // ТОЧНА ЗАМЕНА НА ВТОРИОТ ЦИКЛУС (ПРИКАЗОТ ЗА КУПУВАЧОТ ВО МОДАЛОТ)
        // ==========================================================================

        // 1. Ажурирање на цената доколку продуктот е целосно распродаден
        const totalInventoryPieces = intval(product.total_qty);
        if (totalInventoryPieces === 0) {
          document.getElementById("modalPrice").innerHTML =
            `<span class="modal-out-of-stock-price">OUT OF STOCK</span>`;
          const actionBtnZone = document.getElementById("modalActionContainer");
          if (actionBtnZone && currentUserRole === "user") {
            actionBtnZone.innerHTML = `<button class="btn btn-secondary" disabled style="width:100%; cursor:not-allowed;">Unavailable</button>`;
          }
        } else {
          document.getElementById("modalPrice").innerHTML =
            product.display_price;
        }

        // Креираме или чистиме тракер за пораки веднаш под коцките за броеви
        let noticeSpan = document.getElementById("modalLastPieceNoticeTracker");
        if (!noticeSpan) {
          noticeSpan = document.createElement("div");
          noticeSpan.id = "modalLastPieceNoticeTracker";
          sizesContainer.parentElement.appendChild(noticeSpan);
        }
        noticeSpan.innerText = "";
        noticeSpan.className = "";

        // СТАРТ НА ВТОРИОТ Специфичен Специјален ForEach
        stockItems.forEach((item) => {
          const parts = item.split(":");
          const sizeName = parts[0];
          const sizeQty = parseInt(parts[1]) || 0;

          let sizeBox = document.createElement("button");
          sizeBox.innerText = sizeName;
          sizeBox.className = "modal-size-box-item";
          sizeBox.setAttribute("data-size", sizeName);

          if (sizeQty === 0) {
            // А) АКО Е 0: Станува неактивна, сива и пречкртана
            sizeBox.classList.add("out-of-stock");
          } else if (sizeQty === 1) {
            // Б) АКО Е ТОЧНО 1 ПАРЧЕ: Станува црвена и анимирана
            sizeBox.classList.add("last-piece");

            sizeBox.addEventListener("click", function () {
              document
                .querySelectorAll(".modal-size-box-item")
                .forEach((box) => box.classList.remove("selected"));
              this.classList.add("selected");
              sizeTracker.value = sizeName;

              // Го испишуваме бараниот ургентен англиски текст
              noticeSpan.className = "last-piece-notice-text";
              noticeSpan.innerText =
                "⚠ Last piece available! Order quickly before it sells out.";
            });
          } else {
            // В) СТАНДАРДНА ЗАЛИХА: Нормално кликање без известување
            sizeBox.addEventListener("click", function () {
              document
                .querySelectorAll(".modal-size-box-item")
                .forEach((box) => box.classList.remove("selected"));
              this.classList.add("selected");
              sizeTracker.value = sizeName;

              noticeSpan.innerText = "";
              noticeSpan.className = "";
            });
          }
          sizesContainer.appendChild(sizeBox);
        });
      } else {
        sizeWrapper.style.display = "none";
      }

      const actionContainer = document.getElementById("modalActionContainer");
      if (currentUserRole === "user") {
        // ПОПРАВЕНО: Вметнати точни бекстикови за Add to Cart копчето
        actionContainer.innerHTML = `<button class="add-to-cart-btn dashboard-modal-cart-btn" data-id="${product.id}">Add to Cart</button>`;
      } else {
        actionContainer.innerHTML = `<span class="modal-viewing-status">Viewing as ${currentUserRole}</span>`;
      }

      viewModal.style.display = "flex";
    }
  }
});

function updateModalImage() {
  let imgName = currentImgArray[currentImgIndex];
  let path = imgName === "default-product.png" ? imgName : `uploads/${imgName}`;
  frame.style.backgroundImage = `url('${path}')`;

  if (currentImgArray.length <= 1) {
    prevBtn.style.display = "none";
    nextBtn.style.display = "none";
  } else {
    prevBtn.style.display = "block";
    nextBtn.style.display = "block";
  }
}

if (prevBtn) {
  prevBtn.addEventListener("click", () => {
    currentImgIndex =
      currentImgIndex === 0 ? currentImgArray.length - 1 : currentImgIndex - 1;
    updateModalImage();
  });
}

if (nextBtn) {
  nextBtn.addEventListener("click", () => {
    currentImgIndex =
      currentImgIndex === currentImgArray.length - 1 ? 0 : currentImgIndex + 1;
    updateModalImage();
  });
}

const toggleDescBtn = document.getElementById("toggleDescBtn");
if (toggleDescBtn) {
  toggleDescBtn.addEventListener("click", () => {
    const descMenu = document.getElementById("modalDescription");
    const arrow = document.getElementById("descArrow");
    if (descMenu.style.display === "block") {
      descMenu.style.display = "none";
      arrow.innerText = "▼";
    } else {
      descMenu.style.display = "block";
      arrow.innerText = "▲";
    }
  });
}

if (closeViewModalBtn) {
  closeViewModalBtn.addEventListener("click", () => {
    viewModal.style.display = "none";
  });
}

function setupFilterEventListeners() {
  document.querySelectorAll(".subcat-list li").forEach((li) => {
    li.addEventListener("click", function () {
      document
        .querySelectorAll(".subcat-list li")
        .forEach((el) => el.classList.remove("active"));
      if (this.getAttribute("data-sub") !== "all") this.classList.add("active");
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

  document.getElementById("resetFiltersBtn")?.addEventListener("click", () => {
    // 1. Ги чистиме сите активни класи од подкатегориите
    document
      .querySelectorAll(".subcat-list li")
      .forEach((el) => el.classList.remove("active"));

    // 2. ПАМЕТЕН ФИКС: Кога сè се ресетира, со JavaScript принудно му ја ставаме класата 'active'
    // на нашето ново знаменце за сите производи веднаш да светне како активно!
    const globalAllLi = document.querySelector('[data-sub="global-all"]');
    if (globalAllLi) {
      globalAllLi.classList.add("active");
    }

    // 3. Ресетирање на сите паѓачки менија (dropdown селектори)
    ["brandFilter", "colorFilter", "sizeFilter", "genderFilter"].forEach(
      (id) => {
        const el = document.getElementById(id);
        if (el) el.value = "all";
      },
    );

    // 4. Ресетирање на филтерот за сортирање на цени
    const sortEl = document.getElementById("sortFilter");
    if (sortEl) sortEl.value = "default";

    // 5. Чистење на релационите соби во мебелот
    window.selectedFurnitureRoom = "all";
    document
      .querySelectorAll(".room-dropdown-menu li")
      .forEach((el) => el.classList.remove("active-room"));

    // 6. ФИНАЛЕН БАЈПАС: Ја повикуваме централната функција со нашиот нов глобален клуч
    filterAndRenderProducts("global-all");
  });
}

function setupCheckboxListeners() {
  const checkboxes = document.querySelectorAll(".delete-checkbox");
  checkboxes.forEach((checkbox) => {
    if (checkedProductIds.includes(checkbox.value)) checkbox.checked = true;
    checkbox.addEventListener("change", function () {
      const val = this.value;
      if (this.checked) {
        if (!checkedProductIds.includes(val)) checkedProductIds.push(val);
      } else {
        checkedProductIds = checkedProductIds.filter((id) => id !== val);
      }
    });
  });
}

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
      if (this.readyState == 4 && this.status == 200)
        window.location.href = "index.php";
    };
    req.send(JSON.stringify({ mass_delete: true, id: checkedProductIds }));
  });
}

function floatval(val) {
  return parseFloat(val) || 0;
}
// Најпоследната заграда совршено ја затвора скриптата
function intval(val) {
  return parseInt(val) || 0;
}
document.addEventListener("click", function (e) {
  if (e.target && e.target.classList.contains("edit-price-btn")) {
    const prodId = e.target.getAttribute("data-id");
    const currentPrice = e.target.getAttribute("data-price");
    const currentDiscount = e.target.getAttribute("data-discount");

    document.getElementById("edit_modal_product_id").value = prodId;
    document.getElementById("edit_modal_price").value =
      parseFloat(currentPrice) || 0;
    document.getElementById("edit_modal_discount").value =
      parseInt(currentDiscount) || 0;

    const modal = document.getElementById("editPriceModal");
    if (modal) modal.style.display = "flex";
  }
});
// Настан за затворање на прозорецот при клик на Cancel
document.getElementById("closeEditModalBtn")?.addEventListener("click", () => {
  const modal = document.getElementById("editPriceModal");
  if (modal) modal.style.display = "none";
});
// ФИКСИРАНО ИСПРАЌАЊЕ НА НОВАТА ЦЕНА И ПОПУСТ (SAVE CHANGES)
const savePriceBtn = document.getElementById("savePriceBtn");
if (savePriceBtn) {
  savePriceBtn.addEventListener("click", function () {
    const prodId = document.getElementById("edit_modal_product_id").value;
    const newPrice = document.getElementById("edit_modal_price").value;
    const newDiscount = document.getElementById("edit_modal_discount").value;

    if (
      newPrice === "" ||
      newPrice < 0 ||
      newDiscount === "" ||
      newDiscount < 0
    ) {
      alert(
        "Please enter valid, non-negative values for price and discount fields!",
      );
      return;
    }

    const req = new XMLHttpRequest();
    // ПОПРАВЕНО: Чиста и прецизна патека до бекенд PHP обработувачот
    req.open("POST", "update_price.php", true);
    req.setRequestHeader("Content-Type", "application/json");

    req.onreadystatechange = function () {
      if (this.readyState == 4 && this.status == 200) {
        const res = JSON.parse(this.response);
        if (res.success) {
          window.location.reload(); // Успех: веднаш ја освежуваме страницата!
        } else {
          alert("Server Error: " + res.message);
        }
      }
    };

    req.send(
      JSON.stringify({
        action: "quick_edit",
        product_id: parseInt(prodId),
        price: parseFloat(newPrice),
        discount: parseInt(newDiscount),
      }),
    );
  });
}
// ПОПРАВЕНО: Го додаваме window. за да ја извадиме функцијата во глобалниот систем на Chrome!
window.executeStockUpdatePHP = function (productId, sizeName) {
  const inputElement = document.getElementById(
    `add_qty_input_${productId}_${sizeName}`,
  );
  const addedAmount = parseInt(inputElement ? inputElement.value : 0);

  if (isNaN(addedAmount) || addedAmount <= 0) {
    alert(
      "Please enter a valid number of pieces to add to the inventory system!",
    );
    return;
  }

  const xhttp = new XMLHttpRequest();
  xhttp.open("POST", "update_stock_matrix.php", true);
  xhttp.setRequestHeader("Content-Type", "application/json");

  xhttp.onreadystatechange = function () {
    if (this.readyState == 4 && this.status == 200) {
      const res = JSON.parse(this.response);
      if (res.success) {
        alert("Inventory updated successfully inside the warehouse!");
        window.location.reload();
      } else {
        alert("Matrix Protection Alert: " + res.message);
      }
    }
  };

  xhttp.send(
    JSON.stringify({
      product_id: parseInt(productId),
      size: sizeName,
      quantity_to_add: addedAmount,
    }),
  );
};
