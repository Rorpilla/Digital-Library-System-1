<?php
require __DIR__ . '/bootstrap.php';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Panda Style Starter</title>
    <link rel="stylesheet" href="shared.css">
</head>
<body>
    <div class="shell">
        <header class="hero">
            <div>
                <p class="eyebrow">3-app delivery marketplace starter</p>
                <h1>Launch a food delivery platform with customer, merchant, and rider experiences.</h1>
                <p>This starter includes a customer ordering view, a merchant dashboard, and a rider delivery panel backed by SQLite.</p>
            </div>
        </header>

        <section class="grid">
            <a class="card" href="customer/index.php">
                <h2>Customer app</h2>
                <p>Browse products, add items to the cart, and place a delivery order.</p>
            </a>
            <a class="card" href="merchant/index.php">
                <h2>Merchant web app</h2>
                <p>Add products, receive orders, assign riders, and view sales reports.</p>
            </a>
            <a class="card" href="rider/index.php">
                <h2>Rider app</h2>
                <p>Receive assigned jobs, accept or decline, and upload a delivery photo before completion.</p>
            </a>
        </section>
    </div>
</body>
</html>
