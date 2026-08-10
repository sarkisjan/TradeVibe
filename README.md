# 🚀 TradeVibe - Advanced Multi-Vendor E-Commerce Marketplace

TradeVibe is a highly scalable, high-performance, and feature-rich multi-vendor e-commerce web application built with a focus on Object-Oriented Programming (OOP) in PHP, clean Vanilla JavaScript (AJAX), and clean database design.

This platform operates as a dynamic ecosystem supporting three distinct user authorization levels: Root System Administrators, Seller Admins (Vendors), and Customers (Buyers).

---

## ⚡ Core Architectural Engineering Features

- **🔒 Granular Authorization & Security Control Logs:** Bulletproof session management utilizing native PHP server scripts to handle dynamic permissions for Root, Admin, and User environments seamlessly.
- **🛒 Persistent Session Caching & Seamless Checkout:** Live cart tracker operating on high-velocity state changes. Offers a fluid **Checkout Confirm Dialog Modal Overlay** pulling immediate profile address entities on the fly.
- **📐 Advanced Live Stock Matrix Monitoring:** Tracks multiple size-specific arrays (`product_stock`) inside a relative relational matrix layout.
- **🔥 Urgent Scarcity Marketing Algorithms (UX/UI):** If a specific size asset drops to exactly `1` remaining item, the buyer UI immediately triggers an urgent pulsing crimson badge visual effect alongside a `"Last Piece Available!"` context block. Completely suppresses sold-out configurations.
- **📊 Clean Global Administrative Oversight:** The Root Administrator panel processes dynamic user matrixes allowing on-the-fly password overrides (hashed via native `password_hash()` architecture) and profile deletions utilizing secure SQL cascade protocols.
- **💱 Realtime Multi-Currency Conversion Logic:** Powered by a clean, static OOP helper layout parsing floating values into unified exchange units (MKD, USD, EUR) instantly inside the presentation layer.

---

## 🛠️ Technology Stack Ecosystem

- **Backend Core:** PHP 8.x (Pure Object-Oriented Principles, Automated Class Autoloader Architecture)
- **Database Management:** MySQL (Relational Indexing, Complex `LEFT JOIN` aggregations, Cascade triggers)
- **Frontend Interactivity:** Vanilla JavaScript ES6 (Asynchronous XMLHttpRequests (AJAX), DOM Modification Engines)
- **Aesthetics:** Pure Vanilla CSS3 (Fixed Layout Systems, Keyframe Pulse Effects, Flex Alignment Grids)
- **SEO Protocols:** Pre-vetted `robots.txt` routing maps, comprehensive `sitemap.xml` pathways, and rich structural schema meta tags.

---

## 📂 Database Entity Relationship Overview

The storage layer enforces 1-to-Many strict relational data dependencies:

1.  `users` — Stores credential profiles, role permissions, and active verification checkpoints.
2.  `producttable` — Maps core product attributes tied to a unique `admin_id` owner element.
3.  `product_stock` — Maintains size configurations and physical quantity records.
4.  `orders` & `order_items` — Captures hard order context points and logs real-time transaction timestamp values (`order_date`).

---

## 💻 Local Sandbox Setup Instructions

1. Clone this repository directly inside your XAMPP server environment:
   ```bash
   git clone https://github.com/sarkisjan/TradeVibe
   ```
2. Import the provided `tradevibe_backup.sql` file backup configuration directly inside your local phpMyAdmin dashboard panel
3. Access the demo application using the embedded test environments specified within the live `login.php` preview console panel.
