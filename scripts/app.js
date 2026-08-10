// ==========================================================================
// ПОДАТОЦИ ЗА ПОДКАТЕГОРИИ
// ==========================================================================
const subcategoriesData = {
  "Home & Garden": [
    {
      value: "Souvenirs",
      text: "Souvenirs & Decor",
      inputType: "dimensions",
      brandGroup: "generic",
    },
    {
      value: "Furniture",
      text: "Furniture",
      inputType: "dimensions",
      brandGroup: "furniture",
    },
    {
      value: "Balcony",
      text: "Balcony & Garden",
      inputType: "dimensions",
      brandGroup: "furniture",
    },
    {
      value: "Kitchen",
      text: "Kitchen Appliances",
      inputType: "dimensions",
      brandGroup: "appliances",
    },
    {
      value: "Appliances",
      text: "White Goods / Appliances",
      inputType: "dimensions",
      brandGroup: "appliances",
    },
    {
      value: "Bedding",
      text: "Bedding",
      inputType: "dimensions",
      brandGroup: "generic",
    },
  ],
  "Sports & Recreation": [
    {
      value: "Supplements",
      text: "Supplements (Vitamins, Proteins etc.)",
      inputType: "weight",
      brandGroup: "supplements",
    },
    {
      value: "Book",
      text: "Books",
      inputType: "weight",
      brandGroup: "generic",
    },
    {
      value: "Clothing",
      text: "Sports Clothing",
      inputType: "sizes",
      brandGroup: "sports",
    },
    {
      value: "Equipment",
      text: "Workout Equipment",
      inputType: "dimensions",
      brandGroup: "sports",
    },
    {
      value: "Shoes",
      text: "Footwear / Shoes",
      inputType: "footwear",
      brandGroup: "sports",
    },
    {
      value: "Summer",
      text: "Seasonal: Swimwear & Beach",
      inputType: "sizes",
      brandGroup: "sports",
    },
    {
      value: "Winter",
      text: "Seasonal: Skiing Equipment",
      inputType: "sizes",
      brandGroup: "sports",
    },
  ],
};

// 2. БРЕНД МАТРИЦА СО СИТЕ НОВИ ПОБАРАНИ БРЕНДОВИ
const brandsData = {
  sports: ["Nike", "Adidas", "Puma", "Hummel", "Kappa"],
  furniture: ["Ikea", "Treska", "Formanova", "Jela"],
  supplements: [
    "Optimum Nutrition",
    "MuscleTech",
    "BioTech",
    "Scitec",
    "Ultimate Nutrition",
    "Allmax",
    "NowFood",
    "HerbaLife",
    "Natural Wealth",
    "MyProtein",
  ],
  appliances: ["Samsung", "Whirlpool", "Beko", "Indesit", "LG"],
  generic: ["Generic / No Brand"],
};

// Селектирање на сите клучни HTML елементи од add.php преку document.getElementById (Најсигурно)
const categorySelect = document.getElementById("category");
const subcategorySelect = document.getElementById("subcategory");
const brandSelect = document.getElementById("brand");
const otherBrandSection = document.getElementById("other_brand_section");
const otherBrandInput = document.getElementById("other_brand");
const form = document.getElementById("product_form");
const furnitureSection = document.getElementById("furniture_type_section");
const furnitureRoomInput = document.getElementById("furniture_room");

// Овие два елементи селектираат класи, па затоа го задржуваат querySelector во еден примерок
const shown_form_inputs = document.querySelector(".shown_form_inputs");

// Помошна изолирана функција за следење на чекбоксовите за залиха
function setupDynamicStockListeners() {
  document.querySelectorAll(".stock-size-cb").forEach((cb) => {
    cb.addEventListener("change", function () {
      const sizeVal = this.value;
      const qtyInput = document.querySelector(
        `.stock-size-qty[data-size="${sizeVal}"]`,
      );
      if (qtyInput) {
        qtyInput.disabled = !this.checked;
        if (!this.checked) qtyInput.value = 0;
      }
    });
  });
}

// ==========================================================================
// 3. НАСТАН 1: ПОЛНЕНЕ И ИНСТАНТНО ОТКЛУЧУВАЊЕ НА ПОДКАТЕГОРИИТЕ
// ==========================================================================
if (categorySelect) {
  categorySelect.addEventListener("change", function () {
    const selectedCat = this.value;

    // Ресетирање на сите наредни полиња при промена на главната категорија
    subcategorySelect.innerHTML =
      '<option value="">-- Select Subcategory --</option>';
    subcategorySelect.disabled = true;

    if (brandSelect) {
      brandSelect.innerHTML =
        '<option value="">-- Select Subcategory First --</option>';
      brandSelect.disabled = true;
    }
    if (otherBrandSection) otherBrandSection.style.display = "none";
    if (shown_form_inputs) shown_form_inputs.innerHTML = "";
    if (furnitureSection) furnitureSection.style.display = "none";

    // Ако корисникот избрал валидна категорија, ВЕДНАШ ја одблокираме подкатегоријата
    if (selectedCat && subcategoriesData[selectedCat]) {
      subcategorySelect.disabled = false;

      subcategoriesData[selectedCat].forEach((sub) => {
        let opt = document.createElement("option");
        opt.value = sub.value;
        opt.text = sub.text;

        // КРИТИЧЕН ФИКС: Ги закопуваме дата атрибутите за наредниот чекор да ги прочита без грешка
        opt.setAttribute("data-input", sub.inputType);
        opt.setAttribute("data-brandgroup", sub.brandGroup);
        subcategorySelect.appendChild(opt);
      });
    }
  });
}

// ==========================================================================
// 4. НАСТАН 2: ИНТЕЛИГЕНТНО АКТИВИРАЊЕ НА БРЕНДОВИТЕ И ДИНАМИЧНИТЕ ИНПУТИ
// ==========================================================================
if (subcategorySelect) {
  subcategorySelect.addEventListener("change", function () {
    const selectedOption = this.options[this.selectedIndex];

    // Ако се избере празна опција, ги сокриваме и заклучуваме брендовите и полињата
    if (!selectedOption || this.value === "") {
      if (brandSelect) {
        brandSelect.disabled = true;
        brandSelect.innerHTML =
          '<option value="">-- Select Subcategory First --</option>';
      }
      if (shown_form_inputs) shown_form_inputs.innerHTML = "";
      if (furnitureSection) furnitureSection.style.display = "none";
      return;
    }

    // Ги читаме зачуваните дата атрибути од избраната опција
    const inputType = selectedOption.getAttribute("data-input");
    const brandGroup = selectedOption.getAttribute("data-brandgroup");

    if (shown_form_inputs) shown_form_inputs.innerHTML = "";

    // ОТКЛУЧУВАЊЕ И ПОЛНЕНЕ НА БРЕНДОВИТЕ ВО РЕАЛНО ВРЕМЕ
    if (brandSelect) {
      brandSelect.disabled = false;
      brandSelect.innerHTML =
        '<option value="">-- Select Brand / Manufacturer --</option>';

      if (brandGroup && brandsData[brandGroup]) {
        brandsData[brandGroup].forEach((b) => {
          let bOpt = document.createElement("option");
          bOpt.value = b;
          bOpt.text = b;
          brandSelect.appendChild(bOpt);
        });
      }

      // Опција за рачно внесување бренд
      let otherOpt = document.createElement("option");
      otherOpt.value = "Other";
      otherOpt.text = "Other / Not on list";
      brandSelect.appendChild(otherOpt);
    }

    // КРИТИЧЕН ФИКС: Приказ на соби САМО ако подкатегоријата е точно Мебел (Furniture)
    if (furnitureSection) {
      if (this.value === "Furniture") {
        furnitureSection.style.display = "block";
        if (furnitureRoomInput) furnitureRoomInput.required = true;
      } else {
        furnitureSection.style.display = "none";
        if (furnitureRoomInput) {
          furnitureRoomInput.required = false;
          furnitureRoomInput.value = "";
        }
      }
    }

    // ГЕНЕРИРАЊЕ НА ИНПУТИ СПОРЕД КЛАСИТЕ ВО STYLES.CSS
    if (shown_form_inputs) {
      if (inputType === "dimensions") {
        shown_form_inputs.innerHTML = `
            <div class="dynamic-section">
                <div class="form-item"><label for="height">Height (cm)</label><input id="height" type="number" name="height" required><span class="error-msg" id="heightError"></span></div>
                <div class="form-item"><label for="width">Width (cm)</label><input id="width" type="number" name="width" required><span class="error-msg" id="widthError"></span></div>
                <div class="form-item"><label for="length">Length (cm)</label><input id="length" type="number" name="length" required><span class="error-msg" id="lengthError"></span></div>
            </div>`;
      } else if (inputType === "weight") {
        shown_form_inputs.innerHTML = `
            <div class="dynamic-section">
                <div class="form-item"><label for="weight">Weight (g)</label><input id="weight" type="number" name="weight" required><span class="error-msg" id="weightError"></span></div>
            </div>`;
      } else if (inputType === "sizes") {
        let textSizes = ["XS", "S", "M", "L", "XL", "XXL", "XXXL"];
        let html = `<div class="stock-grid-container"><h4>Select Available Apparel Sizes & Stock</h4><div class="stock-grid-layout">`;
        textSizes.forEach((s) => {
          html += `<div class="stock-item-box"><input type="checkbox" class="stock-size-cb" value="${s}"><span>${s}:</span><input type="number" class="stock-size-qty" data-size="${s}" min="0" value="0" disabled></div>`;
        });
        html += `</div></div>`;
        shown_form_inputs.innerHTML = html;
        setupDynamicStockListeners();
      } else if (inputType === "footwear") {
        let shoeSizes = [
          "35",
          "36",
          "37",
          "38",
          "39",
          "40",
          "41",
          "42",
          "43",
          "44",
          "45",
          "46",
        ];
        let html = `<div class="stock-grid-container"><h4>Select Available Footwear Sizes (EU Standard) & Stock</h4><div class="stock-grid-layout">`;
        shoeSizes.forEach((s) => {
          html += `<div class="stock-item-box"><input type="checkbox" class="stock-size-cb" value="${s}"><span>EU ${s}:</span><input type="number" class="stock-size-qty" data-size="${s}" min="0" value="0" disabled></div>`;
        });
        html += `</div></div>`;
        shown_form_inputs.innerHTML = html;
        setupDynamicStockListeners();
      }
    }
  });
}

// 1. КОНТРОЛА ЗА ПРИКАЖУВАЊЕ НА РАЧНО ВНЕСУВАЊЕ БРЕНД ПРИ ИЗБОР НА "OTHER"
if (brandSelect) {
  brandSelect.addEventListener("change", function () {
    if (this.value === "Other") {
      if (otherBrandSection) otherBrandSection.style.display = "block";
      if (otherBrandInput) otherBrandInput.required = true;
    } else {
      if (otherBrandSection) otherBrandSection.style.display = "none";
      if (otherBrandInput) {
        otherBrandInput.required = false;
        otherBrandInput.value = "";
      }
    }
  });
}

// ==========================================================================
// 2. НАСТАН 3: БЕЗБЕДНО ПАКУВАЊЕ И ИСПРАЌАЊЕ НА ФОРМАТА ДО BACKEND
// ==========================================================================
if (form) {
  form.addEventListener("submit", function (e) {
    e.preventDefault();
    document
      .querySelectorAll(".error-msg, .error")
      .forEach((el) => (el.innerText = ""));

    let finalBrand = brandSelect ? brandSelect.value : "";
    if (finalBrand === "Other") {
      finalBrand = otherBrandInput ? otherBrandInput.value.trim() : "";
      if (finalBrand === "") {
        const errSpan = document.querySelector("#otherBrandError");
        if (errSpan) errSpan.innerText = "Please type the brand name.";
        return;
      }
    }

    // Собирање на големините и количините за залиха
    let stockInventoryData = [];
    document.querySelectorAll(".stock-size-cb:checked").forEach((cb) => {
      const sizeName = cb.value;

      // ПОПРАВЕНО: Се користи точниот селектор со БЕКСТИКОВИ за да не пука скриптата
      const qtyInput = document.querySelector(
        `.stock-size-qty[data-size="${sizeName}"]`,
      );
      const qtyValue = qtyInput ? parseInt(qtyInput.value) || 0 : 0;

      stockInventoryData.push({ size: sizeName, qty: qtyValue });
    });

    // Сите вредности се заштитени за да спречиме крах на екранот
    let data = {
      productSave: true,
      sku: document.querySelector("#sku")?.value || "",
      name: document.querySelector("#name")?.value || "",
      description: document.querySelector("#description")
        ? document.querySelector("#description").value
        : "",
      price: document.querySelector("#price")?.value || 0,
      discount: document.querySelector("#discount")?.value || 0,
      category: categorySelect ? categorySelect.value : "",
      subcategory: subcategorySelect ? subcategorySelect.value : "",
      brand: finalBrand,
      color: document.querySelector("#color")?.value || "",
      gender: document.querySelector("#gender")?.value || "Unisex",
      size: 0,
      weight: document.querySelector("#weight")?.value || 0,
      height: document.querySelector("#height")?.value || 0,
      width: document.querySelector("#width")?.value || 0,
      length: document.querySelector("#length")?.value || 0,
      stockData: stockInventoryData,
      furniture_room: document.querySelector("#furniture_room")?.value || "",
    };

    const xhttp = new XMLHttpRequest();
    xhttp.open("POST", "adding.php", true);

    xhttp.onreadystatechange = function () {
      if (this.readyState == 4 && this.status == 200) {
        var errors = JSON.parse(this.response);

        for (let key in errors) {
          const errorSpan = document.querySelector(`#${key}Error`);
          if (errorSpan) errorSpan.innerText = errors[key];
        }

        if (Object.keys(errors).length === 0) {
          window.location.href = "index.php";
        }
      }
    };

    let formData = new FormData();
    formData.append("productData", JSON.stringify(data));

    const imageFileInput = document.querySelector("#product_image");
    if (imageFileInput && imageFileInput.files.length > 0) {
      for (let i = 0; i < imageFileInput.files.length; i++) {
        formData.append("product_image[]", imageFileInput.files[i]);
      }
    }

    xhttp.send(formData);
  });
}
