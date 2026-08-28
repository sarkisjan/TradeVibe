// ==========================================================================
// Subcategory configuration
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

// ==========================================================================
// Brand configuration
// ==========================================================================

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

// Select the required HTML elements
const categorySelect = document.getElementById("category");
const subcategorySelect = document.getElementById("subcategory");
const brandSelect = document.getElementById("brand");
const otherBrandSection = document.getElementById("other_brand_section");
const otherBrandInput = document.getElementById("other_brand");
const form = document.getElementById("product_form");
const furnitureSection = document.getElementById("furniture_type_section");
const furnitureRoomInput = document.getElementById("furniture_room");

// Select the container used for dynamic form fields
const shown_form_inputs = document.querySelector(".shown_form_inputs");

// Add change listeners to stock size checkboxes
function setupDynamicStockListeners() {
  document.querySelectorAll(".stock-size-cb").forEach((cb) => {
    cb.addEventListener("change", function () {
      const sizeVal = this.value;

      const qtyInput = document.querySelector(
        `.stock-size-qty[data-size="${sizeVal}"]`,
      );

      if (qtyInput) {
        qtyInput.disabled = !this.checked;

        // Reset the quantity when the size is unchecked
        if (!this.checked) {
          qtyInput.value = 0;
        }
      }
    });
  });
}

// ==========================================================================
// Event 1: Load subcategories when a category is selected
// ==========================================================================

if (categorySelect) {
  categorySelect.addEventListener("change", function () {
    const selectedCat = this.value;

    // Reset all dependent fields when the category changes
    subcategorySelect.innerHTML =
      '<option value="">-- Select Subcategory --</option>';
    subcategorySelect.disabled = true;

    if (brandSelect) {
      brandSelect.innerHTML =
        '<option value="">-- Select Subcategory First --</option>';
      brandSelect.disabled = true;
    }

    if (otherBrandSection) {
      otherBrandSection.style.display = "none";
    }

    if (shown_form_inputs) {
      shown_form_inputs.innerHTML = "";
    }

    if (furnitureSection) {
      furnitureSection.style.display = "none";
    }

    // Enable the subcategory field when a valid category is selected
    if (selectedCat && subcategoriesData[selectedCat]) {
      subcategorySelect.disabled = false;

      subcategoriesData[selectedCat].forEach((sub) => {
        const opt = document.createElement("option");

        opt.value = sub.value;
        opt.text = sub.text;

        // Store additional information for the next step
        opt.setAttribute("data-input", sub.inputType);
        opt.setAttribute("data-brandgroup", sub.brandGroup);

        subcategorySelect.appendChild(opt);
      });
    }
  });
}

// ==========================================================================
// Event 2: Load brands and dynamic fields when a subcategory is selected
// ==========================================================================

if (subcategorySelect) {
  subcategorySelect.addEventListener("change", function () {
    const selectedOption = this.options[this.selectedIndex];

    // Reset the fields when no subcategory is selected
    if (!selectedOption || this.value === "") {
      if (brandSelect) {
        brandSelect.disabled = true;
        brandSelect.innerHTML =
          '<option value="">-- Select Subcategory First --</option>';
      }

      if (shown_form_inputs) {
        shown_form_inputs.innerHTML = "";
      }

      if (furnitureSection) {
        furnitureSection.style.display = "none";
      }

      return;
    }

    // Read the stored data attributes from the selected option
    const inputType = selectedOption.getAttribute("data-input");
    const brandGroup = selectedOption.getAttribute("data-brandgroup");

    if (shown_form_inputs) {
      shown_form_inputs.innerHTML = "";
    }

    // Enable and populate the brand list
    if (brandSelect) {
      brandSelect.disabled = false;
      brandSelect.innerHTML =
        '<option value="">-- Select Brand / Manufacturer --</option>';

      if (brandGroup && brandsData[brandGroup]) {
        brandsData[brandGroup].forEach((b) => {
          const bOpt = document.createElement("option");

          bOpt.value = b;
          bOpt.text = b;

          brandSelect.appendChild(bOpt);
        });
      }

      // Add an option for manually entering a brand
      const otherOpt = document.createElement("option");

      otherOpt.value = "Other";
      otherOpt.text = "Other / Not on list";

      brandSelect.appendChild(otherOpt);
    }

    // Show the furniture room field only for the Furniture subcategory
    if (furnitureSection) {
      if (this.value === "Furniture") {
        furnitureSection.style.display = "block";

        if (furnitureRoomInput) {
          furnitureRoomInput.required = true;
        }
      } else {
        furnitureSection.style.display = "none";

        if (furnitureRoomInput) {
          furnitureRoomInput.required = false;
          furnitureRoomInput.value = "";
        }
      }
    }

    // Generate additional fields based on the selected input type
    if (shown_form_inputs) {
      if (inputType === "dimensions") {
        shown_form_inputs.innerHTML = `
          <div class="dynamic-section">
            <div class="form-item">
              <label for="height">Height (cm)</label>
              <input id="height" type="number" name="height" required>
              <span class="error-msg" id="heightError"></span>
            </div>

            <div class="form-item">
              <label for="width">Width (cm)</label>
              <input id="width" type="number" name="width" required>
              <span class="error-msg" id="widthError"></span>
            </div>

            <div class="form-item">
              <label for="length">Length (cm)</label>
              <input id="length" type="number" name="length" required>
              <span class="error-msg" id="lengthError"></span>
            </div>
          </div>
        `;
      } else if (inputType === "weight") {
        shown_form_inputs.innerHTML = `
          <div class="dynamic-section">
            <div class="form-item">
              <label for="weight">Weight (g)</label>
              <input id="weight" type="number" name="weight" required>
              <span class="error-msg" id="weightError"></span>
            </div>
          </div>
        `;
      } else if (inputType === "sizes") {
        const textSizes = ["XS", "S", "M", "L", "XL", "XXL", "XXXL"];

        let html = `
          <div class="stock-grid-container">
            <h4>Select Available Apparel Sizes & Stock</h4>
            <div class="stock-grid-layout">
        `;

        textSizes.forEach((s) => {
          html += `
            <div class="stock-item-box">
              <input
                type="checkbox"
                class="stock-size-cb"
                value="${s}"
              >
              <span>${s}:</span>
              <input
                type="number"
                class="stock-size-qty"
                data-size="${s}"
                min="0"
                value="0"
                disabled
              >
            </div>
          `;
        });

        html += `
            </div>
          </div>
        `;

        shown_form_inputs.innerHTML = html;

        // Add listeners to the newly created stock inputs
        setupDynamicStockListeners();
      } else if (inputType === "footwear") {
        const shoeSizes = [
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

        let html = `
          <div class="stock-grid-container">
            <h4>Select Available Footwear Sizes (EU Standard) & Stock</h4>
            <div class="stock-grid-layout">
        `;

        shoeSizes.forEach((s) => {
          html += `
            <div class="stock-item-box">
              <input
                type="checkbox"
                class="stock-size-cb"
                value="${s}"
              >
              <span>EU ${s}:</span>
              <input
                type="number"
                class="stock-size-qty"
                data-size="${s}"
                min="0"
                value="0"
                disabled
              >
            </div>
          `;
        });

        html += `
            </div>
          </div>
        `;

        shown_form_inputs.innerHTML = html;

        // Add listeners to the newly created stock inputs
        setupDynamicStockListeners();
      }
    }
  });
}

// ==========================================================================
// Handle manual brand input
// ==========================================================================

if (brandSelect) {
  brandSelect.addEventListener("change", function () {
    // Show the manual brand field when "Other" is selected
    if (this.value === "Other") {
      if (otherBrandSection) {
        otherBrandSection.style.display = "block";
      }

      if (otherBrandInput) {
        otherBrandInput.required = true;
      }
    } else {
      // Hide and reset the manual brand field
      if (otherBrandSection) {
        otherBrandSection.style.display = "none";
      }

      if (otherBrandInput) {
        otherBrandInput.required = false;
        otherBrandInput.value = "";
      }
    }
  });
}

// ==========================================================================
// Event 3: Validate and submit the product form
// ==========================================================================

if (form) {
  form.addEventListener("submit", function (e) {
    e.preventDefault();

    // Clear previous validation messages
    document
      .querySelectorAll(".error-msg, .error")
      .forEach((el) => (el.innerText = ""));

    let finalBrand = brandSelect ? brandSelect.value : "";

    // Get the manually entered brand name when "Other" is selected
    if (finalBrand === "Other") {
      finalBrand = otherBrandInput ? otherBrandInput.value.trim() : "";

      if (finalBrand === "") {
        const errSpan = document.querySelector("#otherBrandError");

        if (errSpan) {
          errSpan.innerText = "Please type the brand name.";
        }

        return;
      }
    }

    // Collect selected sizes and their quantities
    const stockInventoryData = [];

    document.querySelectorAll(".stock-size-cb:checked").forEach((cb) => {
      const sizeName = cb.value;

      const qtyInput = document.querySelector(
        `.stock-size-qty[data-size="${sizeName}"]`,
      );

      const qtyValue = qtyInput ? parseInt(qtyInput.value, 10) || 0 : 0;

      stockInventoryData.push({
        size: sizeName,
        qty: qtyValue,
      });
    });

    // Collect all product data from the form
    const data = {
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

    // Create the AJAX request
    const xhttp = new XMLHttpRequest();

    xhttp.open("POST", "adding.php", true);

    xhttp.onreadystatechange = function () {
      if (this.readyState === 4 && this.status === 200) {
        // Parse the JSON response from the server
        const errors = JSON.parse(this.response);

        // Display validation errors returned by the server
        for (const key in errors) {
          const errorSpan = document.querySelector(`#${key}Error`);

          if (errorSpan) {
            errorSpan.innerText = errors[key];
          }
        }

        // Redirect to the product list after a successful save
        if (Object.keys(errors).length === 0) {
          window.location.href = "index.php";
        }
      }
    };

    // Create FormData and send the product data as JSON
    const formData = new FormData();

    formData.append("productData", JSON.stringify(data));

    // Add product images to the request
    const imageFileInput = document.querySelector("#product_image");

    if (imageFileInput && imageFileInput.files.length > 0) {
      for (let i = 0; i < imageFileInput.files.length; i++) {
        formData.append("product_image[]", imageFileInput.files[i]);
      }
    }

    // Send the request to the backend
    xhttp.send(formData);
  });
}
